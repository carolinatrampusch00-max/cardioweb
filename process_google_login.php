<?php
/**
 * PROCESS_GOOGLE_LOGIN.PHP (Legado - Redireciona para o novo sistema centralizado)
 */

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

if ($email) {
    $_POST['login_type'] = 'google';
    $_POST['email'] = $email;
    $_POST['name'] = $name;
    
    include 'auth-process.php';
} else {
    header('Location: index.php?error=E-mail do Google é obrigatório');
    exit();
}
?>