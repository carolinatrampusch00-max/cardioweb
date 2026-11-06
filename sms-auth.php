<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/config/auth.php';

function smsRespond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function smsTwilioRequest(string $action, array $fields): array
{
    $accountSid = authEnv('TWILIO_ACCOUNT_SID');
    $authToken = authEnv('TWILIO_AUTH_TOKEN');
    $serviceSid = authEnv('TWILIO_VERIFY_SERVICE_SID');
    if ($accountSid === '' || $authToken === '' || $serviceSid === '') {
        throw new RuntimeException('Login por SMS não está configurado. Defina as credenciais do Twilio Verify no .env.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão cURL do PHP precisa estar habilitada para enviar SMS.');
    }

    $url = 'https://verify.twilio.com/v2/Services/' . rawurlencode($serviceSid) . '/' . $action;
    $handle = curl_init($url);
    if ($handle === false) {
        throw new RuntimeException('Não foi possível iniciar o serviço seguro de SMS.');
    }
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_USERPWD => $accountSid . ':' . $authToken,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $curlError = curl_error($handle);
    curl_close($handle);

    if (!is_string($body)) {
        error_log('CardioWeb SMS provider request failed: ' . $curlError);
        throw new RuntimeException('Não foi possível conectar ao serviço de SMS. Tente novamente.');
    }
    $response = json_decode($body, true);
    if (!is_array($response) || $status < 200 || $status >= 300) {
        $code = is_array($response) ? (string) ($response['code'] ?? '') : '';
        error_log('CardioWeb SMS provider returned HTTP ' . $status . ($code !== '' ? ' (code ' . $code . ')' : ''));
        throw new RuntimeException('O serviço de SMS não aceitou a solicitação. Confira o número e a configuração do Twilio.');
    }
    return $response;
}

function smsNormalizePhone(string $phone): string
{
    $phone = preg_replace('/[\s().-]+/', '', trim($phone)) ?? '';
    if (preg_match('/^\+[1-9]\d{7,14}$/', $phone) !== 1) {
        throw new RuntimeException('Informe o telefone com código do país, por exemplo +5511999999999.');
    }
    return $phone;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    smsRespond(405, ['error' => 'Método não permitido.']);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token'])
    || !is_string($csrfToken)
    || !hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
    smsRespond(403, ['error' => 'Sua sessão expirou. Atualize a página e tente novamente.']);
}

