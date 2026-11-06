<?php
declare(strict_types=1);

session_start();

function registrationError(string $message): void
{
    $_SESSION['registration_error'] = $message;
    header('Location: index.php?modal=register');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?modal=register');
    exit();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    registrationError('Sua sessão expirou. Atualize a página e tente novamente.');
}

$name = trim((string) ($_POST['nome'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
    registrationError('Preencha todos os campos.');
}

if (strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    registrationError('Confira o nome e informe um e-mail válido.');
}

if (strlen($password) < 8 || strlen($password) > 72) {
    registrationError('A senha deve ter entre 8 e 72 caracteres.');
}

if (!hash_equals($password, $confirmPassword)) {
    registrationError('As senhas não coincidem.');
}

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getConnection();
    $insert = $pdo->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, role, ativo) VALUES (:nome, :email, :senha_hash, :role, 1)'
    );
    $insert->execute([
        'nome' => $name,
        'email' => $email,
        'senha_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'paciente',
    ]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = 'paciente';
    $_SESSION['login_type'] = 'email';
    $_SESSION['logado'] = true;
    unset($_SESSION['registration_error']);

    header('Location: dashboard.php');
    exit();
} catch (PDOException $exception) {
    error_log('CardioWeb registration database error: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        registrationError('Este e-mail já está cadastrado. Faça login ou use outro e-mail.');
    }

    registrationError('Não foi possível salvar seu cadastro agora. Verifique a conexão com o banco e tente novamente.');
} catch (Throwable $exception) {
    error_log('CardioWeb registration error: ' . $exception->getMessage());
    registrationError('Ocorreu um erro ao criar a conta. Tente novamente.');
}
