<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit();
}

$provider = $_POST['provider'] ?? '';
if (!in_array($provider, ['google', 'apple', 'sms'], true)) {
    $_SESSION['login_error'] = 'Método de login inválido.';
    header('Location: index.php');
    exit();
}

$_POST['login_type'] = $provider;
require __DIR__ . '/auth-process.php';