$action = (string) ($_POST['action'] ?? '');
try {
    $phone = smsNormalizePhone((string) ($_POST['phone'] ?? ''));
    require_once __DIR__ . '/config/database.php';
    $pdo = getConnection();

    if ($action === 'start') {
        $now = time();
        $sentAt = (int) ($_SESSION['sms_sent_at'] ?? 0);
        $sendCount = (int) ($_SESSION['sms_send_count'] ?? 0);
        $windowStart = (int) ($_SESSION['sms_window_start'] ?? 0);
        if ($windowStart === 0 || $now - $windowStart >= 900) {
            $windowStart = $now;
            $sendCount = 0;
        }
        if ($now - $sentAt < 60 || $sendCount >= 5) {
            smsRespond(429, ['error' => 'Aguarde antes de solicitar outro código.']);
        }

        $lookup = $pdo->prepare(
            'SELECT id, nome, email, role, ativo FROM usuarios WHERE telefone = :telefone LIMIT 1'
        );
        $lookup->execute(['telefone' => $phone]);
        $existingUser = $lookup->fetch(PDO::FETCH_ASSOC);
        if ($existingUser && (int) ($existingUser['ativo'] ?? 0) !== 1) {
            smsRespond(403, ['error' => 'Esta conta está desativada. Entre em contato com o suporte.']);
        }

        $draft = null;
        if (!$existingUser) {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
                smsRespond(422, ['error' => 'Para criar uma conta pelo SMS, informe nome e e-mail válidos.']);
            }
            if (strlen($password) < 8 || strlen($password) > 72) {
                smsRespond(422, ['error' => 'Crie uma senha de 8 a 72 caracteres para entrar também por e-mail.']);
            }
            $emailCheck = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $emailCheck->execute(['email' => $email]);
            if ($emailCheck->fetchColumn()) {
                smsRespond(409, ['error' => 'Este e-mail já possui cadastro. Entre com e-mail e senha para acessar a conta existente.']);
            }
            $draft = [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ];
        }

        $verification = smsTwilioRequest('Verifications', ['To' => $phone, 'Channel' => 'sms']);
        if (($verification['status'] ?? '') !== 'pending') {
            throw new RuntimeException('O serviço não confirmou o envio do código. Verifique a configuração do SMS.');
        }
        $_SESSION['sms_flow'] = [
            'phone' => $phone,
            'draft' => $draft,
            'started_at' => $now,
            'attempts' => 0,
        ];
        $_SESSION['sms_sent_at'] = $now;
        $_SESSION['sms_send_count'] = $sendCount + 1;
        $_SESSION['sms_window_start'] = $windowStart;
        smsRespond(200, ['message' => 'Código enviado. Confira as mensagens SMS do telefone informado.']);
    }

    if ($action === 'verify') {
        $flow = $_SESSION['sms_flow'] ?? null;
        $code = preg_replace('/\s+/', '', (string) ($_POST['code'] ?? '')) ?? '';
        if (!is_array($flow)
            || ($flow['phone'] ?? '') !== $phone
            || time() - (int) ($flow['started_at'] ?? 0) > 600) {
            unset($_SESSION['sms_flow']);
            smsRespond(400, ['error' => 'A solicitação expirou. Peça um novo código.']);
        }
        if (preg_match('/^\d{4,10}$/', $code) !== 1) {
            smsRespond(422, ['error' => 'Informe o código recebido por SMS.']);
        }
        $attempts = (int) ($flow['attempts'] ?? 0);
        if ($attempts >= 6) {
            unset($_SESSION['sms_flow']);
            smsRespond(429, ['error' => 'Limite de tentativas atingido. Peça um novo código.']);
        }
        $_SESSION['sms_flow']['attempts'] = $attempts + 1;
        $verification = smsTwilioRequest('VerificationCheck', ['To' => $phone, 'Code' => $code]);
        if (($verification['status'] ?? '') !== 'approved') {
            smsRespond(401, ['error' => 'Código incorreto ou expirado.']);
        }

        authEnsureIdentityTable($pdo);
        $pdo->beginTransaction();
        try {
            $lookup = $pdo->prepare(
                'SELECT id, nome, email, role, ativo FROM usuarios WHERE telefone = :telefone LIMIT 1 FOR UPDATE'
            );
            $lookup->execute(['telefone' => $phone]);
            $user = $lookup->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $draft = $flow['draft'] ?? null;
                if (!is_array($draft)) {
                    throw new RuntimeException('Não foi possível localizar ou criar a conta. Inicie novamente.');
                }
                $insert = $pdo->prepare(
                    'INSERT INTO usuarios (nome, email, telefone, senha_hash, role, ativo)
                     VALUES (:nome, :email, :telefone, :senha_hash, :role, 1)'
                );
                $insert->execute([
                    'nome' => $draft['name'],
                    'email' => $draft['email'],
                    'telefone' => $phone,
                    'senha_hash' => $draft['password_hash'],
                    'role' => 'paciente',
                ]);
                $user = [
                    'id' => (int) $pdo->lastInsertId(),
                    'nome' => $draft['name'],
                    'email' => $draft['email'],
                    'role' => 'paciente',
                    'ativo' => 1,
                ];
            }
            if ((int) ($user['ativo'] ?? 0) !== 1) {
                throw new RuntimeException('Esta conta está desativada. Entre em contato com o suporte.');
            }

            $identity = $pdo->prepare(
                'INSERT IGNORE INTO auth_identities (user_id, provider, subject) VALUES (:user_id, :provider, :subject)'
            );
            $identity->execute([
                'user_id' => (int) $user['id'],
                'provider' => 'sms',
                'subject' => $phone,
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        unset($_SESSION['sms_flow']);
        authSetUserSession($user, 'sms');
        smsRespond(200, ['redirect' => 'dashboard.php']);
    }

    smsRespond(400, ['error' => 'Ação de autenticação inválida.']);
} catch (Throwable $exception) {
    error_log('CardioWeb SMS authentication error: ' . $exception->getMessage());
    smsRespond(503, ['error' => $exception instanceof PDOException
        ? 'Não foi possível acessar ou salvar sua conta no banco de dados.'
        : $exception->getMessage()]);
}
