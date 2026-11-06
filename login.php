<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header('Location: dashboard.php');
    exit();
}

header('Location: index.php');
exit();
