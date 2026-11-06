<?php
/**
 * PROCESS_APPLE_LOGIN.PHP (Legado - Redireciona para o novo sistema centralizado)
 */

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email) {
    $_POST['login_type'] = 'apple';
    $_POST['email'] = $email;
    
    include 'auth-process.php';
} else {
    header('Location: index.php?error=E-mail do Apple é obrigatório');
    exit();
}
?>