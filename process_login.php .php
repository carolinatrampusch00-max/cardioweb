<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['login_error'] = 'Login de demonstração desativado. Use o formulário padrão do sistema.';
header('Location: index.php');
exit();
