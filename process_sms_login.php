<?php
/**
 * PROCESS_SMS_LOGIN.PHP (Legado - Redireciona para o novo sistema centralizado)
 */

$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$code = isset($_POST['code']) ? trim($_POST['code']) : '';

if ($phone && $code) {
    $_POST['login_type'] = 'sms';
    $_POST['phone'] = $phone;
    $_POST['code'] = $code;
    
    include 'auth-process.php';
} else {
    header('Location: index.php?error=Telefone e código são obrigatórios');
    exit();
}
?>