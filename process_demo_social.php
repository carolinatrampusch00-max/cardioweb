<?php
declare(strict_types=1);

session_start();

function demoLoginFailure(string $message, string $modal = 'google'): never
{
    $_SESSION['login_error'] = $message;
    header('Location: index.php?modal=' . rawurlencode($modal));
    exit();
}

$host = strtolower((string) parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)
    || !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($csrfToken)
    || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    demoLoginFailure('Sua sessão expirou. Atualize a página e tente novamente.');
}

$provider = (string) ($_POST['provider'] ?? '');
if (!in_array($provider, ['google', 'apple'], true)) {
    demoLoginFailure('Método de demonstração inválido.');
}

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/auth.php';
    $pdo = getConnection();
    authEnsureIdentityTable($pdo);
    $pdo->beginTransaction();

    if ($provider === 'google') {
        $accounts = [
            'ana' => [
                'name' => 'Ana Silva',
                'email' => 'ana.demo@cardioweb.test',
                'password' => 'CardioWeb@2026',
            ],
            'lucas' => [
                'name' => 'Lucas Martins',
                'email' => 'lucas.demo@cardioweb.test',
                'password' => 'CardioWeb@2026',
            ],
        ];
        $accountKey = (string) ($_POST['account'] ?? '');
        if (!isset($accounts[$accountKey])) {
            throw new RuntimeException('Escolha uma das contas de demonstração.');
        }

        $account = $accounts[$accountKey];
        $identity = $pdo->prepare(
            'SELECT u.id, u.nome, u.email, u.role, u.ativo
             FROM auth_identities i JOIN usuarios u ON u.id = i.user_id
             WHERE i.provider = :provider AND i.subject = :subject LIMIT 1'
        );
        $identity->execute(['provider' => 'google_demo', 'subject' => $accountKey]);
        $user = $identity->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $existingEmail = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $existingEmail->execute(['email' => $account['email']]);
            if ($existingEmail->fetchColumn()) {
                throw new RuntimeException('O e-mail de demonstração já está associado a outra conta.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, role, ativo)
                 VALUES (:nome, :email, :senha_hash, :role, 1)'
            );
            $insert->execute([
                'nome' => $account['name'],
                'email' => $account['email'],
                'senha_hash' => password_hash($account['password'], PASSWORD_DEFAULT),
                'role' => 'paciente',
            ]);
            $userId = (int) $pdo->lastInsertId();
            $link = $pdo->prepare(
                'INSERT INTO auth_identities
                    (user_id, provider, subject, password_setup_required)
                 VALUES (:user_id, :provider, :subject, 0)'
            );
            $link->execute([
                'user_id' => $userId,
                'provider' => 'google_demo',
                'subject' => $accountKey,
            ]);
            $user = [
                'id' => $userId,
                'nome' => $account['name'],
                'email' => $account['email'],
                'role' => 'paciente',
                'ativo' => 1,
            ];
        }
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || mb_strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 150 || strlen($password) < 8 || strlen($password) > 72) {
            throw new RuntimeException('Informe nome, e-mail válido e uma senha de 8 a 72 caracteres.');
        }

        $identity = $pdo->prepare(
            'SELECT u.id, u.nome, u.email, u.role, u.ativo, u.senha_hash
             FROM auth_identities i JOIN usuarios u ON u.id = i.user_id
             WHERE i.provider = :provider AND i.subject = :subject LIMIT 1'
        );
        $identity->execute(['provider' => 'apple_demo', 'subject' => $email]);
        $user = $identity->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            if ((int) $user['ativo'] !== 1 || !password_verify($password, (string) $user['senha_hash'])) {
                throw new RuntimeException('E-mail ou senha do Apple de demonstração incorretos.');
            }
        } else {
            $existingEmail = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
            $existingEmail->execute(['email' => $email]);
            if ($existingEmail->fetchColumn()) {
                throw new RuntimeException('Esse e-mail já possui uma conta. Entre com e-mail e senha.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha_hash, role, ativo)
                 VALUES (:nome, :email, :senha_hash, :role, 1)'
            );
            $insert->execute([
                'nome' => $name,
                'email' => $email,
                'senha_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'paciente',
            ]);
            $userId = (int) $pdo->lastInsertId();
            $link = $pdo->prepare(
                'INSERT INTO auth_identities
                    (user_id, provider, subject, password_setup_required)
                 VALUES (:user_id, :provider, :subject, 0)'
            );
            $link->execute([
                'user_id' => $userId,
                'provider' => 'apple_demo',
                'subject' => $email,
            ]);
            $user = [
                'id' => $userId,
                'nome' => $name,
                'email' => $email,
                'role' => 'paciente',
                'ativo' => 1,
            ];
        }
    }

    if ((int) ($user['ativo'] ?? 0) !== 1) {
        throw new RuntimeException('Esta conta está desativada. Entre em contato com o suporte.');
    }

    $pdo->commit();
    authSetUserSession($user, $provider . '_demo');
    header('Location: dashboard.php');
    exit();
} catch (RuntimeException $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    demoLoginFailure($exception->getMessage(), $provider);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('CardioWeb demo social login failed: ' . $exception->getMessage());
    demoLoginFailure('Não foi possível concluir o acesso de demonstração. Tente novamente.', $provider);
}
