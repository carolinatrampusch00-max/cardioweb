<?php
session_start();
$csrfToken = $_SESSION['csrf_token'] ?? null;
if (!is_string($csrfToken) || strlen($csrfToken) !== 64) {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrfToken;
}
$requestHost = strtolower((string) parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
$isLocalDemo = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && in_array($requestHost, ['localhost', '127.0.0.1', '::1'], true);
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    $_SESSION['user_role'] = $_SESSION['user_role'] ?? 'paciente';
    header('Location: dashboard.php');
    exit();
}

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$registrationError = $_SESSION['registration_error'] ?? null;
unset($_SESSION['registration_error']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>CardioWeb - Login | Plataforma de Saúde</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=20261105-login-v27">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-container {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease;
            overflow: hidden;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e5ea;
            background: white;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        
        .modal-header h3 i {
            margin-right: 8px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
            background: white;
            min-height: 250px;
        }
        
        .form-modal h2 {
            font-size: 22px;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .form-modal p {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .form-modal .input-group {
            margin-bottom: 15px;
        }
        
        .form-modal input, .form-modal select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
        }
        
        .form-modal input:focus, .form-modal select:focus {
            outline: none;
            border-color: #851e32;
        }
        
        .form-modal button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            background: #851e32;
            color: white;
            margin-top: 10px;
        }
        
        .form-modal button:hover {
            background: #6a182c;
        }
        
        .form-modal .cancel-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .form-modal .cancel-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .form-modal .cancel-link a:hover {
            color: #851e32;
        }
        
        .step {
            display: none;
        }
        
        .step.active {
            display: block;
        }
        
        .timer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        
        .code-hint {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
        }
        
        .resend-btn {
            background: transparent;
            color: #851e32;
            border: 1px solid #851e32;
            margin-top: 10px;
        }
        
        .resend-btn:hover {
            background: #851e32;
            color: white;
        }

        .account-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border: 1px solid #e5e5ea;
            border-radius: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .account-option:hover {
            background: #f8f9fa;
        }
        
        .account-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        
        .account-info {
            flex: 1;
        }
        
        .account-name {
            font-weight: 500;
            font-size: 15px;
        }
        
        .account-email {
            font-size: 12px;
            color: #666;
        }
        
        .google-logo-modal {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .google-logo-modal i {
            font-size: 48px;
            color: #4285f4;
        }
        
        .privacy-text {
            font-size: 11px;
            color: #999;
            text-align: center;
            margin-top: 20px;
        }
        
        .privacy-text a {
            color: #4285f4;
            text-decoration: none;
        }
        body.auth-modal-open { overflow: hidden; }
        .demo-provider-modal { max-height: min(90vh, 620px); overflow-y: auto; }
        .demo-provider-body { min-height: 0; padding: 18px 22px 22px; }
        .demo-provider-intro { margin: 0 0 16px; color: #5a5054; font-size: 13px; line-height: 1.55; }
        .demo-account-list { display: grid; gap: 7px; }
        .demo-account-list .account-option { width: 100%; margin: 0; background: #fff; color: #33282c; text-align: left; font-family: inherit; }
        .demo-account-list .account-option:hover { border-color: #dfc3ca; background: #fcf6f7; }
        .demo-account-list .account-option > i { color: #9b858b; font-size: 12px; }
        .demo-provider-note { margin: 15px 0 0; color: #7d7277; font-size: 11px; line-height: 1.55; }
        .apple-demo-mark { display: grid; width: 42px; height: 42px; margin: 0 auto 10px; place-items: center; border-radius: 50%; background: #211d1e; color: white; font-size: 23px; }
        .demo-apple-form .input-group { margin-bottom: 10px; }
        .demo-apple-form input, .register-modal-form input { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #e4d9dc; border-radius: 9px; background: #fff; color: #30252a; font: inherit; font-size: 13px; }
        .demo-apple-form input:focus, .register-modal-form input:focus { outline: 2px solid rgba(133,30,50,.18); border-color: #851e32; }
        .demo-apple-form button, .register-modal-form button { min-height: 44px; margin-top: 2px; border-radius: 9px; background: #74152a; }
        .social-login button.social-btn { cursor: pointer; font-family: inherit; }
        .register-modal-container { max-width: 470px; max-height: min(92vh, 690px); overflow-y: auto; }
        .register-modal-container .modal-body { max-height: calc(92vh - 66px); overflow-y: auto; }
        .register-modal-form p { margin: 0 0 15px; }
        .register-modal-form .input-group { margin-bottom: 10px; }
        .register-modal-form .cancel-link { margin-top: 12px; }
        .register-modal-container .login-feedback { margin-bottom: 14px; }
        @media (max-width: 480px) {
            .modal-container { width: calc(100% - 28px); }
            .register-modal-container .modal-body { padding: 16px; }
            .demo-provider-body { padding: 16px; }
        }
    </style>
</head>
<body>
    <main class="login-container">
        <div class="brand-side">
            <div class="logo-area">
                <div class="logo-icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" role="img">
                        <path d="M32 56S7 39 7 21C7 8 24 5 32 19 40 5 57 8 57 21c0 18-25 35-25 35Z" fill="currentColor"/>
                        <path d="M9 31h13l5-9 8 19 7-14 4 4h9" fill="none" stroke="#74152a" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="logo-text">
                    <h2>Cardio<span>Web</span></h2>
                    <p>Saúde & Monitoramento Cardiológico</p>
                </div>
            </div>
            <div class="hero-message">
                <span class="hero-accent" aria-hidden="true"></span>
                <h1>Cuidar do seu<br>coração é <span>viver melhor</span></h1>
                <p>Acompanhe sua saúde, acesse informações confiáveis e tenha mais controle sobre o seu coração.</p>
                <div class="health-stats">
                    <div class="stat"><span><i class="fas fa-wave-square"></i></span><span>Monitoramento<br>em tempo real</span></div>
                    <div class="stat"><span><i class="fas fa-shield-alt"></i></span><span>Dados<br>seguros</span></div>
                    <div class="stat"><span><i class="fas fa-book-open"></i></span><span>Conteúdo<br>especializado</span></div>
                </div>
                <div class="testimonial">
                    <span aria-hidden="true">“</span>
                    <div>Mais saúde, mais tranquilidade<br>em um só lugar.<span>”</span><small>— Mariana S.</small></div>
                </div>
            </div>
            <svg class="brand-ornament" viewBox="0 0 700 560" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <linearGradient id="brand-glow" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#f62b53" stop-opacity=".08"/>
                        <stop offset=".58" stop-color="#bd1238" stop-opacity=".28"/>
                        <stop offset="1" stop-color="#e9274c" stop-opacity=".06"/>
                    </linearGradient>
                    <linearGradient id="brand-pulse" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#9c102d" stop-opacity=".08"/>
                        <stop offset=".45" stop-color="#e21b43" stop-opacity=".68"/>
                        <stop offset="1" stop-color="#b40f31" stop-opacity=".12"/>
                    </linearGradient>
                    <linearGradient id="wave-fade" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="black"/>
                        <stop offset=".2" stop-color="black"/>
                        <stop offset=".4" stop-color="white"/>
                        <stop offset=".76" stop-color="white"/>
                        <stop offset="1" stop-color="black"/>
                    </linearGradient>
                    <mask id="wave-end-fade" maskUnits="objectBoundingBox">
                        <rect width="1" height="1" fill="url(#wave-fade)"/>
                    </mask>
                    <filter id="pulse-glow" x="-30%" y="-50%" width="160%" height="200%">
                        <feGaussianBlur stdDeviation="4"/>
                    </filter>
                    <filter id="wave-soft-edge" x="-10%" y="-80%" width="120%" height="260%">
                        <feGaussianBlur stdDeviation="2.2"/>
                    </filter>
                </defs>
                <path d="M456-20C578 46 554 118 700 214V-20Z" fill="url(#brand-glow)"/>
                <path d="M448-12C560 61 546 111 700 207M483-12C598 59 578 113 700 177" fill="none" stroke="#b6193b" stroke-opacity=".22" stroke-width="2"/>
                <path d="M187 560C292 505 356 492 437 527S598 555 700 493" fill="none" stroke="#e22148" stroke-opacity=".22" stroke-width="8" filter="url(#wave-soft-edge)" mask="url(#wave-end-fade)"/>
                <path d="M187 560C292 505 356 492 437 527S598 555 700 493" fill="none" stroke="#a81031" stroke-opacity=".48" stroke-width="3" mask="url(#wave-end-fade)"/>
                <path d="M225 560C319 515 376 511 452 540S602 556 700 518" fill="none" stroke="#e22148" stroke-opacity=".34" stroke-width="2" mask="url(#wave-end-fade)"/>
                <path d="M450 119h57l15-3 15-27 14 67 18-104 18 123 17-64 17 24 16-2h63" fill="none" stroke="#db163d" stroke-opacity=".5" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" filter="url(#pulse-glow)"/>
                <path d="M450 119h57l15-3 15-27 14 67 18-104 18 123 17-64 17 24 16-2h63" fill="none" stroke="url(#brand-pulse)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <img class="brand-heart-art" src="assets/login-heart.png?v=20261105-1" alt="" aria-hidden="true">
            <svg class="brand-ecg" viewBox="0 0 240 120" aria-hidden="true"><path d="M0 72h72l17-2 12-19 12 38 14-76 16 91 15-48 12 17 12-1h58" /></svg>
        </div>

        <div class="form-side">
            <div class="form-header">
                <h3>Acesse sua <span>conta</span></h3>
                <p>Entre com seus dados para continuar</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="login-feedback login-feedback-error" role="alert">
                    <?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php elseif (is_string($loginError) && $loginError !== ''): ?>
                <div class="login-feedback login-feedback-error" role="alert">
                    <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="login-feedback login-feedback-success" role="status">
                    <?php echo htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="process_login.php" method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="input-group">
                    <div class="input-icon login-input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="input-field" placeholder="Seu e-mail" autocomplete="username" required>
                    </div>
                </div>
                <div class="input-group">
                    <div class="input-icon login-input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="input-field" placeholder="Sua senha" autocomplete="current-password" required>
                    </div>
                </div>
                <div class="forgot-row">
                    <a href="#" class="forgot-link" onclick="openForgotModal(); return false;">Esqueceu a senha?</a>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-arrow-right-to-bracket"></i> Entrar
                </button>
            </form>

            <div class="divider">
                <span>ou acesse com</span>
            </div>

            <div class="social-login">
                <a class="social-btn" href="<?php echo $isLocalDemo ? '#google-demo' : 'oauth.php?provider=google'; ?>" <?php echo $isLocalDemo ? 'onclick="openGoogleModal(); return false;"' : ''; ?>>
                    <svg class="google-mark" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94a11.1 11.1 0 0 1-4.8 7.3l7.73 6C44.38 38.2 46.98 31.9 46.98 24.55Z"/><path fill="#FBBC05" d="M10.53 28.59a14.4 14.4 0 0 1 0-9.18l-7.98-6.19a24 24 0 0 0 0 21.56l7.98-6.19Z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.9-5.8l-7.73-6c-2.15 1.45-4.9 2.3-8.17 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48Z"/></svg> Google
                </a>
                <a class="social-btn" href="<?php echo $isLocalDemo ? '#apple-demo' : 'oauth.php?provider=apple'; ?>" <?php echo $isLocalDemo ? 'onclick="openAppleModal(); return false;"' : ''; ?>>
                    <i class="fab fa-apple"></i> Apple
                </a>
                <button type="button" class="social-btn" onclick="openSmsModal()">
                    <i class="fas fa-mobile-alt"></i> SMS
                </button>
            </div>

            <div class="register-prompt">
                Não tem uma conta? <a href="index.php?modal=register" onclick="openRegisterModal(); return false;">Criar conta gratuita</a>
            </div>
        </div>
    </main>

    <div id="toastMsg" class="toast-message">Ação realizada</div>

    <div id="googleModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="googleModalTitle" aria-hidden="true">
        <div class="modal-container demo-provider-modal">
            <div class="modal-header">
                <h3 id="googleModalTitle"><i class="fab fa-google"></i> Escolher uma conta</h3>
                <button type="button" class="modal-close" aria-label="Fechar" onclick="closeAuthModal('googleModal')">&times;</button>
            </div>
            <div class="modal-body demo-provider-body">
                <p class="demo-provider-intro">Continuar no CardioWeb com uma conta Google de demonstração.</p>
                <form action="process_demo_social.php" method="POST" class="demo-account-list">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="provider" value="google">
                    <button class="account-option" type="submit" name="account" value="ana">
                        <span class="account-avatar" style="background:#8c1f37;">A</span>
                        <span class="account-info"><span class="account-name">Ana Silva</span><span class="account-email">ana.demo@cardioweb.test</span></span>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                    <button class="account-option" type="submit" name="account" value="lucas">
                        <span class="account-avatar" style="background:#4f647f;">L</span>
                        <span class="account-info"><span class="account-name">Lucas Martins</span><span class="account-email">lucas.demo@cardioweb.test</span></span>
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </form>
                <p class="demo-provider-note">Demonstração local do TCC. Senha para entrar pelo formulário: <strong>CardioWeb@2026</strong>.</p>
            </div>
        </div>
    </div>

    <div id="appleModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="appleModalTitle" aria-hidden="true">
        <div class="modal-container demo-provider-modal">
            <div class="modal-header">
                <h3 id="appleModalTitle"><i class="fab fa-apple"></i> Continuar com Apple</h3>
                <button type="button" class="modal-close" aria-label="Fechar" onclick="closeAuthModal('appleModal')">&times;</button>
            </div>
            <div class="modal-body demo-provider-body">
                <div class="apple-demo-mark"><i class="fab fa-apple"></i></div>
                <p class="demo-provider-intro">Crie ou acesse sua conta Apple de demonstração para o CardioWeb.</p>
                <form action="process_demo_social.php" method="POST" class="form-modal demo-apple-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="provider" value="apple">
                    <div class="input-group"><input type="text" name="name" placeholder="Nome completo" maxlength="150" autocomplete="name" required></div>
                    <div class="input-group"><input type="email" name="email" placeholder="E-mail do Apple ID" maxlength="150" autocomplete="email" required></div>
                    <div class="input-group"><input type="password" name="password" placeholder="Senha (mínimo 8 caracteres)" minlength="8" maxlength="72" autocomplete="new-password" required></div>
                    <button type="submit">Continuar</button>
                </form>
                <p class="demo-provider-note">Esta conta fictícia fica salva neste banco local e poderá entrar pelo login normal com o e-mail e a senha escolhida.</p>
            </div>
        </div>
    </div>

    <div id="registerModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="registerModalTitle" aria-hidden="true">
        <div class="modal-container register-modal-container">
            <div class="modal-header">
                <h3 id="registerModalTitle"><i class="fas fa-user-plus"></i> Criar conta</h3>
                <button type="button" class="modal-close" aria-label="Fechar" onclick="closeAuthModal('registerModal')">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (is_string($registrationError) && $registrationError !== ''): ?>
                    <div class="login-feedback login-feedback-error" role="alert"><?php echo htmlspecialchars($registrationError, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form action="process_register.php" method="POST" class="form-modal register-modal-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <p>Preencha os dados para criar sua conta CardioWeb.</p>
                    <div class="input-group"><input type="text" name="nome" placeholder="Nome completo" maxlength="150" autocomplete="name" required></div>
                    <div class="input-group"><input type="email" name="email" placeholder="E-mail" maxlength="150" autocomplete="email" required></div>
                    <div class="input-group"><input type="password" name="password" placeholder="Senha (mínimo 8 caracteres)" minlength="8" maxlength="72" autocomplete="new-password" required></div>
                    <div class="input-group"><input type="password" name="confirm_password" placeholder="Confirmar senha" minlength="8" maxlength="72" autocomplete="new-password" required></div>
                    <button type="submit">Criar conta</button>
                    <div class="cancel-link"><a href="#" onclick="closeAuthModal('registerModal'); return false;">Já tem conta? Voltar ao login</a></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para SMS -->
    <div id="smsModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fas fa-mobile-alt"></i> SMS</h3>
                <button class="modal-close" onclick="closeSmsModal()">&times;</button>
            </div>
            <div class="modal-body" id="smsModalBody"></div>
        </div>
    </div>

    <!-- Modal para Esqueceu a Senha -->
    <div id="forgotModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Recuperar senha</h3>
                <button class="modal-close" onclick="closeForgotModal()">&times;</button>
            </div>
            <div class="modal-body" id="forgotModalBody"></div>
        </div>
    </div>

    <script>
        const authCsrfToken = <?php echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function openAuthModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('auth-modal-open');
        }

        function closeAuthModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            if (!document.querySelector('.modal-overlay.active')) document.body.classList.remove('auth-modal-open');
        }

        function openGoogleModal() { openAuthModal('googleModal'); }
        function openAppleModal() { openAuthModal('appleModal'); }
        function openRegisterModal() { openAuthModal('registerModal'); }

        function openSmsModal() {
            const modal = document.getElementById('smsModal');
            const modalBody = document.getElementById('smsModalBody');
            modalBody.innerHTML = `
                <div class="form-modal">
                    <h2>Entrar com SMS</h2>
                    <p>Enviaremos um código para confirmar seu telefone. Na primeira entrada, informe nome, e-mail e uma senha para acessar também pelo login normal.</p>
                    <form id="smsStartForm">
                        <div class="input-group"><input id="smsPhone" type="tel" placeholder="Telefone com código do país (+55...)" autocomplete="tel" required></div>
                        <div class="input-group"><input id="smsName" type="text" placeholder="Nome completo (primeiro acesso)" maxlength="150" autocomplete="name"></div>
                        <div class="input-group"><input id="smsEmail" type="email" placeholder="E-mail (primeiro acesso)" maxlength="150" autocomplete="email"></div>
                        <div class="input-group"><input id="smsPassword" type="password" placeholder="Crie uma senha de 8 a 72 caracteres" minlength="8" maxlength="72" autocomplete="new-password"></div>
                        <button id="smsSendButton" type="submit">Enviar código</button>
                    </form>
                    <form id="smsVerifyForm" hidden>
                        <div class="input-group"><input id="smsCode" type="text" inputmode="numeric" autocomplete="one-time-code" placeholder="Código recebido por SMS" required></div>
                        <button id="smsVerifyButton" type="submit">Confirmar e entrar</button>
                    </form>
                    <div id="smsStatus" class="login-feedback" role="status" aria-live="polite"></div>
                </div>
            `;
            modal.classList.add('active');

            const startForm = document.getElementById('smsStartForm');
            const verifyForm = document.getElementById('smsVerifyForm');
            const status = document.getElementById('smsStatus');
            startForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const button = document.getElementById('smsSendButton');
                button.disabled = true;
                status.textContent = '';
                try {
                    const response = await fetch('sms-auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                        body: new URLSearchParams({
                            csrf_token: authCsrfToken,
                            action: 'start',
                            phone: document.getElementById('smsPhone').value,
                            name: document.getElementById('smsName').value,
                            email: document.getElementById('smsEmail').value,
                            password: document.getElementById('smsPassword').value
                        })
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.error || 'Não foi possível enviar o código.');
                    status.textContent = result.message;
                    status.classList.remove('login-feedback-error');
                    status.classList.add('login-feedback-success');
                    verifyForm.hidden = false;
                    document.getElementById('smsCode').focus();
                } catch (error) {
                    status.textContent = error.message || 'Falha ao enviar o código.';
                    status.classList.remove('login-feedback-success');
                    status.classList.add('login-feedback-error');
                } finally {
                    button.disabled = false;
                }
            });
            verifyForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const button = document.getElementById('smsVerifyButton');
                button.disabled = true;
                status.textContent = '';
                try {
                    const response = await fetch('sms-auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                        body: new URLSearchParams({
                            csrf_token: authCsrfToken,
                            action: 'verify',
                            phone: document.getElementById('smsPhone').value,
                            code: document.getElementById('smsCode').value
                        })
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.error || 'Não foi possível confirmar o código.');
                    window.location.assign(result.redirect);
                } catch (error) {
                    status.textContent = error.message || 'Falha ao confirmar o código.';
                    status.classList.remove('login-feedback-success');
                    status.classList.add('login-feedback-error');
                } finally {
                    button.disabled = false;
                }
            });
        }

        function closeSmsModal() {
            document.getElementById('smsModal').classList.remove('active');
        }

        // ==================== ESQUECEU A SENHA ====================
        function openForgotModal() {
            const modal = document.getElementById('forgotModal');
            const modalBody = document.getElementById('forgotModalBody');

            modalBody.innerHTML = `
                <div class="form-modal">
                    <h2>Recuperar senha</h2>
                    <p>A recuperação automática ainda não está configurada. Nenhum código será enviado e sua senha não será alterada por este formulário.</p>
                    <div class="cancel-link">
                        <a href="#" onclick="closeForgotModal(); return false;">Fechar</a>
                    </div>
                </div>
            `;
            modal.classList.add('active');
        }
        
        function closeForgotModal() {
            const modal = document.getElementById('forgotModal');
            modal.classList.remove('active');
        }
        
        // Fechar modais ao clicar fora
        document.getElementById('smsModal').addEventListener('click', function(e) {
            if(e.target === this) closeSmsModal();
        });
        
        document.getElementById('forgotModal').addEventListener('click', function(e) {
            if(e.target === this) closeForgotModal();
        });

        document.querySelectorAll('.modal-overlay').forEach(function(modal) {
            modal.addEventListener('click', function(event) {
                if (event.target !== modal) return;
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                if (!document.querySelector('.modal-overlay.active')) document.body.classList.remove('auth-modal-open');
            });
        });

        document.addEventListener('keydown', function(event) {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('.modal-overlay.active').forEach(function(modal) {
                closeAuthModal(modal.id);
            });
        });

        const modalToOpen = <?php echo json_encode((string) ($_GET['modal'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        if (modalToOpen === 'register') openRegisterModal();
        if (modalToOpen === 'google') openGoogleModal();
        
        function showToast(message) {
            const toast = document.getElementById('toastMsg');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
