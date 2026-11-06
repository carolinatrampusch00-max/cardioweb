<?php
/**
 * PROCESS_LOGIN.PHP (Legado - Redireciona para o novo sistema centralizado)
 * Este arquivo redirecion para a novo sistema de autenticação centralizado
 */

// Receber dados
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Redirecionar para o novo sistema centralizado
if ($email && $password) {
    $_POST['login_type'] = 'email';
    $_POST['email'] = $email;
    $_POST['password'] = $password;
    
    // Fazer forward para auth-process.php
    require __DIR__ . '/auth-process.php';
} else {
    header('Location: index.php?error=E-mail e senha são obrigatórios');
    exit();
}
?>
