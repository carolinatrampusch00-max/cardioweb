<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['login_error'] = 'O login com Apple ainda não está configurado. Use e-mail e senha até que o provedor seja integrado.';
header('Location: index.php');
exit();
