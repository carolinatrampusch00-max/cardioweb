<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['login_error'] = 'O login com Google ainda não está configurado com credenciais reais. Use e-mail e senha ou configure a integração do provedor.';
header('Location: index.php');
exit();
