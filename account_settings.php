<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function sendJson(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, ['success' => false, 'error' => 'Método não permitido.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    sendJson(403, ['success' => false, 'error' => 'Sua sessão expirou. Atualize a página e tente novamente.']);
}

$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if (($_SESSION['logado'] ?? false) !== true || !$userId) {
    sendJson(401, ['success' => false, 'error' => 'Faça login novamente para alterar sua conta.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    sendJson(400, ['success' => false, 'error' => 'Não foi possível interpretar os dados enviados.']);
}

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getConnection();

    if (($input['action'] ?? '') === 'update_profile') {
        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));

        if ($name === '' || mb_strlen($name) > 150) {
            sendJson(422, ['success' => false, 'error' => 'Informe um nome com até 150 caracteres.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            sendJson(422, ['success' => false, 'error' => 'Informe um endereço de e-mail válido.']);
        }

        $account = $pdo->prepare('SELECT id FROM usuarios WHERE id = :id LIMIT 1');
        $account->execute(['id' => $userId]);
        if (!$account->fetchColumn()) {
            sendJson(404, ['success' => false, 'error' => 'Conta não encontrada.']);
        }

        $duplicate = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1');
        $duplicate->execute(['email' => $email, 'id' => $userId]);
        if ($duplicate->fetchColumn()) {
            sendJson(409, ['success' => false, 'error' => 'Este e-mail já está sendo usado por outra conta.']);
        }

        $update = $pdo->prepare('UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id');
        $update->execute(['nome' => $name, 'email' => $email, 'id' => $userId]);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        sendJson(200, ['success' => true, 'message' => 'Dados da conta atualizados.']);
    }

    if (($input['action'] ?? '') === 'update_password') {
        $currentPassword = (string)($input['current_password'] ?? '');
        $newPassword = (string)($input['new_password'] ?? '');
        if ($currentPassword === '') {
            sendJson(422, ['success' => false, 'error' => 'Informe sua senha atual.']);
        }
        if (mb_strlen($newPassword, 'UTF-8') < 8 || strlen($newPassword) > 72) {
            sendJson(422, ['success' => false, 'error' => 'A nova senha deve ter entre 8 e 72 caracteres.']);
        }

        $query = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = :id LIMIT 1');
        $query->execute(['id' => $userId]);
        $storedPassword = $query->fetchColumn();
        if (!is_string($storedPassword) || $storedPassword === '') {
            sendJson(404, ['success' => false, 'error' => 'Conta não encontrada ou sem senha cadastrada.']);
        }

        $passwordMatches = password_verify($currentPassword, $storedPassword) || hash_equals($storedPassword, $currentPassword);
        if (!$passwordMatches) {
            sendJson(422, ['success' => false, 'error' => 'A senha atual não confere.']);
        }
        if (password_verify($newPassword, $storedPassword) || hash_equals($storedPassword, $newPassword)) {
            sendJson(422, ['success' => false, 'error' => 'Escolha uma senha diferente da atual.']);
        }

        $update = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha WHERE id = :id');
        $update->execute(['senha' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]);
        sendJson(200, ['success' => true, 'message' => 'Senha atualizada com sucesso.']);
    }

    sendJson(400, ['success' => false, 'error' => 'Ação de conta inválida.']);
} catch (PDOException $exception) {
    error_log('CardioWeb account settings database error: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        sendJson(409, ['success' => false, 'error' => 'Este e-mail já está sendo usado por outra conta.']);
    }
    sendJson(500, ['success' => false, 'error' => 'Não foi possível salvar os dados agora. Tente novamente em instantes.']);
} catch (Throwable $exception) {
    error_log('CardioWeb account settings error: ' . $exception->getMessage());
    sendJson(500, ['success' => false, 'error' => 'Ocorreu um erro ao atualizar sua conta.']);
}
