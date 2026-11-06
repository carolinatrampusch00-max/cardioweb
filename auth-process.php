<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function loginFailure(string $message): void
{
    $_SESSION['login_error'] = $message;
    header('Location: index.php');
    exit();
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'POST';
if ($requestMethod !== 'POST') {
    header('Location: index.php');
    exit();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    loginFailure('Sua sessão expirou. Atualize a página e tente novamente.');
}

$loginType = (string) ($_POST['login_type'] ?? '');
if ($loginType !== 'email') {
    $providerNames = [
        'google' => 'Google OAuth',
        'apple' => 'Sign in with Apple',
        'sms' => 'um serviço de SMS',
    ];
    $provider = $providerNames[$loginType] ?? null;
    loginFailure($provider
        ? 'Este login requer integração verificada com ' . $provider . '. O acesso de demonstração por e-mail foi desativado; use e-mail e senha ou configure o provedor.'
        : 'Método de login inválido.');
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
if ($email === '' || $password === '') {
    loginFailure('E-mail e senha são obrigatórios.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    loginFailure('Informe um e-mail válido.');
}

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getConnection();
    $query = $pdo->prepare(
        'SELECT id, nome, email, senha_hash, role, ativo FROM usuarios WHERE email = :email LIMIT 1'
    );
    $query->execute(['email' => $email]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('CardioWeb login database error: ' . $exception->getMessage());
    loginFailure('Não foi possível acessar o banco de dados. Verifique se o MySQL do XAMPP está iniciado.');
}

if (!$user || (int) $user['ativo'] !== 1) {
    loginFailure('E-mail ou senha incorretos.');
}

$storedHash = (string) ($user['senha_hash'] ?? '');
$passwordValid = $storedHash !== '' && password_verify($password, $storedHash);
if (!$passwordValid && $storedHash !== '' && hash_equals($storedHash, $password)) {
    $passwordValid = true;
    $upgrade = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
    $upgrade->execute([
        'senha_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => (int) $user['id'],
    ]);
}

if (!$passwordValid) {
    loginFailure('E-mail ou senha incorretos.');
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_email'] = (string) $user['email'];
$_SESSION['user_name'] = (string) $user['nome'];
$_SESSION['user_role'] = (string) ($user['role'] ?? 'paciente');
$_SESSION['login_type'] = 'email';
$_SESSION['logado'] = true;
unset($_SESSION['login_error']);

header('Location: dashboard.php');
exit();
