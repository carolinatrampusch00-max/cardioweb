<?php
declare(strict_types=1);

session_start();
$pendingUser = $_SESSION['auth_pending_user'] ?? null;
if (($_SESSION['auth_password_setup'] ?? false) !== true || !is_array($pendingUser)) {
    header('Location: index.php');
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !is_string($csrfToken) || !hash_equals($csrfToken, $token)) {
        $_SESSION['password_setup_error'] = 'Sua sessão expirou. Atualize a página e tente novamente.';
        header('Location: set-password.php');
        exit();
    }

    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['confirm_password'] ?? '');
    if (strlen($password) < 8 || strlen($password) > 72) {
        $_SESSION['password_setup_error'] = 'A senha deve ter entre 8 e 72 caracteres.';
        header('Location: set-password.php');
        exit();
    }
    if (!hash_equals($password, $confirmation)) {
        $_SESSION['password_setup_error'] = 'As senhas não coincidem.';
        header('Location: set-password.php');
        exit();
    }

    try {
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/config/auth.php';
        $pdo = getConnection();
        authEnsureIdentityTable($pdo);
        $pdo->beginTransaction();
        $update = $pdo->prepare(
            'UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id AND ativo = 1'
        );
        $update->execute([
            'senha_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $pendingUser['id'],
        ]);
        $query = $pdo->prepare(
            'SELECT id, nome, email, role, ativo FROM usuarios WHERE id = :id LIMIT 1'
        );
        $query->execute(['id' => (int) $pendingUser['id']]);
        $user = $query->fetch(PDO::FETCH_ASSOC);
        if (!$user || (int) $user['ativo'] !== 1) {
            throw new RuntimeException('Não foi possível concluir o cadastro. Tente novamente.');
        }

        $clearPasswordSetup = $pdo->prepare(
            'UPDATE auth_identities SET password_setup_required = 0 WHERE user_id = :id'
        );
        $clearPasswordSetup->execute(['id' => (int) $pendingUser['id']]);
        $pdo->commit();
        authSetUserSession($user, (string) ($pendingUser['provider'] ?? 'oauth'));
        header('Location: dashboard.php');
        exit();
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('CardioWeb social account password setup failed: ' . $exception->getMessage());
        $_SESSION['password_setup_error'] = 'Não foi possível salvar a senha. Tente novamente.';
        header('Location: set-password.php');
        exit();
    }
}

$error = $_SESSION['password_setup_error'] ?? '';
unset($_SESSION['password_setup_error']);
$csrfToken = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crie sua senha - CardioWeb</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=20261105-login-v27">
</head>
<body class="register-page">
    <main class="login-container register-container" style="max-width: 500px;">
        <section class="form-side" style="flex: 1;">
            <header class="form-header">
                <h3>Crie sua senha</h3>
                <p>Seu cadastro com <?php echo htmlspecialchars(ucfirst((string) ($pendingUser['provider'] ?? 'provedor')), ENT_QUOTES, 'UTF-8'); ?> está salvo. Defina uma senha para entrar também com seu e-mail.</p>
            </header>
            <p class="register-prompt" style="margin-bottom: 20px;">
                <?php echo htmlspecialchars((string) ($pendingUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <?php if (is_string($error) && $error !== ''): ?>
                <div class="login-feedback login-feedback-error" role="alert">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            <form action="set-password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="input-field" placeholder="Nova senha (mínimo 8 caracteres)" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" class="input-field" placeholder="Confirme sua senha" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="btn-login">Salvar senha e acessar</button>
            </form>
        </section>
    </main>
</body>
</html>
