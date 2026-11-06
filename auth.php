<?php
declare(strict_types=1);

function authEnv(string $name): string
{
    $value = getenv($name);
    if (is_string($value) && $value !== '') {
        return trim($value);
    }

    static $fileValues = null;
    if ($fileValues === null) {
        $fileValues = [];
        $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envFile) && is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/', $line, $matches) === 1) {
                    $fileValues[$matches[1]] = trim($matches[2], " \t\n\r\0\x0B\"'");
                }
            }
        }
    }

    return (string) ($fileValues[$name] ?? '');
}

function authBaseUrl(): string
{
    $configured = rtrim(authEnv('CARDIO_AUTH_BASE_URL'), '/');
    if ($configured !== '') {
        return $configured;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (!in_array($host, ['localhost', '127.0.0.1'], true)) {
        throw new RuntimeException('Configure CARDIO_AUTH_BASE_URL com a URL HTTPS pública do site.');
    }

    $directory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $directory = $directory === '/' || $directory === '.' ? '' : '/' . trim($directory, '/');
    return 'http://' . $host . $directory;
}

function authRedirectUri(string $provider): string
{
    $configured = authEnv(strtoupper($provider) . '_REDIRECT_URI');
    return $configured !== '' ? $configured : authBaseUrl() . '/oauth.php?provider=' . rawurlencode($provider);
}

function authHttpRequest(string $url, string $method = 'GET', array $fields = [], array $headers = []): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão cURL do PHP precisa estar habilitada.');
    }

    $handle = curl_init($url);
    if ($handle === false) {
        throw new RuntimeException('Não foi possível iniciar a conexão segura com o provedor.');
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => $headers,
    ];
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($fields);
    }
    curl_setopt_array($handle, $options);
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if (!is_string($body)) {
        error_log('CardioWeb provider request failed: ' . $error);
        throw new RuntimeException('Não foi possível conectar ao serviço de autenticação. Tente novamente.');
    }

    $decoded = json_decode($body, true);
    if ($status < 200 || $status >= 300 || !is_array($decoded)) {
        error_log('CardioWeb provider returned HTTP ' . $status);
        throw new RuntimeException('O provedor recusou a autenticação. Confira a configuração e tente novamente.');
    }

    return $decoded;
}

function authBase64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function authBase64UrlDecode(string $value): string|false
{
    $padding = strlen($value) % 4;
    if ($padding !== 0) {
        $value .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($value, '-_', '+/'), true);
}

function authDecodeJwt(string $jwt): array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        throw new RuntimeException('O provedor retornou uma credencial inválida.');
    }

    $headerJson = authBase64UrlDecode($parts[0]);
    $claimsJson = authBase64UrlDecode($parts[1]);
    $signature = authBase64UrlDecode($parts[2]);
    $header = is_string($headerJson) ? json_decode($headerJson, true) : null;
    $claims = is_string($claimsJson) ? json_decode($claimsJson, true) : null;
    if (!is_array($header) || !is_array($claims) || !is_string($signature)) {
        throw new RuntimeException('O provedor retornou uma credencial inválida.');
    }

    return [$parts[0] . '.' . $parts[1], $header, $claims, $signature];
}

function authDerLength(int $length): string
{
    if ($length < 128) {
        return chr($length);
    }

    $bytes = '';
    while ($length > 0) {
        $bytes = chr($length & 0xff) . $bytes;
        $length >>= 8;
    }
    return chr(0x80 | strlen($bytes)) . $bytes;
}

function authEcdsaJoseToDer(string $signature): string
{
    if (strlen($signature) !== 64) {
        throw new RuntimeException('A assinatura do provedor é inválida.');
    }

    $integers = [];
    foreach ([substr($signature, 0, 32), substr($signature, 32, 32)] as $integer) {
        $integer = ltrim($integer, "\0");
        if ($integer === '') {
            $integer = "\0";
        }
        if ((ord($integer[0]) & 0x80) !== 0) {
            $integer = "\0" . $integer;
        }
        $integers[] = "\x02" . authDerLength(strlen($integer)) . $integer;
    }

    $sequence = implode('', $integers);
    return "\x30" . authDerLength(strlen($sequence)) . $sequence;
}

