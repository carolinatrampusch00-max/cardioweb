<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/auth.php';

function oauthFailure(string $message): never
{
    unset($_SESSION['oauth_flow']);
    $_SESSION['login_error'] = $message;
    header('Location: index.php');
    exit();
}

function oauthCallback(string $provider, string $redirectUri, array $flow): array
{
    $code = trim((string) ($_GET['code'] ?? ''));
    if ($code === '') {
        throw new RuntimeException('O provedor não retornou o código de autorização.');
    }

    if ($provider === 'google') {
        $clientId = authEnv('GOOGLE_CLIENT_ID');
        $clientSecret = authEnv('GOOGLE_CLIENT_SECRET');
        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Configure GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no arquivo .env.');
        }
        $tokens = authHttpRequest('https://oauth2.googleapis.com/token', 'POST', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'code_verifier' => (string) ($flow['verifier'] ?? ''),
        ]);
        $idToken = (string) ($tokens['id_token'] ?? '');
        if ($idToken === '') {
            throw new RuntimeException('O Google não retornou uma identidade para esta conta.');
        }
        $claims = authHttpRequest(
            'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken)
        );
        $emailVerified = in_array((string) ($claims['email_verified'] ?? ''), ['true', '1'], true);
        if (($claims['aud'] ?? '') !== $clientId
            || !in_array((string) ($claims['iss'] ?? ''), ['accounts.google.com', 'https://accounts.google.com'], true)
            || (int) ($claims['exp'] ?? 0) <= time()
            || !is_string($claims['sub'] ?? null)
            || !$emailVerified
            || !is_string($claims['nonce'] ?? null)
            || !hash_equals((string) $flow['nonce'], $claims['nonce'])) {
            throw new RuntimeException('Não foi possível confirmar a identidade retornada pelo Google.');
        }

        return [
            'subject' => $claims['sub'],
            'email' => (string) ($claims['email'] ?? ''),
            'name' => (string) ($claims['name'] ?? ''),
        ];
    }

    $clientId = authEnv('APPLE_CLIENT_ID');
    if ($clientId === '') {
        throw new RuntimeException('Configure APPLE_CLIENT_ID, APPLE_TEAM_ID, APPLE_KEY_ID e APPLE_PRIVATE_KEY_PATH no arquivo .env.');
    }
    $tokens = authHttpRequest('https://appleid.apple.com/auth/token', 'POST', [
        'client_id' => $clientId,
        'client_secret' => authAppleClientSecret(),
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirectUri,
    ]);
    $idToken = (string) ($tokens['id_token'] ?? '');
    if ($idToken === '') {
        throw new RuntimeException('A Apple não retornou uma identidade para esta conta.');
    }
    $claims = authVerifyAppleToken($idToken, (string) $flow['nonce']);

    return [
        'subject' => (string) $claims['sub'],
        'email' => (string) $claims['email'],
        'name' => '',
    ];
}

$provider = (string) ($_GET['provider'] ?? '');
if (!in_array($provider, ['google', 'apple'], true)) {
    oauthFailure('Provedor de autenticação inválido.');
}

try {
    $clientId = authEnv(strtoupper($provider) . '_CLIENT_ID');
    if ($clientId === '') {
        $required = $provider === 'google'
            ? 'GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET'
            : 'APPLE_CLIENT_ID, APPLE_TEAM_ID, APPLE_KEY_ID e APPLE_PRIVATE_KEY_PATH';
        throw new RuntimeException('Para ativar o login com ' . ucfirst($provider) . ', configure ' . $required . ' no arquivo .env.');
    }

    $redirectUri = authRedirectUri($provider);
    if ($provider === 'apple') {
        $appleClientSecretReady = authEnv('APPLE_TEAM_ID') !== ''
            && authEnv('APPLE_KEY_ID') !== ''
            && authEnv('APPLE_PRIVATE_KEY_PATH') !== '';
        if (!$appleClientSecretReady) {
            throw new RuntimeException('Configure APPLE_TEAM_ID, APPLE_KEY_ID e APPLE_PRIVATE_KEY_PATH no arquivo .env.');
        }
        if (!str_starts_with($redirectUri, 'https://')) {
            throw new RuntimeException('O login Apple exige uma URL de callback pública com HTTPS.');
        }
    }

    if (isset($_GET['code']) || isset($_POST['code']) || isset($_GET['error']) || isset($_POST['error'])) {
        $flow = $_SESSION['oauth_flow'] ?? null;
        $responseState = (string) ($_GET['state'] ?? $_POST['state'] ?? '');
        if (!is_array($flow)
            || ($flow['provider'] ?? '') !== $provider
            || !is_string($flow['state'] ?? null)
            || $responseState === ''
            || !hash_equals($flow['state'], $responseState)) {
            throw new RuntimeException('A solicitação de acesso expirou. Tente novamente.');
        }
        if (isset($_GET['error']) || isset($_POST['error'])) {
            throw new RuntimeException('O acesso foi cancelado ou recusado pelo provedor.');
        }

        $identity = oauthCallback($provider, $redirectUri, $flow);
        unset($_SESSION['oauth_flow']);
        require_once __DIR__ . '/config/database.php';
        [$user, $needsPasswordSetup] = authStoreExternalIdentity(
            getConnection(),
            $provider,
            $identity['subject'],
            $identity['email'],
            $identity['name']
        );

        if ($needsPasswordSetup) {
            session_regenerate_id(true);
            $_SESSION['auth_password_setup'] = true;
            $_SESSION['auth_pending_user'] = [
                'id' => (int) $user['id'],
                'email' => (string) $user['email'],
                'name' => (string) $user['nome'],
                'role' => (string) ($user['role'] ?? 'paciente'),
                'provider' => $provider,
            ];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: set-password.php');
            exit();
        }

        authSetUserSession($user, $provider);
        header('Location: dashboard.php');
        exit();
    }

    if ($provider === 'google' && authEnv('GOOGLE_CLIENT_SECRET') === '') {
        throw new RuntimeException('Configure GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET no arquivo .env.');
    }

    $state = bin2hex(random_bytes(32));
    $nonce = bin2hex(random_bytes(32));
    $authorizationUrl = $provider === 'google'
        ? 'https://accounts.google.com/o/oauth2/v2/auth'
        : 'https://appleid.apple.com/auth/authorize';
    $parameters = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'response_mode' => 'query',
        'scope' => 'openid email' . ($provider === 'google' ? ' profile' : ' name'),
        'state' => $state,
        'nonce' => $provider === 'apple' ? hash('sha256', $nonce) : $nonce,
    ];
    if ($provider === 'google') {
        $verifier = authBase64UrlEncode(random_bytes(48));
        $parameters['code_challenge'] = authBase64UrlEncode(hash('sha256', $verifier, true));
        $parameters['code_challenge_method'] = 'S256';
        $_SESSION['oauth_flow'] = [
            'provider' => $provider,
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
        ];
    } else {
        $_SESSION['oauth_flow'] = [
            'provider' => $provider,
            'state' => $state,
            'nonce' => $nonce,
        ];
    }
    header('Location: ' . $authorizationUrl . '?' . http_build_query($parameters));
    exit();
} catch (Throwable $exception) {
    error_log('CardioWeb ' . $provider . ' authentication error: ' . $exception->getMessage());
    oauthFailure($exception instanceof PDOException
        ? 'Não foi possível salvar ou acessar sua conta no banco de dados.'
        : $exception->getMessage());
}
