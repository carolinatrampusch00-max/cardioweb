<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['login_error'] = 'O login com Google ainda não está configurado com credenciais reais. Configure o provedor antes de habilitar este acesso.';
echo json_encode(['success' => false, 'error' => 'OAuth do Google não configurado.']);
exit();