function authEcdsaDerToJose(string $signature): string
{
    $offset = 0;
    if (ord($signature[$offset++] ?? "\0") !== 0x30) {
        throw new RuntimeException('A chave privada Apple não gerou uma assinatura válida.');
    }
    $sequenceLength = ord($signature[$offset++] ?? "\0");
    if (($sequenceLength & 0x80) !== 0) {
        $lengthBytes = $sequenceLength & 0x7f;
        $sequenceLength = 0;
        for ($i = 0; $i < $lengthBytes; $i++) {
            $sequenceLength = ($sequenceLength << 8) | ord($signature[$offset++] ?? "\0");
        }
    }
    if (ord($signature[$offset++] ?? "\0") !== 0x02) {
        throw new RuntimeException('A chave privada Apple não gerou uma assinatura válida.');
    }
    $rLength = ord($signature[$offset++] ?? "\0");
    $r = substr($signature, $offset, $rLength);
    $offset += $rLength;
    if (ord($signature[$offset++] ?? "\0") !== 0x02) {
        throw new RuntimeException('A chave privada Apple não gerou uma assinatura válida.');
    }
    $sLength = ord($signature[$offset++] ?? "\0");
    $s = substr($signature, $offset, $sLength);

    return str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT)
        . str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
}

function authAppleClientSecret(): string
{
    $teamId = authEnv('APPLE_TEAM_ID');
    $clientId = authEnv('APPLE_CLIENT_ID');
    $keyId = authEnv('APPLE_KEY_ID');
    $keyPath = authEnv('APPLE_PRIVATE_KEY_PATH');
    if ($teamId === '' || $clientId === '' || $keyId === '' || $keyPath === '' || !is_file($keyPath)) {
        throw new RuntimeException('Configure APPLE_CLIENT_ID, APPLE_TEAM_ID, APPLE_KEY_ID e APPLE_PRIVATE_KEY_PATH no .env.');
    }

    $privateKeyPem = file_get_contents($keyPath);
    $privateKey = is_string($privateKeyPem) ? openssl_pkey_get_private($privateKeyPem) : false;
    if ($privateKey === false) {
        throw new RuntimeException('Não foi possível ler a chave privada configurada para Apple.');
    }

    $now = time();
    $header = authBase64UrlEncode((string) json_encode(['alg' => 'ES256', 'kid' => $keyId]));
    $claims = authBase64UrlEncode((string) json_encode([
        'iss' => $teamId,
        'iat' => $now,
        'exp' => $now + 300,
        'aud' => 'https://appleid.apple.com',
        'sub' => $clientId,
    ]));
    $signingInput = $header . '.' . $claims;
    if (!openssl_sign($signingInput, $derSignature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Não foi possível assinar a solicitação para Apple.');
    }

    return $signingInput . '.' . authBase64UrlEncode(authEcdsaDerToJose($derSignature));
}

function authVerifyAppleToken(string $jwt, string $expectedNonce): array
{
    [$signingInput, $header, $claims, $signature] = authDecodeJwt($jwt);
    if (($header['alg'] ?? '') !== 'ES256' || !is_string($header['kid'] ?? null)) {
        throw new RuntimeException('A Apple retornou uma credencial inválida.');
    }

    $keys = authHttpRequest('https://appleid.apple.com/auth/keys');
    $jwk = null;
    foreach (($keys['keys'] ?? []) as $candidate) {
        if (is_array($candidate) && ($candidate['kid'] ?? '') === $header['kid'] && ($candidate['kty'] ?? '') === 'EC') {
            $jwk = $candidate;
            break;
        }
    }
    if (!is_array($jwk) || ($jwk['crv'] ?? '') !== 'P-256') {
        throw new RuntimeException('Não foi possível validar a assinatura da Apple.');
    }

    $x = authBase64UrlDecode((string) ($jwk['x'] ?? ''));
    $y = authBase64UrlDecode((string) ($jwk['y'] ?? ''));
    if (!is_string($x) || !is_string($y) || strlen($x) !== 32 || strlen($y) !== 32) {
        throw new RuntimeException('A chave pública Apple é inválida.');
    }
    $derKey = hex2bin('3059301306072a8648ce3d020106082a8648ce3d03010703420004') . $x . $y;
    $publicKey = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode($derKey), 64, "\n")
        . "-----END PUBLIC KEY-----\n");
    if ($publicKey === false || openssl_verify($signingInput, authEcdsaJoseToDer($signature), $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
        throw new RuntimeException('Não foi possível confirmar a identidade Apple.');
    }

    $now = time();
    if (($claims['iss'] ?? '') !== 'https://appleid.apple.com'
        || ($claims['aud'] ?? '') !== authEnv('APPLE_CLIENT_ID')
        || !is_numeric($claims['exp'] ?? null)
        || (int) $claims['exp'] < $now
        || (int) ($claims['iat'] ?? 0) > $now + 60
        || !isset($claims['sub'])
        || !is_string($claims['nonce'] ?? null)
        || !hash_equals(hash('sha256', $expectedNonce), $claims['nonce'])) {
        throw new RuntimeException('A resposta Apple expirou ou não corresponde a esta solicitação.');
    }

    if (!filter_var((string) ($claims['email'] ?? ''), FILTER_VALIDATE_EMAIL)
        || !in_array((string) ($claims['email_verified'] ?? ''), ['true', '1'], true)) {
        throw new RuntimeException('A Apple não confirmou um e-mail válido para esta conta.');
    }

    return $claims;
}

function authEnsureIdentityTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS auth_identities (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            provider VARCHAR(20) NOT NULL,
            subject VARCHAR(191) NOT NULL,
            password_setup_required TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_provider_subject (provider, subject),
            KEY auth_identity_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $column = $pdo->query("SHOW COLUMNS FROM auth_identities LIKE 'password_setup_required'")->fetch();
    if (!$column) {
        try {
            $pdo->exec(
                'ALTER TABLE auth_identities ADD COLUMN password_setup_required TINYINT(1) NOT NULL DEFAULT 0'
            );
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1060) {
                throw $exception;
            }
        }
    }
}

function authSetUserSession(array $user, string $loginType): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_name'] = (string) $user['nome'];
    $_SESSION['user_role'] = (string) ($user['role'] ?? 'paciente');
    $_SESSION['login_type'] = $loginType;
    $_SESSION['logado'] = true;
    unset($_SESSION['auth_password_setup'], $_SESSION['auth_pending_user'], $_SESSION['login_error']);
}

function authStoreExternalIdentity(PDO $pdo, string $provider, string $subject, string $email, string $name): array
{
    authEnsureIdentityTable($pdo);
    $email = strtolower(trim($email));
    $subject = trim($subject);
    $name = trim($name);
    if ($subject === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('O provedor não retornou uma identidade verificada.');
    }

    $pdo->beginTransaction();
    try {
        $findIdentity = $pdo->prepare(
            'SELECT u.id, u.nome, u.email, u.role, u.ativo, u.senha_hash, i.password_setup_required
             FROM auth_identities i JOIN usuarios u ON u.id = i.user_id
             WHERE i.provider = :provider AND i.subject = :subject LIMIT 1'
        );
        $findIdentity->execute(['provider' => $provider, 'subject' => $subject]);
        $user = $findIdentity->fetch(PDO::FETCH_ASSOC);
        $identityAlreadyLinked = is_array($user);
        $needsPasswordSetup = $identityAlreadyLinked
            && (int) ($user['password_setup_required'] ?? 0) === 1;

        if (!$user) {
            $findEmail = $pdo->prepare(
                'SELECT id, nome, email, role, ativo, senha_hash FROM usuarios WHERE email = :email LIMIT 1'
            );
            $findEmail->execute(['email' => $email]);
            $user = $findEmail->fetch(PDO::FETCH_ASSOC);
        }

        $created = false;
        if (!$user) {
            $fallbackName = strstr($email, '@', true);
            $name = $name !== '' ? mb_substr($name, 0, 150) : mb_substr((string) $fallbackName, 0, 150);
            $insert = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, role, ativo)
                 VALUES (:nome, :email, :senha_hash, :role, 1)'
            );
            $insert->execute([
                'nome' => $name,
                'email' => $email,
                'senha_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                'role' => 'paciente',
            ]);
            $user = [
                'id' => (int) $pdo->lastInsertId(),
                'nome' => $name,
                'email' => $email,
                'role' => 'paciente',
                'ativo' => 1,
                'senha_hash' => '',
            ];
            $created = true;
            $needsPasswordSetup = true;
        }

        if ((int) ($user['ativo'] ?? 0) !== 1) {
            throw new RuntimeException('Esta conta está desativada. Entre em contato com o suporte.');
        }

        if (!$identityAlreadyLinked) {
            if (!$created) {
                $pendingPassword = $pdo->prepare(
                    'SELECT 1 FROM auth_identities
                     WHERE user_id = :user_id AND password_setup_required = 1 LIMIT 1'
                );
                $pendingPassword->execute(['user_id' => (int) $user['id']]);
                $needsPasswordSetup = (bool) $pendingPassword->fetchColumn();
            }
            $link = $pdo->prepare(
                'INSERT INTO auth_identities
                    (user_id, provider, subject, password_setup_required)
                 VALUES (:user_id, :provider, :subject, :password_setup_required)'
            );
            $link->execute([
                'user_id' => (int) $user['id'],
                'provider' => $provider,
                'subject' => $subject,
                'password_setup_required' => $needsPasswordSetup ? 1 : 0,
            ]);
        }
        $pdo->commit();
        return [$user, $needsPasswordSetup];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($exception instanceof PDOException) {
            error_log('CardioWeb identity database error: ' . $exception->getMessage());
            throw new RuntimeException('Não foi possível salvar o vínculo da conta. Tente novamente.');
        }
        throw $exception;
    }
}
