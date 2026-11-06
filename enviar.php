<?php
header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit();
}

$email = trim((string) ($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail inválido.']);
    exit();
}

echo json_encode(['status' => 'success', 'message' => 'O envio de e-mail está desabilitado até o SMTP do projeto ser configurado.']);
