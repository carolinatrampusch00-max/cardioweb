<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'] ?? '';
$accountEditingEnabled = filter_var($user_id, FILTER_VALIDATE_INT) !== false && (int)$user_id > 0;
$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_email = $_SESSION['user_email'] ?? $_SESSION['user_telefone'] ?? 'usuario@email.com';
$login_type = $_SESSION['login_type'] ?? 'Padrão';
$user_role = ucfirst($_SESSION['user_role'] ?? 'Paciente');
if ($user_name === 'Usuário' && filter_var($user_id, FILTER_VALIDATE_INT)) {
    try {
        require_once __DIR__ . '/config/database.php';
        $nameQuery = getConnection()->prepare('SELECT nome FROM usuarios WHERE id = :id LIMIT 1');
        $nameQuery->execute(['id' => $user_id]);
        $storedName = $nameQuery->fetchColumn();
        if (is_string($storedName) && trim($storedName) !== '') {
            $user_name = trim($storedName);
            $_SESSION['user_name'] = $user_name;
        }
    } catch (PDOException $exception) {
        error_log('CardioWeb dashboard account-name lookup failed: ' . $exception->getMessage());
    }
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$page = $_GET['page'] ?? 'monitoramento';

// Incluir arquivos de seções
require_once __DIR__ . '/blog.php';
require_once __DIR__ . '/exames.php';
require_once __DIR__ . '/informacoes.php';
require_once __DIR__ . '/suporte.php';
require_once __DIR__ . '/inicio.php';
require_once __DIR__ . '/relogio.php';

// Monta o HTML de cada seção UMA VEZ e passa pro JavaScript como JSON
$blogHtml = function_exists('getDashboardBlogHtml') ? getDashboardBlogHtml() : (function_exists('getBlogHtml') ? getBlogHtml() : '');
$examesHtml = function_exists('getExamesHtml') ? getExamesHtml() : '';
$informacoesHtml = function_exists('getInformacoesHtml') ? getInformacoesHtml() : '';
$suporteHtml = function_exists('getSuporteHtml') ? getSuporteHtml() : '';
$inicioHtml = function_exists('getInicioHtml') ? getInicioHtml($user_name) : '';
$relogioHtml = function_exists('getRelogioHtml') ? getRelogioHtml() : '';

// Se não existir getDashboardBlogHtml, monta um fallback
if (empty($blogHtml) && function_exists('getBlogArticles')) {
    $articles = getBlogArticles();
    $html = '<div class="blog-shell"><div class="info-card"><h3><i class="fas fa-newspaper"></i> Artigos Recentes</h3>';
    foreach ($articles as $id => $a) {
        $html .= '<div class="blog-post cursor-pointer" onclick="openArticle(\'' . htmlspecialchars($id, ENT_QUOTES) . '\', event)">';
        $html .= '<div class="blog-title">' . htmlspecialchars($a['title']) . '</div>';
        $html .= '<div class="blog-date">' . htmlspecialchars($a['date']) . '</div>';
        $html .= '</div>';
    }
    $html .= '</div></div>';
    $blogHtml = $html;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title>CardioWeb - Painel Principal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f0f1; overflow: hidden; height: 100vh; }
        .app-container { display: flex; height: 100vh; width: 100%; background: #f5f0f1; }
        .sidebar { width: 290px; background: linear-gradient(180deg, #450a18 0%, #6f1528 100%); color: white; display: flex; flex-direction: column; box-shadow: 4px 0 20px rgba(0,0,0,0.14); position: relative; }
        .logo-area { padding: 30px 24px 18px; border-bottom: 1px solid rgba(255,255,255,0.10); }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 42px; height: 42px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 23px; color: #fff; background: rgba(255,255,255,0.08); }
        .logo-text h2 { font-size: 18px; letter-spacing: -0.06em; font-weight: 800; margin: 0; }
        .logo-text p { font-size: 9px; opacity: 0.9; margin-top: 2px; letter-spacing: 0.12em; text-transform: uppercase; }
        .nav-menu { flex: 1; padding: 18px 18px 0; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 15px 18px; margin-bottom: 8px; border-radius: 12px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease; color: rgba(255,255,255,0.78); }
        .nav-item:hover { background: rgba(255,255,255,0.05); color: white; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(26, 5, 12, 0.14); }
        .nav-item.active { background: rgba(255,255,255,0.16); color: white; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08); }
        .nav-item i { width: 20px; font-size: 18px; text-align: center; }
        .nav-item span { font-size: 15px; }
        .sidebar-wave { height: 150px; display: flex; align-items: end; justify-content: center; opacity: 0.9; margin-top: auto; }
        .sidebar-wave svg { display: block; }
        .main-header { background: rgba(255,255,255,0.2); padding: 14px 18px 12px 18px; border-bottom: 1px solid #eadfe3; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .page-title { display: none; }
        .header-actions { display: flex; align-items: center; gap: 12px; }
        .header-icon { width: 38px; height: 38px; background: rgba(255,255,255,0.75); border: 1px solid rgba(118,84,92,0.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #4f2a33; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .header-icon:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(58, 14, 24, 0.08); }
        .user-mini { display: flex; align-items: center; gap: 10px; padding: 4px 10px 4px 4px; background: rgba(255,255,255,0.3); border-radius: 999px; color: #4d2a33; font-weight: 600; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .user-mini:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(58, 14, 24, 0.08); }
        .mini-avatar { width: 30px; height: 30px; border-radius: 50%; background: #8d1e36; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 13px; }
        .content-area { flex: 1; overflow-y: auto; padding: 24px 22px 18px; }
        .welcome-card { background: linear-gradient(135deg, #851e32 0%, #5a1e2c 100%); color: white; padding: 30px; border-radius: 20px; margin-bottom: 30px; }
        .welcome-card h2 { font-size: 28px; margin-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(68, 17, 29, 0.12); }
        .stat-icon { width: 50px; height: 50px; background: #fff0f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #851e32; margin-bottom: 15px; }
        .stat-card h3 { font-size: 28px; color: #1e2a3a; margin-bottom: 5px; }
        .stat-card p { color: #64748b; font-size: 14px; }
        .info-card { background: white; border-radius: 16px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(68, 17, 29, 0.08); }
        .info-card h3 { color: #1e2a3a; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .blog-post { padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.3s; }
        .blog-post:hover { background: #f8fafc; }
        .blog-title { font-weight: 600; color: #1e2a3a; margin-bottom: 5px; }
        .blog-date { font-size: 12px; color: #94a3b8; }
        .support-card { background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
        .article-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 16px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .article-back-btn { display: inline-flex; align-items: center; gap: 8px; color: #851e32; margin-bottom: 20px; cursor: pointer; padding: 8px 12px; border-radius: 8px; transition: all 0.3s; font-weight: 500; }
        .article-back-btn:hover { background: #f8fafc; }
        .article-header { margin-bottom: 30px; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; }
        .article-title { font-size: 32px; font-weight: 700; color: #1e2a3a; margin-bottom: 10px; line-height: 1.3; }
        .article-meta { display: flex; gap: 20px; color: #666; font-size: 14px; }
        .article-meta-item { display: flex; align-items: center; gap: 5px; }
        .article-content { line-height: 1.8; color: #333; font-size: 16px; }
        .article-content p { margin-bottom: 20px; text-align: justify; }
        .article-content h3 { font-size: 20px; font-weight: 700; color: #1e2a3a; margin: 30px 0 15px 0; }
        .article-image-inline { max-width: 300px; height: auto; border-radius: 12px; margin: 15px 15px 15px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .article-image-inline.right { float: right; margin-left: 15px; margin-right: 0; }
        .article-reading { width: 100%; max-width: 1040px; margin: 0 auto; padding-bottom: 16px; }
        .article-reading-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .article-reading-top .article-back-btn { margin: 0; }
        .article-share-note { color: #84777c; font-size: 10px; }
        .article-feature { position: relative; display: grid; min-height: 330px; grid-template-columns: minmax(0,1.1fr) minmax(260px,.9fr); overflow: hidden; border-radius: 22px; background: linear-gradient(125deg,#3a0715 0%,#71182e 55%,#a74459 100%); box-shadow: 0 18px 42px rgba(58,6,21,.15); color: #fff; }
        .article-feature::after { position: absolute; right: 33%; bottom: -145px; width: 320px; height: 320px; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; box-shadow: 0 0 0 30px rgba(255,255,255,.035),0 0 0 60px rgba(255,255,255,.025); content: ""; pointer-events: none; }
        .article-feature-copy { position: relative; z-index: 1; display: flex; flex-direction: column; justify-content: center; padding: 34px clamp(22px,4vw,52px); }
        .article-feature-kicker { display: inline-flex; align-items: center; gap: 8px; color: #f1cdd5; font-size: 9px; font-weight: 750; letter-spacing: .15em; text-transform: uppercase; }
        .article-feature h1 { max-width: 590px; margin: 15px 0 12px; color: #fff; font-size: clamp(27px,3.5vw,44px); letter-spacing: -.045em; line-height: 1.08; }
        .article-feature-copy > p { max-width: 570px; margin: 0; color: rgba(255,255,255,.82); font-size: 13px; line-height: 1.7; }
        .article-feature-meta { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 20px; }
        .article-feature-meta span { display: inline-flex; align-items: center; gap: 7px; padding: 8px 10px; border: 1px solid rgba(255,255,255,.19); border-radius: 999px; background: rgba(255,255,255,.09); color: #fff7f8; font-size: 9px; }
        .article-feature-image { position: relative; min-height: 330px; overflow: hidden; background: linear-gradient(135deg,#a24459,#efb7c2); }
        .article-feature-image::after { position: absolute; inset: 0; background: linear-gradient(90deg,rgba(63,8,23,.2),transparent 40%,rgba(45,4,16,.08)); content: ""; }
        .article-feature-image img { display: block; width: 100%; height: 100%; min-height: 330px; object-fit: cover; }
        .article-feature-image.image-unavailable::before { position: absolute; inset: 0; display: grid; place-items: center; color: rgba(255,255,255,.88); font-family: "Font Awesome 6 Free"; font-size: 62px; font-weight: 900; content: "\f21e"; }
        .article-visual.image-unavailable::before { position: absolute; inset: 0; display: grid; place-items: center; color: rgba(255,255,255,.85); font-family: "Font Awesome 6 Free"; font-size: 42px; font-weight: 900; content: "\f21e"; }
        .article-reading-content { display: grid; grid-template-columns: minmax(0,1fr) 250px; gap: 20px; align-items: start; margin-top: 20px; }
        .article-prose { min-width: 0; padding: clamp(20px,3.3vw,38px); border: 1px solid #eee7e9; border-radius: 18px; background: #fff; box-shadow: 0 8px 28px rgba(54,22,31,.045); color: #45353b; }
        .article-prose > p:first-child { color: #543641; font-size: 17px; font-weight: 500; line-height: 1.8; }
        .article-prose p { margin: 0 0 17px; color: #665b60; font-size: 13px; line-height: 1.85; }
        .article-prose h2 { margin: 32px 0 10px; color: #381923; font-size: 22px; letter-spacing: -.025em; }
        .article-prose h3 { margin: 25px 0 9px; color: #6e1a30; font-size: 16px; }
        .article-prose ul { display: grid; gap: 9px; margin: 0 0 18px; padding-left: 21px; color: #665b60; font-size: 13px; line-height: 1.75; }
        .article-prose li::marker { color: #9a3450; }
        .article-prose strong { color: #47222e; }
        .article-visual-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; margin: 24px 0; }
        .article-visual { position: relative; min-width: 0; overflow: hidden; margin: 0; border-radius: 14px; background: linear-gradient(135deg,#4c1024,#cc7184); }
        .article-visual img { display: block; width: 100%; height: clamp(140px,19vw,220px); object-fit: cover; transition: transform .35s ease; }
        .article-visual-inline { margin: 24px 0; }
        .article-visual-inline img { height: clamp(190px,28vw,320px); }
        .article-visual:hover img { transform: scale(1.04); }
        .article-visual figcaption { position: absolute; right: 0; bottom: 0; left: 0; padding: 23px 12px 10px; background: linear-gradient(transparent,rgba(35,7,16,.78)); color: #fff; font-size: 9px; line-height: 1.4; }
        .article-visual.image-unavailable { min-height: 140px; }
        .article-callout { margin: 22px 0; padding: 17px 18px; border: 1px solid #f0dfe3; border-left: 4px solid #8c2039; border-radius: 12px; background: linear-gradient(120deg,#fbf3f5,#fff); }
        .article-callout strong { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; color: #74152a; font-size: 12px; }
        .article-callout p { margin: 0; font-size: 11px; }
        .article-reading-aside { display: grid; gap: 12px; }
        .article-aside-card { padding: 17px; border: 1px solid #eee7e9; border-radius: 15px; background: #fff; box-shadow: 0 6px 20px rgba(54,22,31,.04); }
        .article-aside-card h2 { margin: 0 0 11px; color: #3e202a; font-size: 13px; }
        .article-aside-card p { margin: 0; color: #74686d; font-size: 10px; line-height: 1.65; }
        .article-takeaway { background: linear-gradient(145deg,#591026,#8e2942); color: #fff; }
        .article-takeaway h2, .article-takeaway p { color: #fff; }
        .article-source { display: flex; gap: 9px; color: #74152a; }
        .article-source i { margin-top: 2px; }
        .article-disclaimer { margin-top: 14px !important; color: #91848a !important; font-size: 9px !important; }
        body.dark-mode .article-prose, body.dark-mode .article-aside-card { border-color: #39323a; background: #262229; }
        body.dark-mode .article-prose > p:first-child, body.dark-mode .article-prose h2, body.dark-mode .article-prose strong, body.dark-mode .article-aside-card h2 { color: #f3eef0; }
        body.dark-mode .article-prose p, body.dark-mode .article-prose ul, body.dark-mode .article-aside-card p { color: #c2b8be; }
        body.dark-mode .article-callout { border-color: #49303a; background: linear-gradient(120deg,#30232a,#262229); }
        @media (max-width: 760px) {
            .article-feature { grid-template-columns: minmax(0,1fr); }
            .article-feature-copy { padding: 27px 23px; }
            .article-feature-image, .article-feature-image img { min-height: 220px; max-height: 260px; }
            .article-reading-content { grid-template-columns: minmax(0,1fr); }
            .article-reading-aside { grid-template-columns: repeat(2,minmax(0,1fr)); }
        }
        @media (max-width: 500px) {
            .article-reading-top { align-items: flex-start; }
            .article-share-note { display: none; }
            .article-feature { border-radius: 16px; }
            .article-feature-copy { padding: 23px 18px; }
            .article-feature h1 { font-size: 29px; }
            .article-feature-image, .article-feature-image img { min-height: 190px; max-height: 215px; }
            .article-reading-content { margin-top: 12px; }
            .article-prose { padding: 19px 16px; border-radius: 14px; }
            .article-prose > p:first-child { font-size: 15px; }
            .article-visual-grid { grid-template-columns: minmax(0,1fr); }
            .article-visual img { height: 200px; }
            .article-reading-aside { grid-template-columns: minmax(0,1fr); }
        }
        .cursor-pointer { cursor: pointer; }
        .blog-shell {
            display: flex;
            flex-direction: column;
            gap: 28px;
            min-height: 100%;
        }
        .cardio-hero {
            position: relative;
            min-height: 560px;
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(0,0,0,0.18) 0%, rgba(0,0,0,0.54) 100%),
                url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1800&q=80') center/cover no-repeat;
            border-radius: 0;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.06);
        }
        .cardio-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at center, rgba(248, 214, 0, 0.35), transparent 46%);
        }
        .cardio-hero-controls {
            position: absolute;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(17, 17, 17, 0.42);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            z-index: 2;
        }
        .cardio-hero-controls i {
            font-size: 22px;
            color: white;
        }
        .cardio-hero-content {
            position: absolute;
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%);
            width: min(980px, 80%);
            z-index: 2;
        }
        .cardio-yellow-panel {
            background: linear-gradient(135deg, rgba(130, 18, 35, 0.96) 0%, rgba(157, 30, 50, 0.96) 100%);
            color: #fdf2f5;
            padding: 42px 28px 22px;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
            border-radius: 0;
            box-shadow: 0 26px 42px rgba(62, 11, 20, 0.2);
        }
        .cardio-yellow-panel .line {
            display: block;
            font-size: clamp(2.1rem, 3vw, 4.0rem);
            font-weight: 900;
            letter-spacing: -0.08em;
            line-height: 0.92;
            text-transform: uppercase;
            text-align: center;
            color: #fdf2f5;
        }
        .cardio-yellow-panel .line + .line {
            margin-top: 6px;
        }
        .cardio-yellow-panel .line.black-outline {
            display: inline-block;
            width: 100%;
            position: relative;
            padding: 0 2px;
        }
        .cardio-yellow-panel .line.black-outline::before {
            content: "";
            position: absolute;
            inset: 0;
            border-bottom: 6px solid rgba(255,255,255,0.65);
            bottom: -10px;
            left: 0;
            width: 100%;
        }
        .cardio-heart-stage {
            position: relative;
            padding: 26px 24px 0;
        }
        .heart-orbit {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: min(330px, 70vw);
            height: 330px;
            margin: 0 auto;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.10), rgba(244,212,0,0.10) 44%, rgba(255,255,255,0.02) 72%);
            box-shadow: inset 0 0 50px rgba(244,212,0,0.15);
            animation: heartPulse 2.6s ease-in-out infinite;
        }
        @keyframes heartPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.06); }
        }
        .heart-svg {
            width: 60%;
            height: 60%;
            filter: drop-shadow(0 16px 28px rgba(244,212,0,0.28));
        }
        .heart-svg path {
            fill: url(#heartGradient);
            stroke: rgba(255,255,255,0.35);
            stroke-width: 3;
        }
        #heartGradient stop:first-child { stop-color: #ffd7db; }
        #heartGradient stop:nth-child(2) { stop-color: #c51c3c; }
        #heartGradient stop:last-child { stop-color: #6b0d1a; }
        .cardio-section {
            display: grid;
            grid-template-columns: 1.15fr 1fr 0.9fr;
            gap: 26px;
            align-items: start;
        }
        .cardio-card {
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(148,163,184,0.12);
            box-shadow: 0 12px 24px rgba(15,23,42,0.05);
            padding: 24px 22px;
        }
        .cardio-card h3 {
            font-size: clamp(1.8rem, 2vw, 2.4rem);
            letter-spacing: -0.06em;
            font-weight: 900;
            color: #111827;
            margin-bottom: 16px;
        }
        .cardio-card p {
            color: #4b5563;
            line-height: 1.75;
            font-size: 1rem;
        }
        .cardio-card .mini-tag {
            display: inline-block;
            background: rgba(244,212,0,0.16);
            color: #7a6300;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .cardio-stack {
            display: grid;
            gap: 18px;
        }
        .pill-info {
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(249,250,251,1));
            border: 1px solid rgba(148,163,184,0.12);
            padding: 18px 18px;
            box-shadow: 0 10px 20px rgba(15,23,42,0.04);
        }
        .pill-info h4 {
            margin: 0 0 8px;
            font-size: 1.2rem;
            font-weight: 800;
            color: #111827;
        }
        .pill-info p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        .cardio-lists {
            display: grid;
            gap: 18px;
            margin-top: 30px;
        }
        .cardio-list-item {
            display: grid;
            grid-template-columns: 92px 1fr;
            gap: 18px;
            align-items: center;
            background: rgba(255,255,255,0.94);
            border: 1px solid rgba(148,163,184,0.12);
            padding: 18px 16px;
            box-shadow: 0 10px 20px rgba(15,23,42,0.04);
        }
        .cardio-list-badge {
            width: 92px;
            height: 92px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(244,212,0,0.18), rgba(254,243,195,0.65));
            border: 1px solid rgba(244,212,0,0.4);
            color: #a26900;
            font-size: 38px;
            font-weight: 900;
        }
        .cardio-list-item h4 {
            margin: 0 0 8px;
            color: #111827;
            font-weight: 800;
            font-size: 1.2rem;
        }
        .cardio-list-item p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
            font-size: 0.96rem;
        }
        .cardio-video-fallback {
            position: absolute;
            right: 28px;
            bottom: 26px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 2;
            color: white;
            font-weight: 700;
            font-size: 0.92rem;
        }
        .cardio-video-fallback .circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.30);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }
        @media (max-width: 900px) {
            .cardio-section {
                grid-template-columns: 1fr;
            }
            .cardio-hero { min-height: 440px; }
        }
        .blog-shell.blog-scroll-layout { display: flex; flex-direction: column; gap: 26px; }
        .blog-intro {
            background: linear-gradient(135deg, rgba(133,30,50,0.98), rgba(94,19,35,0.98));
            padding: 32px 34px;
            border-radius: 22px;
            box-shadow: 0 18px 30px rgba(72, 16, 27, 0.12);
            color: white;
        }
        .blog-intro-copy h2 {
            margin: 12px 0 10px;
            font-size: clamp(2rem, 2.8vw, 3.1rem);
            letter-spacing: -0.08em;
            line-height: 1;
        }
        .blog-intro-copy p {
            margin: 0;
            max-width: 620px;
            color: rgba(255,255,255,0.82);
            line-height: 1.7;
        }
        .story-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.12);
            color: white;
            border: 1px solid rgba(255,255,255,0.18);
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .scroll-story {
            display: grid;
            grid-template-columns: minmax(260px, 1.2fr) minmax(0, 1.6fr);
            gap: 26px;
            align-items: stretch;
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(148,163,184,0.12);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15,23,42,0.05);
            opacity: 0;
            transform: translateY(36px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .scroll-story.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .story-image img {
            width: 100%;
            height: 100%;
            min-height: 260px;
            object-fit: cover;
            display: block;
            background: #f3f4f6;
        }
        .story-content {
            padding: 30px 28px 24px;
        }
        .story-topline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .story-badge:not(.blog-intro .story-badge) {
            background: rgba(133,30,50,0.08);
            color: #7d1528;
            border-color: rgba(133,30,50,0.12);
        }
        .story-date {
            font-size: 12px;
            color: #6b7280;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .story-content h3 {
            margin: 0 0 12px;
            font-size: clamp(1.8rem, 2vw, 2.5rem);
            line-height: 1.1;
            letter-spacing: -0.06em;
            color: #111827;
        }
        .story-summary {
            margin: 0 0 18px;
            color: #4b5563;
            line-height: 1.7;
            font-size: 1rem;
        }
        .blog-page-layout { display: grid; width: 100%; max-width: 1320px; min-height: 100%; grid-template-columns: minmax(0,1fr) 290px; align-items: stretch; gap: 22px; margin: 0 auto; }
        .blog-main-column { min-width: 0; }
        .blog-search { display: flex; height: 38px; align-items: center; gap: 12px; margin: 0 0 18px; padding: 0 16px; border: 1px solid #ede9ea; border-radius: 14px; background: #f1eff0; color: #30303a; }
        .blog-search i { font-size: 14px; }
        .blog-search input { width: 100%; border: 0; outline: 0; background: transparent; color: #34343d; font-family: inherit; font-size: 11px; }
        .blog-search input::placeholder { color: #77747c; }
        .blog-hero { position: relative; display: flex; min-height: 172px; align-items: center; overflow: hidden; padding: 21px 30px; border-radius: 12px; background: linear-gradient(90deg,#30020f 0%,#410415 48%,rgba(55,3,17,.15) 100%),url('assets/dashboard-welcome-art.png') right center/auto 100% no-repeat,#3d0715; color: #fff; }
        .blog-hero-copy { position: relative; z-index: 1; max-width: 62%; }
        .blog-eyebrow { display: flex; align-items: center; gap: 9px; margin-bottom: 10px; font-size: 8px; font-weight: 700; letter-spacing: .08em; }
        .blog-eyebrow > span, .blog-section-heading h3 > span { width: 27px; height: 2px; background: #ce3e55; }
        .blog-hero h2 { margin: 0 0 7px; font-size: clamp(21px,2.2vw,31px); font-weight: 750; letter-spacing: -.045em; line-height: 1.05; }
        .blog-hero h2 em { color: #d83d57; font-style: normal; }
        .blog-hero p { margin: 0; color: rgba(255,255,255,.86); font-size: 10px; line-height: 1.45; }
        .blog-hero > img { position: absolute; right: 0; bottom: 0; width: 42%; height: 100%; object-fit: cover; object-position: 45% center; mix-blend-mode: screen; opacity: .88; }
        .blog-section-heading { display: flex; align-items: center; justify-content: space-between; margin: 19px 0 12px; }
        .blog-section-heading h3 { display: flex; align-items: center; gap: 10px; margin: 0; color: #282833; font-size: 15px; }
        .blog-section-heading button { display: flex; align-items: center; gap: 9px; padding: 5px 0 5px 8px; border: 0; background: none; color: #75152a; font-family: inherit; font-size: 10px; cursor: pointer; }
        .blog-articles-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 12px; }
        .blog-article-card { display: flex; min-width: 0; flex-direction: column; overflow: hidden; padding: 0; border: 1px solid #eeebec; border-radius: 11px; background: #fff; color: #30232a; text-align: left; box-shadow: 0 4px 12px rgba(45,18,25,.045); cursor: pointer; transition: transform .18s ease,box-shadow .18s ease; }
        .blog-article-card:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(45,18,25,.09); }
        .blog-card-image { position: relative; display: block; height: clamp(92px,9vw,124px); overflow: hidden; background: linear-gradient(135deg,#541426,#d39da5); }
        .blog-card-image img, .blog-featured-image img { width: 100%; height: 100%; object-fit: cover; }
        .blog-card-image.image-unavailable { background: radial-gradient(circle at 72% 28%,rgba(255,255,255,.22),transparent 24%),linear-gradient(135deg,#531224,#a73b55); }
        .blog-card-image.image-unavailable::after { position: absolute; inset: 0; display: grid; place-items: center; color: rgba(255,255,255,.76); font-family: "Font Awesome 6 Free"; font-size: 35px; font-weight: 900; content: "\f21e"; }
        .blog-category { position: absolute; bottom: 6px; left: 9px; max-width: calc(100% - 18px); overflow: hidden; padding: 4px 8px; border-radius: 999px; background: #74152a; color: #fff; font-size: 8px; font-weight: 700; text-overflow: ellipsis; text-transform: uppercase; white-space: nowrap; }
        .blog-card-copy { display: flex; flex: 1; flex-direction: column; gap: 6px; padding: 9px 11px 8px; }
        .blog-card-copy > strong { display: -webkit-box; min-height: 30px; overflow: hidden; color: #30303a; font-size: 10px; line-height: 1.4; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .blog-card-summary { display: -webkit-box; min-height: 29px; overflow: hidden; color: #77747c; font-size: 9px; line-height: 1.45; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .blog-card-date { display: flex; align-items: center; gap: 6px; margin-top: auto; padding-top: 5px; color: #7e7478; font-size: 8px; }
        .blog-card-date > i:first-child { color: #74152a; }
        .blog-card-date > i:last-child { margin-left: auto; color: #74152a; }
        .blog-featured-column { min-width: 0; }
        .blog-featured-card { display: flex; height: 100%; min-height: 540px; flex-direction: column; padding: 16px; border: 1px solid #eeebec; border-radius: 14px; background: #fff; box-shadow: 0 5px 18px rgba(45,18,25,.045); }
        .blog-featured-heading { display: flex; align-items: center; gap: 10px; margin: 0 0 16px; }
        .blog-featured-heading > span { display: grid; width: 31px; height: 31px; place-items: center; border-radius: 50%; background: #74152a; color: white; }
        .blog-featured-heading strong, .blog-featured-heading small { display: block; }
        .blog-featured-heading strong { color: #30303a; font-size: 12px; }
        .blog-featured-heading small { margin-top: 3px; color: #77747c; font-size: 9px; }
        .blog-featured-article { display: flex; min-width: 0; flex-direction: column; padding: 0; border: 0; background: transparent; color: #30232a; text-align: left; cursor: pointer; }
        .blog-featured-image { position: relative; display: block; height: 130px; overflow: hidden; border-radius: 9px; background: linear-gradient(135deg,#d9c4c5,#64172c); }
        .blog-featured-article > strong { margin: 25px 0 7px; color: #30303a; font-size: 12px; line-height: 1.5; }
        .blog-featured-summary { color: #77747c; font-size: 9px; line-height: 1.6; }
        .blog-featured-article .blog-card-date { margin-top: 16px; padding-top: 10px; border-top: 1px solid #f0ebed; }
        .blog-quote { margin-top: 18px; padding: 16px; border-radius: 10px; background: #faf5f5; color: #8f3348; }
        .blog-quote > i { color: #d6aeb7; font-size: 21px; }
        .blog-quote p { margin: -18px 0 6px 50px; color: #777078; font-size: 10px; line-height: 1.6; }
        .blog-quote small { display: block; margin-left: 50px; color: #62575c; font-size: 8px; }
        .blog-featured-ecg { min-height: 70px; flex: 1; margin-top: 12px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 90'%3E%3Cpath d='M0 53h120l18-2 12-5 13-24 19 57 24-74 22 57 15-22 14 13h143' fill='none' stroke='%23d8aeb7' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/100% 100% no-repeat; opacity: .75; }
        .blog-empty-state { grid-column: 1/-1; padding: 28px; border: 1px dashed #d9c8cd; border-radius: 12px; color: #71666c; font-size: 12px; text-align: center; }
        .blog-editorial { display: grid; width: 100%; max-width: 1320px; gap: 18px; margin: 0 auto; }
        .blog-editorial-hero { position: relative; display: grid; min-height: 345px; grid-template-columns: minmax(0,1fr) minmax(240px,1fr) minmax(190px,.72fr); align-items: center; gap: 12px; overflow: hidden; padding: 30px; border: 1px solid #eee5e7; border-radius: 20px; background: linear-gradient(120deg,#fff 0%,#fff 56%,#fbf1f3 100%); }
        .blog-editorial-hero::before { position: absolute; top: -90px; right: 23%; width: 260px; height: 520px; transform: rotate(34deg); background: linear-gradient(180deg,rgba(116,21,42,.02),rgba(116,21,42,.08)); content: ""; pointer-events: none; }
        .blog-editorial-copy, .blog-editorial-feature, .blog-heart-stage { position: relative; z-index: 1; min-width: 0; }
        .blog-editorial-copy { align-self: center; }
        .blog-editorial-kicker { display: block; color: #8a293f; font-size: 9px; font-weight: 800; letter-spacing: .13em; }
        .blog-editorial-copy h2 { margin: 15px 0 12px; color: #2d1920; font-size: clamp(32px,4vw,54px); font-weight: 750; letter-spacing: -.055em; line-height: .99; }
        .blog-editorial-copy h2 em { color: #851e32; font-style: normal; }
        .blog-editorial-copy > p { max-width: 420px; margin: 0; color: #706168; font-size: 13px; line-height: 1.75; }
        .blog-editorial-note { display: inline-flex; align-items: center; gap: 8px; margin-top: 23px; padding: 9px 12px; border-radius: 999px; background: #f8edef; color: #74152a; font-size: 10px; font-weight: 650; }
        .blog-heart-stage { display: grid; min-height: 280px; place-items: center; border-radius: 50%; background: radial-gradient(circle,#f5dce1 0%,#faedf0 44%,rgba(255,255,255,0) 73%); }
        .blog-heart-stage model-viewer { position: relative; z-index: 2; width: 100%; height: 305px; --poster-color: transparent; }
        .blog-heart-fallback { position: absolute; z-index: 1; display: grid; justify-items: center; gap: 8px; color: #9b233b; text-align: center; }
        .blog-heart-fallback i { font-size: 78px; filter: drop-shadow(0 12px 12px rgba(116,21,42,.18)); }
        .blog-heart-fallback small { color: #74152a; font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .blog-heart-stage.model-loaded .blog-heart-fallback { display: none; }
        .blog-editorial-feature { align-self: stretch; display: flex; flex-direction: column; justify-content: center; padding: 19px; border-left: 1px solid #eadde0; }
        .blog-editorial-feature-icon { display: grid; width: 38px; height: 38px; place-items: center; margin-bottom: 17px; border-radius: 12px; background: #74152a; color: white; }
        .blog-editorial-feature h3 { margin: 9px 0; color: #35222a; font-size: 17px; line-height: 1.35; }
        .blog-editorial-feature > p { margin: 0; color: #777078; font-size: 11px; line-height: 1.65; }
        .blog-editorial-feature > button { display: flex; align-items: center; gap: 8px; align-self: flex-start; margin-top: 16px; padding: 0; border: 0; background: transparent; color: #74152a; font-family: inherit; font-size: 10px; font-weight: 700; cursor: pointer; }
        .blog-editorial-feature > button:hover { color: #a32b47; }
        .blog-library { overflow: hidden; padding: 27px; border: 1px solid #642237; border-radius: 20px; background: linear-gradient(145deg,#230812 0%,#310b17 55%,#3a1020 100%); box-shadow: 0 16px 36px rgba(46,5,17,.14); }
        .blog-library-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; margin-bottom: 19px; }
        .blog-library .blog-editorial-kicker { color: #e6a2b0; }
        .blog-library-heading h3 { margin: 7px 0 0; color: #fff8fa; font-size: clamp(18px,2vw,24px); letter-spacing: -.025em; }
        .blog-library-count { display: inline-flex; align-items: center; gap: 7px; color: #d8aeb7; font-size: 10px; white-space: nowrap; }
        .blog-library-count i { color: #d74862; }
        .blog-library .blog-articles-grid { grid-template-columns: repeat(4,minmax(0,1fr)); gap: 14px; }
        .blog-library .blog-article-card { border-color: #61283a; border-radius: 13px; background: linear-gradient(155deg,#42101f,#32101b); color: #fff; box-shadow: 0 8px 20px rgba(0,0,0,.2); transition: transform .2s ease,border-color .2s ease,box-shadow .2s ease; }
        .blog-library .blog-article-card:hover { transform: translateY(-3px); border-color: #a3425b; background: linear-gradient(155deg,#501326,#3c1020); box-shadow: 0 14px 28px rgba(12,0,5,.3); }
        .blog-library .blog-article-card:focus-visible { outline: 3px solid #e7a2b0; outline-offset: 3px; }
        .blog-library .blog-card-image { height: clamp(130px,14vw,190px); background: linear-gradient(135deg,#4b0c20,#9a2a43); }
        .blog-library .blog-card-image img { transition: transform .35s ease; }
        .blog-library .blog-article-card:hover .blog-card-image img { transform: scale(1.04); }
        .blog-library .blog-category { background: #650d24; box-shadow: 0 2px 8px rgba(18,0,6,.22); }
        .blog-library .blog-card-copy { gap: 8px; padding: 13px 14px 12px; }
        .blog-library .blog-card-copy > strong { min-height: 40px; color: #fff8fa; font-size: 12px; }
        .blog-library .blog-card-summary { min-height: 42px; color: #cdbec3; font-size: 10px; }
        .blog-library .blog-card-date { color: #c4adb5; font-size: 9px; }
        .blog-library .blog-card-date > i:first-child, .blog-library .blog-card-date > i:last-child { color: #e47b8c; }
        .blog-library .blog-empty-state { border-color: #64454e; color: #e2d4d8; }
        .blog-disclaimer { margin: -3px 3px 0; color: #83767b; font-size: 10px; line-height: 1.6; }
        body.dark-mode .blog-editorial-hero { border-color: #39323a; background: linear-gradient(120deg,#262229 0%,#262229 56%,#30252b 100%); }
        body.dark-mode .blog-editorial-copy h2, body.dark-mode .blog-editorial-feature h3 { color: #f3eef0; }
        body.dark-mode .blog-editorial-copy > p, body.dark-mode .blog-editorial-feature > p, body.dark-mode .blog-disclaimer { color: #c2b8be; }
        body.dark-mode .blog-editorial-feature { border-color: #49414a; }
        body.dark-mode .blog-editorial-note { background: #49303a; color: #ffdbe3; }
        @media (max-width: 1100px) {
            .blog-editorial-hero { grid-template-columns: minmax(0,1fr) minmax(220px,.9fr); }
            .blog-editorial-feature { grid-column: 1/-1; display: grid; grid-template-columns: 40px minmax(0,1fr) auto; align-items: center; gap: 8px 13px; padding: 15px 0 0; border-top: 1px solid #eadde0; border-left: 0; }
            .blog-editorial-feature-icon { grid-row: 1/4; margin: 0; }
            .blog-editorial-feature .blog-editorial-kicker, .blog-editorial-feature h3, .blog-editorial-feature > p { grid-column: 2; }
            .blog-editorial-feature h3 { margin: 0; }
            .blog-editorial-feature > button { grid-column: 3; grid-row: 1/4; margin: 0; }
            .blog-library .blog-articles-grid { grid-template-columns: repeat(3,minmax(0,1fr)); }
        }
        @media (max-width: 700px) {
            .blog-editorial-hero { grid-template-columns: minmax(0,1fr); gap: 0; padding: 23px; }
            .blog-editorial-copy h2 { font-size: clamp(34px,10vw,48px); }
            .blog-heart-stage { min-height: 250px; margin: 5px 0; }
            .blog-heart-stage model-viewer { height: 265px; }
            .blog-editorial-feature { grid-template-columns: 38px minmax(0,1fr); }
            .blog-editorial-feature .blog-editorial-kicker, .blog-editorial-feature h3, .blog-editorial-feature > p { grid-column: 2; }
            .blog-editorial-feature > button { grid-column: 2; grid-row: auto; margin-top: 5px; }
            .blog-library { padding: 19px 14px; }
            .blog-library .blog-articles-grid { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 10px; }
            .blog-library .blog-card-image { height: 130px; }
            .blog-library .blog-card-copy { padding: 10px; }
            .blog-library .blog-card-copy > strong { font-size: 11px; }
        }
        @media (max-width: 440px) {
            .blog-library .blog-articles-grid { grid-template-columns: minmax(0,1fr); }
            .blog-library .blog-card-image { height: 190px; }
            .blog-library-heading { align-items: flex-start; flex-direction: column; }
        }
        .app-container.blog-active .chat-sidebar { display: none; }
        .app-container.blog-active .content-area { padding: 14px 17px 28px 22px; }
        body.dark-mode .blog-search { border-color: #39323a; background: #262229; color: #f3eef0; }
        body.dark-mode .blog-search input { color: #f3eef0; }
        body.dark-mode .blog-section-heading h3, body.dark-mode .blog-card-copy > strong, body.dark-mode .blog-featured-heading strong, body.dark-mode .blog-featured-article > strong { color: #f3eef0; }
        body.dark-mode .blog-article-card, body.dark-mode .blog-featured-card { border-color: #39323a; background: #262229; color: #f3eef0; }
        body.dark-mode .blog-quote { background: #302b33; }
        body.dark-mode .blog-quote p, body.dark-mode .blog-quote small, body.dark-mode .blog-card-summary, body.dark-mode .blog-featured-summary, body.dark-mode .blog-card-date { color: #c2b8be; }
        .exam-page { display: grid; max-width: 1120px; gap: 18px; margin: 0 auto; }
        .section-hero { padding: 22px 24px; border: 1px solid #efe6e8; border-radius: 15px; background: linear-gradient(115deg,#fff 0%,#fbf5f6 100%); }
        .section-kicker { display: flex; align-items: center; gap: 8px; color: #851e32; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .section-hero h2 { margin: 8px 0 6px; color: #30232a; font-size: 21px; letter-spacing: -.035em; }
        .section-hero p { max-width: 720px; color: #777078; font-size: 12px; line-height: 1.6; }
        .exam-summary-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 12px; }
        .exam-summary-card { display: flex; align-items: center; gap: 11px; padding: 14px; border: 1px solid #eee8ea; border-radius: 12px; background: #fff; }
        .exam-summary-icon { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 11px; background: #f7edef; color: #78152c; }
        .exam-summary-card strong, .exam-summary-card span { display: block; }
        .exam-summary-card strong { color: #35222a; font-size: 16px; }
        .exam-summary-card span { margin-top: 2px; color: #817a80; font-size: 10px; }
        .exam-agenda-layout { display: grid; grid-template-columns: minmax(260px,.85fr) minmax(0,1.4fr); gap: 16px; align-items: start; }
        .exam-panel, .info-page-card, .support-page-card { padding: 18px; border: 1px solid #eee8ea; border-radius: 13px; background: #fff; box-shadow: 0 4px 14px rgba(45,18,25,.035); }
        .exam-panel h3, .info-page-card h3, .support-page-card h3 { margin: 0 0 6px; color: #35222a; font-size: 14px; }
        .exam-panel > p, .info-page-card > p, .support-page-card > p { margin-bottom: 12px; color: #817a80; font-size: 10px; line-height: 1.55; }
        .exam-entry-form { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 10px; }
        .exam-entry-form label { display: grid; min-width: 0; gap: 5px; color: #655a60; font-size: 10px; font-weight: 600; }
        .exam-entry-form label.exam-field-wide, .exam-entry-form .exam-save-row { grid-column: 1/-1; }
        .exam-entry-form input, .exam-entry-form select, .exam-entry-form textarea { width: 100%; min-width: 0; min-height: 37px; padding: 8px 9px; border: 1px solid #e7dfe1; border-radius: 8px; background: white; color: #30232a; font-family: inherit; font-size: 11px; }
        .exam-entry-form textarea { min-height: 76px; resize: vertical; }
        .exam-save-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .exam-save-row .contact-btn { padding: 9px 12px; font-size: 10px; }
        .exam-entry-list { display: grid; gap: 9px; }
        .exam-entry-card { display: grid; grid-template-columns: 39px minmax(0,1fr) auto; align-items: start; gap: 10px; padding: 12px; border: 1px solid #f0ebed; border-radius: 10px; background: #fdfbfb; }
        .exam-entry-icon { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 9px; background: #f5e9ec; color: #78152c; }
        .exam-entry-title { color: #35222a; font-size: 11px; font-weight: 700; }
        .exam-entry-meta { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 5px; color: #817a80; font-size: 9px; }
        .exam-entry-notes { margin-top: 7px; color: #655a60; font-size: 10px; line-height: 1.5; white-space: pre-wrap; }
        .exam-entry-status { padding: 4px 7px; border-radius: 999px; background: #f2edef; color: #74152a; font-size: 8px; font-weight: 700; white-space: nowrap; }
        .exam-entry-delete { grid-column: 2/-1; justify-self: end; padding: 4px 0; border: 0; background: transparent; color: #8c5a66; font-family: inherit; font-size: 9px; cursor: pointer; }
        .exam-empty-state { padding: 24px 14px; border: 1px dashed #e5d8dc; border-radius: 10px; color: #817a80; font-size: 11px; line-height: 1.6; text-align: center; }
        body.dark-mode .section-hero, body.dark-mode .exam-summary-card, body.dark-mode .exam-panel, body.dark-mode .info-page-card, body.dark-mode .support-page-card { border-color: #39323a; background: #262229; }
        body.dark-mode .section-hero h2, body.dark-mode .exam-summary-card strong, body.dark-mode .exam-panel h3, body.dark-mode .info-page-card h3, body.dark-mode .support-page-card h3, body.dark-mode .exam-entry-title { color: #f3eef0; }
        body.dark-mode .section-hero p, body.dark-mode .exam-summary-card span, body.dark-mode .exam-panel > p, body.dark-mode .info-page-card > p, body.dark-mode .support-page-card > p, body.dark-mode .exam-entry-meta, body.dark-mode .exam-entry-notes { color: #c2b8be; }
        body.dark-mode .exam-entry-form input, body.dark-mode .exam-entry-form select, body.dark-mode .exam-entry-form textarea { border-color: #49414a; background: #302b33; color: #fff; }
        body.dark-mode .exam-entry-card { border-color: #39323a; background: #302b33; }
        body.dark-mode .exam-empty-state { border-color: #51454d; color: #c2b8be; }
        .info-intro-grid, .support-contact-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 12px; }
        .support-page { container-type: inline-size; }
        .info-feature { padding: 15px; border: 1px solid #eee8ea; border-radius: 12px; background: #fff; }
        .info-feature > i { display: grid; width: 36px; height: 36px; place-items: center; margin-bottom: 10px; border-radius: 10px; background: #f7edef; color: #78152c; }
        .info-feature strong { display: block; margin-bottom: 5px; color: #35222a; font-size: 11px; }
        .info-feature p { margin: 0; color: #817a80; font-size: 10px; line-height: 1.55; }
        .support-contact { display: flex; min-width: 0; min-height: 190px; flex-direction: column; align-items: flex-start; gap: 14px; padding: 18px; border: 1px solid #eee8ea; border-radius: 15px; background: linear-gradient(155deg,#fff 0%,#fdfafb 100%); box-shadow: 0 5px 16px rgba(54,14,27,.035); transition: transform .18s ease,border-color .18s ease,box-shadow .18s ease; }
        .support-contact:hover { transform: translateY(-2px); border-color: #e6cbd2; box-shadow: 0 10px 24px rgba(54,14,27,.075); }
        .support-contact-icon { display: grid; width: 46px; height: 46px; flex: 0 0 46px; place-items: center; border: 1px solid #f0dfe3; border-radius: 14px; background: linear-gradient(145deg,#fbf1f3,#f5e5e9); color: #78152c; font-size: 18px; }
        .support-contact-copy { display: flex; min-width: 0; flex: 1; flex-direction: column; align-items: flex-start; }
        .support-contact-copy h3 { margin: 0; color: #35222a; font-size: 14px; font-weight: 750; line-height: 1.4; }
        .support-contact-copy p { margin: 6px 0 14px; color: #756a70; font-size: 11px; line-height: 1.6; }
        .support-contact-action { display: inline-flex; min-height: 36px; align-items: center; justify-content: center; gap: 8px; margin-top: auto; padding: 0 12px; border: 1px solid #ead4da; border-radius: 9px; background: #fff; color: #78152c; font: inherit; font-size: 11px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background .18s ease,border-color .18s ease,color .18s ease; }
        .support-contact-action:hover { border-color: #9b2a45; background: #80172f; color: #fff; }
        .support-contact-action:focus-visible { outline: 2px solid #891b34; outline-offset: 3px; }
        @container (max-width: 760px) {
            .support-contact-grid { grid-template-columns: minmax(0,1fr); gap: 10px; }
            .support-contact { min-height: 0; flex-direction: row; align-items: center; gap: 14px; padding: 15px; }
            .support-contact-icon { width: 44px; height: 44px; flex-basis: 44px; }
            .support-contact-copy { align-self: stretch; }
            .support-contact-copy p { margin-bottom: 10px; }
        }
        .info-privacy-link { display: inline; padding: 0; border: 0; background: transparent; color: #891b34; font: inherit; font-weight: 750; text-decoration: underline; text-decoration-color: rgba(137,27,52,.45); text-underline-offset: 3px; cursor: pointer; }
        .info-privacy-link:hover { color: #5c0c21; text-decoration-color: currentColor; }
        .info-privacy-link:focus-visible { border-radius: 3px; outline: 2px solid #891b34; outline-offset: 3px; }
        .watch-page { display: grid; max-width: 1120px; gap: 16px; margin: 0 auto; }
        .watch-hero { display: grid; grid-template-columns: minmax(0,1fr) 220px; align-items: center; gap: 18px; padding: 22px 24px; overflow: hidden; border: 1px solid #ead9dd; border-radius: 16px; background: linear-gradient(115deg,#fff 0%,#fbf4f5 60%,#f4e6e9 100%); }
        .watch-kicker { display: inline-flex; align-items: center; gap: 7px; color: #841a33; font-size: 9px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .watch-hero h2 { margin: 8px 0 6px; color: #351b24; font-size: 24px; letter-spacing: -.04em; }
        .watch-hero p { max-width: 650px; color: #71656a; font-size: 11px; line-height: 1.7; }
        .watch-clock-card { display: grid; justify-items: end; gap: 3px; padding: 13px 15px; border: 1px solid rgba(122,24,47,.12); border-radius: 13px; background: rgba(255,255,255,.78); }
        .watch-clock-card > span { color: #84757b; font-size: 8px; font-weight: 700; letter-spacing: .1em; }
        .watch-clock-card > span i { margin-right: 4px; color: #861b34; }
        .watch-clock-card time { color: #501427; font-size: 28px; font-weight: 750; font-variant-numeric: tabular-nums; letter-spacing: -.04em; }
        .watch-clock-card small { color: #81757a; font-size: 9px; }
        .watch-demo-note { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 1px solid #f0d7dc; border-left: 3px solid #901f3b; border-radius: 10px; background: #fff7f8; color: #69424c; font-size: 10px; line-height: 1.6; }
        .watch-demo-note > i { margin-top: 2px; color: #8c1c38; }
        .watch-demo-note strong { color: #65172d; }
        .watch-appointment-alert { display: grid; gap: 8px; padding: 12px 14px; border: 1px solid #ead4da; border-radius: 11px; background: #fff; box-shadow: 0 5px 16px rgba(54,14,27,.04); }
        .watch-appointment-alert[hidden] { display: none; }
        .watch-agenda-actions { display: flex; align-items: center; gap: 10px; }
        .watch-agenda-count { color: #81767a; font-size: 9px; }
        .watch-agenda-count strong { color: #74152a; }
        .watch-agenda-note { margin: 8px 0 2px; color: #81767a; font-size: 9px; line-height: 1.5; }
        body.dark-mode .watch-agenda-count, body.dark-mode .watch-agenda-note { color: #c2b8be; }
        body.dark-mode .watch-agenda-count strong { color: #f2cbd4; }
        .watch-alert-heading { display: flex; align-items: center; gap: 8px; color: #71152b; font-size: 11px; font-weight: 700; }
        .watch-alert-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-top: 7px; border-top: 1px solid #f0ebed; }
        .watch-alert-item strong { display: block; color: #39212a; font-size: 10px; }
        .watch-alert-item span { display: block; margin-top: 3px; color: #81767a; font-size: 9px; }
        .watch-alert-tag { flex: 0 0 auto; padding: 5px 8px; border-radius: 999px; background: #f7ecee; color: #79172e; font-size: 9px; font-weight: 700; }
        body.dark-mode .watch-appointment-alert { border-color: #49333c; background: #262229; }
        body.dark-mode .watch-measurement { border-color: #39323a; background: #302b33; }
        body.dark-mode .watch-measurement > span, body.dark-mode .watch-measurement > small { color: #c2b8be; }
        body.dark-mode .watch-measurement > strong { color: #f3eef0; }
        body.dark-mode .watch-ecg-trace { background: repeating-linear-gradient(90deg,transparent 0 11px,rgba(242,203,212,.11) 11px 12px),repeating-linear-gradient(0deg,transparent 0 10px,rgba(242,203,212,.11) 10px 11px); }
        body.dark-mode .watch-ecg-trace path { stroke: #f2cbd4; }
        body.dark-mode .watch-alert-heading, body.dark-mode .watch-alert-item strong { color: #f2cbd4; }
        body.dark-mode .watch-alert-item { border-color: #39323a; }
        body.dark-mode .watch-alert-item span { color: #c2b8be; }
        body.dark-mode .watch-alert-tag { background: #49303a; color: #ffdbe3; }
        .watch-layout { display: grid; grid-template-columns: minmax(0,1.45fr) minmax(250px,.8fr); gap: 14px; align-items: start; }
        .watch-monitor-card, .watch-scenarios, .watch-project-note { min-width: 0; padding: 18px; border: 1px solid #eee5e7; border-radius: 15px; background: #fff; box-shadow: 0 8px 24px rgba(54,14,27,.045); }
        .watch-card-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-bottom: 14px; border-bottom: 1px solid #f0e9eb; }
        .watch-card-heading > div { display: flex; min-width: 0; align-items: center; gap: 10px; }
        .watch-card-icon { display: grid; width: 37px; height: 37px; flex: 0 0 37px; place-items: center; border-radius: 11px; background: #f7eaed; color: #841a33; font-size: 15px; }
        .watch-card-heading h3 { margin: 0; color: #39212a; font-size: 12px; }
        .watch-card-heading p { margin-top: 3px; color: #8b8084; font-size: 9px; }
        .watch-status { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 6px; padding: 6px 9px; border-radius: 999px; background: #f6f2f3; color: #796c71; font-size: 8px; font-weight: 700; }
        .watch-status > i { width: 6px; height: 6px; border-radius: 50%; background: #9e9297; }
        .watch-status.active { background: #f7ecee; color: #75172f; }
        .watch-status.active > i { background: #a12240; box-shadow: 0 0 0 3px rgba(161,34,64,.1); }
        .watch-status.connected { background: #edf6f0; color: #326348; }
        .watch-status.connected > i { background: #43875f; }
        .watch-reading { display: grid; grid-template-columns: 54px auto 1fr; align-items: center; gap: 13px; padding: 22px 2px 18px; }
        .watch-heart { display: grid; width: 50px; height: 50px; place-items: center; border-radius: 15px; background: linear-gradient(145deg,#851a34,#b64058); color: #fff; font-size: 21px; box-shadow: 0 7px 16px rgba(112,16,40,.16); }
        .watch-heart.beating { animation: watchBeat .72s ease-in-out infinite; }
        @keyframes watchBeat { 0%,100% { transform: scale(1); } 20% { transform: scale(1.11); } 38% { transform: scale(1); } 55% { transform: scale(1.07); } }
        .watch-bpm { display: flex; align-items: baseline; gap: 6px; }
        .watch-bpm strong { color: #351b24; font-size: 48px; font-variant-numeric: tabular-nums; letter-spacing: -.07em; line-height: 1; }
        .watch-bpm span { color: #84777c; font-size: 10px; font-weight: 800; letter-spacing: .08em; }
        .watch-reading > p { justify-self: end; max-width: 150px; color: #73676c; font-size: 9px; line-height: 1.5; text-align: right; }
        .watch-measurements { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 8px; margin: -7px 0 14px; }
        .watch-measurement { display: grid; min-width: 0; gap: 4px; padding: 10px; border: 1px solid #f0e9eb; border-radius: 10px; background: #fdfafb; }
        .watch-measurement > span { color: #74656b; font-size: 9px; font-weight: 700; }
        .watch-measurement > span i { margin-right: 4px; color: #841a33; }
        .watch-measurement > strong { overflow-wrap: anywhere; color: #351b24; font-size: 16px; line-height: 1.25; }
        .watch-measurement > small { color: #8b8084; font-size: 8px; line-height: 1.35; }
        .watch-ecg-trace { height: 22px; margin-top: 2px; overflow: hidden; border-radius: 4px; background: repeating-linear-gradient(90deg,transparent 0 11px,rgba(123,23,47,.08) 11px 12px),repeating-linear-gradient(0deg,transparent 0 10px,rgba(123,23,47,.08) 10px 11px); }
        .watch-ecg-trace svg { display: block; width: 100%; height: 100%; }
        .watch-ecg-trace path { fill: none; stroke: #8c1d38; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .watch-chart-wrap { padding: 12px; border: 1px solid #f0e9eb; border-radius: 12px; background: linear-gradient(180deg,#fff 0%,#fdf9fa 100%); }
        .watch-chart-label { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
        .watch-chart-label strong { color: #4d3039; font-size: 9px; }
        .watch-chart-label span { color: #9a8e93; font-size: 8px; }
        #watchChart { display: block; width: 100%; height: 104px; }
        .watch-controls { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 13px; }
        .watch-button { display: inline-flex; min-height: 37px; align-items: center; justify-content: center; gap: 7px; padding: 8px 11px; border: 1px solid transparent; border-radius: 9px; font-family: inherit; font-size: 9px; font-weight: 700; cursor: pointer; transition: background .18s ease,border-color .18s ease,transform .18s ease; }
        .watch-button:hover:not(:disabled) { transform: translateY(-1px); }
        .watch-button:focus-visible, .watch-scenario-list button:focus-visible { outline: 2px solid #8a1d37; outline-offset: 2px; }
        .watch-button:disabled { cursor: not-allowed; opacity: .48; }
        .watch-button-primary { background: linear-gradient(120deg,#76142d,#941e3a); color: #fff; box-shadow: 0 4px 12px rgba(118,20,45,.16); }
        .watch-button-secondary { border-color: #eadfe2; background: #f8f4f5; color: #633746; }
        .watch-button-outline { border-color: #ead2d8; background: #fff; color: #7d1831; }
        .watch-connection-modal { max-width: 460px; }
        .watch-connection-description { margin: 0 0 16px; color: #71656a; font-size: 12px; line-height: 1.6; }
        .watch-demo-device { display: flex; align-items: center; gap: 12px; padding: 13px; border: 1px solid #eadfe2; border-radius: 12px; background: #fcf8f9; color: #78152c; }
        .watch-demo-device-icon { display: grid; width: 40px; height: 40px; flex: 0 0 40px; place-items: center; border-radius: 11px; background: #f3e5e9; font-size: 16px; }
        .watch-demo-device > span:nth-child(2) { min-width: 0; flex: 1; }
        .watch-demo-device strong, .watch-demo-device small { display: block; }
        .watch-demo-device strong { color: #39212a; font-size: 12px; }
        .watch-demo-device small { margin-top: 4px; color: #81767a; font-size: 10px; }
        .watch-connection-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
        body.dark-mode .watch-connection-modal { border: 1px solid #39323a; background: #262229; }
        body.dark-mode .watch-connection-modal .modal-header { border-color: #39323a; }
        body.dark-mode .watch-connection-modal .modal-header h2, body.dark-mode .watch-demo-device strong { color: #f3eef0; }
        body.dark-mode .watch-connection-description, body.dark-mode .watch-demo-device small { color: #c2b8be; }
        body.dark-mode .watch-demo-device { border-color: #39323a; background: #302b33; }
        .watch-side-column { display: grid; gap: 14px; }
        .watch-scenarios h3, .watch-project-note h3 { margin: 6px 0; color: #39212a; font-size: 13px; }
        .watch-scenarios > p, .watch-project-note > p { margin-bottom: 12px; color: #81767a; font-size: 9px; line-height: 1.6; }
        .watch-scenario-list { display: grid; gap: 7px; }
        .watch-scenario-list button { display: grid; grid-template-columns: 30px minmax(0,1fr) 12px; align-items: center; gap: 8px; padding: 9px; border: 1px solid #f0e9eb; border-radius: 10px; background: #fff; color: #75172f; text-align: left; cursor: pointer; transition: border-color .18s ease,background .18s ease; }
        .watch-scenario-list button:hover { border-color: #dfbfc7; background: #fcf7f8; }
        .watch-scenario-list button > i:first-child { display: grid; width: 29px; height: 29px; place-items: center; border-radius: 9px; background: #f7edef; font-size: 12px; }
        .watch-scenario-list button > i:last-child { color: #a58c93; font-size: 9px; }
        .watch-scenario-list strong, .watch-scenario-list small { display: block; }
        .watch-scenario-list strong { color: #483039; font-size: 9px; }
        .watch-scenario-list small { margin-top: 3px; color: #91858a; font-size: 8px; }
        .watch-project-note { background: linear-gradient(145deg,#4c0b1d,#74182f); color: #fff; }
        .watch-project-icon { display: grid; width: 33px; height: 33px; place-items: center; border: 1px solid rgba(255,255,255,.18); border-radius: 10px; background: rgba(255,255,255,.1); color: #f4d5dc; }
        .watch-project-note h3 { margin-top: 10px; color: #fff; }
        .watch-project-note > p { color: rgba(255,255,255,.76); }
        .watch-project-note > span:last-child { display: flex; align-items: center; gap: 6px; padding-top: 9px; border-top: 1px solid rgba(255,255,255,.17); color: #f4d5dc; font-size: 8px; font-weight: 700; }
        body.dark-mode .watch-hero, body.dark-mode .watch-clock-card, body.dark-mode .watch-monitor-card, body.dark-mode .watch-scenarios { border-color: #39323a; background: #262229; }
        body.dark-mode .watch-hero h2, body.dark-mode .watch-card-heading h3, body.dark-mode .watch-scenarios h3, body.dark-mode .watch-scenario-list strong, body.dark-mode .watch-bpm strong { color: #f3eef0; }
        body.dark-mode .watch-hero p, body.dark-mode .watch-card-heading p, body.dark-mode .watch-scenarios > p, body.dark-mode .watch-reading > p { color: #c2b8be; }
        body.dark-mode .watch-clock-card time { color: #f2cbd4; }
        body.dark-mode .watch-chart-wrap { border-color: #39323a; background: #302b33; }
        body.dark-mode .watch-scenario-list button { border-color: #39323a; background: #302b33; }
        body.dark-mode .watch-scenario-list button:hover { border-color: #764352; background: #3a2b32; }
        body.dark-mode .watch-scenario-list small, body.dark-mode .watch-chart-label span { color: #c2b8be; }
        body.dark-mode .watch-demo-note { border-color: #583641; background: #30232a; color: #e2cbd1; }
        body.dark-mode .watch-demo-note strong { color: #f2cbd4; }
        @media (max-width: 760px) {
            .watch-hero { grid-template-columns: minmax(0,1fr); padding: 18px; }
            .watch-clock-card { justify-items: start; grid-template-columns: auto 1fr; align-items: center; gap: 4px 12px; }
            .watch-clock-card > span { grid-column: 1/-1; }
            .watch-clock-card time { font-size: 24px; }
            .watch-layout { grid-template-columns: minmax(0,1fr); }
        }
        @media (max-width: 600px) {
            .watch-measurements { gap: 6px; }
            .watch-measurement { padding: 8px 7px; }
            .watch-measurement > strong { font-size: 14px; }
            .watch-reading { grid-template-columns: 45px auto 1fr; gap: 9px; }
            .watch-heart { width: 42px; height: 42px; font-size: 18px; }
            .watch-bpm strong { font-size: 42px; }
            .watch-controls > button { flex: 1 1 145px; }
        }
        .faq-list { display: grid; gap: 8px; }
        .faq-list details { padding: 11px 13px; border: 1px solid #eee8ea; border-radius: 10px; background: #fff; }
        .faq-list summary { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: #35222a; font-size: 11px; font-weight: 600; cursor: pointer; list-style: none; }
        .faq-list summary::-webkit-details-marker { display: none; }
        .faq-list summary::after { color: #78152c; content: "+"; font-size: 16px; }
        .faq-list details[open] summary::after { content: "−"; }
        .faq-list details p { margin: 10px 0 0; color: #777078; font-size: 10px; line-height: 1.6; }
        .support-emergency { padding: 13px 15px; border-left: 3px solid #a6223a; border-radius: 8px; background: #fff5f6; color: #6b192c; font-size: 10px; line-height: 1.6; }
        body.dark-mode .info-feature, body.dark-mode .support-contact, body.dark-mode .faq-list details { border-color: #39323a; background: #262229; }
        body.dark-mode .support-contact-icon { border-color: #553740; background: #38272e; color: #f2cbd4; }
        body.dark-mode .support-contact-copy h3, body.dark-mode .faq-list summary { color: #f3eef0; }
        body.dark-mode .info-feature p, body.dark-mode .support-contact-copy p, body.dark-mode .faq-list details p { color: #c2b8be; }
        body.dark-mode .support-contact-action { border-color: #59414a; background: #302b33; color: #f2cbd4; }
        body.dark-mode .support-contact-action:hover { border-color: #a22b48; background: #80172f; color: #fff; }
        body.dark-mode .support-emergency { border-color: #a64459; background: #39252d; color: #f3dce2; }
        .story-points {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .story-points span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f9f1f3;
            border: 1px solid rgba(133,30,50,0.08);
            border-radius: 999px;
            color: #541728;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .story-body {
            color: #374151;
            line-height: 1.8;
            font-size: 0.98rem;
        }
        .story-body p {
            margin: 0 0 18px;
        }
        .story-body h3 {
            font-size: 1.15rem;
            margin: 20px 0 10px;
            color: #111827;
        }
        @media (max-width: 880px) {
            .scroll-story { grid-template-columns: 1fr; }
            .story-image img { min-height: 220px; }
        }
        .chat-sidebar { width: 360px; background: #f4f1f1; border-left: 1px solid #efdce0; display: flex; flex-direction: column; box-shadow: -2px 0 18px rgba(0,0,0,0.04); }
        .chat-header { padding: 16px 16px 12px; border-bottom: 1px solid #efdce0; background: #f4f1f1; color: #421f2d; }
        .chat-header h3 { font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .chat-header p { font-size: 12px; opacity: 0.9; margin-top: 5px; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 18px 14px; display: flex; flex-direction: column; gap: 15px; }
        .message { display: flex; gap: 12px; max-width: 94%; }
        .message.user { align-self: flex-end; flex-direction: row-reverse; }
        .message-avatar { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
        .message.user .message-avatar { background: #851e32; color: white; }
        .message.bot .message-avatar { background: #6f1d32; color: white; }
        .message-bubble { background: #f1f5f9; padding: 12px 14px; border-radius: 16px; font-size: 13px; line-height: 1.4; color: #1e2a3a; box-shadow: 0 6px 16px rgba(15,23,42,0.02); }
        .message.user .message-bubble { background: #eae5e6; color: #3d1824; }
        .chat-input-area { padding: 14px 14px 18px; border-top: 1px solid #e9dfe2; display: flex; gap: 10px; background: #f4f1f1; align-items: center; }
        .chat-input { flex: 1; padding: 12px 14px; border: 1px solid #e5d7db; border-radius: 999px; outline: none; font-family: inherit; background: rgba(255,255,255,0.8); transition: all 0.2s ease; }
        .chat-input:focus { border-color: #851e32; box-shadow: 0 0 0 3px rgba(133,30,50,0.08); }
        .chat-actions { display: flex; align-items: center; gap: 8px; width: 100%; }
        .chat-attach { width: 42px; height: 42px; background: rgba(125,22,40,0.08); border: 1px solid rgba(125,22,40,0.18); border-radius: 50%; color: #7d1628; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease; }
        .chat-attach:hover { background: rgba(125,22,40,0.12); transform: translateY(-2px); box-shadow: 0 8px 18px rgba(125,22,40,0.14); }
        .chat-send { width: 42px; height: 42px; background: #7d1628; border: none; border-radius: 50%; color: white; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease; }
        .chat-send:hover { background: #5d1022; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(125,22,40,0.18); }
        .chat-file-preview { display: none; margin-top: 8px; padding: 8px 10px; border-radius: 10px; background: #fcecef; color: #7d1628; font-size: 12px; border: 1px solid rgba(125,22,40,0.12); }
        .chat-file-preview.visible { display: block; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .modal-content { background: white; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 25px; border-bottom: 2px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 22px; font-weight: 700; color: #1e2a3a; margin: 0; display: flex; align-items: center; gap: 10px; }
        .modal-close { background: none; border: none; font-size: 28px; color: #999; cursor: pointer; transition: color 0.3s; }
        .modal-close:hover { color: #851e32; }
        .modal-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e2a3a; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { font-family: inherit; border: 1px solid #ddd; border-radius: 8px; transition: border-color 0.3s; width: 100%; padding: 12px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #851e32; box-shadow: 0 0 0 3px rgba(133,30,50,0.1); }
        .contact-btn { background: #851e32; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; }
        .contact-btn:hover { background: #5a1e2c; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(133,30,50,0.3); }
        .contact-btn:active { transform: translateY(0); }
        @media (max-width: 1000px) { .chat-sidebar { width: 300px; } }
        @media (max-width: 800px) { .chat-sidebar { display: none; } }

        .app-container { height: 100vh; height: 100dvh; min-height: 0; overflow: hidden; background: #f4f3f3; }
        .sidebar { flex: 0 0 clamp(220px, 18.7vw, 270px); width: clamp(220px, 18.7vw, 270px); background: linear-gradient(180deg, #261813 0%, #45121b 50%, #741a2b 100%); box-shadow: none; }
        .logo-area { padding: 22px 24px 19px; border: 0; border-bottom: 1px solid rgba(255,255,255,.15); }
        .logo { gap: 10px; }
        .logo-icon { width: 50px; height: 50px; flex: 0 0 50px; background: none; border-radius: 0; }
        .logo-icon svg { display: block; width: 100%; height: 100%; }
        .logo-text h2 { font-size: 21px; font-weight: 600; letter-spacing: -0.04em; }
        .logo-text p { font-size: 8px; line-height: 1.55; letter-spacing: 0.04em; }
        .nav-menu { flex: 0 0 auto; padding: 5px 23px 0; }
        .nav-item { position: relative; min-height: 46px; padding: 12px 17px; margin-bottom: 8px; gap: 16px; border-radius: 11px; color: rgba(255,255,255,.78); }
        .nav-item.active { background: linear-gradient(90deg, #74102a, #671026); box-shadow: none; color: #fff; }
        .nav-item.active::before { position: absolute; inset: 0 auto 0 0; width: 3px; border-radius: 3px; background: #ebd34d; content: ""; }
        .nav-item i { width: 22px; font-size: 17px; }
        .nav-item span { font-size: 13px; }
        .sidebar-wave { position: absolute; right: 0; bottom: 28%; left: 0; height: 130px; opacity: .27; pointer-events: none; }
        .sidebar-wave svg { display: block; width: 100%; height: 100%; }
        .sidebar-wave .ecg-line { fill: none; stroke: #c33b55; stroke-width: 5; stroke-linecap: round; stroke-linejoin: round; }
        .workspace { display: flex; flex: 1 1 auto; flex-direction: column; min-width: 0; min-height: 0; }
        .main-header { flex: 0 0 65px; min-height: 65px; padding: 0 22px; gap: 24px; background: rgba(255,255,255,.78); border-bottom: 1px solid #e8e5e5; }
        .header-actions { gap: 14px; margin-left: auto; }
        .header-control { position: relative; display: flex; }
        .header-icon { width: 37px; height: 37px; border: 0; background: #f4f3f4; color: #282833; cursor: pointer; }
        .header-icon:focus-visible, .user-mini:focus-visible { outline: 3px solid rgba(126,20,43,.28); outline-offset: 2px; }
        .header-icon.notification { position: relative; }
        .header-icon.notification::after { position: absolute; top: 5px; right: 5px; width: 8px; height: 8px; border: 1px solid white; border-radius: 50%; background: #b81931; content: ""; }
        .header-icon.notification.read::after { display: none; }
        .user-mini { min-width: 155px; max-width: min(34vw, 310px); padding: 3px 8px; border: 0; background: transparent; color: #4d2a33; font-family: inherit; text-align: left; cursor: pointer; }
        .profile-button-copy > span:first-child { display: block; max-width: min(26vw, 245px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mini-avatar { width: 34px; height: 34px; background: #720d25; }
        .header-popover { position: absolute; z-index: 20; top: calc(100% + 12px); right: 0; display: none; width: 320px; overflow: hidden; border: 1px solid #eee8ea; border-radius: 15px; background: #fff; box-shadow: 0 18px 44px rgba(48,8,20,.17); color: #30232a; }
        .header-popover.open { display: block; animation: popoverIn .16s ease-out; }
        @keyframes popoverIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        .popover-heading { display: flex; align-items: center; justify-content: space-between; padding: 15px 16px 12px; border-bottom: 1px solid #f0ebed; }
        .popover-heading h3 { margin: 0; color: #451727; font-size: 14px; }
        .popover-action { padding: 5px 0; border: 0; background: none; color: #7c1830; font-family: inherit; font-size: 11px; cursor: pointer; }
        .notification-list { padding: 4px 8px 8px; }
        .notification-item { display: flex; gap: 11px; padding: 12px 9px; border-bottom: 1px solid #f4f0f1; }
        .notification-item:last-child { border-bottom: 0; }
        .notification-mark { display: flex; width: 32px; height: 32px; flex: 0 0 32px; align-items: center; justify-content: center; border-radius: 10px; background: #f7ecee; color: #79172e; }
        .notification-item strong { display: block; margin-bottom: 3px; color: #35222a; font-size: 11px; }
        .notification-item p { margin: 0; color: #777078; font-size: 10px; line-height: 1.45; }
        .notification-time { display: block; margin-top: 5px; color: #a09aa0; font-size: 9px; }
        .notification-empty { padding: 14px 9px; color: #817a80; font-size: 11px; line-height: 1.5; }
        .profile-popover { width: 250px; padding: 8px; }
        .profile-summary { display: flex; align-items: center; gap: 10px; padding: 10px 8px 13px; border-bottom: 1px solid #f0ebed; }
        .profile-summary .mini-avatar { width: 40px; height: 40px; flex: 0 0 40px; }
        .profile-summary strong { display: block; color: #38212a; font-size: 12px; }
        .profile-summary span { display: block; margin-top: 3px; color: #817a80; font-size: 10px; }
        .profile-menu-item { display: flex; width: 100%; align-items: center; gap: 10px; padding: 10px 9px; border: 0; border-radius: 8px; background: transparent; color: #51464c; font-family: inherit; font-size: 11px; text-align: left; text-decoration: none; cursor: pointer; }
        .profile-menu-item:hover { background: #f8f2f4; color: #75152a; }
        .profile-menu-item i { width: 16px; color: #861b34; text-align: center; }
        .profile-menu-item.logout { color: #a62d3c; }
        .dashboard-body { display: flex; flex: 1 1 auto; min-width: 0; min-height: 0; }
        .content-area { min-width: 0; padding: 14px 17px 28px 22px; scrollbar-color: #d6ced0 transparent; scrollbar-width: none; }
        .content-area::-webkit-scrollbar { width: 0; }
        .monitoring-shell { display: flex; flex-direction: column; gap: 16px; max-width: 1100px; min-height: 100%; }
        .welcome-card.dashboard-welcome { position: relative; display: flex; flex: 0 0 144px; align-items: center; justify-content: space-between; min-height: 144px; overflow: hidden; margin: 0; padding: 20px 26px; border-radius: 14px; background: linear-gradient(110deg, #5b061b 0%, #450315 54%, #32000f 100%); box-shadow: none; }
        .dashboard-welcome::before { position: absolute; inset: 0; z-index: 1; background: linear-gradient(90deg,#4b0618 0%,rgba(75,6,24,.98) 34%,rgba(75,6,24,.52) 66%,rgba(47,2,14,.06) 100%); content: ""; pointer-events: none; }
        .dashboard-welcome::after { position: absolute; top: -150px; right: 15%; width: 320px; height: 320px; border: 1px solid rgba(255,255,255,.12); border-radius: 50%; box-shadow: 0 0 0 35px rgba(255,255,255,.035),0 0 0 70px rgba(255,255,255,.025); content: ""; pointer-events: none; }
        .welcome-copy { position: relative; z-index: 3; max-width: 440px; }
        .welcome-title { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .welcome-title > span { width: 28px; height: 3px; background: #e5d34d; }
        .welcome-title h2 { margin: 0; font-size: 28px; font-weight: 650; letter-spacing: -.035em; text-shadow: 0 2px 16px rgba(18,0,6,.24); }
        .welcome-copy p { max-width: 410px; margin: 0; color: rgba(255,255,255,.9); font-size: 12px; line-height: 1.7; text-wrap: pretty; }
        .welcome-art { position: absolute; z-index: 2; inset: 0 0 0 auto; width: 57%; height: 100%; background: url("assets/dashboard-banner-art.png?v=20261106-11") left center / auto 100% no-repeat; }
        .welcome-art::after { position: absolute; top: 54%; right: 0; width: 180px; height: 78px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 78' preserveAspectRatio='none'%3E%3Cpath d='M0 42h14c7 0 9-1 12-7l5-12 7 20 7-10 7 10 8-40 9 53 8-24 9 3 8-13 8 15 7-7h6c7 0 9-1 12-7l5-12 7 20 7-10 7 10 8-40 9 53 8-24 9 3 8-13 8 15 7-7h12' fill='none' stroke='%23f04461' stroke-opacity='.2' stroke-width='9' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M0 42h14c7 0 9-1 12-7l5-12 7 20 7-10 7 10 8-40 9 53 8-24 9 3 8-13 8 15 7-7h6c7 0 9-1 12-7l5-12 7 20 7-10 7 10 8-40 9 53 8-24 9 3 8-13 8 15 7-7h12' fill='none' stroke='%23ff8396' stroke-opacity='.6' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/100% 100% no-repeat; content: ""; transform: translateY(-50%); }
        .stats-grid.dashboard-stats { height: 129px; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin: 0 0 5px; }
        .stat-card.dashboard-stat { position: relative; display: block; width: 100%; min-width: 0; min-height: 129px; padding: 13px 15px 12px; overflow: hidden; border: 1px solid #eee9ea; border-radius: 14px; background: rgba(255,255,255,.88); box-shadow: 0 5px 15px rgba(45,18,25,.035); color: inherit; font: inherit; text-align: left; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .stat-card.dashboard-stat:hover { transform: translateY(-2px); border-color: #e4c4cc; box-shadow: 0 10px 24px rgba(100,25,42,.12); }
        .stat-card.dashboard-stat:focus-visible { outline: 3px solid rgba(139,28,55,.3); outline-offset: 3px; }
        .stat-card.dashboard-stat .stat-top { display: flex; width: 100%; }
        .stat-card.dashboard-stat::after { position: absolute; right: 0; bottom: 0; left: 0; height: 2px; background: #75152a; content: ""; }
        .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 9px; }
        .dashboard-stat .stat-icon { width: 41px; height: 41px; margin: 0; border-radius: 50%; background: #f4e9eb; color: #701126; font-size: 17px; }
        .stat-arrow { display: grid; width: 28px; height: 28px; place-items: center; border-radius: 50%; background: #fae3e3; color: #7c1830; font-size: 10px; }
        .stat-card.dashboard-stat > strong { display: block; color: #391620; font-size: 24px; line-height: 1.05; }
        .stat-card.dashboard-stat > span:last-child { color: #65636a; font-size: 11px; }
        .info-card.dashboard-panel { margin: 0; padding: 12px 18px 8px; border: 1px solid #efebec; border-radius: 14px; background: rgba(255,255,255,.88); box-shadow: 0 5px 18px rgba(45,18,25,.035); }
        .info-card.records-panel { flex: 0 0 auto; min-height: 193px; margin-bottom: -1px; }
        .info-card.appointments-panel { flex: 0 0 auto; min-height: 115px; }
        .panel-heading { display: flex; min-height: 37px; align-items: center; justify-content: space-between; border-bottom: 1px solid #efebec; }
        .panel-heading h3 { display: flex; align-items: center; gap: 9px; margin: 0; color: #421b25; font-size: 15px; font-weight: 700; }
        .panel-heading h3 > span { display: flex; width: 29px; height: 29px; align-items: center; justify-content: center; border-radius: 50%; background: #f4e9eb; color: #71152b; font-size: 14px; }
        .view-all { display: inline-flex; min-height: 34px; align-items: center; gap: 7px; padding: 0 13px; border: 0; border-radius: 999px; background: #fae3e3; color: #6c1a2b; font-family: inherit; font-size: 11px; font-weight: 700; cursor: pointer; }
        .view-all i { margin-left: 6px; }
        .record-row { display: grid; min-height: 34px; grid-template-columns: 1.5fr 1fr 58px; align-items: center; gap: 12px; border-bottom: 1px solid #f0eded; color: #5e5e66; font-size: 11px; }
        .records-demo-note { margin: 5px 0 2px; color: #817a80; font-size: 9px; line-height: 1.4; }
        .record-row:last-child { border-bottom: 0; }
        .record-name { display: flex; align-items: center; gap: 12px; }
        .record-name i { display: flex; width: 28px; height: 28px; align-items: center; justify-content: center; border-radius: 9px; background: #f5eeee; color: #77172d; font-size: 13px; }
        .record-row > strong { color: #34343d; font-size: 11px; font-weight: 600; }
        .record-status { padding: 5px 8px; border-radius: 999px; background: #eef3eb; color: #55724c; text-align: center; font-size: 10px; }
        .record-row { grid-template-columns: minmax(0,1.45fr) minmax(90px,.8fr) minmax(74px,auto) auto; }
        .record-edit-button { display: inline-grid; width: 28px; height: 28px; place-items: center; border: 1px solid transparent; border-radius: 7px; background: transparent; color: #7c1830; cursor: pointer; }
        .record-edit-button:hover { border-color: #ead4da; background: #f8eff1; }
        .record-edit-button:focus-visible { outline: 2px solid #891b34; outline-offset: 2px; }
        .record-note { grid-column: 1/-1; padding: 0 0 5px 40px; color: #817a80; font-size: 9px; line-height: 1.4; overflow-wrap: anywhere; }
        .watch-record-row { grid-template-columns: minmax(0,1.45fr) minmax(90px,.8fr) minmax(100px,auto); }
        .health-record-modal-card { width: min(430px,100%); }
        .health-history-modal-card { width: min(620px,100%); }
        .health-history-modal-card .appointment-modal-heading p { margin: 5px 0 0; color: #817a80; font-size: 11px; }
        .health-history-content { display: grid; max-height: min(68dvh,560px); gap: 10px; overflow-y: auto; }
        .health-history-content h3 { margin: 5px 0 0; color: #421b25; font-size: 13px; }
        .health-history-current { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 8px; }
        .health-history-current-item, .health-history-change { display: grid; gap: 5px; padding: 11px; border: 1px solid #f0dfe3; border-radius: 10px; background: #fffafa; color: #49353c; font-size: 11px; }
        .health-history-current-item > strong, .health-history-change > strong { color: #4a1423; }
        .health-history-current-item > span { font-size: 13px; font-weight: 700; }
        .health-history-current-item small, .health-history-change small, .health-history-change time { color: #817a80; font-size: 9px; }
        .health-history-changes { display: grid; gap: 7px; }
        .health-history-change { grid-template-columns: minmax(0,1fr) auto; align-items: center; }
        .health-history-change time { text-align: right; }
        .health-history-change p { grid-column: 1/-1; margin: 0; color: #5b313d; font-weight: 600; }
        .health-history-change small { grid-column: 1/-1; }
        .health-history-empty, .health-history-loading, .health-history-error, .health-record-error { margin: 5px 0; font-size: 11px; line-height: 1.5; }
        .health-history-loading, .health-history-empty { color: #817a80; }
        .health-history-error, .health-record-error { color: #9c1835; }
        .health-record-error { padding: 5px 0; }
        .dashboard-insight-card { width: min(520px,100%); }
        .dashboard-insight-card .appointment-modal-heading { justify-content: flex-start; }
        .dashboard-insight-heading-icon { display: grid; width: 40px; height: 40px; flex: 0 0 40px; place-items: center; border-radius: 12px; background: #f7e7ea; color: #871b36; font-size: 16px; }
        .dashboard-insight-heading-copy { min-width: 0; flex: 1; }
        .dashboard-insight-heading-copy h2 { margin: 0; color: #451727; font-size: 17px; }
        .dashboard-insight-heading-copy p { margin: 4px 0 0; color: #817a80; font-size: 10px; line-height: 1.45; }
        .dashboard-insight-content { display: grid; gap: 10px; color: #51434a; font-size: 12px; line-height: 1.55; }
        .dashboard-insight-content > p { margin: 0; }
        .dashboard-insight-reading { display: flex; align-items: baseline; gap: 8px; padding: 14px; border: 1px solid #f0dfe3; border-radius: 12px; background: linear-gradient(120deg,#fff8f7,#f9eaed); }
        .dashboard-insight-reading strong { color: #65142a; font-size: 27px; line-height: 1; }
        .dashboard-insight-reading span { color: #77636a; font-size: 11px; }
        .dashboard-insight-list { display: grid; max-height: min(38dvh,270px); gap: 7px; overflow-y: auto; }
        .dashboard-insight-appointment { display: grid; grid-template-columns: 44px minmax(0,1fr); align-items: center; gap: 10px; padding: 9px; border: 1px solid #f0e2e5; border-radius: 10px; background: #fffdfc; }
        .dashboard-insight-date { display: grid; min-height: 42px; align-content: center; justify-items: center; border-radius: 9px; background: #78152c; color: #fff; line-height: 1.1; }
        .dashboard-insight-date strong { font-size: 14px; }
        .dashboard-insight-date span { margin-top: 3px; font-size: 8px; text-transform: uppercase; }
        .dashboard-insight-appointment strong, .dashboard-insight-appointment small { display: block; overflow-wrap: anywhere; }
        .dashboard-insight-appointment strong { color: #45202b; font-size: 11px; }
        .dashboard-insight-appointment small { margin-top: 3px; color: #817a80; font-size: 10px; }
        .dashboard-insight-note { padding: 10px 11px; border-left: 3px solid #d69aa7; border-radius: 0 9px 9px 0; background: #fff4f5; color: #6f5960; font-size: 10px; }
        .dashboard-insight-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; margin-top: 15px; }
        .dashboard-insight-actions button { min-height: 36px; padding: 0 12px; border: 1px solid #ead4da; border-radius: 9px; background: #fff; color: #74152a; font: inherit; font-size: 11px; font-weight: 650; cursor: pointer; }
        .dashboard-insight-actions button.primary { border-color: #74152a; background: #74152a; color: #fff; }
        .dashboard-insight-actions button:hover { border-color: #8b1c37; background: #f8eff1; }
        .dashboard-insight-actions button.primary:hover { background: #5d1022; color: #fff; }
        .dashboard-insight-actions button:focus-visible { outline: 2px solid #891b34; outline-offset: 2px; }
        @media (max-width: 600px) {
            .dashboard-insight-card { padding: 16px; }
            .dashboard-insight-card .appointment-modal-heading { gap: 9px; }
            .dashboard-insight-heading-icon { width: 36px; height: 36px; flex-basis: 36px; }
            .dashboard-insight-heading-copy h2 { font-size: 15px; }
            .dashboard-insight-list { max-height: 34dvh; }
        }
        body.dark-mode .health-history-modal-card .appointment-modal-heading p,
        body.dark-mode .health-history-current-item small,
        body.dark-mode .health-history-change small,
        body.dark-mode .health-history-change time,
        body.dark-mode .health-history-loading,
        body.dark-mode .health-history-empty { color: #c2b8be; }
        body.dark-mode .health-history-content h3,
        body.dark-mode .health-history-current-item > strong,
        body.dark-mode .health-history-change > strong { color: #f8e9ed; }
        body.dark-mode .health-history-current-item,
        body.dark-mode .health-history-change { border-color: #4b2b36; background: #28161e; color: #f8edf0; }
        body.dark-mode .health-history-change p { color: #f2cbd4; }
        body.dark-mode .record-edit-button { color: #f2cbd4; }
        body.dark-mode .record-edit-button:hover { border-color: #59414a; background: #38272e; }
        body.dark-mode .record-note { color: #c2b8be; }
        body.dark-mode .records-demo-note { color: #c2b8be; }
        body.dark-mode .dashboard-insight-heading-icon { background: #422330; color: #f2cbd4; }
        body.dark-mode .dashboard-insight-heading-copy h2, body.dark-mode .dashboard-insight-appointment strong { color: #f8e9ed; }
        body.dark-mode .dashboard-insight-heading-copy p, body.dark-mode .dashboard-insight-appointment small { color: #c2b8be; }
        body.dark-mode .dashboard-insight-content { color: #e3d2d8; }
        body.dark-mode .dashboard-insight-reading, body.dark-mode .dashboard-insight-appointment { border-color: #4b2b36; background: #28161e; }
        body.dark-mode .dashboard-insight-reading strong { color: #f2cbd4; }
        body.dark-mode .dashboard-insight-reading span, body.dark-mode .dashboard-insight-note { color: #dfc7cf; }
        body.dark-mode .dashboard-insight-note { border-color: #9c5267; background: #38202a; }
        body.dark-mode .dashboard-insight-actions button { border-color: #583440; background: #302129; color: #f2cbd4; }
        body.dark-mode .dashboard-insight-actions button.primary { border-color: #9d3150; background: #791832; color: #fff; }
        .watch-record-row { grid-template-columns: 1.5fr 1fr 100px; }
        .record-status.watch-record-active { background: #eef3eb; color: #55724c; }
        body.dark-mode .watch-record-row .record-status { background: #26352b; color: #b8d4ad; }
        .appointment-list { display: flex; max-height: 220px; flex-direction: column; overflow-y: auto; }
        .appointment-row { display: grid; min-height: 47px; grid-template-columns: 46px minmax(0,1fr) auto; align-items: center; gap: 12px; }
        .appointment-row time { display: flex; width: 42px; height: 40px; flex-direction: column; align-items: center; justify-content: center; border-radius: 9px; background: #701027; color: white; }
        .appointment-row time strong { font-size: 15px; font-weight: 500; line-height: 1; }
        .appointment-row time span { margin-top: 3px; font-size: 8px; }
        .appointment-row > div { display: flex; min-width: 0; flex-direction: column; gap: 3px; }
        .appointment-row > div strong { overflow: hidden; color: #30303a; font-size: 11px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .appointment-row > div span { color: #77747c; font-size: 10px; }
        .appointment-row > div span i { margin-right: 3px; }
        .appointment-status { min-width: 75px; padding: 6px 9px; border-radius: 999px; text-align: center; font-size: 10px; }
        .appointment-status.confirmed { background: #f3e9eb; color: #74152a; }
        .appointment-status.scheduled { background: #f5f2ef; color: #806d63; }
        .appointment-add-button { display: inline-flex; min-height: 30px; align-items: center; justify-content: center; gap: 6px; padding: 0 10px; border: 1px solid #ead4da; border-radius: 8px; background: #fff; color: #78152c; font-family: inherit; font-size: 10px; font-weight: 700; cursor: pointer; }
        .appointment-add-button:hover { border-color: #8b1c37; background: #851b35; color: #fff; }
        .appointment-add-button:focus-visible, .appointment-action:focus-visible { outline: 2px solid #891b34; outline-offset: 2px; }
        .appointment-actions { display: flex; align-items: center; gap: 4px; }
        .appointment-action { display: inline-grid; width: 28px; height: 28px; place-items: center; border: 1px solid transparent; border-radius: 7px; background: transparent; color: #7c1830; cursor: pointer; }
        .appointment-action:hover { border-color: #ead4da; background: #f8eff1; }
        .appointment-action.delete { color: #a62d3c; }
        .appointment-empty { padding: 14px 2px 6px; color: #817a80; font-size: 10px; line-height: 1.5; }
        .appointment-notes { color: #817a80; font-size: 9px; line-height: 1.45; }
        body.dark-mode .appointment-add-button { border-color: #59414a; background: #302b33; color: #f2cbd4; }
        body.dark-mode .appointment-empty, body.dark-mode .appointment-notes, body.dark-mode .notification-empty { color: #c2b8be; }
        body.dark-mode .appointment-action { color: #f2cbd4; }
        body.dark-mode .appointment-action:hover { border-color: #59414a; background: #38272e; }
        .message-bubble .assistant-markdown { color: inherit; font-size: inherit; line-height: 1.7; }
        .assistant-markdown p { margin: 0 0 10px; }
        .assistant-markdown p:last-child { margin-bottom: 0; }
        .assistant-markdown ul, .assistant-markdown ol { display: grid; gap: 5px; margin: 8px 0 12px; padding-left: 22px; }
        .assistant-markdown li { padding-left: 2px; }
        .assistant-markdown h2, .assistant-markdown h3 { margin: 13px 0 7px; font-size: 1.05em; line-height: 1.4; }
        .assistant-markdown h2:first-child, .assistant-markdown h3:first-child { margin-top: 0; }
        .appointment-modal { position: fixed; z-index: 130; inset: 0; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(25,9,15,.54); }
        .appointment-modal.open { display: flex; }
        .appointment-modal-card { width: min(480px,100%); max-height: min(90dvh,720px); overflow: auto; padding: 20px; border: 1px solid #eee4e7; border-radius: 16px; background: #fff; box-shadow: 0 20px 60px rgba(20,0,8,.24); color: #38212a; }
        .appointment-modal-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .appointment-modal-heading h2 { margin: 0; font-size: 17px; }
        .appointment-modal-close { display: grid; width: 32px; height: 32px; place-items: center; border: 0; border-radius: 8px; background: #f7eff1; color: #74152a; cursor: pointer; }
        .appointment-form { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; }
        .appointment-form label { display: grid; gap: 5px; color: #655a60; font-size: 11px; font-weight: 600; }
        .appointment-form label.wide, .appointment-form .appointment-reminder-note, .appointment-form .appointment-form-actions { grid-column: 1/-1; }
        .appointment-form input, .appointment-form textarea { width: 100%; min-height: 38px; padding: 8px 10px; border: 1px solid #e7dfe1; border-radius: 8px; background: #fff; color: #30232a; font: inherit; font-size: 12px; }
        .appointment-form textarea { min-height: 72px; resize: vertical; }
        .appointment-reminder-note { margin: 0; color: #817a80; font-size: 10px; line-height: 1.5; }
        .appointment-form-actions { display: flex; justify-content: flex-end; gap: 8px; padding-top: 4px; }
        .appointment-form-actions button { min-height: 36px; padding: 0 13px; border: 1px solid #e7dfe1; border-radius: 8px; background: #fff; color: #655a60; font: inherit; font-size: 11px; font-weight: 600; cursor: pointer; }
        .appointment-form-actions button[type="submit"] { border-color: #74152a; background: #74152a; color: #fff; }
        body.dark-mode .appointment-modal-card { border-color: #39323a; background: #262229; color: #f3eef0; }
        body.dark-mode .appointment-form label, body.dark-mode .appointment-reminder-note { color: #c2b8be; }
        body.dark-mode .appointment-form input, body.dark-mode .appointment-form textarea { border-color: #49414a; background: #302b33; color: #fff; }
        body.dark-mode .appointment-form-actions button { border-color: #49414a; background: #302b33; color: #f3eef0; }
        .chat-sidebar { position: relative; display: flex; flex: 0 0 520px; width: 520px; min-width: 0; min-height: 0; margin: 14px 15px 36px 0; overflow: hidden; border: 1px solid #eeebec; border-radius: 15px; background: rgba(255,255,255,.96); box-shadow: 0 5px 18px rgba(45,18,25,.04); }
        .chat-navigation { display: flex; width: 155px; min-width: 155px; flex: 0 0 155px; flex-direction: column; gap: 5px; padding: 13px 9px 10px; border-right: 1px solid #eee7e9; background: #faf8f8; }
        @media (min-width: 901px) {
            body:not(.phone-presentation) .chat-sidebar:not(.chat-session-open) { flex-basis: 240px; width: 240px; }
            body:not(.phone-presentation) .chat-sidebar:not(.chat-session-open) .chat-navigation { width: 100%; min-width: 0; flex-basis: 100%; border-right: 0; }
            body:not(.phone-presentation) .chat-sidebar:not(.chat-session-open) .chat-conversation-panel { display: none; }
            body:not(.phone-presentation) .chat-sidebar .chat-nav-toggle { display: grid; }
            body:not(.phone-presentation) .chat-sidebar.chat-navigation-collapsed .chat-navigation { display: none; }
            body:not(.phone-presentation) .chat-sidebar.chat-navigation-open .chat-navigation { position: absolute; z-index: 5; inset: 0; display: flex; width: 100%; min-width: 0; flex-basis: 100%; border-right: 0; background: #fffafa; box-shadow: none; }
        }
        .chat-navigation-brand { display: flex; align-items: center; gap: 8px; padding: 1px 5px 12px; color: #381c26; }
        .chat-navigation-brand > span { display: grid; width: 26px; height: 26px; place-items: center; border-radius: 8px; background: #74152d; color: #fff; font-size: 12px; }
        .chat-navigation-brand strong { font-size: 14px; letter-spacing: -.02em; }
        .chat-navigation-action { display: flex; min-height: 34px; align-items: center; gap: 8px; padding: 0 8px; border: 1px solid transparent; border-radius: 8px; background: transparent; color: #51444a; font: inherit; font-size: 10px; text-align: left; cursor: pointer; }
        .chat-navigation-action i { width: 15px; color: #76172f; text-align: center; }
        .chat-navigation-action:hover, .chat-navigation-action.active { border-color: #ead9de; background: #f5ecee; color: #641329; }
        .chat-recent-heading { margin: 13px 5px 2px; color: #756970; font-size: 9px; font-weight: 750; }
        .chat-recent-list { display: grid; align-content: start; gap: 3px; min-height: 0; overflow-y: auto; }
        .chat-recent-item { display: flex; min-height: 32px; align-items: center; gap: 6px; padding: 5px 7px; border: 1px solid transparent; border-radius: 7px; background: transparent; color: #51444a; font: inherit; font-size: 9px; text-align: left; cursor: pointer; }
        .chat-recent-item span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-recent-item i { color: #8c6872; font-size: 9px; }
        .chat-recent-item:hover, .chat-recent-item.active { border-color: #ead9de; background: #f3e9ec; color: #5b1528; }
        .chat-recent-empty { padding: 4px 6px; color: #8c8085; font-size: 9px; line-height: 1.5; }
        .chat-navigation-note { margin: auto 5px 0; padding-top: 9px; border-top: 1px solid #eee7e9; color: #8b7e83; font-size: 8px; line-height: 1.45; }
        .chat-conversation-panel { display: flex; min-width: 0; min-height: 0; flex: 1 1 auto; flex-direction: column; }
        .chat-conversation-view { display: flex; min-height: 0; flex: 1 1 auto; flex-direction: column; }
        .chat-conversation-view[hidden] { display: none; }
        .chat-nav-toggle { display: none; width: 30px; height: 30px; flex: 0 0 30px; place-items: center; border: 1px solid #eadfe2; border-radius: 8px; background: #fff; color: #74152d; cursor: pointer; }
        .chat-header-identity { display: flex; min-width: 0; align-items: center; gap: 9px; }
        .chat-header-avatar { display: grid; width: 33px; height: 33px; flex: 0 0 33px; place-items: center; border-radius: 50%; background: #78132a; color: #fff; }
        .chat-header-copy { min-width: 0; flex: 1 1 auto; overflow: hidden; }
        #chatTitle, #chatSubtitle { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-images-view { display: flex; min-height: 0; flex: 1 1 auto; flex-direction: column; padding: 13px 12px; overflow-y: auto; }
        .chat-images-view[hidden] { display: none; }
        .chat-images-view > header { margin-bottom: 12px; }
        .chat-images-view h3 { margin: 0; color: #39212a; font-size: 14px; }
        .chat-images-view header p { margin: 4px 0 0; color: #81767a; font-size: 9px; line-height: 1.45; }
        .chat-image-library { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); align-content: start; gap: 8px; }
        .chat-image-card { display: grid; min-width: 0; gap: 5px; padding: 5px; border: 1px solid #eee5e7; border-radius: 9px; background: #fff; color: #55464c; font: inherit; text-align: left; cursor: pointer; }
        .chat-image-card:hover { border-color: #d9b6bf; background: #fcf7f8; }
        .chat-image-card img { display: block; width: 100%; height: 82px; object-fit: cover; border-radius: 6px; background: #f2eaec; }
        .chat-image-card strong, .chat-image-card span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-image-card strong { font-size: 8px; }
        .chat-image-card span { color: #8c8085; font-size: 7px; }
        .chat-image-empty { grid-column: 1/-1; padding: 12px 5px; color: #81767a; font-size: 10px; line-height: 1.5; }
        .message-attachment-image { display: block; width: min(100%,260px); max-height: 200px; margin: 8px 0 3px; border-radius: 9px; object-fit: contain; background: rgba(0,0,0,.04); }
        .message-image-caption { display: block; overflow: hidden; max-width: 220px; margin-top: 5px; font-size: 9px; opacity: .8; text-overflow: ellipsis; white-space: nowrap; }
        body.dark-mode .chat-navigation { border-color: #39323a; background: #211e24; }
        body.dark-mode .chat-navigation-brand, body.dark-mode .chat-images-view h3 { color: #f3eef0; }
        body.dark-mode .chat-navigation-action, body.dark-mode .chat-recent-item { color: #d7cbd0; }
        body.dark-mode .chat-navigation-action:hover, body.dark-mode .chat-navigation-action.active, body.dark-mode .chat-recent-item:hover, body.dark-mode .chat-recent-item.active { border-color: #59414a; background: #38272e; color: #f2cbd4; }
        body.dark-mode .chat-recent-heading, body.dark-mode .chat-navigation-note, body.dark-mode .chat-recent-empty, body.dark-mode .chat-images-view header p { color: #c2b8be; }
        body.dark-mode .chat-navigation-note { border-color: #39323a; }
        body.dark-mode .chat-image-card { border-color: #39323a; background: #302b33; color: #f3eef0; }
        body.dark-mode .chat-image-card span, body.dark-mode .chat-image-empty { color: #c2b8be; }
        body.dark-mode .chat-nav-toggle { border-color: #49414a; background: #302b33; color: #f2cbd4; }
        .chat-sidebar .chat-header { gap: 6px; padding: 9px 10px; }
        .chat-sidebar .chat-header > div:first-child { flex: 1 1 auto; }
        .chat-sidebar .chat-messages { min-height: 0; }
        .chat-sidebar .chat-input-area { display: block; flex: 0 0 auto; }
        .mobile-chat-toggle { touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
        @media (max-width: 1150px) {
            .chat-sidebar { flex-basis: 300px; width: 300px; }
            .chat-navigation { display: none; }
            .chat-sidebar.chat-navigation-open .chat-navigation { position: absolute; z-index: 5; inset: 0; display: flex; width: 100%; min-width: 0; flex-basis: 100%; border-right: 0; background: #fffafa; box-shadow: none; }
            .chat-nav-toggle { display: grid; }
        }
        .chat-header { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; min-height: 64px; padding: 10px 12px; border-bottom: 1px solid #eeebec; background: transparent; }
        .chat-header > div:first-child { gap: 10px !important; }
        .chat-header > div:first-child > div:first-child { width: 36px !important; height: 36px !important; background: #78132a !important; color: white; }
        .chat-header > div:first-child > div:first-child i { font-size: 14px; }
        .chat-header > div:first-child > div:last-child > div:first-child { font-size: 13px !important; font-weight: 700 !important; }
        .chat-header > div:first-child > div:last-child > div:last-child { color: #77747c; font-size: 10px !important; }
        .chat-messages { position: relative; gap: 14px; padding: 16px 12px; }
        .chat-messages::after { position: absolute; right: 12px; bottom: 22px; left: 12px; height: 36px; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 240 36'%3E%3Cpath d='M0 23h94l8-2 5-11 8 22 10-31 9 29 8-17 5 10h93' fill='none' stroke='%23f2eeee' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/100% 100% no-repeat; content: ""; pointer-events: none; }
        .message { z-index: 1; max-width: 100%; }
        .message-avatar { width: 31px; height: 31px; background: #78132a; color: white; }
        .message-bubble { padding: 12px 13px; border-radius: 16px; background: #f3f1f1; font-size: 11px; line-height: 1.65; }
        .message.bot .message-bubble { margin-left: 0; }
        .chat-input-area { z-index: 1; padding: 12px; border-top: 1px solid #eeebec; background: transparent; }
        .chat-actions { gap: 8px; }
        .chat-attach { display: flex; flex: 0 0 40px; width: 40px; height: 40px; align-items: center; justify-content: center; background: #f5eff0; border: 1px solid #eee4e6; color: #75152a; }
        .chat-attach:hover { background: #efe1e4; color: #571023; }
        .chat-file-preview { align-items: center; justify-content: space-between; gap: 10px; }
        .chat-file-preview.visible { display: flex; }
        .chat-file-preview > span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-ai-disclosure { margin: 8px 3px 0; color: #7f7378; font-size: 9px; line-height: 1.45; }
        body.dark-mode .chat-ai-disclosure { color: #c2b8be; }
        .chat-input { min-width: 0; height: 40px; padding: 0 14px; border-color: #ece9ea; background: #fff; font-size: 10px; }
        .chat-input::placeholder { color: #96939a; }
        .chat-send { flex: 0 0 40px; width: 40px; height: 40px; }
        .chat-file-preview { margin-top: 8px; }
        .mobile-chat-toggle { position: fixed; z-index: 70; right: 22px; bottom: 22px; display: none; width: 48px; height: 48px; align-items: center; justify-content: center; border: 0; border-radius: 50%; background: #701027; box-shadow: 0 8px 22px rgba(58,6,21,.28); color: #fff; font-size: 17px; cursor: pointer; }
        .mobile-chat-toggle i { font-size: 19px; }
        .chat-mobile-close { display: none; width: 30px; height: 30px; align-items: center; justify-content: center; border: 0; border-radius: 50%; background: #f5eff0; color: #71152b; cursor: pointer; }
        body.font-large .welcome-card.dashboard-welcome { min-height: 174px; flex-basis: auto; }
        body.font-large .info-card.records-panel, body.font-large .info-card.appointments-panel { min-height: 0; flex-basis: auto; }
        body.font-large .record-row { min-height: 42px; grid-template-columns: minmax(0,1fr) auto auto; }
        body.font-large .appointment-row { min-height: 58px; grid-template-columns: 46px minmax(0,1fr) auto; }
        body.font-large .appointment-row > div { overflow-wrap: anywhere; }
        body.font-large .appointment-row > div strong { white-space: normal; }
        body.font-large .content-area :is(.exam-panel,.info-page-card,.support-page-card,.watch-monitor-card,.watch-scenarios,.watch-project-note,.blog-card,.article-prose,.article-aside-card) { min-width: 0; overflow-wrap: anywhere; }
        body.font-large .settings-modal-content { max-height: 92vh; max-height: 92dvh; }
        body.font-large .password-form label { min-width: 0; }
        body.font-large .password-guidance { overflow-wrap: anywhere; }
        @media (max-width: 900px) {
            body.font-large .content-area { padding-right: 12px; padding-left: 12px; }
            body.font-large .record-row:not(.watch-record-row) { grid-template-columns: minmax(0,1fr) auto auto; }
            body.font-large .record-status { grid-column: 1/-1; justify-self: end; }
            body.font-large .appointment-row { grid-template-columns: 42px minmax(0,1fr); gap: 8px; }
            body.font-large .appointment-row .appointment-actions { grid-column: 2; justify-content: flex-end; }
            body.font-large .stats-grid.dashboard-stats { height: auto; }
        }
        @media (max-width: 600px) {
            body.font-large .content-area { padding-right: 9px; padding-left: 9px; }
            body.font-large .welcome-card.dashboard-welcome { min-height: 184px; padding: 15px 12px; }
            body.font-large .welcome-copy { max-width: 88%; }
            body.font-large .record-row { gap: 5px; }
            body.font-large .record-row:not(.watch-record-row) { grid-template-columns: minmax(0,1fr) 40px; gap: 5px 8px; }
            body.font-large .record-row:not(.watch-record-row) .record-name { grid-column: 1/-1; }
            body.font-large .record-row:not(.watch-record-row) > .health-record-value { grid-column: 1; grid-row: 2; text-align: left; }
            body.font-large .record-edit-button { grid-column: 2; grid-row: 2; width: 40px; height: 40px; border-color: #ead4da; border-radius: 10px; background: #f8eff1; }
            body.font-large .info-card.dashboard-panel { padding-right: 10px; padding-left: 10px; }
            body.font-large .settings-modal-content .modal-body { padding: 12px; }
            body.font-large .settings-tab { padding: 9px 10px; }
            body.font-large .appointment-row { grid-template-columns: 38px minmax(0,1fr) auto; gap: 6px; }
            body.font-large .appointment-row .appointment-actions { grid-column: auto; justify-content: flex-end; gap: 1px; }
        }
        body.high-contrast { background: #e7e2e3; }
        body.high-contrast .main-header, body.high-contrast .chat-sidebar { background: #fff; }
        body.high-contrast .info-card.dashboard-panel, body.high-contrast .stat-card.dashboard-stat { background: #fff; border-color: #d4c9cc; }
        body.reduce-motion *, body.reduce-motion *::before, body.reduce-motion *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        body.dark-mode, body.dark-mode .app-container { background: #17151a; color: #f3eef0; }
        body.dark-mode .workspace, body.dark-mode .dashboard-body, body.dark-mode .content-area { background: #17151a; }
        body.dark-mode .main-header, body.dark-mode .chat-sidebar, body.dark-mode .chat-header, body.dark-mode .chat-input-area { background: #211e24; border-color: #39323a; color: #f3eef0; }
        body.dark-mode .content-area .dashboard-panel, body.dark-mode .content-area .dashboard-stat, body.dark-mode .content-area .info-card { background-color: #262229; border-color: #39323a; color: #f3eef0; }
        body.dark-mode .content-area .support-card, body.dark-mode .content-area .article-container, body.dark-mode .content-area .blog-post { background-color: #262229; border-color: #39323a; color: #f3eef0; }
        body.dark-mode .content-area h1, body.dark-mode .content-area h2, body.dark-mode .content-area h3, body.dark-mode .content-area strong, body.dark-mode .page-title { color: #f3eef0; }
        body.dark-mode .content-area p, body.dark-mode .content-area span, body.dark-mode .record-row, body.dark-mode .appointment-row > div span { color: #c2b8be; }
        body.dark-mode .content-area .blog-post:hover { background: #302b33; }
        body.dark-mode .content-area .info-card h3 { border-color: #39323a; }
        body.dark-mode .chat-messages .message-bubble { background: #302b33; color: #f3eef0; }
        body.dark-mode .chat-input { background: #302b33; border-color: #49414a; color: #fff; }
        body.dark-mode .header-icon { background: #302b33; color: #f3eef0; }
        body.dark-mode .user-mini { color: #f3eef0; }
        body.dark-mode .header-popover { background: #262229; border-color: #39323a; color: #f3eef0; }
        body.dark-mode .profile-menu-item { color: #f3eef0; }
        body.dark-mode .profile-menu-item:hover, body.dark-mode .notification-item:hover { background: #302b33; }
        body.dark-mode .profile-summary, body.dark-mode .popover-heading { border-color: #39323a; }
        body.dark-mode .setting-row, body.dark-mode .settings-tabs { border-color: #39323a; }
        body.dark-mode .setting-row strong, body.dark-mode .account-detail strong { color: #f3eef0; }
        body.dark-mode .setting-row span, body.dark-mode .settings-intro, body.dark-mode .account-detail span { color: #c2b8be; }
        body.dark-mode .setting-row select, body.dark-mode .account-form input { background: #302b33; border-color: #49414a; color: #fff; }
        body.dark-mode .account-detail { background: #302b33; }
        .settings-content { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
        .settings-intro { margin: 0 0 10px; color: #777078; font-size: 12px; line-height: 1.5; }
        .setting-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 13px 0; border-top: 1px solid #f0ebed; }
        .setting-row strong { display: block; color: #38212a; font-size: 12px; }
        .setting-row span { display: block; margin-top: 4px; color: #817a80; font-size: 10px; }
        .setting-row select { min-width: 135px; padding: 8px 10px; border: 1px solid #e7dfe1; border-radius: 8px; background: white; color: #3e3439; font-family: inherit; font-size: 11px; }
        .setting-toggle { position: relative; width: 42px; height: 24px; flex: 0 0 42px; padding: 0; border: 0; border-radius: 999px; background: #cbc4c7; cursor: pointer; transition: background .2s; }
        .setting-toggle::after { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: white; box-shadow: 0 1px 3px rgba(0,0,0,.18); content: ""; transition: transform .2s; }
        .setting-toggle[aria-checked="true"] { background: #78152c; }
        .setting-toggle[aria-checked="true"]::after { transform: translateX(18px); }
        .settings-footer { display: flex; justify-content: flex-end; gap: 9px; padding-top: 12px; border-top: 1px solid #f0ebed; }
        .settings-footer .contact-btn { padding: 9px 13px; font-size: 11px; }
        .settings-reset { border: 1px solid #e7dfe1; background: white; color: #655a60; }
        .account-details { display: grid; gap: 10px; }
        .account-detail { padding: 10px 12px; border-radius: 9px; background: #f8f5f6; }
        .account-detail span { display: block; margin-bottom: 4px; color: #817a80; font-size: 10px; }
        .account-detail strong { color: #38212a; font-size: 12px; }
        .settings-modal-content { width: min(680px, calc(100vw - 28px)); max-width: 680px; max-height: min(88vh, 760px); max-height: min(88dvh, 760px); display: flex; flex-direction: column; }
        .settings-modal-content .modal-body { overflow-y: auto; padding: 20px 24px 24px; }
        .settings-layout { display: flex; min-width: 0; flex-direction: column; gap: 12px; }
        .settings-tabs { display: flex; gap: 5px; overflow-x: auto; padding: 0 0 8px; border: 0; border-bottom: 1px solid #f0ebed; }
        .settings-tab { display: flex; width: auto; flex: 0 0 auto; align-items: center; gap: 9px; padding: 10px 12px; border: 0; border-radius: 8px; background: transparent; color: #71666c; font-family: inherit; font-size: 11px; font-weight: 600; text-align: left; cursor: pointer; }
        .settings-tab[aria-selected="true"] { background: #f7edef; color: #74152a; }
        .settings-tab i { width: 15px; text-align: center; }
        .settings-panels { min-width: 0; }
        .settings-panel { display: none; padding-top: 10px; }
        .settings-panel.active { display: block; }
        .settings-panel h3 { margin: 8px 0 3px; color: #38212a; font-size: 14px; }
        .settings-subtitle { margin: 0 0 10px; color: #817a80; font-size: 11px; line-height: 1.5; }
        .account-form { display: grid; gap: 10px; }
        .account-form.profile-form, .account-form.password-form { grid-template-columns: minmax(0,1fr); }
        .account-form .settings-message, .account-form .contact-btn { grid-column: 1/-1; }
        .account-form label { display: grid; gap: 5px; color: #655a60; font-size: 11px; font-weight: 600; }
        .account-form input { width: 100%; min-height: 38px; padding: 8px 10px; border: 1px solid #e7dfe1; border-radius: 8px; color: #30232a; font: inherit; }
        .password-input-wrap { display: flex; min-width: 0; }
        .password-input-wrap input { min-width: 0; border-radius: 8px 0 0 8px; }
        .password-visibility-toggle { display: grid; width: 42px; flex: 0 0 42px; place-items: center; border: 1px solid #e7dfe1; border-left: 0; border-radius: 0 8px 8px 0; background: #f8f5f6; color: #74152a; cursor: pointer; }
        .password-guidance { margin: 0; color: #817a80; font-size: 10px; line-height: 1.5; }
        .password-strength { display: flex; align-items: center; gap: 9px; }
        .password-strength-track { display: grid; flex: 1; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 4px; }
        .password-strength-track span { height: 4px; border-radius: 999px; background: #e8e2e4; }
        .password-strength-track span.active { background: #a62d3c; }
        .password-strength[data-strength="good"] .password-strength-track span.active { background: #c28a25; }
        .password-strength[data-strength="strong"] .password-strength-track span.active { background: #4d8052; }
        .password-strength-label { min-width: 58px; color: #817a80; font-size: 10px; text-align: right; }
        body.dark-mode .password-input-wrap input { border-color: #49414a; background: #302b33; color: #fff; }
        body.dark-mode .password-visibility-toggle { border-color: #49414a; background: #3a333d; color: #f2cbd4; }
        body.dark-mode .password-strength-track span { background: #49414a; }
        body.dark-mode .password-guidance, body.dark-mode .password-strength-label { color: #c2b8be; }
        .account-form .contact-btn { justify-self: start; padding: 9px 13px; font-size: 11px; }
        .account-form .contact-btn:disabled { cursor: not-allowed; opacity: .5; transform: none; box-shadow: none; }
        .settings-message { min-height: 17px; color: #55724c; font-size: 11px; }
        .settings-message.error { color: #a51d36; }
        .settings-links { display: flex; flex-wrap: wrap; gap: 9px; margin: 12px 0; }
        .settings-link { padding: 9px 12px; border: 1px solid #e7dfe1; border-radius: 9px; background: #fff; color: #74152a; font-size: 11px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .cookie-banner { position: fixed; z-index: 120; right: 18px; bottom: 18px; display: none; width: min(480px, calc(100vw - 36px)); padding: 16px; border: 1px solid #eee4e7; border-radius: 16px; background: #fff; box-shadow: 0 14px 40px rgba(48,8,20,.2); color: #38212a; }
        .cookie-banner.visible { display: block; }
        .cookie-banner strong { display: block; margin-bottom: 5px; font-size: 13px; }
        .cookie-banner p { margin: 0 0 12px; color: #71666c; font-size: 11px; line-height: 1.55; }
        .cookie-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .cookie-actions button { padding: 8px 10px; border: 1px solid #e7dfe1; border-radius: 8px; background: #fff; color: #74152a; font-family: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .cookie-actions button:first-child { border-color: #74152a; background: #74152a; color: #fff; }
        .terms-copy { color: #655a60; font-size: 12px; line-height: 1.7; }
        body.dark-mode .settings-tab[aria-selected="true"] { background: #49303a; color: #ffdbe3; }
        body.dark-mode .settings-tabs { border-color: #39323a; }
        body.dark-mode .settings-link, body.dark-mode .cookie-banner, body.dark-mode .settings-modal-content, body.dark-mode .terms-copy { background: #262229; border-color: #39323a; color: #f3eef0; }
        body.dark-mode .cookie-banner p { color: #c2b8be; }
        body.dark-mode .settings-links .settings-link { background: #302b33; }
        .sidebar-footer { position: relative; z-index: 1; margin: auto 18px 20px; padding: 12px 10px 0; border-top: 1px solid rgba(255,255,255,.12); color: rgba(255,255,255,.64); font-size: 9px; line-height: 1.6; }
        .sidebar-footer strong { display: block; color: rgba(255,255,255,.9); font-size: 10px; letter-spacing: .04em; }
        .nav-menu .nav-item { cursor: pointer; transition: background .18s ease, color .18s ease, transform .18s ease; }
        .nav-menu .nav-item:hover { background: rgba(255,255,255,.08); color: #fff; transform: translateX(2px); }
        .nav-menu .nav-item.active:hover { background: linear-gradient(90deg, #74102a, #671026); }
        @media (max-width: 1150px) {
            .sidebar { flex-basis: 220px; width: 220px; }
            .chat-sidebar { flex-basis: 270px; width: 270px; }
            .content-area { padding-left: 16px; padding-right: 12px; }
            .main-header { padding-right: 16px; padding-left: 16px; }
        }
        @media (max-width: 900px) {
            body:not(.phone-presentation) .sidebar { position: fixed; z-index: 70; right: 0; bottom: 0; left: 0; display: flex; width: 100%; height: calc(66px + env(safe-area-inset-bottom)); min-height: 66px; flex: none; flex-direction: row; padding-bottom: env(safe-area-inset-bottom); border-top: 1px solid rgba(255,255,255,.14); background: linear-gradient(100deg,#261813 0%,#45121b 52%,#741a2b 100%); box-shadow: 0 -8px 22px rgba(32,0,10,.16); }
            body:not(.phone-presentation) .logo-area, body:not(.phone-presentation) .sidebar-wave, body:not(.phone-presentation) .sidebar-footer { display: none; }
            body:not(.phone-presentation) .nav-menu { display: flex; width: 100%; height: 100%; flex: 1; align-items: center; justify-content: space-around; gap: 2px; padding: 4px 5px; }
            body:not(.phone-presentation) .nav-item { display: flex; min-width: 0; min-height: 54px; flex: 1 1 0; flex-direction: column; justify-content: center; gap: 4px; padding: 5px 2px; margin: 0; border-radius: 9px; }
            body:not(.phone-presentation) .nav-item span { display: block; overflow: hidden; max-width: 100%; font-size: 9px; line-height: 1.1; text-overflow: ellipsis; white-space: nowrap; }
            body:not(.phone-presentation) .nav-item i { width: auto; font-size: 16px; }
            body:not(.phone-presentation) .nav-item.active::before { inset: 0 0 auto; width: auto; height: 2px; }
            body:not(.phone-presentation) .workspace { width: 100%; }
            body:not(.phone-presentation) .dashboard-body { position: relative; }
            .logo-area { padding: 22px 14px; }
            .logo-text, .nav-item span { display: none; }
            .nav-menu { padding: 10px; }
            .nav-item { justify-content: center; padding: 12px; }
            .nav-item i { width: auto; }
            .sidebar-wave { display: block; bottom: 28%; }
            .chat-sidebar { display: none; }
            .mobile-chat-toggle { display: flex; }
            .mobile-chat-toggle { position: absolute; z-index: 35; right: 9px; bottom: calc(76px + env(safe-area-inset-bottom)); width: auto; min-width: 88px; height: 42px; gap: 8px; padding: 0 13px; border-radius: 999px; background: #8c0f2c; font-size: 12px; }
            .mobile-chat-toggle i { font-size: 16px; }
            .chat-sidebar.mobile-open { position: fixed; z-index: 80; right: 12px; bottom: calc(78px + env(safe-area-inset-bottom)); display: flex; width: min(420px, calc(100vw - 24px)); height: min(680px, calc(100dvh - 72px)); min-height: min(360px, calc(100dvh - 72px)); margin: 0; }
            body:not(.phone-presentation).mobile-chat-open::before { position: fixed; z-index: 119; inset: 0; background: rgba(25,9,15,.58); content: ""; }
            .chat-sidebar.mobile-open { inset: auto; top: 50%; right: auto; bottom: auto; left: 50%; z-index: 120; width: min(460px,calc(100vw - 24px)); height: min(740px,calc(100dvh - 48px)); min-height: min(360px,calc(100dvh - 48px)); margin: 0; transform: translate(-50%,-50%); border-radius: 18px; box-shadow: 0 20px 60px rgba(20,0,8,.32); }
            .chat-sidebar.mobile-open .chat-navigation { display: none; }
            .chat-sidebar.mobile-open.chat-navigation-open .chat-navigation { position: absolute; z-index: 6; inset: 0; display: flex; width: 100%; min-width: 0; flex-basis: 100%; border-right: 0; background: #fffafa; box-shadow: none; }
            .chat-sidebar.mobile-open .chat-conversation-panel { display: flex; width: 100%; min-width: 0; }
            .mobile-chat-open .cookie-banner { display: none; }
            .chat-sidebar.mobile-open .chat-header { min-width: 0; min-height: 58px; flex: 0 0 58px; padding-top: 8px; }
            .chat-sidebar.mobile-open .chat-header-identity { flex: 1 1 0; }
            .chat-sidebar.mobile-open .chat-input-area { padding-bottom: calc(12px + env(safe-area-inset-bottom)); }
            .chat-sidebar.mobile-open .chat-nav-toggle { width: 42px; height: 42px; flex-basis: 42px; }
            .chat-mobile-close { display: flex; }
            body.mobile-chat-open .mobile-chat-toggle { display: none; }
            body:not(.phone-presentation) .content-area { padding: 14px 14px calc(88px + env(safe-area-inset-bottom)); }
            .main-header { gap: 10px; }
            .sidebar-footer { display: none; }
            .blog-page-layout { grid-template-columns: minmax(0,1fr) 250px; gap: 14px; }
            .blog-articles-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
            .blog-featured-card { padding: 12px; }
            .settings-modal-content { width: min(700px,calc(100vw - 24px)); }
        }
        @media (max-width: 600px) {
            .app-container { min-height: 0; }
            body:not(.phone-presentation) .sidebar { height: calc(64px + env(safe-area-inset-bottom)); min-height: 64px; }
            .logo-area { padding: 16px 8px; }
            .logo-icon { width: 40px; height: 40px; flex-basis: 40px; }
            body:not(.phone-presentation) .nav-menu { padding: 3px 4px; }
            body:not(.phone-presentation) .nav-item { min-height: 52px; }
            body:not(.phone-presentation) .nav-item span { font-size: 8px; }
            body:not(.phone-presentation) .nav-item i { font-size: 15px; }
            .main-header { flex-basis: 58px; min-height: 58px; padding: 0 10px; }
            .header-actions { gap: 5px; }
            .user-mini { min-width: 0; max-width: calc(100vw - 208px); gap: 6px; padding: 0 4px; }
            .user-mini > .profile-button-copy { display: flex !important; min-width: 0; max-width: calc(100vw - 248px); }
            .user-mini > .profile-button-copy > span:first-child { max-width: 100%; font-size: 10px !important; }
            .user-mini > .profile-button-copy > span:last-child, .user-mini > i { display: none !important; }
            .user-mini .mini-avatar { width: 30px; height: 30px; flex: 0 0 30px; }
            .header-popover { position: fixed; top: 64px; right: 9px; width: min(320px, calc(100vw - 76px)); }
            .profile-popover { width: min(250px, calc(100vw - 76px)); }
            body:not(.phone-presentation) .content-area { padding: 10px 10px calc(82px + env(safe-area-inset-bottom)); }
            .welcome-card.dashboard-welcome { flex-basis: 150px; min-height: 150px; padding: 16px; }
            .welcome-title h2 { font-size: 21px; }
            .welcome-copy { max-width: 75%; }
            .welcome-copy p { font-size: 11px; }
            .welcome-art { right: 0; width: 100%; background-size: 100% 100%; opacity: .72; -webkit-mask-image: none; mask-image: none; }
            .stats-grid.dashboard-stats { height: auto; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 9px; }
            .stat-card.dashboard-stat { min-height: 112px; padding: 10px; }
            .record-row { grid-template-columns: minmax(0,1fr) auto; gap: 6px; padding: 5px 0; }
            .record-row > strong { text-align: right; }
            .record-status { display: none; }
            .watch-record-row { grid-template-columns: minmax(0,1fr) auto; }
            .watch-record-row .record-status { display: block; grid-column: 1/-1; justify-self: end; margin-top: -3px; padding: 2px 6px; font-size: 8px; }
            .info-card.records-panel { flex-basis: auto; min-height: 210px; }
            .record-row:not(.watch-record-row) { grid-template-columns: minmax(0,1fr) 40px; gap: 5px 8px; }
            .record-row:not(.watch-record-row) .record-name { grid-column: 1/-1; }
            .record-row:not(.watch-record-row) > .health-record-value { grid-column: 1; grid-row: 2; align-self: center; text-align: left; }
            .record-edit-button { grid-column: 2; grid-row: 2; width: 40px; height: 40px; border-color: #ead4da; border-radius: 10px; background: #f8eff1; }
            .record-note { padding-left: 0; }
            .watch-record-row { grid-template-columns: minmax(0,1fr) auto; }
            .info-card.dashboard-panel { padding-right: 12px; padding-left: 12px; }
            .appointment-row { grid-template-columns: 38px minmax(0,1fr) auto; gap: 6px; padding: 4px 0; }
            .appointment-row time { width: 36px; }
            .appointment-row .appointment-actions { grid-column: auto; justify-content: flex-end; gap: 1px; }
            .appointment-action { width: 25px; height: 28px; }
            .appointment-status { display: none; }
            .blog-page-layout { grid-template-columns: minmax(0,1fr); }
            .blog-featured-column { display: none; }
            .blog-hero { min-height: 158px; padding: 18px; }
            .blog-hero-copy { max-width: 76%; }
            .blog-hero-break { display: none; }
            .blog-hero > img { width: 48%; opacity: .65; }
            .blog-articles-grid { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 9px; }
            .blog-card-image { height: 105px; }
            .exam-summary-grid { grid-template-columns: 1fr; gap: 8px; }
            .exam-agenda-layout { grid-template-columns: minmax(0,1fr); }
            .info-intro-grid, .support-contact-grid { grid-template-columns: minmax(0,1fr); }
            .settings-layout { gap: 10px; }
            .settings-tabs { gap: 4px; }
            .settings-tab { width: auto; flex: 0 0 auto; }
            .settings-panels { width: 100%; }
            .settings-modal-content .modal-body { padding: 16px; }
            .settings-panel .setting-row { gap: 10px; }
            .appointment-form { grid-template-columns: minmax(0,1fr); }
            .appointment-form label.wide, .appointment-form .appointment-reminder-note, .appointment-form .appointment-form-actions { grid-column: 1; }
        }
        @media (max-height: 650px) {
            .app-container { min-height: 0; }
            .logo-area { padding-top: 14px; padding-bottom: 12px; }
            .nav-item { min-height: 40px; margin-bottom: 4px; }
            .sidebar-wave { bottom: 18%; height: 95px; }
            .sidebar-footer { margin-bottom: 10px; }
        }
        body.phone-presentation { display: grid; min-width: 320px; min-height: 100vh; height: auto; place-items: center; overflow: auto; padding: 18px 0; background: radial-gradient(ellipse at 50% 15%,#5a2434 0%,#28171e 50%,#151216 100%); }
        body.phone-presentation .app-container { position: relative; width: min(390px,calc(100vw - 30px)); height: min(844px,calc(100dvh - 36px)); min-height: 520px; margin: auto; overflow: hidden; border: 8px solid #171417; border-radius: 38px; background: #f4f0f1; box-shadow: 0 30px 90px rgba(0,0,0,.48),0 0 0 1px rgba(255,255,255,.2); }
        body.phone-presentation .workspace { width: 100%; }
        body.phone-presentation .dashboard-body { position: relative; min-height: 0; }
        body.phone-presentation .sidebar { position: absolute; z-index: 30; right: 0; bottom: 0; left: 0; width: 100%; height: 68px; min-height: 68px; flex: 0 0 68px; flex-direction: row; border-top: 1px solid rgba(255,255,255,.15); background: linear-gradient(100deg,#261813 0%,#45121b 52%,#741a2b 100%); box-shadow: 0 -8px 22px rgba(32,0,10,.16); }
        body.phone-presentation .logo-area, body.phone-presentation .sidebar-wave, body.phone-presentation .sidebar-footer { display: none; }
        body.phone-presentation .nav-menu { display: flex; width: 100%; height: 100%; flex: 1; align-items: center; justify-content: space-around; gap: 2px; padding: 5px 3px calc(5px + env(safe-area-inset-bottom)); }
        body.phone-presentation .nav-item { display: flex; min-width: 0; min-height: 52px; flex: 1 1 20%; flex-direction: column; justify-content: center; gap: 4px; margin: 0; padding: 5px 2px; border-radius: 9px; text-align: center; transform: none; }
        body.phone-presentation .nav-item span { display: block; max-width: 100%; overflow: hidden; font-size: 8px; line-height: 1.2; text-overflow: ellipsis; white-space: nowrap; }
        body.phone-presentation .nav-item i { width: auto; font-size: 16px; }
        body.phone-presentation .nav-item.active { background: rgba(255,255,255,.14); }
        body.phone-presentation .main-header { z-index: 5; min-height: 58px; flex: 0 0 58px; gap: 6px; padding: 5px 8px; background: rgba(255,255,255,.92); }
        body.phone-presentation .header-actions { gap: 5px; }
        body.phone-presentation .header-icon { width: 34px; height: 34px; flex: 0 0 34px; }
        body.phone-presentation .user-mini { min-width: 0; max-width: 118px; gap: 5px; padding: 2px 5px 2px 2px; }
        body.phone-presentation .user-mini .mini-avatar { width: 28px; height: 28px; flex: 0 0 28px; }
        body.phone-presentation .user-mini > .profile-button-copy { display: flex !important; min-width: 0; max-width: 76px; }
        body.phone-presentation .user-mini > .profile-button-copy > span:first-child { display: block; max-width: 100%; overflow: hidden; font-size: 9px !important; text-overflow: ellipsis; white-space: nowrap; }
        body.phone-presentation .user-mini > .profile-button-copy > span:last-child, body.phone-presentation .user-mini > i { display: none !important; }
        body.phone-presentation .header-popover { position: fixed; top: max(66px,calc((100vh - min(844px,100dvh - 36px))/2 + 60px)); right: max(15px,calc((100vw - 390px)/2 + 10px)); width: min(300px,calc(100vw - 50px)); }
        body.phone-presentation .content-area { min-height: 0; padding: 12px 11px 80px; scrollbar-width: thin; }
        body.phone-presentation .monitoring-shell { width: 100%; min-height: 100%; gap: 12px; }
        body.phone-presentation .welcome-card.dashboard-welcome { min-height: 155px; flex-basis: auto; padding: 17px 15px; border-radius: 15px; }
        body.phone-presentation .welcome-copy { max-width: 84%; }
        body.phone-presentation .welcome-title { gap: 8px; }
        body.phone-presentation .welcome-title > span { width: 20px; }
        body.phone-presentation .welcome-title h2 { font-size: 20px; }
        body.phone-presentation .welcome-copy p { max-width: 250px; font-size: 10px; }
        body.phone-presentation .welcome-art { right: 0; width: 100%; background-size: 100% 100%; opacity: .72; -webkit-mask-image: none; mask-image: none; }
        body.phone-presentation .stats-grid.dashboard-stats { height: auto; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 9px; margin: 0; }
        body.phone-presentation .stat-card.dashboard-stat { min-height: 103px; padding: 10px; border-radius: 12px; }
        body.phone-presentation .dashboard-stat .stat-icon { width: 34px; height: 34px; font-size: 14px; }
        body.phone-presentation .stat-arrow { width: 20px; height: 20px; margin-top: 18px; font-size: 9px; }
        body.phone-presentation .stat-card.dashboard-stat > strong { font-size: 21px; }
        body.phone-presentation .stat-card.dashboard-stat > span:last-child { font-size: 9px; }
        body.phone-presentation .info-card.dashboard-panel { min-height: 0; padding: 10px 11px 8px; border-radius: 12px; }
        body.phone-presentation .info-card.records-panel, body.phone-presentation .info-card.appointments-panel { flex: 0 0 auto; }
        body.phone-presentation .panel-heading { min-height: 35px; }
        body.phone-presentation .panel-heading h3 { gap: 7px; font-size: 12px; }
        body.phone-presentation .panel-heading h3 > span { width: 25px; height: 25px; font-size: 12px; }
        body.phone-presentation .view-all { font-size: 9px; }
        body.phone-presentation .record-row { min-height: 42px; grid-template-columns: minmax(0,1fr) auto; gap: 5px 8px; padding: 6px 0; font-size: 9px; }
        body.phone-presentation .record-name { gap: 7px; }
        body.phone-presentation .record-name i { width: 24px; height: 24px; font-size: 11px; }
        body.phone-presentation .record-row > strong { font-size: 9px; text-align: right; }
        body.phone-presentation .record-status { display: none; }
        body.phone-presentation .record-row:not(.watch-record-row) { grid-template-columns: minmax(0,1fr) 40px; gap: 5px 8px; }
        body.phone-presentation .record-row:not(.watch-record-row) .record-name { grid-column: 1/-1; }
        body.phone-presentation .record-row:not(.watch-record-row) > .health-record-value { grid-column: 1; grid-row: 2; align-self: center; text-align: left; }
        body.phone-presentation .record-edit-button { grid-column: 2; grid-row: 2; width: 40px; height: 40px; border-color: #ead4da; border-radius: 10px; background: #f8eff1; }
        body.phone-presentation .appointment-row { min-height: 48px; grid-template-columns: 39px minmax(0,1fr); gap: 7px; }
        body.phone-presentation .appointment-row time { width: 36px; height: 37px; }
        body.phone-presentation .appointment-row > div strong { font-size: 9px; }
        body.phone-presentation .appointment-row > div span { font-size: 8px; }
        body.phone-presentation .appointment-status { display: none; }
        body.phone-presentation .appointment-row { grid-template-columns: 36px minmax(0,1fr) auto; gap: 7px; padding: 5px 0; }
        body.phone-presentation .appointment-row time { width: 34px; }
        body.phone-presentation .appointment-row .appointment-actions { grid-column: auto; justify-content: flex-end; gap: 2px; }
        body.phone-presentation .appointment-action { width: 28px; height: 30px; }
        body.phone-presentation .appointment-row > div strong { white-space: normal; overflow-wrap: anywhere; }
        body.phone-presentation .appointment-row > div span { overflow-wrap: anywhere; }
        body.phone-presentation .watch-page { width: 100%; min-width: 0; gap: 10px; }
        body.phone-presentation .watch-hero { grid-template-columns: minmax(0,1fr); gap: 10px; padding: 13px; border-radius: 13px; }
        body.phone-presentation .watch-hero h2 { margin: 5px 0; font-size: 21px; }
        body.phone-presentation .watch-hero p { font-size: 11px; line-height: 1.5; }
        body.phone-presentation .watch-clock-card { grid-template-columns: minmax(0,1fr) auto; gap: 3px 8px; padding: 9px 10px; }
        body.phone-presentation .watch-clock-card > span { grid-column: 1; font-size: 8px; }
        body.phone-presentation .watch-clock-card time { grid-column: 2; grid-row: 1 / span 2; font-size: 22px; }
        body.phone-presentation .watch-clock-card small { font-size: 9px; }
        body.phone-presentation .watch-demo-note { gap: 7px; padding: 9px 10px; font-size: 10px; }
        body.phone-presentation .watch-layout { grid-template-columns: minmax(0,1fr); gap: 10px; }
        body.phone-presentation .watch-monitor-card,
        body.phone-presentation .watch-scenarios,
        body.phone-presentation .watch-project-note { padding: 12px; border-radius: 13px; }
        body.phone-presentation .watch-card-heading { align-items: flex-start; gap: 6px; padding-bottom: 10px; }
        body.phone-presentation .watch-card-heading > div { gap: 7px; }
        body.phone-presentation .watch-card-icon { width: 32px; height: 32px; flex-basis: 32px; }
        body.phone-presentation .watch-card-heading h3 { font-size: 12px; }
        body.phone-presentation .watch-card-heading p { font-size: 9px; line-height: 1.4; }
        body.phone-presentation .watch-status { padding: 5px 7px; font-size: 8px; }
        body.phone-presentation .watch-reading { grid-template-columns: 42px auto minmax(0,1fr); gap: 8px; padding: 16px 0 13px; }
        body.phone-presentation .watch-heart { width: 40px; height: 40px; }
        body.phone-presentation .watch-bpm strong { font-size: 40px; }
        body.phone-presentation .watch-reading > p { max-width: none; font-size: 9px; line-height: 1.45; text-align: right; }
        body.phone-presentation .watch-measurements { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 7px; margin: 0 0 10px; }
        body.phone-presentation .watch-measurement { min-height: 78px; padding: 9px; }
        body.phone-presentation .watch-measurement > span { font-size: 10px; }
        body.phone-presentation .watch-measurement > strong { font-size: 16px; }
        body.phone-presentation .watch-measurement > small { font-size: 9px; }
        body.phone-presentation .watch-measurement:last-child { grid-column: 1 / -1; }
        body.phone-presentation .watch-ecg-trace { height: 27px; }
        body.phone-presentation .watch-chart-wrap { padding: 9px; }
        body.phone-presentation .watch-chart-label { flex-wrap: wrap; gap: 4px; }
        body.phone-presentation .watch-chart-label strong { font-size: 10px; }
        body.phone-presentation .watch-chart-label span { font-size: 9px; }
        body.phone-presentation #watchChart { height: 88px; }
        body.phone-presentation .watch-controls { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 7px; margin-top: 10px; }
        body.phone-presentation .watch-controls > button { min-width: 0; min-height: 42px; padding: 8px 6px; font-size: 10px; }
        body.phone-presentation .watch-scenarios h3,
        body.phone-presentation .watch-project-note h3 { font-size: 14px; }
        body.phone-presentation .watch-scenarios > p,
        body.phone-presentation .watch-project-note > p { font-size: 10px; line-height: 1.55; }
        body.phone-presentation .watch-scenario-list { grid-template-columns: minmax(0,1fr); }
        body.phone-presentation .watch-scenario-list button { min-height: 48px; grid-template-columns: 32px minmax(0,1fr) 12px; }
        body.phone-presentation .watch-scenario-list strong { font-size: 10px; }
        body.phone-presentation .watch-scenario-list small { font-size: 9px; }
        body.phone-presentation .watch-project-note > span:last-child { font-size: 9px; }
        body.phone-presentation .mobile-chat-toggle { position: absolute; z-index: 35; right: 9px; bottom: calc(76px + env(safe-area-inset-bottom)); display: flex; width: auto; min-width: 88px; height: 42px; gap: 8px; padding: 0 13px; border-radius: 999px; background: #8c0f2c; font-size: 12px; }
        body.phone-presentation .mobile-chat-toggle i { font-size: 16px; }
        body.phone-presentation .chat-sidebar { display: none; }
        body.phone-presentation.mobile-chat-open .app-container::after { position: absolute; z-index: 83; inset: 0; border-radius: 30px; background: rgba(25,9,15,.58); content: ""; }
        body.phone-presentation .chat-sidebar.mobile-open { position: absolute; top: 50%; right: auto; bottom: auto; left: 50%; z-index: 84; display: flex; width: min(430px,calc(100% - 20px)); height: min(710px,calc(100% - 48px)); min-height: min(340px,calc(100% - 48px)); margin: 0; transform: translate(-50%,-50%); border-radius: 16px; box-shadow: 0 20px 60px rgba(20,0,8,.32); }
        body.phone-presentation .chat-sidebar.mobile-open .chat-navigation { display: none; }
        body.phone-presentation .chat-sidebar.mobile-open.chat-navigation-open .chat-navigation { position: absolute; z-index: 6; inset: 0; display: flex; width: 100%; min-width: 0; flex-basis: 100%; border-right: 0; background: #fffafa; box-shadow: none; }
        body.phone-presentation .chat-sidebar.mobile-open .chat-header { min-width: 0; min-height: 58px; flex: 0 0 58px; }
        body.phone-presentation .chat-sidebar.mobile-open .chat-header-identity { flex: 1 1 0; }
        body.phone-presentation .chat-nav-toggle { display: grid; }
        body.phone-presentation .chat-mobile-close { display: flex; flex: 0 0 30px; }
        body.phone-presentation.mobile-chat-open .mobile-chat-toggle { display: none; }
        body.phone-presentation .blog-editorial { gap: 12px; }
        body.phone-presentation .blog-editorial-hero { min-height: 0; grid-template-columns: minmax(0,1fr); gap: 0; padding: 20px; border-radius: 15px; }
        body.phone-presentation .blog-editorial-copy h2 { margin: 10px 0; font-size: 34px; }
        body.phone-presentation .blog-editorial-copy > p { font-size: 11px; }
        body.phone-presentation .blog-editorial-note { margin-top: 14px; }
        body.phone-presentation .blog-heart-stage { min-height: 225px; margin: 8px 0; }
        body.phone-presentation .blog-heart-stage model-viewer { height: 235px; }
        body.phone-presentation .blog-editorial-feature { display: flex; align-items: flex-start; padding: 13px 0 0; border-top: 1px solid #eadde0; border-left: 0; }
        body.phone-presentation .blog-editorial-feature-icon { margin-bottom: 8px; }
        body.phone-presentation .blog-editorial-feature h3 { margin: 6px 0; font-size: 14px; }
        body.phone-presentation .blog-editorial-feature > button { margin-top: 9px; }
        body.phone-presentation .blog-library { padding: 15px 10px; border-radius: 15px; }
        body.phone-presentation .blog-library-heading { align-items: flex-start; flex-direction: column; gap: 7px; }
        body.phone-presentation .blog-library .blog-articles-grid { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 8px; }
        body.phone-presentation .blog-library .blog-card-image { height: 105px; }
        body.phone-presentation .blog-library .blog-card-copy { gap: 5px; padding: 8px; }
        body.phone-presentation .blog-library .blog-card-copy > strong { min-height: 32px; font-size: 9px; }
        body.phone-presentation .blog-library .blog-card-summary { min-height: 35px; font-size: 8px; }
        body.phone-presentation .blog-library .blog-card-date { font-size: 7px; }
        body.phone-presentation .info-intro-grid, body.phone-presentation .support-contact-grid, body.phone-presentation .exam-summary-grid, body.phone-presentation .exam-agenda-layout { grid-template-columns: minmax(0,1fr); }
        body.phone-presentation .modal, body.phone-presentation .appointment-modal { position: absolute; inset: 0; padding: 8px; }
        body.phone-presentation .modal-content { width: 100%; max-width: 100%; max-height: calc(100% - 12px); }
        body.phone-presentation .settings-modal-content { width: 100%; max-width: 100%; }
        body.phone-presentation .settings-modal-content .modal-body { padding: 13px; }
        body.phone-presentation .header-popover { position: absolute; top: 48px; right: 0; width: min(300px,calc(100vw - 60px)); }
        body.phone-presentation .article-reading { max-width: 100%; }
        body.phone-presentation .article-feature { grid-template-columns: minmax(0,1fr); }
        body.phone-presentation .article-feature-copy { padding: 23px 18px; }
        body.phone-presentation .article-feature h1 { font-size: 29px; }
        body.phone-presentation .article-feature-image, body.phone-presentation .article-feature-image img { min-height: 190px; max-height: 215px; }
        body.phone-presentation .article-reading-content { grid-template-columns: minmax(0,1fr); }
        body.phone-presentation .article-prose { padding: 19px 16px; }
        body.phone-presentation .article-visual-grid, body.phone-presentation .article-reading-aside { grid-template-columns: minmax(0,1fr); }
        body.phone-presentation .article-visual img { height: 200px; }
        @media (max-width: 430px) {
            body.phone-presentation { padding: 8px 0; }
            body.phone-presentation .app-container { width: calc(100vw - 16px); height: calc(100dvh - 16px); min-height: 500px; border-width: 6px; border-radius: 32px; }
            body.phone-presentation .content-area { padding-right: 9px; padding-left: 9px; padding-bottom: 80px; }
            body.phone-presentation .watch-reading { grid-template-columns: 38px auto; gap: 7px; }
            body.phone-presentation .watch-reading > p { grid-column: 1 / -1; justify-self: start; text-align: left; }
            body.phone-presentation .watch-heart { width: 36px; height: 36px; font-size: 16px; }
            body.phone-presentation .watch-bpm strong { font-size: 36px; }
            body.phone-presentation .watch-card-heading { flex-wrap: wrap; }
            body.phone-presentation .watch-card-heading > div { flex: 1 1 160px; }
            body.phone-presentation .watch-status { margin-left: 39px; }
            body.phone-presentation .watch-controls { grid-template-columns: minmax(0,1fr); }
        }
        @media (min-width: 1151px) {
            body { background: #fae7ea; font-family: Georgia, "Times New Roman", serif; }
            .app-container { background: linear-gradient(135deg,#fff5f5 0%,#f7dce1 100%); }
            .sidebar {
                flex: 0 0 clamp(230px,17vw,260px);
                width: clamp(230px,17vw,260px);
                overflow: hidden;
                background: linear-gradient(180deg,#350410 0%,#410613 28%,#480714 64%,#580817 100%);
            }
            .sidebar::before { content: none; }
            .sidebar::after {
                position: absolute;
                right: 0;
                bottom: 79px;
                left: 0;
                z-index: 1;
                height: 190px;
                background: url("assets/dashboard-sidebar-heart.png?v=20261106-7") center/100% 100% no-repeat;
                -webkit-mask-image: radial-gradient(ellipse 88% 82% at 50% 50%,#000 68%,rgba(0,0,0,.72) 84%,transparent 100%);
                mask-image: radial-gradient(ellipse 88% 82% at 50% 50%,#000 68%,rgba(0,0,0,.72) 84%,transparent 100%);
                content: "";
                pointer-events: none;
            }
            .logo-area, .nav-menu { position: relative; z-index: 2; }
            .sidebar-wave { display: none; }
            .nav-item.active { background: linear-gradient(100deg,#99122e,#7b0d27); box-shadow: 0 7px 17px rgba(35,0,10,.2); }
            .nav-menu .nav-item.active:hover { background: linear-gradient(100deg,#a11532,#85102b); }
            .logo-icon svg path:first-child { fill: #fff; stroke: #fff; }
            .logo-icon svg path:nth-child(2) { stroke: #70152a; }
            .workspace {
                position: relative;
                isolation: isolate;
                overflow: hidden;
                background: linear-gradient(135deg,#f8e7ea 0%,#fff8f7 50%,#f6e1e5 100%);
            }
            .workspace::before, .workspace::after { z-index: 0; }
            .workspace > .main-header, .workspace > .dashboard-body { position: relative; }
            .workspace::before {
                position: absolute;
                top: 0;
                right: 0;
                left: 0;
                height: 110px;
                background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1500 110' preserveAspectRatio='none'%3E%3Cpath fill='%23e99aaa' fill-opacity='.78' d='M0 0h1500v18c-230 16-386-4-595 8S424 59 0 34z'/%3E%3Cpath fill='%23f4c8d0' fill-opacity='.95' d='M0 0h1500v31c-271-11-445 11-664 24S356 75 0 50z'/%3E%3Cpath fill='%23fff8f7' d='M0 0h1500v47c-248-14-447 9-671 24S349 87 0 66z'/%3E%3C/svg%3E") top center / 100% 100% no-repeat;
                content: "";
                pointer-events: none;
            }
            .workspace::after {
                position: absolute;
                right: -1%;
                bottom: 0;
                left: -1%;
                height: 86px;
                background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1500 90' preserveAspectRatio='none'%3E%3Cpath fill='%23f0c1ca' fill-opacity='.72' d='M0 44c246-25 422 19 680-3s543-13 820 4v45H0z'/%3E%3Cpath fill='%23fff8f7' fill-opacity='.96' d='M0 58c250-23 427 17 697-2s542-12 803 2v34H0z'/%3E%3Cpath fill='%23dc8495' fill-opacity='.2' d='M0 74c251-18 425 10 690-4s546-9 810 2v18H0z'/%3E%3C/svg%3E") center bottom / 100% 100% no-repeat;
                content: "";
                pointer-events: none;
            }
            .main-header {
                flex: 0 0 88px;
                min-height: 88px;
                padding: 0 24px;
                border: 0;
                background: transparent;
            }
            .dashboard-body { background: transparent; }
            .content-area {
                padding: 1px 20px 28px 22px;
                background:
                    radial-gradient(ellipse at 4% 12%,rgba(255,255,255,.68),transparent 32%),
                    radial-gradient(ellipse at 94% 78%,rgba(232,137,156,.2),transparent 38%),
                    linear-gradient(145deg,rgba(255,244,244,.4),rgba(250,220,225,.48));
            }
            .monitoring-shell { max-width: none; gap: 18px; }
            .welcome-card.dashboard-welcome {
                flex-basis: 163px;
                min-height: 163px;
                padding: 22px 30px;
                border-radius: 20px;
                background: linear-gradient(112deg,#69051d 0%,#450414 56%,#35010f 100%);
                box-shadow: 0 8px 24px rgba(89,9,30,.13);
            }
            .dashboard-welcome::before {
                background: linear-gradient(90deg,rgba(79,5,23,.92) 0%,rgba(79,5,23,.72) 30%,rgba(71,4,21,.32) 57%,rgba(47,2,14,.04) 100%);
            }
            .welcome-copy { max-width: 52%; }
            .welcome-title { gap: 12px; margin-bottom: 8px; }
            .welcome-title > span { width: 40px; height: 3px; background: #e8d34d; }
            .welcome-title h2 { font-size: clamp(25px,2.1vw,34px); font-weight: 600; }
            .welcome-copy p { max-width: 460px; font-size: 13px; line-height: 1.65; }
            .welcome-art { z-index: 2; width: 57%; overflow: hidden; background: url("assets/dashboard-banner-art.png?v=20261106-11") left center / auto 100% no-repeat; -webkit-mask-image: linear-gradient(90deg,transparent 0%,#000 14%,#000 100%); mask-image: linear-gradient(90deg,transparent 0%,#000 14%,#000 100%); }
            .welcome-art::after { top: 54%; right: 0; width: 180px; height: 78px; }
            .stats-grid.dashboard-stats { height: 125px; gap: 14px; margin-bottom: 0; }
            .stat-card.dashboard-stat {
                min-height: 125px;
                padding: 12px 17px;
                border-color: #f0dfe3;
                border-radius: 19px;
                background: rgba(255,250,249,.88);
                box-shadow: 0 7px 15px rgba(97,22,41,.09);
            }
            .dashboard-stat .stat-icon { width: 45px; height: 45px; background: #f7e4e6; }
            .stat-card.dashboard-stat > strong { font-size: 29px; }
            .stat-card.dashboard-stat > span:last-child { font-size: 12px; }
            .info-card.dashboard-panel {
                padding: 12px 18px 8px;
                border-color: #f0dfe3;
                border-radius: 19px;
                background: rgba(255,250,249,.9);
                box-shadow: 0 7px 18px rgba(97,22,41,.08);
            }
            .info-card.records-panel { min-height: 314px; margin-top: -1px; padding-bottom: 3px; }
            .panel-heading { min-height: 45px; }
            .panel-heading h3 { font-size: 18px; }
            .panel-heading h3 > span { width: 34px; height: 34px; }
            .records-demo-note { margin: 4px 0 2px; font-size: 10px; }
            .record-row { min-height: 32.5px; font-size: 12px; }
            .record-row > strong { font-size: 12px; }
            .record-status { font-size: 10px; }
            .chat-sidebar {
                flex: 0 0 260px;
                width: 260px;
                margin: 1px 14px 61px 0;
                border-color: #f0dfe3;
                border-radius: 20px;
                background: rgba(255,250,249,.9);
                box-shadow: 0 8px 22px rgba(100,20,41,.08);
            }
            body:not(.phone-presentation) .chat-sidebar:not(.chat-session-open) { flex-basis: 244px; width: 244px; }
            .chat-navigation { padding: 18px 13px 13px; background: transparent; }
            .chat-navigation-brand { gap: 10px; padding: 2px 5px 16px; }
            .chat-navigation-brand > span { width: 34px; height: 34px; flex: 0 0 34px; }
            .chat-navigation-brand strong { font-size: 17px; }
            .chat-navigation-action { min-height: 40px; padding: 0 8px; font-size: 12px; }
            .chat-recent-heading { margin: 17px 5px 5px; font-size: 12px; }
            .chat-recent-item { min-height: 38px; padding: 6px 8px; font-size: 11px; }
            .chat-navigation-note { display: flex; min-height: 75px; align-items: center; gap: 12px; margin: auto 5px 26px; padding: 12px; border: 0; border-radius: 14px; background: #f9e7ea; font-size: 10px; }
            .chat-navigation-note i { flex: 0 0 36px; color: #e58b9b; font-size: 32px; }
            .chat-navigation-note span { line-height: 1.35; }
        }
        @media (min-width: 1151px) and (max-height: 720px) {
            .main-header { flex-basis: 78px; min-height: 78px; }
            .content-area { padding-top: 1px; }
            .monitoring-shell { gap: 13px; }
            .welcome-card.dashboard-welcome { flex-basis: 145px; min-height: 145px; }
            .stats-grid.dashboard-stats { height: 112px; }
            .stat-card.dashboard-stat { min-height: 112px; padding-top: 9px; padding-bottom: 8px; }
            .dashboard-stat .stat-icon { width: 40px; height: 40px; }
            .info-card.records-panel { min-height: 270px; padding-top: 8px; }
            .panel-heading { min-height: 39px; }
            .panel-heading h3 > span { width: 30px; height: 30px; }
            .records-demo-note { margin: 3px 0; font-size: 9px; }
            .record-row { min-height: 28px; }
            .record-name i { width: 25px; height: 25px; }
        }
        @media (max-width: 900px) {
            body:not(.phone-presentation) .mobile-chat-toggle { z-index: 90; bottom: calc(76px + env(safe-area-inset-bottom)); }
            body.phone-presentation .mobile-chat-toggle { z-index: 90; }
        }
        :root { color-scheme: light; }
        body { background: #faeef0; }
        .app-container { background: linear-gradient(135deg,#fff8f7 0%,#f9eaed 52%,#fff5f2 100%); }
        .main-header { background: rgba(255,247,247,.88); border-color: #efdee2; }
        .content-area { background: radial-gradient(ellipse at 88% 4%,rgba(255,255,255,.62),transparent 34%),linear-gradient(145deg,rgba(255,244,244,.58),rgba(250,235,237,.42)); }
        .content-area :is(.info-card,.stat-card,.blog-article-card,.blog-featured-card,.blog-library,.exam-panel,.exam-summary-card,.exam-agenda-card,.info-page-card,.info-feature,.support-page-card,.support-contact,.watch-monitor-card,.watch-scenarios,.watch-project-note,.watch-hero,.watch-appointment-alert,.blog-card,.article-prose,.article-aside-card,.cardio-card,.pill-info,.scroll-story) {
            border-color: #f0dfe3;
            box-shadow: 0 8px 24px rgba(89,20,39,.055);
        }
        .settings-modal-content { width: min(900px,calc(100vw - 32px)); max-width: 900px; max-height: min(90vh,820px); max-height: min(90dvh,820px); overflow: hidden; border: 1px solid #f1dce1; border-radius: 22px; background: #fffaf9; box-shadow: 0 28px 80px rgba(50,5,20,.25); }
        .settings-modal-content .modal-header { flex: 0 0 auto; padding: 20px 26px; border-bottom: 1px solid #f2e3e6; background: linear-gradient(110deg,#fff9f8,#f8e9ec); }
        .settings-modal-content .modal-header h2 { color: #4a1022; font-size: 20px; letter-spacing: -.025em; }
        .settings-modal-content .modal-header h2 i { color: #901c3b; }
        .settings-modal-content .modal-close { width: 38px; height: 38px; border-radius: 50%; background: rgba(120,21,44,.07); color: #78152c; font-size: 25px; }
        .settings-modal-content .modal-body { padding: 20px 26px 22px; }
        .settings-intro { display: flex; align-items: center; gap: 11px; margin: 0 0 18px; padding: 13px 15px; border: 1px solid #f1dce1; border-radius: 13px; background: linear-gradient(110deg,#fff4f4,#f9e9ec); color: #704451; font-size: 12px; line-height: 1.55; }
        .settings-intro > i { display: grid; width: 34px; height: 34px; flex: 0 0 34px; place-items: center; border-radius: 50%; background: #f1dce1; color: #861832; font-size: 15px; }
        .settings-layout { display: grid; grid-template-columns: minmax(160px,.65fr) minmax(0,1.8fr); gap: 20px; align-items: start; }
        .settings-tabs { flex-direction: column; gap: 5px; overflow: visible; padding: 0 12px 0 0; border: 0; border-right: 1px solid #f0e2e5; }
        .settings-tab { min-height: 44px; width: 100%; padding: 10px 12px; border: 1px solid transparent; border-radius: 11px; color: #75646b; font-size: 12px; transition: background .18s ease,border-color .18s ease,color .18s ease,transform .18s ease; }
        .settings-tab:hover { background: #fff2f3; color: #71152b; transform: translateX(2px); }
        .settings-tab[aria-selected="true"] { border-color: #f0dce1; background: linear-gradient(110deg,#f9e9ec,#fff5f4); color: #74152a; box-shadow: inset 3px 0 #9c2743; }
        .settings-tab i { width: 18px; color: #8a1b36; }
        .settings-panels { min-height: 270px; padding: 18px; border: 1px solid #f0e1e4; border-radius: 16px; background: rgba(255,255,255,.78); box-shadow: 0 6px 20px rgba(80,19,37,.035); }
        .settings-panel { padding-top: 0; }
        .settings-panel h3 { margin: 0 0 5px; color: #421a26; font-size: 17px; letter-spacing: -.02em; }
        .settings-subtitle { margin-bottom: 15px; color: #7d6e74; font-size: 12px; line-height: 1.6; }
        .account-form { gap: 13px; }
        .account-form.profile-form { grid-template-columns: repeat(2,minmax(0,1fr)); }
        .account-form label { gap: 7px; color: #5b424b; font-size: 12px; }
        .account-form input { min-height: 43px; padding: 10px 12px; border-color: #ead9de; border-radius: 10px; background: #fffdfd; font-size: 13px; }
        .account-form input:focus, .setting-row select:focus, .settings-link:focus-visible, .settings-tab:focus-visible { outline: 3px solid rgba(139,28,55,.2); outline-offset: 2px; border-color: #9c2743; }
        .account-form .contact-btn, .settings-footer .contact-btn { min-height: 40px; padding: 0 16px; border-radius: 10px; background: linear-gradient(110deg,#8b1a35,#6f1028); box-shadow: 0 6px 14px rgba(112,16,40,.16); font-size: 12px; }
        .settings-content > .settings-footer { margin-top: 17px; padding-top: 15px; border-color: #f0e2e5; }
        .settings-reset { border-color: #ead9de; background: #fff; color: #6f5961; box-shadow: none !important; }
        .settings-intro + .settings-layout + .settings-footer { flex: 0 0 auto; }
        .settings-theme-card { display: flex; min-width: 0; align-items: center; gap: 11px; margin: 12px 0 5px; padding: 12px; border: 1px solid #f0dfe3; border-radius: 13px; background: linear-gradient(110deg,#fff7f6,#f9ecee); }
        .settings-theme-icon { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 12px; background: #f2dfe3; color: #801832; font-size: 16px; }
        .settings-theme-copy { display: grid; min-width: 0; gap: 3px; }
        .settings-theme-copy strong { color: #491927; font-size: 12px; }
        .settings-theme-copy > span { color: #806d74; font-size: 10px; line-height: 1.45; }
        .settings-theme-swatches { display: flex; flex: 0 0 auto; gap: 4px; margin-left: auto; }
        .settings-theme-swatches i { width: 15px; height: 15px; border: 2px solid rgba(255,255,255,.85); border-radius: 50%; box-shadow: 0 1px 3px rgba(48,9,20,.15); }
        .settings-theme-swatches i:nth-child(1) { background: #450a18; }
        .settings-theme-swatches i:nth-child(2) { background: #851e32; }
        .settings-theme-swatches i:nth-child(3) { background: #e8a4b0; }
        .settings-theme-swatches i:nth-child(4) { background: #fff0f1; }
        .setting-row { gap: 16px; padding: 15px 0; border-color: #f0e5e7; }
        .setting-row strong { color: #44202b; font-size: 13px; }
        .setting-row span { color: #806f76; font-size: 11px; line-height: 1.5; }
        .setting-row-featured { margin: 8px 0 2px; padding: 13px 14px; border: 1px solid #f0dce1; border-radius: 12px; background: linear-gradient(110deg,#fff5f4,#f9e9ec); }
        .setting-row select { min-width: 150px; min-height: 42px; padding: 8px 34px 8px 12px; border-color: #ead9de; border-radius: 10px; color: #4b2934; font-size: 12px; }
        .setting-toggle { width: 48px; height: 28px; flex-basis: 48px; background: #d7c8cd; }
        .setting-toggle::after { top: 3px; left: 3px; width: 22px; height: 22px; }
        .setting-toggle[aria-checked="true"] { background: linear-gradient(110deg,#a52b49,#74152a); }
        .setting-toggle[aria-checked="true"]::after { transform: translateX(20px); }
        .setting-toggle:focus-visible { outline: 3px solid rgba(139,28,55,.25); outline-offset: 3px; }
        .settings-links { gap: 8px; }
        .settings-link { min-height: 39px; display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-color: #ead9de; border-radius: 10px; background: #fffdfd; color: #78152c; font-size: 11px; transition: background .18s ease,border-color .18s ease,transform .18s ease; }
        .settings-link:hover { transform: translateY(-1px); border-color: #d6abb5; background: #fff3f4; }
        body.dark-mode { color-scheme: dark; }
        body.dark-mode, body.dark-mode .app-container { background: #170b11; color: #f8edf0; }
        body.dark-mode .app-container { background: linear-gradient(135deg,#1b0d14 0%,#211018 54%,#180b11 100%); }
        body.dark-mode .workspace { background: linear-gradient(135deg,#211018 0%,#29131e 48%,#1b0c13 100%); }
        body.dark-mode .workspace::before { background: radial-gradient(ellipse at 70% 8%,rgba(117,42,63,.32) 0 20%,transparent 58%),linear-gradient(169deg,rgba(128,28,54,.22) 0 22%,rgba(63,28,39,.72) 22.5% 57%,rgba(130,51,70,.24) 58% 67%,rgba(47,20,29,.7) 68% 100%); }
        body.dark-mode .workspace::after { background: linear-gradient(174deg,rgba(64,35,45,.76) 0 34%,rgba(128,45,65,.4) 35% 52%,rgba(46,23,32,.8) 53% 73%,rgba(112,19,45,.38) 74% 84%,transparent 85%); }
        body.dark-mode .dashboard-body { background: transparent; }
        body.dark-mode .content-area { background: radial-gradient(ellipse at 85% 4%,rgba(124,38,61,.16),transparent 36%),linear-gradient(145deg,rgba(37,16,25,.66),rgba(25,11,17,.6)); }
        body.dark-mode .sidebar { background: linear-gradient(180deg,#1b0911 0%,#350d1b 52%,#591226 100%); }
        body.dark-mode .main-header { background: rgba(38,17,26,.94); border-color: #4a2934; }
        body.dark-mode .header-icon { background: #38202a; color: #ffeaf0; }
        body.dark-mode .user-mini { background: rgba(87,31,49,.46); color: #f8edf0; }
        body.dark-mode .chat-sidebar, body.dark-mode .chat-header, body.dark-mode .chat-input-area { background: #25131b; border-color: #4b2b36; color: #f8edf0; }
        body.dark-mode .chat-navigation { background: transparent; }
        body.dark-mode .chat-navigation-action { border-color: #58313e; background: #321b25; color: #f6e5e9; }
        body.dark-mode .chat-navigation-action:hover, body.dark-mode .chat-recent-item:hover { background: #43212f; }
        body.dark-mode .chat-recent-item { color: #e6d4da; }
        body.dark-mode .chat-navigation-note { background: #38202a; color: #dfc7cf; }
        body.dark-mode .chat-messages .message-bubble { background: #39232d; color: #faedf1; }
        body.dark-mode .chat-messages .message.user .message-bubble { background: #6f1730; color: #fff4f6; }
        body.dark-mode .chat-input { background: #321c26; border-color: #63404c; color: #fff5f7; }
        body.dark-mode .chat-attach { border-color: #62404c; background: #38212b; color: #f2cbd4; }
        body.dark-mode .content-area :is(.info-card,.stat-card,.blog-article-card,.blog-featured-card,.blog-library,.exam-panel,.exam-summary-card,.exam-agenda-card,.info-page-card,.info-feature,.support-page-card,.support-contact,.watch-monitor-card,.watch-scenarios,.watch-project-note,.watch-hero,.watch-appointment-alert,.blog-card,.article-prose,.article-aside-card,.cardio-card,.pill-info,.scroll-story) {
            border-color: #4b2b36;
            background: #28161e;
            color: #f8edf0;
            box-shadow: 0 10px 28px rgba(0,0,0,.2);
        }
        body.dark-mode .content-area :is(h1,h2,h3,h4,h5,h6,strong,.panel-heading h3,.blog-section-heading h3,.watch-card-heading h3,.cardio-card h3,.pill-info h4,.story-content h3) { color: #f8e9ed; }
        body.dark-mode .content-area :is(p,small,label,.settings-subtitle,.records-demo-note,.record-row,.record-note,.watch-hero p,.watch-card-heading p,.watch-scenarios > p,.watch-reading > p,.support-contact-copy p,.info-feature p,.story-summary,.article-prose) { color: #d1bdc5; }
        body.dark-mode .content-area :is(.record-row,.appointment-row,.panel-heading,.watch-card-heading,.exam-panel,.info-page-card,.support-page-card,.blog-search,.blog-article-card,.blog-featured-card,.article-prose,.article-aside-card,.settings-panels,.settings-theme-card,.setting-row-featured) { border-color: #4b2b36; }
        body.dark-mode .content-area :is(.record-name i,.stat-icon,.watch-card-icon,.support-contact-icon,.panel-heading h3 > span) { background: #422330; color: #f2cbd4; }
        body.dark-mode .content-area :is(input,textarea,select) { border-color: #63404c; background: #321c26; color: #fff4f6; }
        body.dark-mode .content-area :is(button:not(.contact-btn),.settings-link) { border-color: #583440; }
        body.dark-mode .content-area .record-status, body.dark-mode .content-area .appointment-status { background: #2a3a2f; color: #c1d9b8; }
        body.dark-mode .header-popover { background: #28161e; border-color: #4b2b36; color: #f8edf0; }
        body.dark-mode .profile-menu-item { color: #f3e5e9; }
        body.dark-mode .profile-menu-item:hover, body.dark-mode .notification-item:hover { background: #3a202b; }
        body.dark-mode .settings-modal-content { border-color: #4b2b36; background: #211119; }
        body.dark-mode .settings-modal-content .modal-header { border-color: #4b2b36; background: linear-gradient(110deg,#321722,#26131c); }
        body.dark-mode .settings-modal-content .modal-header h2, body.dark-mode .settings-panel h3 { color: #ffeaf0; }
        body.dark-mode .settings-modal-content .modal-close { background: #432330; color: #f7d8e0; }
        body.dark-mode .settings-intro { border-color: #52313c; background: linear-gradient(110deg,#38212b,#2d1922); color: #e3cdd4; }
        body.dark-mode .settings-intro > i, body.dark-mode .settings-theme-icon { background: #532b3a; color: #ffdbe3; }
        body.dark-mode .settings-tabs { border-color: #4b2b36; }
        body.dark-mode .settings-tab { color: #d7c2ca; }
        body.dark-mode .settings-tab:hover { background: #38212b; color: #ffe8ee; }
        body.dark-mode .settings-tab[aria-selected="true"] { border-color: #603542; background: linear-gradient(110deg,#4a2432,#361b27); color: #ffe5eb; }
        body.dark-mode .settings-tab i { color: #f1a9b8; }
        body.dark-mode .settings-panels { border-color: #4b2b36; background: #28161e; }
        body.dark-mode .settings-subtitle, body.dark-mode .settings-theme-copy > span, body.dark-mode .setting-row span { color: #cdb8c0; }
        body.dark-mode .settings-theme-card, body.dark-mode .setting-row-featured { border-color: #52313c; background: linear-gradient(110deg,#38212b,#2d1922); }
        body.dark-mode .settings-theme-copy strong, body.dark-mode .setting-row strong { color: #f8e9ed; }
        body.dark-mode .setting-row, body.dark-mode .settings-content > .settings-footer { border-color: #4b2b36; }
        body.dark-mode .setting-row select, body.dark-mode .account-form input { border-color: #63404c; background: #321c26; color: #fff4f6; }
        body.dark-mode .account-form label { color: #dfccd2; }
        body.dark-mode .settings-link { border-color: #60404b; background: #321c26; color: #f4dce2; }
        body.dark-mode .settings-link:hover { background: #432430; }
        body.dark-mode .settings-reset { background: #321c26; color: #e4d3d9; }
        body.dark-mode .account-detail { background: #321c26; }
        body.dark-mode .account-detail strong { color: #f8e9ed; }
        @media (min-width: 1151px) {
            .sidebar-footer { z-index: 3; display: flex; align-items: center; justify-content: center; gap: 9px; margin: auto 20px 24px; padding: 11px 8px; border: 1px solid rgba(255,210,220,.15); border-radius: 999px; background: linear-gradient(100deg,rgba(255,255,255,.035),rgba(231,71,103,.12)); color: rgba(255,232,237,.82); text-align: center; }
            .sidebar-footer > span { display: inline-flex; align-items: center; gap: 7px; font-family: Inter, sans-serif; font-size: 9px; font-style: normal; font-weight: 700; letter-spacing: .12em; line-height: 1; text-transform: uppercase; }
            .sidebar-footer i { width: 6px; height: 6px; border-radius: 50%; background: #fa8c9f; box-shadow: 0 0 10px rgba(250,140,159,.8); font-size: 0; }
            .main-header {
                background: transparent;
            }
            .welcome-card.dashboard-welcome { background: linear-gradient(110deg,#4b0618 0%,#5b0819 42%,#6e0819 78%,#6e0819 100%); box-shadow: 0 8px 22px rgba(80,10,30,.12); }
            .dashboard-welcome::before { background: linear-gradient(90deg,rgba(61,3,17,.94) 0%,rgba(74,5,21,.78) 34%,rgba(83,6,24,.34) 61%,rgba(86,7,24,.05) 100%); }
            .dashboard-welcome::after { display: none; }
            .welcome-art { z-index: 2; width: 57%; overflow: hidden; background-image: url("assets/dashboard-banner-art.png?v=20261106-15"),linear-gradient(180deg,#7f051f 0%,#6e0819 50%,#590915 100%); background-position: right center,right center; background-size: auto 100%,100% 100%; background-repeat: no-repeat; -webkit-mask-image: linear-gradient(90deg,transparent 0%,transparent 8%,rgba(0,0,0,.18) 30%,rgba(0,0,0,.62) 56%,#000 82%,#000 100%); mask-image: linear-gradient(90deg,transparent 0%,transparent 8%,rgba(0,0,0,.18) 30%,rgba(0,0,0,.62) 56%,#000 82%,#000 100%); }
            .welcome-art::before { content: none; }
            .welcome-copy { max-width: 49%; }
            .welcome-copy p { max-width: 450px; font-size: 13px; line-height: 1.58; }
            .welcome-title > span { width: 42px; background: #e5c952; }
            .stats-grid.dashboard-stats { gap: 13px; }
            .stat-card.dashboard-stat { border-color: rgba(240,213,218,.95); border-radius: 18px; background: linear-gradient(145deg,rgba(255,250,247,.97),rgba(255,242,239,.94)); box-shadow: 0 7px 15px rgba(100,25,42,.09); }
            .stat-card.dashboard-stat::after { height: 2px; background: linear-gradient(90deg,#9c1736,#6f0f27); }
            .dashboard-stat .stat-icon { background: linear-gradient(145deg,#fae8e9,#f2dadd); color: #8b1732; }
            .stat-card.dashboard-stat > strong { color: #45101f; }
            .stat-card.dashboard-stat > span:last-child { color: #5d4249; }
            .info-card.dashboard-panel { border-color: rgba(240,213,218,.95); border-radius: 19px; background: linear-gradient(145deg,rgba(255,250,248,.97),rgba(255,244,241,.95)); box-shadow: 0 8px 19px rgba(100,25,42,.075); }
            .panel-heading h3 { color: #491421; }
            .record-row { color: #5b4449; }
            .record-row > strong { color: #3d2029; }
            .record-name i { border-radius: 50%; background: #f7e5e7; color: #8f1833; }
        }
        @media (max-width: 1150px) {
            .dashboard-welcome::after { display: none; }
        }
        @media (max-width: 600px) {
            .welcome-art { right: 0; width: 100%; background-position: left center; background-size: auto 70%; opacity: .5; }
            .welcome-art::after { display: block; right: 6%; width: 72px; height: 24px; }
            .welcome-copy { max-width: 78%; }
            .welcome-copy p { max-width: 235px; }
            .health-history-current { grid-template-columns: minmax(0,1fr); }
            .health-history-change { grid-template-columns: minmax(0,1fr); }
            .health-history-change time { text-align: left; }
        }
        @media (max-width: 900px) {
            .chat-sidebar.mobile-open .chat-navigation { display: none; }
            .chat-sidebar.mobile-open.chat-navigation-open .chat-navigation { position: absolute; z-index: 6; inset: 0; display: flex; width: 100%; min-width: 0; flex-basis: 100%; border-right: 0; background: #fffafa; box-shadow: none; }
            .chat-sidebar.mobile-open .chat-conversation-panel { display: flex; width: 100%; min-width: 0; }
            .chat-sidebar.mobile-open .chat-nav-toggle { display: grid; }
            .chat-sidebar.mobile-open .chat-mobile-close { display: flex; }
        }
        @media (max-width: 700px) {
            .settings-modal-content { width: min(620px,calc(100vw - 20px)); max-height: min(92vh,920px); max-height: min(92dvh,920px); border-radius: 18px; }
            .settings-modal-content .modal-header { padding: 15px 17px; }
            .settings-modal-content .modal-header h2 { font-size: 18px; }
            .settings-modal-content .modal-body { padding: 14px 15px 16px; }
            .settings-intro { margin-bottom: 12px; padding: 10px 11px; font-size: 11px; }
            .settings-layout { grid-template-columns: minmax(0,1fr); gap: 12px; }
            .settings-tabs { flex-direction: row; gap: 5px; overflow-x: auto; padding: 0 0 7px; border-right: 0; border-bottom: 1px solid #f0e2e5; scrollbar-width: thin; }
            .settings-tab { width: auto; min-height: 40px; flex: 0 0 auto; padding: 9px 10px; font-size: 11px; }
            .settings-panels { min-height: 0; padding: 14px; }
            .account-form.profile-form { grid-template-columns: minmax(0,1fr); }
            .settings-theme-card { gap: 8px; padding: 10px; }
            .settings-theme-swatches { gap: 2px; }
            .settings-theme-swatches i { width: 12px; height: 12px; }
            .setting-row { align-items: flex-start; gap: 12px; }
            .setting-row > div { min-width: 0; }
            .setting-row select { min-width: 118px; }
            .settings-links { flex-direction: column; align-items: stretch; }
            .settings-link { justify-content: flex-start; text-align: left; }
        }
        .chat-sidebar.chat-navigation-open .chat-conversation-panel { display: none !important; }
        .chat-sidebar.chat-navigation-open .chat-navigation { position: absolute; z-index: 6; inset: 0; display: flex; width: auto; min-width: 0; flex: 1 1 auto; border-right: 0; background: #fffafa; box-shadow: none; }
        @media (prefers-reduced-motion: reduce) {
            body:not(.reduce-motion) .settings-tab, body:not(.reduce-motion) .settings-link { transition-duration: .01ms; }
        }
    </style>
</head>
<body>
    <div class="app-container<?php echo $page === 'blog' ? ' blog-active' : ''; ?>">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="logo-area">
                <div class="logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 52 52" aria-hidden="true">
                            <path d="M26 45S5 32 5 17C5 6 19 2 26 13 33 2 47 6 47 17c0 15-21 28-21 28Z" fill="none" stroke="#e8d34c" stroke-width="3.5" stroke-linejoin="round"/>
                            <path d="M7 25h12l4-8 6 17 5-11 3 2h8" fill="none" stroke="#e8d34c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <h2>CardioWeb</h2>
                        <p>Saúde & Monitoramento<br>Cardiológico</p>
                    </div>
                </div>
            </div>
            <div class="nav-menu">
                <div class="nav-item <?php echo $page == 'monitoramento' ? 'active' : ''; ?>" role="button" tabindex="0" title="Monitoramento" aria-label="Monitoramento" onclick="changePage('monitoramento')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <i class="fas fa-heart-pulse"></i><span>Monitoramento</span>
                </div>
                <div class="nav-item <?php echo $page == 'relogio' ? 'active' : ''; ?>" role="button" tabindex="0" title="Relógio cardíaco" aria-label="Relógio cardíaco" onclick="changePage('relogio')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <i class="fas fa-stopwatch"></i><span>Relógio</span>
                </div>
                <div class="nav-item <?php echo $page == 'blog' ? 'active' : ''; ?>" role="button" tabindex="0" title="Blog" aria-label="Blog" onclick="changePage('blog')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <i class="fas fa-newspaper"></i><span>Blog</span>
                </div>
                <div class="nav-item <?php echo $page == 'exames' ? 'active' : ''; ?>" role="button" tabindex="0" title="Exames" aria-label="Exames" onclick="changePage('exames')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <i class="fas fa-flask"></i><span>Exames</span>
                </div>
                <div class="nav-item <?php echo $page == 'informacoes' ? 'active' : ''; ?>" role="button" tabindex="0" title="Informações" aria-label="Informações" onclick="changePage('informacoes')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <i class="fas fa-info-circle"></i><span>Informações</span>
                </div>
                <div class="nav-item <?php echo $page == 'suporte' ? 'active' : ''; ?>" role="button" tabindex="0" title="Suporte" aria-label="Suporte" onclick="changePage('suporte')" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}">
                    <i class="fas fa-headset"></i><span>Suporte</span>
                </div>
            </div>
            <div class="sidebar-wave" aria-hidden="true">
                <svg viewBox="0 0 260 150" width="100%" height="150" preserveAspectRatio="none">
                    <path class="ecg-line" d="M-8 91h69l15-2 12-5 12-29 15 62 19-91 19 70 13-25 11 20h91"/>
                </svg>
            </div>
            <div class="sidebar-footer" aria-label="Monitoramento contínuo"><span><i aria-hidden="true"></i>Monitoramento ativo</span></div>
        </div>

        <div class="workspace">
            <div class="main-header">
                <div class="header-actions">
                    <div class="header-control">
                        <button type="button" id="notificationsButton" class="header-icon notification" aria-label="Notificações" aria-haspopup="dialog" aria-expanded="false" aria-controls="notificationsMenu" onclick="toggleHeaderPopover('notificationsMenu')"><i class="fas fa-bell"></i></button>
                        <section class="header-popover" id="notificationsMenu" role="dialog" aria-label="Notificações">
                            <header class="popover-heading">
                                <h3>Notificações</h3>
                                <button type="button" class="popover-action" onclick="markNotificationsRead()">Marcar como lidas</button>
                            </header>
                            <div class="notification-list" id="notificationList" aria-live="polite"></div>
                        </section>
                    </div>
                    <div class="header-control">
                        <button type="button" id="settingsButton" class="header-icon" title="Configurações" aria-label="Abrir configurações" onclick="openSettingsModal()"><i class="fas fa-gear"></i></button>
                    </div>
                    <div class="header-control">
                        <button type="button" id="profileButton" class="user-mini" aria-haspopup="menu" aria-expanded="false" aria-controls="profileMenu" onclick="toggleHeaderPopover('profileMenu')">
                            <span class="mini-avatar"><?php echo htmlspecialchars(strtoupper(substr($user_email, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="profile-button-copy" style="display:flex; flex-direction:column; line-height:1.1;">
                                <span style="font-size:12px; font-weight:700;" title="<?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span style="font-size:10px; opacity:.78;"><?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                            <i class="fas fa-chevron-down" style="font-size:11px; opacity:.75;"></i>
                        </button>
                        <nav class="header-popover profile-popover" id="profileMenu" role="menu" aria-label="Menu do perfil">
                            <div class="profile-summary">
                                <span class="mini-avatar"><?php echo htmlspecialchars(strtoupper(substr($user_name, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                                <div><strong><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?></span></div>
                            </div>
                            <button type="button" class="profile-menu-item" role="menuitem" onclick="closeHeaderPopovers(); openSettingsModal('account')"><i class="fas fa-user"></i> Minha conta</button>
                            <button type="button" class="profile-menu-item" role="menuitem" onclick="closeHeaderPopovers(); openSettingsModal()"><i class="fas fa-sliders"></i> Preferências</button>
                            <a class="profile-menu-item logout" role="menuitem" href="logout.php"><i class="fas fa-right-from-bracket"></i> Sair da conta</a>
                        </nav>
                    </div>
                </div>
                <h1 class="page-title" id="pageTitle">Monitoramento</h1>
            </div>
            <div class="appointment-modal" id="appointmentModal" role="dialog" aria-modal="true" aria-labelledby="appointmentModalTitle" onclick="if(event.target===this)closeAppointmentModal()">
                <section class="appointment-modal-card">
                    <header class="appointment-modal-heading">
                        <h2 id="appointmentModalTitle">Adicionar compromisso</h2>
                        <button type="button" class="appointment-modal-close" aria-label="Fechar" onclick="closeAppointmentModal()"><i class="fas fa-xmark"></i></button>
                    </header>
                    <form class="appointment-form" id="appointmentForm" onsubmit="saveAppointment(event)">
                        <input type="hidden" id="appointmentId">
                        <label class="wide">Compromisso<input id="appointmentTitle" maxlength="100" required placeholder="Ex.: Consulta com cardiologista"></label>
                        <label>Data<input id="appointmentDate" type="date" required></label>
                        <label>Horário<input id="appointmentTime" type="time" required></label>
                        <label class="wide">Local ou modalidade<input id="appointmentLocation" maxlength="120" placeholder="Ex.: Clínica ou teleconsulta"></label>
                        <label class="wide">Observações<textarea id="appointmentNotes" maxlength="400" placeholder="Informações úteis para o dia"></textarea></label>
                        <p class="appointment-reminder-note">Os lembretes diários são verificados enquanto o CardioWeb estiver aberto. Notificações do navegador dependem de permissão e o site aberto.</p>
                        <div class="appointment-form-actions">
                            <button type="button" onclick="closeAppointmentModal()">Cancelar</button>
                            <button type="submit">Salvar compromisso</button>
                        </div>
                    </form>
                </section>
            </div>
            <div id="settingsModal" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="settingsTitle" aria-describedby="settingsIntro">
                <section class="modal-content settings-modal-content">
                    <header class="modal-header">
                        <h2 id="settingsTitle"><i class="fas fa-sliders"></i> Configurações</h2>
                        <button type="button" class="modal-close" aria-label="Fechar configurações" onclick="closeSettingsModal()">&times;</button>
                    </header>
                    <div class="modal-body settings-content">
                        <p class="settings-intro" id="settingsIntro"><i class="fas fa-heart-pulse" aria-hidden="true"></i><span>Personalize sua conta e deixe o CardioWeb mais confortável para você.</span></p>
                        <div class="settings-layout">
                            <nav class="settings-tabs" role="tablist" aria-label="Seções das configurações" aria-orientation="horizontal">
                                <button type="button" class="settings-tab" id="settingsTabAccount" role="tab" aria-controls="settingsPanelAccount" aria-selected="true" onclick="showSettingsTab('account')"><i class="fas fa-user"></i> Conta</button>
                                <button type="button" class="settings-tab" id="settingsTabPrivacy" role="tab" aria-controls="settingsPanelPrivacy" aria-selected="false" onclick="showSettingsTab('privacy')"><i class="fas fa-shield-halved"></i> Privacidade</button>
                                <button type="button" class="settings-tab" id="settingsTabAppearance" role="tab" aria-controls="settingsPanelAppearance" aria-selected="false" onclick="showSettingsTab('appearance')"><i class="fas fa-palette"></i> Aparência</button>
                                <button type="button" class="settings-tab" id="settingsTabAbout" role="tab" aria-controls="settingsPanelAbout" aria-selected="false" onclick="showSettingsTab('about')"><i class="fas fa-circle-info"></i> Sobre</button>
                            </nav>
                            <div class="settings-panels">
                        <section class="settings-panel active" id="settingsPanelAccount" role="tabpanel" aria-labelledby="settingsTabAccount">
                            <h3>Dados da conta</h3>
                            <p class="settings-subtitle">As alterações de nome e e-mail são salvas na sua conta.</p>
                            <?php if (!$accountEditingEnabled): ?><p class="settings-subtitle">A conta de demonstração é somente para visualização; edições exigem uma conta cadastrada.</p><?php endif; ?>
                            <form class="account-form profile-form" id="profileSettingsForm" onsubmit="updateAccountProfile(event)">
                                <label>Nome exibido<input id="accountName" name="name" maxlength="150" required value="<?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>"></label>
                                <label>E-mail<input id="accountEmail" name="email" type="email" maxlength="150" required value="<?php echo htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?>"></label>
                                <div class="settings-message" id="profileSettingsMessage" role="status" aria-live="polite"></div>
                                <button class="contact-btn" type="submit" <?php echo $accountEditingEnabled ? '' : 'disabled'; ?>><i class="fas fa-check"></i> Salvar dados</button>
                            </form>
                            <h3 style="margin-top:22px;">Trocar senha</h3>
                            <p class="settings-subtitle">Confirme sua senha atual e escolha uma nova senha com pelo menos 8 caracteres.</p>
                            <form class="account-form password-form" id="passwordSettingsForm" onsubmit="updateAccountPassword(event)">
                                <label>Senha atual<span class="password-input-wrap"><input name="current_password" type="password" autocomplete="current-password" required><button class="password-visibility-toggle" type="button" aria-label="Mostrar senha atual" aria-pressed="false" onclick="togglePasswordVisibility('current_password', this)"><i class="far fa-eye"></i></button></span></label>
                                <label>Nova senha<span class="password-input-wrap"><input name="new_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required aria-describedby="passwordGuidance passwordStrength"><button class="password-visibility-toggle" type="button" aria-label="Mostrar nova senha" aria-pressed="false" onclick="togglePasswordVisibility('new_password', this)"><i class="far fa-eye"></i></button></span></label>
                                <div class="password-strength" id="passwordStrength" data-strength="empty" aria-live="polite">
                                    <span class="password-strength-track" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
                                    <span class="password-strength-label">Sem senha</span>
                                </div>
                                <p class="password-guidance" id="passwordGuidance">Use ao menos 8 caracteres. Misturar letras, números e símbolos ajuda a criar uma senha mais segura.</p>
                                <label>Confirmar nova senha<span class="password-input-wrap"><input name="confirm_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required aria-describedby="passwordMatchMessage"><button class="password-visibility-toggle" type="button" aria-label="Mostrar confirmação da senha" aria-pressed="false" onclick="togglePasswordVisibility('confirm_password', this)"><i class="far fa-eye"></i></button></span></label>
                                <div class="settings-message" id="passwordMatchMessage" role="status" aria-live="polite"></div>
                                <div class="settings-message" id="passwordSettingsMessage" role="status" aria-live="polite"></div>
                                <button class="contact-btn" type="submit" <?php echo $accountEditingEnabled ? '' : 'disabled'; ?>><i class="fas fa-lock"></i> Salvar nova senha</button>
                            </form>
                            <div class="account-details" style="margin-top:18px;">
                                <div class="account-detail"><span>Tipo de conta</span><strong><?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <div class="account-detail"><span>Forma de acesso</span><strong><?php echo htmlspecialchars($login_type, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            </div>
                        </section>
                        <section class="settings-panel" id="settingsPanelPrivacy" role="tabpanel" aria-labelledby="settingsTabPrivacy">
                            <h3>Histórico de conversas</h3>
                            <p class="settings-subtitle">Conversas e imagens ficam salvas neste navegador e são separadas por conta neste dispositivo.</p>
                            <div class="settings-links">
                                <button type="button" class="settings-link" onclick="clearChatHistory()"><i class="fas fa-trash"></i> Apagar histórico deste dispositivo</button>
                                <button type="button" class="settings-link" onclick="exportAccountData()"><i class="fas fa-download"></i> Baixar meus dados</button>
                            </div>
                            <h3>Cookies</h3>
                            <p class="settings-subtitle">Usamos o cookie essencial de sessão para manter seu login. Não usamos cookies de publicidade nem rastreamento.</p>
                            <div class="settings-links">
                                <button type="button" class="settings-link" onclick="saveCookieChoice('accepted')">Aceitar cookies essenciais</button>
                                <button type="button" class="settings-link" onclick="saveCookieChoice('essential')">Recusar cookies opcionais</button>
                            </div>
                            <h3>Privacidade e termos</h3>
                            <div class="settings-links"><button type="button" class="settings-link" onclick="openTermsModal()">Ler informações de uso e privacidade</button></div>
                        </section>
                        <section class="settings-panel" id="settingsPanelAppearance" role="tabpanel" aria-labelledby="settingsTabAppearance">
                            <h3>Aparência e acessibilidade</h3>
                            <p class="settings-subtitle">Ajuste as cores, a leitura e o movimento. Suas preferências ficam salvas neste dispositivo.</p>
                            <div class="settings-theme-card" aria-label="Paleta visual CardioWeb">
                                <span class="settings-theme-icon"><i class="fas fa-heart-pulse" aria-hidden="true"></i></span>
                                <span class="settings-theme-copy"><strong>Identidade CardioWeb</strong><span>Bordô, rosa suave e tons acolhedores em todo o painel.</span></span>
                                <span class="settings-theme-swatches" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
                            </div>
                            <div class="setting-row setting-row-featured">
                                <div><strong>Tema noturno bordô</strong><span>Uma versão escura em tons de vinho e rosa, com leitura confortável.</span></div>
                                <button type="button" class="setting-toggle" id="darkModeToggle" role="switch" aria-checked="false" aria-label="Ativar modo escuro" onclick="toggleDashboardSetting('darkMode')"></button>
                            </div>
                            <div class="setting-row">
                                <div><strong>Alto contraste</strong><span>Realça os limites e os fundos dos cartões.</span></div>
                                <button type="button" class="setting-toggle" id="highContrastToggle" role="switch" aria-checked="false" aria-label="Ativar alto contraste" onclick="toggleDashboardSetting('highContrast')"></button>
                            </div>
                            <div class="setting-row">
                                <div><strong>Tamanho da fonte</strong><span>O texto se ajusta sem travar a navegação.</span></div>
                                <select id="textSizeSetting" aria-label="Tamanho da fonte" onchange="saveDashboardSetting('fontSize', this.value)">
                                    <option value="small">Pequeno</option><option value="medium">Padrão</option><option value="large">Grande</option><option value="xlarge">Muito grande</option>
                                </select>
                            </div>
                            <div class="setting-row">
                                <div><strong>Reduzir animações</strong><span>Desativa movimentos e transições decorativas.</span></div>
                                <button type="button" class="setting-toggle" id="reduceMotionToggle" role="switch" aria-checked="false" aria-label="Reduzir animações" onclick="toggleDashboardSetting('reduceMotion')"></button>
                            </div>
                            <div class="setting-row">
                                <div><strong>Apresentação em celular</strong><span>Exibe o painel em um quadro de smartphone para demonstrações e apresentações.</span></div>
                                <button type="button" class="setting-toggle" id="phonePresentationToggle" role="switch" aria-checked="false" aria-label="Ativar apresentação em celular" onclick="toggleDashboardSetting('phonePresentation')"></button>
                            </div>
                            <footer class="settings-footer"><button type="button" class="contact-btn settings-reset" onclick="resetDashboardSettings()">Restaurar padrão</button></footer>
                        </section>
                        <section class="settings-panel" id="settingsPanelAbout" role="tabpanel" aria-labelledby="settingsTabAbout">
                            <h3>CardioWeb</h3>
                            <p class="settings-subtitle">Versão 1.0 · Plataforma acadêmica de monitoramento cardiovascular.</p>
                            <div class="settings-links">
                                <a class="settings-link" href="mailto:suporte@cardioweb.com"><i class="fas fa-envelope"></i> suporte@cardioweb.com</a>
                                <button type="button" class="settings-link" onclick="closeSettingsModal(); changePage('suporte')"><i class="fas fa-circle-question"></i> Ajuda e perguntas frequentes</button>
                                <button type="button" class="settings-link" onclick="openTermsModal()"><i class="fas fa-file-lines"></i> Termos e privacidade</button>
                            </div>
                            <p class="settings-subtitle">Este sistema é um projeto acadêmico e não substitui avaliação ou orientação de profissionais de saúde.</p>
                        </section>
                            </div>
                        </div>
                        <footer class="settings-footer"><button type="button" class="contact-btn" onclick="closeSettingsModal()">Concluir</button></footer>
                    </div>
                </section>
            </div>
            <div id="termsModal" class="modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="termsTitle">
                <section class="modal-content">
                    <header class="modal-header">
                        <h2 id="termsTitle"><i class="fas fa-shield-heart"></i> Uso e privacidade</h2>
                        <button type="button" class="modal-close" aria-label="Fechar informações" onclick="closeTermsModal()">&times;</button>
                    </header>
                    <div class="modal-body terms-copy">
                        <p>O CardioWeb é um projeto acadêmico demonstrativo. As informações exibidas e as respostas do assistente não são diagnóstico, prescrição nem substituto de atendimento médico.</p>
                        <p>O acesso à conta usa um cookie de sessão essencial. As preferências e o histórico desta demonstração ficam no armazenamento local do navegador; apague-os em Configurações &gt; Privacidade ou encerre a sessão em dispositivos compartilhados.</p>
                        <p>Não insira informações médicas sensíveis nesta demonstração. Para dúvidas sobre a plataforma, fale com <a href="mailto:suporte@cardioweb.com">suporte@cardioweb.com</a>.</p>
                    </div>
                </section>
            </div>
            <div class="dashboard-body">
            <div class="content-area" id="contentArea">
                <!-- Conteúdo dinâmico -->
            </div>

        <!-- MODAL NOVA CONSULTA -->
        <div id="consultaModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-calendar-plus"></i> Agendar Nova Consulta</h2>
                    <button class="modal-close" onclick="closeConsultaModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="consultaForm" onsubmit="agendarConsulta(event)">
                        <div class="form-group">
                            <label><i class="fas fa-user-md"></i> Médico:</label>
                            <select id="medicSelect" required>
                                <option value="">Selecione um médico</option>
                                <option value="Dr. Roberto Mendes|Cardiologia">Dr. Roberto Mendes - Cardiologia</option>
                                <option value="Dra. Aline Costa|Arritmologia">Dra. Aline Costa - Arritmologia</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Data:</label>
                            <input type="date" id="dataConsulta" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Horário:</label>
                            <select id="horaConsulta" required>
                                <option value="">Selecione um horário</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-stethoscope"></i> Tipo de Consulta:</label>
                            <select id="tipoConsulta" required>
                                <option value="">Selecione o tipo</option>
                                <option value="Presencial">Presencial</option>
                                <option value="Online">Online</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-file-alt"></i> Observações (opcional):</label>
                            <textarea id="obsConsulta" placeholder="Descreva os sintomas ou motivo da consulta..."></textarea>
                        </div>
                        <div style="display:flex; gap:10px; margin-top:20px;">
                            <button type="submit" class="contact-btn" style="flex:1;"><i class="fas fa-check"></i> Confirmar Agendamento</button>
                            <button type="button" class="contact-btn" style="flex:1; background:#6c757d;" onclick="closeConsultaModal()"><i class="fas fa-times"></i> Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- CHAT -->
        <div class="chat-sidebar" id="chatSidebar">
            <aside class="chat-navigation" id="chatNavigation" aria-label="Navegação das conversas">
                <div class="chat-navigation-brand"><span><i class="fas fa-heart-pulse"></i></span><strong>CardioWeb</strong></div>
                <button type="button" class="chat-navigation-action" onclick="startNewChat()"><i class="fas fa-pen-to-square"></i> Novo chat</button>
                <button type="button" class="chat-navigation-action" id="chatImagesButton" onclick="showChatImages()"><i class="far fa-images"></i> Imagens</button>
                <div class="chat-recent-heading">Recentes</div>
                <nav class="chat-recent-list" id="chatRecentList" aria-label="Conversas recentes"></nav>
                <p class="chat-navigation-note"><i class="far fa-heart" aria-hidden="true"></i><span>Conversas e imagens ficam salvas neste navegador.</span></p>
            </aside>
            <section class="chat-conversation-panel">
                <div class="chat-header">
                    <button type="button" class="chat-nav-toggle" aria-label="Abrir menu de conversas: novo chat, imagens e recentes" aria-expanded="false" onclick="toggleChatNavigation()"><i class="fas fa-bars"></i></button>
                    <div class="chat-header-identity">
                        <div class="chat-header-avatar"><i class="fas fa-heartbeat"></i></div>
                        <div class="chat-header-copy">
                            <div id="chatTitle">CardioChat</div>
                        </div>
                    </div>
                    <button type="button" class="chat-mobile-close" aria-label="Fechar conversa" onclick="toggleMobileChat(false)"><i class="fas fa-xmark"></i></button>
                </div>
                <section id="chatConversationView" class="chat-conversation-view">
                    <div class="chat-messages" id="chatMessages"></div>
                    <div class="chat-input-area">
                        <div class="chat-actions">
                            <button class="chat-attach" type="button" title="Anexar imagem" aria-label="Anexar imagem" onclick="document.getElementById('chatFileInput').click();"><i class="fas fa-paperclip"></i></button>
                            <input type="file" id="chatFileInput" style="display:none;" accept="image/jpeg,image/png,image/webp" />
                            <input type="text" class="chat-input" id="chatInput" placeholder="Digite sua mensagem..." onkeypress="if(event.key === 'Enter') sendMessage()">
                            <button class="chat-send" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                        </div>
                        <div class="chat-file-preview" id="chatFilePreview">Nenhum arquivo selecionado</div>
                        <p class="chat-ai-disclosure">Mensagens e imagens são enviadas a um serviço externo de IA. O histórico fica neste navegador. Evite dados identificáveis. Não use para emergências.</p>
                    </div>
                </section>
                <section class="chat-images-view" id="chatImagesView" hidden>
                    <header><h3>Imagens</h3><p>Imagens enviadas nas suas conversas.</p></header>
                    <div class="chat-image-library" id="chatImageLibrary"></div>
                </section>
            </section>
        </div>
        <button type="button" class="mobile-chat-toggle" id="mobileChatToggle" aria-label="Abrir conversa com o assistente" aria-expanded="false" aria-controls="chatSidebar" onclick="toggleMobileChat()"><i class="fas fa-heartbeat" aria-hidden="true"></i><span>Chat</span></button>
            </div>
        </div>
    </div>
    <aside class="cookie-banner" id="cookieBanner" aria-label="Preferências de cookies">
        <strong>Privacidade e cookies</strong>
        <p>Usamos somente o cookie essencial para manter sua sessão. Não utilizamos cookies opcionais de publicidade ou rastreamento.</p>
        <div class="cookie-actions">
            <button type="button" onclick="saveCookieChoice('accepted')">Aceitar essenciais</button>
            <button type="button" onclick="saveCookieChoice('essential')">Recusar cookies opcionais</button>
        </div>
    </aside>

    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"></script>
    <script>
        // ========== DADOS DOS ARTIGOS ==========
        const articlesData = <?php echo json_encode(function_exists('getBlogArticles') ? getBlogArticles() : []); ?>;

        // ========== HTML DAS SEÇÕES (vindos do PHP) ==========
        const sectionHtml = {
            'monitoramento': <?php echo json_encode($inicioHtml, JSON_UNESCAPED_UNICODE); ?>,
            'relogio':      <?php echo json_encode($relogioHtml, JSON_UNESCAPED_UNICODE); ?>,
            'blog':         <?php echo json_encode($blogHtml, JSON_UNESCAPED_UNICODE); ?>,
            'exames':       <?php echo json_encode($examesHtml, JSON_UNESCAPED_UNICODE); ?>,
            'informacoes':  <?php echo json_encode($informacoesHtml, JSON_UNESCAPED_UNICODE); ?>,
            'suporte':      <?php echo json_encode($suporteHtml, JSON_UNESCAPED_UNICODE); ?>
        };

        const settingsStoragePrefix = 'cardioweb.dashboard.';
        localStorage.removeItem(`${settingsStoragePrefix}voice`);
        const legacyLargeText = localStorage.getItem(`${settingsStoragePrefix}largeText`) === 'true';
        const dashboardSettings = {
            highContrast: localStorage.getItem(`${settingsStoragePrefix}highContrast`) === 'true',
            darkMode: localStorage.getItem(`${settingsStoragePrefix}darkMode`) === 'true',
            phonePresentation: localStorage.getItem(`${settingsStoragePrefix}phonePresentation`) === 'true',
            fontSize: localStorage.getItem(`${settingsStoragePrefix}fontSize`) || (legacyLargeText ? 'large' : 'medium'),
            reduceMotion: localStorage.getItem(`${settingsStoragePrefix}reduceMotion`) === 'true'
        };
        const chatStorageKey = `cardioweb.chatHistory.<?php echo rawurlencode((string)$user_id); ?>`;
        const chatConversationsStorageKey = `cardioweb.chatConversations.<?php echo rawurlencode((string)$user_id); ?>`;
        const activeChatStorageKey = `cardioweb.activeChat.<?php echo rawurlencode((string)$user_id); ?>`;
        const chatMessagesStoragePrefix = `cardioweb.chatMessages.<?php echo rawurlencode((string)$user_id); ?>.`;
        const chatImageDatabaseName = 'cardioweb-chat-images-<?php echo rawurlencode((string)$user_id); ?>';
        let activeChatId = '';
        let chatViewMode = 'conversation';
        let chatImageDatabasePromise = null;
        const chatObjectUrls = new Set();
        const appointmentStorageKey = `cardioweb.appointments.<?php echo rawurlencode((string)$user_id); ?>`;
        const watchSnapshotKey = `cardioweb.watchSnapshot.<?php echo rawurlencode((string)$user_id); ?>`;
        const healthRecordsStorageKey = `cardioweb.healthRecords.<?php echo rawurlencode((string)$user_id); ?>`;
        const appointmentReminderKey = `cardioweb.appointmentReminders.<?php echo rawurlencode((string)$user_id); ?>`;
        const notificationsReadKey = `cardioweb.notificationsRead.<?php echo rawurlencode((string)$user_id); ?>`;
        let watchClockTimer = null;
        let watchSimulationTimer = null;
        let watchPairTimer = null;
        let lastWatchDatabaseSyncAt = 0;
        let lastWatchDatabaseSyncKey = '';
        let watchDatabaseSyncPending = false;
        let queuedWatchDatabaseValues = null;
        let watchMode = 'idle';
        let watchValues = [];
        let currentWatchMeasurements = {};
        let dashboardInsightReturnFocus = null;
        const fontScaleOriginals = new WeakMap();
        const fontScaleElements = new Set();
        let fontScaleObserver = null;
        let appliedFontSize = null;
        const fontScaleFactors = { small: 0.92, medium: 1, large: 1.18, xlarge: 1.32 };

        function localDateKey(date = new Date()) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        }

        function getAppointments() {
            const appointments = JSON.parse(localStorage.getItem(appointmentStorageKey) || '[]');
            return Array.isArray(appointments)
                ? appointments.filter(item => item && typeof item.id === 'string' && typeof item.title === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(item.date) && /^\d{2}:\d{2}$/.test(item.time))
                : [];
        }

        function saveAppointments(appointments) {
            localStorage.setItem(appointmentStorageKey, JSON.stringify(appointments));
        }

        const healthRecordDefinitions = [
            { id: 'heart-rate', label: 'Batimentos cardíacos (FC)', icon: 'fa-heart-pulse', unit: 'BPM', editable: false, watchOnly: true, initial: '0' },
            { id: 'hrv', label: 'Variabilidade (VFC)', icon: 'fa-wave-square', unit: 'ms', editable: false, watchOnly: true, initial: '—' },
            { id: 'spo2', label: 'Oxigenação (SpO₂)', icon: 'fa-lungs', unit: '%', editable: false, watchOnly: true, initial: '—' },
            { id: 'ecg', label: 'ECG', icon: 'fa-heart-pulse', unit: '', editable: false, watchOnly: true, initial: '—' },
            { id: 'blood-pressure', label: 'Pressão arterial', icon: 'fa-notes-medical', unit: 'mmHg', editable: true, initial: '120/80' },
            { id: 'cholesterol', label: 'Colesterol total', icon: 'fa-droplet', unit: 'mg/dL', editable: true, initial: '180' },
            { id: 'glucose', label: 'Glicemia', icon: 'fa-vial', unit: 'mg/dL', editable: true, initial: '95' }
        ];

        function getHealthRecords() {
            const stored = JSON.parse(localStorage.getItem(healthRecordsStorageKey) || '{}');
            return stored && typeof stored === 'object' && !Array.isArray(stored) ? stored : {};
        }

        async function requestHealthRecords(action, data = {}) {
            const response = await fetch('api/health-records.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ action, ...data })
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.error || 'Não foi possível acessar os registros de saúde.');
            }
            return result;
        }

        async function persistWatchDatabaseValues(values) {
            if (watchDatabaseSyncPending) {
                queuedWatchDatabaseValues = values;
                return;
            }
            watchDatabaseSyncPending = true;
            try {
                await requestHealthRecords('sync_watch', { values });
                lastWatchDatabaseSyncKey = JSON.stringify(values);
                lastWatchDatabaseSyncAt = Date.now();
            } catch (error) {
                lastWatchDatabaseSyncAt = Date.now();
                console.error('CardioWeb watch readings could not be saved:', error);
                const list = document.getElementById('healthRecordList');
                if (list) {
                    const message = document.createElement('p');
                    message.className = 'health-record-error';
                    message.textContent = 'Não foi possível atualizar as leituras do relógio no banco de dados.';
                    list.prepend(message);
                }
            } finally {
                watchDatabaseSyncPending = false;
                const queuedValues = queuedWatchDatabaseValues;
                queuedWatchDatabaseValues = null;
                if (queuedValues && JSON.stringify(queuedValues) !== lastWatchDatabaseSyncKey) {
                    persistWatchDatabaseValues(queuedValues);
                }
            }
        }

        async function loadHealthRecordsFromDatabase() {
            try {
                const legacy = getHealthRecords();
                const snapshot = JSON.parse(localStorage.getItem(watchSnapshotKey) || '{}');
                if (snapshot.updatedAt) {
                    legacy['heart-rate'] = { value: String(Number(snapshot.bpm) || 0) };
                }
                if (snapshot.updatedAt && snapshot.measurements && typeof snapshot.measurements === 'object') {
                    Object.entries(snapshot.measurements).forEach(([recordId, measurement]) => {
                        if (measurement && typeof measurement === 'object') legacy[recordId] = measurement;
                    });
                }
                const result = await requestHealthRecords('load', { legacy });
                localStorage.setItem(healthRecordsStorageKey, JSON.stringify(result.records));
                const watchRecordIds = ['heart-rate', 'hrv', 'spo2', 'ecg'];
                lastWatchDatabaseSyncKey = JSON.stringify(Object.fromEntries(
                    watchRecordIds.map(recordId => [recordId, result.records[recordId]?.value || ''])
                ));
                lastWatchDatabaseSyncAt = Date.now();
                renderHealthRecords();
            } catch (error) {
                console.error('CardioWeb health records could not be loaded:', error);
                const list = document.getElementById('healthRecordList');
                if (list) {
                    const message = document.createElement('p');
                    message.className = 'health-record-error';
                    message.textContent = error instanceof Error ? error.message : 'Não foi possível carregar os registros do banco de dados.';
                    list.prepend(message);
                }
            }
        }

        function renderHealthRecords() {
            const list = document.getElementById('healthRecordList');
            if (!list) return;
            const records = getHealthRecords();
            const snapshot = JSON.parse(localStorage.getItem(watchSnapshotKey) || '{}');
            const watchConnected = Boolean(snapshot.active);
            const measurements = snapshot.measurements && typeof snapshot.measurements === 'object'
                ? snapshot.measurements
                : {};
            list.replaceChildren();
            healthRecordDefinitions.forEach(definition => {
                const measurementReady = definition.id === 'heart-rate'
                    ? Number(snapshot.bpm) > 0
                    : Boolean(measurements[definition.id]);
                const record = definition.id === 'heart-rate'
                    ? {
                        value: String(Number(snapshot.bpm) || 0),
                        source: snapshot.bpm ? 'Relógio · demonstração' : 'Aguardando relógio',
                        updatedAt: snapshot.updatedAt || ''
                    }
                    : definition.watchOnly
                        ? measurements[definition.id] || { value: definition.initial }
                        : records[definition.id] || { value: definition.initial, source: 'Registro editável', updatedAt: '' };
                const row = document.createElement('div');
                row.className = `record-row${definition.watchOnly ? ' watch-record-row' : ''}`;
                row.dataset.recordId = definition.id;
                const name = document.createElement('span');
                name.className = 'record-name';
                const icon = document.createElement('i');
                icon.className = `fas ${definition.icon}`;
                icon.setAttribute('aria-hidden', 'true');
                name.append(icon, document.createTextNode(definition.label));
                const value = document.createElement('strong');
                value.className = 'health-record-value';
                value.textContent = `${record.value}${definition.unit ? ` ${definition.unit}` : ''}`;
                const status = document.createElement('span');
                status.className = `record-status${definition.watchOnly && watchConnected && measurementReady ? ' watch-record-active' : ''}`;
                status.textContent = definition.watchOnly
                    ? !watchConnected && measurementReady
                        ? `Última leitura · ${snapshot.updatedAt ? new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(new Date(snapshot.updatedAt)) : 'salva'}`
                        : watchConnected && measurementReady
                            ? 'Conectado (demo)'
                            : 'Aguardando relógio'
                    : record.updatedAt
                        ? `${record.source || 'Atualizado'} · ${new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(new Date(record.updatedAt))}`
                        : record.source || 'Exemplo editável';
                row.append(name, value, status);
                if (definition.editable) {
                    const edit = document.createElement('button');
                    edit.type = 'button';
                    edit.className = 'record-edit-button';
                    edit.setAttribute('aria-label', `Editar ${definition.label}`);
                    edit.title = `Editar ${definition.label}`;
                    edit.innerHTML = '<i class="fas fa-pen" aria-hidden="true"></i>';
                    edit.addEventListener('click', () => openHealthRecordEditor(definition.id));
                    row.appendChild(edit);
                }
                if (!definition.watchOnly && record.note) {
                    const note = document.createElement('span');
                    note.className = 'record-note';
                    note.textContent = record.note;
                    row.appendChild(note);
                }
                list.appendChild(row);
            });
        }

        function formatHealthRecordDate(value) {
            const date = new Date(value);
            return Number.isNaN(date.getTime())
                ? 'Data indisponível'
                : new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(date);
        }

        async function openHealthHistory() {
            const modal = document.getElementById('healthHistoryModal');
            const content = document.getElementById('healthHistoryContent');
            modal.classList.add('open');
            content.replaceChildren();
            const loading = document.createElement('p');
            loading.className = 'health-history-loading';
            loading.textContent = 'Carregando histórico salvo no banco de dados...';
            content.appendChild(loading);

            try {
                const result = await requestHealthRecords('history');
                const currentHeading = document.createElement('h3');
                currentHeading.textContent = 'Valores atuais';
                const currentList = document.createElement('div');
                currentList.className = 'health-history-current';
                healthRecordDefinitions.forEach(definition => {
                    const record = result.records[definition.id];
                    if (!record) return;
                    const item = document.createElement('div');
                    item.className = 'health-history-current-item';
                    const name = document.createElement('strong');
                    name.textContent = definition.label;
                    const value = document.createElement('span');
                    value.textContent = `${record.value}${definition.unit ? ` ${definition.unit}` : ''}`;
                    const date = document.createElement('small');
                    date.textContent = record.updatedAt ? `Atualizado em ${formatHealthRecordDate(record.updatedAt)}` : 'Valor inicial';
                    item.append(name, value, date);
                    if (record.note) {
                        const note = document.createElement('small');
                        note.textContent = `Observação: ${record.note}`;
                        item.appendChild(note);
                    }
                    currentList.appendChild(item);
                });

                const changesHeading = document.createElement('h3');
                changesHeading.textContent = 'Alterações anteriores';
                const changesList = document.createElement('div');
                changesList.className = 'health-history-changes';
                if (!result.history.length) {
                    const empty = document.createElement('p');
                    empty.className = 'health-history-empty';
                    empty.textContent = 'Ainda não há alterações registradas. Novas edições ficarão salvas aqui.';
                    changesList.appendChild(empty);
                } else {
                    result.history.forEach(change => {
                        const item = document.createElement('article');
                        item.className = 'health-history-change';
                        const heading = document.createElement('strong');
                        heading.textContent = change.label;
                        const date = document.createElement('time');
                        date.dateTime = change.changedAt;
                        date.textContent = formatHealthRecordDate(change.changedAt);
                        const values = document.createElement('p');
                        values.textContent = `${change.oldValue}${change.unit ? ` ${change.unit}` : ''} → ${change.newValue}${change.unit ? ` ${change.unit}` : ''}`;
                        item.append(heading, date, values);
                        if (change.oldNote || change.newNote) {
                            const notes = document.createElement('small');
                            notes.textContent = `Observações: ${change.oldNote || '—'} → ${change.newNote || '—'}`;
                            item.appendChild(notes);
                        }
                        changesList.appendChild(item);
                    });
                }
                content.replaceChildren(currentHeading, currentList, changesHeading, changesList);
            } catch (error) {
                console.error('CardioWeb health history could not be loaded:', error);
                const message = document.createElement('p');
                message.className = 'health-history-error';
                message.textContent = error instanceof Error ? error.message : 'Não foi possível carregar o histórico do banco de dados.';
                content.replaceChildren(message);
            }
        }

        function closeHealthHistory() {
            document.getElementById('healthHistoryModal')?.classList.remove('open');
        }

        function openDashboardInsight(insight) {
            const definitions = {
                records: {
                    icon: 'fa-wave-square',
                    title: 'Registros de saúde',
                    subtitle: 'Indicadores e alterações da sua conta'
                },
                'heart-rate': {
                    icon: 'fa-heart-pulse',
                    title: 'Frequência cardíaca',
                    subtitle: 'Leitura demonstrativa do relógio'
                },
                appointments: {
                    icon: 'fa-calendar-days',
                    title: 'Próximos compromissos',
                    subtitle: 'Lembretes salvos neste navegador'
                },
                goal: {
                    icon: 'fa-trophy',
                    title: 'Meta de saúde',
                    subtitle: 'Informação demonstrativa do painel'
                }
            };
            const definition = definitions[insight];
            if (!definition) return;

            const modal = document.getElementById('dashboardInsightModal');
            const content = document.getElementById('dashboardInsightContent');
            const actions = document.getElementById('dashboardInsightActions');
            document.getElementById('dashboardInsightIcon').innerHTML = `<i class="fas ${definition.icon}"></i>`;
            document.getElementById('dashboardInsightTitle').textContent = definition.title;
            document.getElementById('dashboardInsightSubtitle').textContent = definition.subtitle;
            content.replaceChildren();
            actions.replaceChildren();
            dashboardInsightReturnFocus = document.activeElement;

            const addParagraph = (text, className = '') => {
                const paragraph = document.createElement('p');
                paragraph.textContent = text;
                if (className) paragraph.className = className;
                content.appendChild(paragraph);
                return paragraph;
            };
            const addAction = (label, callback, primary = false) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                if (primary) button.classList.add('primary');
                button.addEventListener('click', () => {
                    closeDashboardInsight();
                    callback();
                });
                actions.appendChild(button);
            };

            if (insight === 'records') {
                addParagraph('Acompanhe os indicadores registrados e consulte os valores atuais e as alterações salvas na sua conta.');
                addParagraph('Os dados do relógio são demonstrativos; os demais valores podem ser atualizados na lista de registros.', 'dashboard-insight-note');
                addAction('Ver histórico completo', openHealthHistory, true);
            } else if (insight === 'heart-rate') {
                const snapshot = JSON.parse(localStorage.getItem(watchSnapshotKey) || '{}');
                const hasReading = Number.isFinite(Number(snapshot.bpm)) && Number(snapshot.bpm) > 0;
                const reading = document.createElement('div');
                reading.className = 'dashboard-insight-reading';
                const value = document.createElement('strong');
                value.textContent = hasReading ? String(Number(snapshot.bpm)) : '—';
                const unit = document.createElement('span');
                unit.textContent = hasReading ? 'BPM · última leitura' : 'Sem leitura disponível';
                reading.append(value, unit);
                content.appendChild(reading);
                if (snapshot.updatedAt && !Number.isNaN(Date.parse(snapshot.updatedAt))) {
                    addParagraph(`Atualizado em ${new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(snapshot.updatedAt))}.`);
                } else {
                    addParagraph('Abra o relógio cardíaco para iniciar uma leitura de demonstração.');
                }
                addParagraph('Este valor não é uma medição clínica e não deve ser usado para avaliar sintomas.', 'dashboard-insight-note');
                addAction('Abrir relógio cardíaco', () => changePage('relogio'), true);
            } else if (insight === 'appointments') {
                const now = new Date();
                const appointments = getAppointments()
                    .map(item => ({ ...item, dateTime: new Date(`${item.date}T${item.time}:00`) }))
                    .filter(item => item.date >= localDateKey(now) && !Number.isNaN(item.dateTime.getTime()))
                    .sort((a, b) => a.dateTime - b.dateTime);
                if (appointments.length) {
                    const list = document.createElement('div');
                    list.className = 'dashboard-insight-list';
                    appointments.forEach(item => {
                        const row = document.createElement('article');
                        row.className = 'dashboard-insight-appointment';
                        const date = document.createElement('time');
                        date.className = 'dashboard-insight-date';
                        date.dateTime = `${item.date}T${item.time}`;
                        const dateValue = document.createElement('strong');
                        dateValue.textContent = new Intl.DateTimeFormat('pt-BR', { day: '2-digit' }).format(item.dateTime);
                        const month = document.createElement('span');
                        month.textContent = new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(item.dateTime).replace('.', '');
                        date.append(dateValue, month);
                        const details = document.createElement('div');
                        const title = document.createElement('strong');
                        title.textContent = item.title;
                        const when = document.createElement('small');
                        when.textContent = `${new Intl.DateTimeFormat('pt-BR', { weekday: 'short' }).format(item.dateTime)} · ${item.time}${item.location ? ` · ${item.location}` : ''}`;
                        details.append(title, when);
                        row.append(date, details);
                        list.appendChild(row);
                    });
                    content.appendChild(list);
                } else {
                    addParagraph('Você ainda não tem compromissos futuros. Crie um lembrete para organizar consultas, exames ou outros cuidados.');
                }
                addAction('Abrir agenda', () => changePage('relogio'));
                addAction('Adicionar lembrete', openAppointmentModal, true);
            } else {
                const progress = document.createElement('div');
                progress.className = 'dashboard-insight-reading';
                const value = document.createElement('strong');
                value.textContent = '85%';
                const label = document.createElement('span');
                label.textContent = 'valor ilustrativo';
                progress.append(value, label);
                content.appendChild(progress);
                addParagraph('Este percentual é apenas visual e não é calculado com seus dados. O CardioWeb não avalia risco cardiovascular nem define metas clínicas.');
                addParagraph('Use os registros para organizar informações e converse com um profissional de saúde sobre metas adequadas para você.', 'dashboard-insight-note');
                addAction('Ver meus registros', openHealthHistory, true);
            }

            modal.classList.add('open');
            modal.querySelector('.appointment-modal-close').focus();
        }

        function closeDashboardInsight() {
            const modal = document.getElementById('dashboardInsightModal');
            if (modal) modal.classList.remove('open');
            if (dashboardInsightReturnFocus instanceof HTMLElement && dashboardInsightReturnFocus.isConnected) {
                dashboardInsightReturnFocus.focus();
            }
            dashboardInsightReturnFocus = null;
        }

        function openHealthRecordEditor(recordId) {
            const definition = healthRecordDefinitions.find(item => item.id === recordId);
            if (!definition || !definition.editable) return;
            const existing = getHealthRecords()[recordId] || {
                value: definition.initial,
                note: '',
                source: 'Registro editável',
                updatedAt: ''
            };
            document.getElementById('healthRecordId').value = recordId;
            document.getElementById('healthRecordModalTitle').textContent = `Editar ${definition.label}`;
            document.getElementById('healthRecordValueLabel').firstChild.textContent = `Valor${definition.unit ? ` (${definition.unit})` : ''}`;
            const input = document.getElementById('healthRecordInput');
            input.value = existing.value === '—' ? '' : existing.value;
            input.setCustomValidity('');
            input.type = ['cholesterol', 'glucose', 'hrv', 'spo2'].includes(definition.id) ? 'number' : 'text';
            input.step = definition.id === 'hrv' ? '0.1' : '1';
            input.min = definition.id === 'spo2' ? '50' : definition.id === 'hrv' ? '1' : '0';
            input.max = definition.id === 'spo2' ? '100' : definition.id === 'hrv' ? '300' : definition.id === 'cholesterol' ? '1000' : definition.id === 'glucose' ? '2000' : '';
            input.placeholder = definition.id === 'blood-pressure' ? 'Ex.: 120/80' : definition.id === 'ecg' ? 'Ex.: Registro anotado' : 'Digite o valor';
            document.getElementById('healthRecordNote').value = existing.note || '';
            document.getElementById('healthRecordModal').classList.add('open');
            document.getElementById('healthRecordInput').focus();
        }

        function closeHealthRecordModal() {
            document.getElementById('healthRecordModal')?.classList.remove('open');
        }

        async function saveHealthRecord(event) {
            event.preventDefault();
            const recordId = document.getElementById('healthRecordId').value;
            const definition = healthRecordDefinitions.find(item => item.id === recordId);
            const value = document.getElementById('healthRecordInput').value.trim();
            if (!definition || !definition.editable || !value) return;
            if (definition.id === 'blood-pressure' && !/^\d{2,3}\s*\/\s*\d{2,3}$/.test(value)) {
                document.getElementById('healthRecordInput').setCustomValidity('Informe a pressão no formato 120/80.');
                document.getElementById('healthRecordInput').reportValidity();
                return;
            }
            if (definition.id !== 'blood-pressure' && definition.id !== 'ecg') {
                const numericValue = Number(value);
                const minimum = definition.id === 'spo2' ? 50 : definition.id === 'hrv' ? 1 : 0;
                const maximum = definition.id === 'spo2' ? 100 : definition.id === 'hrv' ? 300 : definition.id === 'cholesterol' ? 1000 : 2000;
                if (!Number.isFinite(numericValue) || numericValue < minimum || numericValue > maximum) {
                    const input = document.getElementById('healthRecordInput');
                    input.setCustomValidity(`Informe um valor entre ${minimum} e ${maximum}.`);
                    input.reportValidity();
                    return;
                }
            }
            document.getElementById('healthRecordInput').setCustomValidity('');
            const submit = document.querySelector('#healthRecordForm button[type="submit"]');
            submit.disabled = true;
            try {
                const result = await requestHealthRecords('update', {
                    recordId,
                    value,
                    note: document.getElementById('healthRecordNote').value.trim()
                });
                const records = getHealthRecords();
                records[recordId] = result.records[recordId];
                localStorage.setItem(healthRecordsStorageKey, JSON.stringify(records));
                closeHealthRecordModal();
                renderHealthRecords();
            } catch (error) {
                console.error('CardioWeb health record could not be saved:', error);
                alert(error instanceof Error ? error.message : 'Não foi possível salvar o registro no banco de dados.');
            } finally {
                submit.disabled = false;
            }
        }

        function renderAppointments() {
            const list = document.getElementById('appointmentList');
            if (!list) return;
            const appointments = getAppointments().sort((a, b) => `${a.date}T${a.time}`.localeCompare(`${b.date}T${b.time}`));
            const now = new Date();
            const today = localDateKey(now);
            const upcomingCount = appointments.filter(item => item.date >= today).length;
            const count = document.getElementById('appointmentCount');
            const readOnly = list.closest('#watchAgenda') !== null;
            if (count) count.textContent = String(upcomingCount);
            list.replaceChildren();
            if (!appointments.length) {
                const empty = document.createElement('p');
                empty.className = 'appointment-empty';
                empty.textContent = readOnly
                    ? 'Você ainda não tem lembretes na agenda do relógio.'
                    : 'Sua agenda está vazia. Adicione uma consulta ou exame para organizar seus compromissos.';
                list.appendChild(empty);
                return;
            }
            appointments.forEach(item => {
                const row = document.createElement('article');
                row.className = 'appointment-row';
                const day = document.createElement('time');
                day.dateTime = `${item.date}T${item.time}`;
                const date = new Date(`${item.date}T12:00:00`);
                const dayNumber = document.createElement('strong');
                dayNumber.textContent = String(date.getDate()).padStart(2, '0');
                const month = document.createElement('span');
                month.textContent = new Intl.DateTimeFormat('pt-BR', { month: 'short' }).format(date).replace('.', '').toUpperCase();
                day.append(dayNumber, month);
                const details = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = item.title;
                const place = document.createElement('span');
                place.innerHTML = '<i class="far fa-clock" aria-hidden="true"></i> ';
                place.append(document.createTextNode(`${item.time}${item.location ? ` · ${item.location}` : ''}`));
                details.append(title, place);
                if (item.notes) {
                    const notes = document.createElement('span');
                    notes.className = 'appointment-notes';
                    notes.textContent = item.notes;
                    details.appendChild(notes);
                }
                row.append(day, details);
                if (!readOnly) {
                    const actions = document.createElement('div');
                    actions.className = 'appointment-actions';
                    const editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'appointment-action';
                    editButton.setAttribute('aria-label', `Editar ${item.title}`);
                    editButton.title = 'Editar';
                    editButton.innerHTML = '<i class="fas fa-pen" aria-hidden="true"></i>';
                    editButton.addEventListener('click', () => editAppointment(item.id));
                    const deleteButton = document.createElement('button');
                    deleteButton.type = 'button';
                    deleteButton.className = 'appointment-action delete';
                    deleteButton.setAttribute('aria-label', `Excluir ${item.title}`);
                    deleteButton.title = 'Excluir';
                    deleteButton.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
                    deleteButton.addEventListener('click', () => deleteAppointment(item.id));
                    actions.append(editButton, deleteButton);
                    row.appendChild(actions);
                }
                list.appendChild(row);
            });
        }

        function openAppointmentModal(id = '') {
            const modal = document.getElementById('appointmentModal');
            const form = document.getElementById('appointmentForm');
            const appointment = id ? getAppointments().find(item => item.id === id) : null;
            if (id && !appointment) return;
            form.reset();
            document.getElementById('appointmentId').value = appointment ? appointment.id : '';
            document.getElementById('appointmentModalTitle').textContent = appointment ? 'Editar compromisso' : 'Adicionar compromisso';
            document.getElementById('appointmentTitle').value = appointment ? appointment.title : '';
            document.getElementById('appointmentDate').value = appointment ? appointment.date : localDateKey();
            document.getElementById('appointmentTime').value = appointment ? appointment.time : '09:00';
            document.getElementById('appointmentLocation').value = appointment ? appointment.location || '' : '';
            document.getElementById('appointmentNotes').value = appointment ? appointment.notes || '' : '';
            modal.classList.add('open');
            document.getElementById('appointmentTitle').focus();
        }

        function closeAppointmentModal() {
            const modal = document.getElementById('appointmentModal');
            if (modal) modal.classList.remove('open');
        }

        async function saveAppointment(event) {
            event.preventDefault();
            const id = document.getElementById('appointmentId').value;
            const appointment = {
                id: id || (window.crypto && typeof window.crypto.randomUUID === 'function' ? window.crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2)}`),
                title: document.getElementById('appointmentTitle').value.trim(),
                date: document.getElementById('appointmentDate').value,
                time: document.getElementById('appointmentTime').value,
                location: document.getElementById('appointmentLocation').value.trim(),
                notes: document.getElementById('appointmentNotes').value.trim()
            };
            if (!appointment.title || !appointment.date || !appointment.time) return;
            const appointments = getAppointments();
            const index = appointments.findIndex(item => item.id === id);
            if (index >= 0) appointments[index] = appointment;
            else appointments.push(appointment);
            saveAppointments(appointments);
            closeAppointmentModal();
            renderAppointments();
            checkAppointmentReminders();
            if (!id) {
                const currentPage = new URL(window.location.href).searchParams.get('page');
                if (currentPage !== 'relogio') changePage('relogio');
                window.requestAnimationFrame(() => {
                    document.getElementById('watchAgenda')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }
            if (!id && typeof Notification === 'function' && Notification.permission === 'default') {
                await Notification.requestPermission();
                checkAppointmentReminders();
            }
        }

        function editAppointment(id) {
            openAppointmentModal(id);
        }

        function deleteAppointment(id) {
            const appointment = getAppointments().find(item => item.id === id);
            if (!appointment || !window.confirm(`Excluir "${appointment.title}" da agenda?`)) return;
            saveAppointments(getAppointments().filter(item => item.id !== id));
            renderAppointments();
            checkAppointmentReminders();
        }

        function checkAppointmentReminders() {
            const now = new Date();
            const today = localDateKey(now);
            const reminderState = JSON.parse(localStorage.getItem(appointmentReminderKey) || '{}');
            const inAppReminders = [];
            getAppointments().forEach(item => {
                const scheduled = new Date(`${item.date}T${item.time}:00`);
                if (Number.isNaN(scheduled.getTime()) || scheduled <= now || item.date < today) return;
                const reminder = reminderState[item.id] || {};
                const dateLabel = new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'long' }).format(new Date(`${item.date}T12:00:00`));
                const daysRemaining = Math.max(0, Math.round((new Date(`${item.date}T12:00:00`) - new Date(`${today}T12:00:00`)) / 86400000));
                inAppReminders.push({ title: item.title, date: dateLabel, time: item.time, daysRemaining });
                if (typeof Notification === 'function' && Notification.permission === 'granted' && reminder.browserDate !== today) {
                    new Notification('Lembrete do CardioWeb', {
                        body: `${item.title} · ${daysRemaining === 0 ? 'hoje' : `em ${daysRemaining} ${daysRemaining === 1 ? 'dia' : 'dias'}`} às ${item.time}.`,
                        tag: `cardioweb-appointment-${item.id}-${today}`
                    });
                    reminder.browserDate = today;
                }
                reminderState[item.id] = reminder;
            });
            localStorage.setItem(appointmentReminderKey, JSON.stringify(reminderState));
            renderAppointmentNotifications(inAppReminders);
        }

        function renderAppointmentNotifications(reminders) {
            const watchAlert = document.getElementById('watchAppointmentAlert');
            if (watchAlert) {
                watchAlert.replaceChildren();
                watchAlert.hidden = !reminders.length;
                if (reminders.length) {
                    const heading = document.createElement('div');
                    heading.className = 'watch-alert-heading';
                    heading.innerHTML = '<i class="fas fa-bell" aria-hidden="true"></i> Notificações da agenda';
                    watchAlert.appendChild(heading);
                    reminders.forEach(reminder => {
                        const item = document.createElement('article');
                        item.className = 'watch-alert-item';
                        const details = document.createElement('div');
                        const title = document.createElement('strong');
                        title.textContent = reminder.title;
                        const time = document.createElement('span');
                        time.textContent = `${reminder.date} · ${reminder.time}`;
                        details.append(title, time);
                        const tag = document.createElement('span');
                        tag.className = 'watch-alert-tag';
                        tag.textContent = reminder.daysRemaining === 0
                            ? 'Hoje'
                            : `Em ${reminder.daysRemaining} ${reminder.daysRemaining === 1 ? 'dia' : 'dias'}`;
                        item.append(details, tag);
                        watchAlert.appendChild(item);
                    });
                }
            }
            const list = document.getElementById('notificationList');
            if (!list) return;
            const button = document.getElementById('notificationsButton');
            if (button) button.classList.toggle('read', !reminders.length || localStorage.getItem(notificationsReadKey) === localDateKey());
            list.replaceChildren();
            if (!reminders.length) {
                const empty = document.createElement('p');
                empty.className = 'notification-empty';
                empty.textContent = 'Você não tem lembretes de agenda para hoje.';
                list.appendChild(empty);
                return;
            }
            reminders.forEach(reminder => {
                const item = document.createElement('article');
                item.className = 'notification-item';
                const mark = document.createElement('span');
                mark.className = 'notification-mark';
                mark.innerHTML = '<i class="fas fa-calendar-check" aria-hidden="true"></i>';
                const content = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = reminder.title;
                const description = document.createElement('p');
                description.textContent = `Compromisso ${reminder.daysRemaining === 0 ? 'hoje' : `em ${reminder.daysRemaining} ${reminder.daysRemaining === 1 ? 'dia' : 'dias'}`} · ${reminder.date}, às ${reminder.time}.`;
                const time = document.createElement('span');
                time.className = 'notification-time';
                time.textContent = 'Lembrete diário · Agenda';
                content.append(title, description, time);
                item.append(mark, content);
                list.appendChild(item);
            });
        }

        function syncWatchSummary(active, bpm = 0) {
            const updatedAt = new Date().toISOString();
            const snapshot = { active, bpm: Math.max(0, bpm), measurements: currentWatchMeasurements, updatedAt };
            localStorage.setItem(watchSnapshotKey, JSON.stringify(snapshot));
            const watchDatabaseValues = {
                'heart-rate': String(snapshot.bpm),
                hrv: snapshot.measurements.hrv?.value || '—',
                spo2: snapshot.measurements.spo2?.value || '—',
                ecg: snapshot.measurements.ecg?.value || '—'
            };
            const watchDatabaseKey = JSON.stringify(watchDatabaseValues);
            if (watchDatabaseKey !== lastWatchDatabaseSyncKey
                && (!active || Date.now() - lastWatchDatabaseSyncAt >= 30000)) {
                persistWatchDatabaseValues(watchDatabaseValues);
            }
            if (active && Object.keys(currentWatchMeasurements).length) {
                const records = getHealthRecords();
                Object.entries(currentWatchMeasurements).forEach(([recordId, measurement]) => {
                    records[recordId] = {
                        value: measurement.value,
                        note: 'Leitura simulada do relógio; não é uma medição clínica.',
                        source: 'Relógio · demonstração',
                        updatedAt
                    };
                });
                localStorage.setItem(healthRecordsStorageKey, JSON.stringify(records));
            }
            renderHealthRecords();
            const value = document.getElementById('watchRecordValue');
            const status = document.getElementById('watchRecordStatus');
            const summary = document.getElementById('watchSummaryBpm');
            if (value) value.textContent = `${snapshot.bpm} BPM`;
            if (summary) summary.textContent = String(snapshot.bpm);
            if (status) {
                const time = new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(new Date(snapshot.updatedAt));
                status.textContent = active ? 'Conectado (demo)' : `Desconectado · ${time}`;
            }
            const row = document.getElementById('watchRecordRow');
            if (row) row.classList.toggle('watch-record-active', active);
        }

        function renderWatchSummary() {
            const snapshot = JSON.parse(localStorage.getItem(watchSnapshotKey) || '{"active":false,"bpm":0}');
            if (snapshot.active && watchMode === 'idle') {
                snapshot.active = false;
                localStorage.setItem(watchSnapshotKey, JSON.stringify(snapshot));
            }
            currentWatchMeasurements = snapshot.measurements && typeof snapshot.measurements === 'object' ? snapshot.measurements : {};
            const active = Boolean(snapshot.active) && watchMode !== 'idle';
            const bpm = Number.isFinite(Number(snapshot.bpm)) ? Number(snapshot.bpm) : 0;
            const value = document.getElementById('watchRecordValue');
            const status = document.getElementById('watchRecordStatus');
            const summary = document.getElementById('watchSummaryBpm');
            if (value) value.textContent = `${bpm} BPM`;
            if (summary) summary.textContent = String(bpm);
            if (status) {
                const lastUpdated = snapshot.updatedAt ? new Date(snapshot.updatedAt) : new Date();
                const time = new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(lastUpdated);
                status.textContent = active ? 'Conectado (demo)' : `Desconectado · ${time}`;
            }
            const row = document.getElementById('watchRecordRow');
            if (row) row.classList.toggle('watch-record-active', active);
            renderHealthRecords();
        }

        function updateWatchClock() {
            const clock = document.getElementById('watchClock');
            const date = document.getElementById('watchDate');
            if (!clock || !date) return;
            const now = new Date();
            clock.dateTime = now.toISOString();
            clock.textContent = new Intl.DateTimeFormat('pt-BR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }).format(now);
            date.textContent = new Intl.DateTimeFormat('pt-BR', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            }).format(now);
        }

        function setWatchStatus(mode, label) {
            const status = document.getElementById('watchStatus');
            if (!status) return;
            status.className = `watch-status ${mode}`;
            status.querySelector('span').textContent = label;
        }

        function drawWatchChart() {
            const canvas = document.getElementById('watchChart');
            if (!canvas) return;
            const width = canvas.clientWidth;
            const height = canvas.clientHeight;
            if (!width || !height) return;
            const ratio = window.devicePixelRatio || 1;
            canvas.width = Math.round(width * ratio);
            canvas.height = Math.round(height * ratio);
            const context = canvas.getContext('2d');
            if (!context) return;
            context.scale(ratio, ratio);
            context.clearRect(0, 0, width, height);
            context.strokeStyle = '#f0e6e9';
            context.lineWidth = 1;
            for (let line = 1; line < 4; line++) {
                const y = Math.round((height / 4) * line) + .5;
                context.beginPath();
                context.moveTo(0, y);
                context.lineTo(width, y);
                context.stroke();
            }
            if (watchValues.length < 2) return;
            const min = Math.min(...watchValues) - 8;
            const max = Math.max(...watchValues) + 8;
            const range = Math.max(max - min, 20);
            const points = watchValues.map((value, index) => ({
                x: (width * index) / (watchValues.length - 1),
                y: height - 8 - ((value - min) / range) * (height - 16)
            }));
            const fill = context.createLinearGradient(0, 0, 0, height);
            fill.addColorStop(0, 'rgba(137,27,52,.2)');
            fill.addColorStop(1, 'rgba(137,27,52,0)');
            context.beginPath();
            context.moveTo(points[0].x, height);
            points.forEach(point => context.lineTo(point.x, point.y));
            context.lineTo(points[points.length - 1].x, height);
            context.closePath();
            context.fillStyle = fill;
            context.fill();
            context.beginPath();
            points.forEach((point, index) => {
                if (index === 0) context.moveTo(point.x, point.y);
                else context.lineTo(point.x, point.y);
            });
            context.strokeStyle = '#8c1d38';
            context.lineWidth = 2.5;
            context.lineJoin = 'round';
            context.lineCap = 'round';
            context.stroke();
        }

        function updateHeartReading(bpm, description, sourceLabel) {
            const value = document.getElementById('watchBpm');
            const context = document.getElementById('watchContext');
            const heart = document.getElementById('watchHeart');
            const source = document.getElementById('watchSource');
            if (!value || !context || !heart || !source) return;
            value.textContent = String(bpm);
            context.textContent = description;
            source.textContent = sourceLabel;
            heart.classList.add('beating');
            watchValues.push(bpm);
            if (watchValues.length > 36) watchValues.shift();
            currentWatchMeasurements = {
                hrv: { value: String(35 + Math.floor(Math.random() * 46)), source: 'Relógio · demonstração' },
                spo2: { value: String(95 + Math.floor(Math.random() * 6)), source: 'Relógio · demonstração' },
                ecg: { value: 'Traçado ilustrativo', source: 'Relógio · demonstração' }
            };
            const hrvValue = document.getElementById('watchHrvValue');
            const spo2Value = document.getElementById('watchSpo2Value');
            const ecgValue = document.getElementById('watchEcgValue');
            if (hrvValue) hrvValue.textContent = `${currentWatchMeasurements.hrv.value} ms`;
            if (spo2Value) spo2Value.textContent = `${currentWatchMeasurements.spo2.value}%`;
            if (ecgValue) ecgValue.textContent = currentWatchMeasurements.ecg.value;
            drawWatchChart();
            syncWatchSummary(watchMode !== 'idle', bpm);
        }

        function resetWatchReading(description) {
            const value = document.getElementById('watchBpm');
            const context = document.getElementById('watchContext');
            const heart = document.getElementById('watchHeart');
            const lastBpm = Number(value ? value.textContent : 0) || 0;
            if (value) value.textContent = '0';
            if (context) context.textContent = description;
            if (heart) heart.classList.remove('beating');
            const hrvValue = document.getElementById('watchHrvValue');
            const spo2Value = document.getElementById('watchSpo2Value');
            const ecgValue = document.getElementById('watchEcgValue');
            if (hrvValue) hrvValue.textContent = '—';
            if (spo2Value) spo2Value.textContent = '—';
            if (ecgValue) ecgValue.textContent = '—';
            watchValues = [];
            drawWatchChart();
            syncWatchSummary(false, lastBpm);
        }

        function startHeartDemo(initialBpm = 78, sourceLabel = 'Cenário de demonstração ativo') {
            clearInterval(watchSimulationTimer);
            watchMode = 'simulation';
            const pauseButton = document.getElementById('watchPauseButton');
            if (!pauseButton) return;
            pauseButton.disabled = false;
            setWatchStatus('active', 'Simulação ativa');
            const connectButton = document.getElementById('watchConnectButton');
            if (connectButton) {
                connectButton.innerHTML = '<i class="fab fa-bluetooth-b"></i> Conectar';
                connectButton.setAttribute('aria-label', 'Conectar dispositivo demonstrativo');
            }
            let bpm = initialBpm;
            updateHeartReading(bpm, 'Valor aleatório ilustrativo; não é uma medição real.', sourceLabel);
            watchSimulationTimer = window.setInterval(() => {
                bpm = Math.max(50, Math.min(145, bpm + Math.round(Math.random() * 6) - 3));
                updateHeartReading(bpm, 'Valor aleatório ilustrativo; não é uma medição real.', sourceLabel);
            }, 1100);
        }

        function setHeartScenario(scenario) {
            const sampleValues = { repouso: 62, rotina: 78, atividade: 118 };
            const initialBpm = sampleValues[scenario];
            if (typeof initialBpm !== 'number') return;
            const scenarioNames = { repouso: 'Cenário repouso', rotina: 'Cenário rotina leve', atividade: 'Cenário atividade' };
            startHeartDemo(initialBpm, scenarioNames[scenario]);
        }

        function connectHeartSensor() {
            if (watchMode === 'demo-sensor') {
                disconnectDemoHeartSensor();
                return;
            }
            const modal = document.getElementById('watchConnectionModal');
            const name = document.getElementById('watchDemoDeviceName');
            const status = document.getElementById('watchDemoDeviceStatus');
            const pairButton = document.getElementById('watchDemoPairButton');
            if (!modal || !name || !status || !pairButton) return;
            const deviceId = Math.random().toString(36).slice(2, 6).toUpperCase();
            name.textContent = `CardioWeb Pulse ${deviceId}`;
            status.textContent = 'Disponível para demonstração';
            pairButton.disabled = false;
            pairButton.innerHTML = '<i class="fab fa-bluetooth-b"></i> Conectar demonstração';
            modal.style.display = 'flex';
            pairButton.focus();
        }

        function closeWatchConnectionModal(cancelPairing = true) {
            const modal = document.getElementById('watchConnectionModal');
            if (modal) modal.style.display = 'none';
            if (cancelPairing && watchPairTimer) {
                clearTimeout(watchPairTimer);
                watchPairTimer = null;
                const status = document.getElementById('watchDemoDeviceStatus');
                const pairButton = document.getElementById('watchDemoPairButton');
                if (status) status.textContent = 'Disponível para demonstração';
                if (pairButton) {
                    pairButton.disabled = false;
                    pairButton.innerHTML = '<i class="fab fa-bluetooth-b"></i> Conectar demonstração';
                }
            }
        }

        function pairDemoHeartSensor() {
            const status = document.getElementById('watchDemoDeviceStatus');
            const pairButton = document.getElementById('watchDemoPairButton');
            if (!status || !pairButton || watchPairTimer) return;
            status.textContent = 'Conectando dispositivo demonstrativo…';
            pairButton.disabled = true;
            pairButton.textContent = 'Conectando…';
            watchPairTimer = window.setTimeout(() => {
                watchPairTimer = null;
                closeWatchConnectionModal(false);
                clearInterval(watchSimulationTimer);
                watchMode = 'demo-sensor';
                const initialBpm = 65 + Math.floor(Math.random() * 31);
                const deviceName = document.getElementById('watchDemoDeviceName')?.textContent || 'dispositivo demonstrativo';
                const source = `Dispositivo demonstrativo aleatório: ${deviceName}`;
                setWatchStatus('connected', 'Conectado (demo)');
                const pauseButton = document.getElementById('watchPauseButton');
                const connectButton = document.getElementById('watchConnectButton');
                if (pauseButton) pauseButton.disabled = false;
                if (connectButton) {
                    connectButton.innerHTML = '<i class="fas fa-link-slash"></i> Desconectar';
                    connectButton.setAttribute('aria-label', 'Desconectar dispositivo demonstrativo');
                }
                updateHeartReading(initialBpm, 'Leitura aleatória de demonstração; nenhum sensor real está conectado.', source);
                let bpm = initialBpm;
                watchSimulationTimer = window.setInterval(() => {
                    bpm = Math.max(55, Math.min(125, bpm + Math.floor(Math.random() * 7) - 3));
                    updateHeartReading(bpm, 'Leitura aleatória de demonstração; nenhum sensor real está conectado.', source);
                }, 1100);
            }, 650);
        }

        function disconnectDemoHeartSensor() {
            clearInterval(watchSimulationTimer);
            watchSimulationTimer = null;
            watchMode = 'idle';
            const pauseButton = document.getElementById('watchPauseButton');
            if (pauseButton) pauseButton.disabled = true;
            const connectButton = document.getElementById('watchConnectButton');
            if (connectButton) {
                connectButton.innerHTML = '<i class="fab fa-bluetooth-b"></i> Conectar';
                connectButton.setAttribute('aria-label', 'Conectar dispositivo demonstrativo');
            }
            setWatchStatus('', 'Em espera');
            const source = document.getElementById('watchSource');
            if (source) source.textContent = 'Conecte para iniciar a demonstração';
            resetWatchReading('Sem conexão demonstrativa. Os batimentos permanecem zerados.');
        }

        function pauseHeartMonitor() {
            clearInterval(watchSimulationTimer);
            watchSimulationTimer = null;
            watchMode = 'idle';
            const pauseButton = document.getElementById('watchPauseButton');
            if (pauseButton) pauseButton.disabled = true;
            const connectButton = document.getElementById('watchConnectButton');
            if (connectButton) {
                connectButton.innerHTML = '<i class="fab fa-bluetooth-b"></i> Conectar';
                connectButton.setAttribute('aria-label', 'Conectar dispositivo demonstrativo');
            }
            setWatchStatus('', 'Pausado');
            const source = document.getElementById('watchSource');
            if (source) source.textContent = 'Demonstração pausada';
            resetWatchReading('A demonstração foi pausada e os batimentos foram zerados.');
        }

        function initializeHeartWatch() {
            if (!document.getElementById('watchClock')) return;
            updateWatchClock();
            const snapshot = JSON.parse(localStorage.getItem(watchSnapshotKey) || '{}');
            const measurements = snapshot.measurements && typeof snapshot.measurements === 'object'
                ? snapshot.measurements
                : {};
            const connected = Boolean(snapshot.active) && watchMode !== 'idle';
            if (connected) currentWatchMeasurements = measurements;
            const hrvValue = document.getElementById('watchHrvValue');
            const spo2Value = document.getElementById('watchSpo2Value');
            const ecgValue = document.getElementById('watchEcgValue');
            if (hrvValue) hrvValue.textContent = connected && measurements.hrv ? `${measurements.hrv.value} ms` : '—';
            if (spo2Value) spo2Value.textContent = connected && measurements.spo2 ? `${measurements.spo2.value}%` : '—';
            if (ecgValue) ecgValue.textContent = connected && measurements.ecg ? measurements.ecg.value : '—';
            if (!watchClockTimer) watchClockTimer = window.setInterval(updateWatchClock, 1000);
            drawWatchChart();
        }

        window.addEventListener('resize', () => {
            drawWatchChart();
            applyAccessibleFontSize();
        });

        function closeHeaderPopovers() {
            document.querySelectorAll('.header-popover.open').forEach(popover => popover.classList.remove('open'));
            document.querySelectorAll('.header-control > button[aria-expanded="true"]').forEach(button => button.setAttribute('aria-expanded', 'false'));
        }

        function toggleHeaderPopover(popoverId) {
            const popover = document.getElementById(popoverId);
            const button = popoverId === 'notificationsMenu'
                ? document.getElementById('notificationsButton')
                : document.getElementById('profileButton');
            const shouldOpen = !popover.classList.contains('open');
            closeHeaderPopovers();
            if (shouldOpen) {
                popover.classList.add('open');
                button.setAttribute('aria-expanded', 'true');
            }
        }

        function markNotificationsRead() {
            localStorage.setItem(notificationsReadKey, localDateKey());
            document.getElementById('notificationsButton').classList.add('read');
        }

        function collectAccessibleTextTargets(root, includeDescendants = true) {
            const targets = new Set();
            const inspect = element => {
                if (!(element instanceof Element)) return;
                const hasDirectText = Array.from(element.childNodes).some(node =>
                    node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== ''
                );
                if (hasDirectText || element.matches('input,textarea,select')) targets.add(element);
            };
            if (root instanceof Text) inspect(root.parentElement);
            else if (root instanceof Element) {
                inspect(root);
                if (includeDescendants) root.querySelectorAll('*').forEach(inspect);
            }
            return Array.from(targets);
        }

        function restoreAccessibleFontSize() {
            fontScaleElements.forEach(element => {
                const original = fontScaleOriginals.get(element);
                if (!original || !element.isConnected) return;
                if (original.value) element.style.setProperty('font-size', original.value, original.priority);
                else element.style.removeProperty('font-size');
            });
            fontScaleElements.clear();
        }

        function scaleAccessibleTextTargets(targets, scale) {
            const measurements = [];
            targets.forEach(element => {
                if (!element.isConnected) return;
                if (!fontScaleOriginals.has(element)) {
                    fontScaleOriginals.set(element, {
                        value: element.style.getPropertyValue('font-size'),
                        priority: element.style.getPropertyPriority('font-size')
                    });
                }
                const original = fontScaleOriginals.get(element);
                if (original.value) element.style.setProperty('font-size', original.value, original.priority);
                else element.style.removeProperty('font-size');
                const size = Number.parseFloat(getComputedStyle(element).fontSize);
                if (Number.isFinite(size) && size > 0) measurements.push({ element, size });
            });
            measurements.forEach(({ element, size }) => {
                const parentSize = Number.parseFloat(getComputedStyle(element.parentElement || element).fontSize);
                const inheritsScaledSize = fontScaleElements.has(element.parentElement) &&
                    Number.isFinite(parentSize) && Math.abs(size - parentSize) < 0.1;
                const adjustedSize = inheritsScaledSize ? size / fontScaleFactors[dashboardSettings.fontSize] : size;
                element.style.setProperty('font-size', `${Math.min(72, Math.max(9, adjustedSize * scale))}px`);
                fontScaleElements.add(element);
            });
        }

        function applyAccessibleFontSize(force = false) {
            const nextSize = Object.prototype.hasOwnProperty.call(fontScaleFactors, dashboardSettings.fontSize)
                ? dashboardSettings.fontSize
                : 'medium';
            if (!force && nextSize === appliedFontSize) return;
            restoreAccessibleFontSize();
            appliedFontSize = nextSize;
            const scale = fontScaleFactors[nextSize];
            if (scale !== 1) {
                scaleAccessibleTextTargets(collectAccessibleTextTargets(document.querySelector('.app-container')), scale);
            }
            if (!fontScaleObserver) {
                fontScaleObserver = new MutationObserver(records => {
                    const activeScale = fontScaleFactors[dashboardSettings.fontSize] || 1;
                    if (activeScale === 1) return;
                    const addedTargets = new Set();
                    records.forEach(record => {
                        if (record.target instanceof Element) {
                            collectAccessibleTextTargets(record.target, false).forEach(element => addedTargets.add(element));
                        }
                        record.addedNodes.forEach(node => {
                            if (node instanceof Element || node instanceof Text) {
                                collectAccessibleTextTargets(node).forEach(element => addedTargets.add(element));
                            }
                        });
                    });
                    scaleAccessibleTextTargets(Array.from(addedTargets), activeScale);
                });
                fontScaleObserver.observe(document.querySelector('.app-container'), { childList: true, subtree: true });
            }
        }

        function applyDashboardSettings() {
            document.body.classList.toggle('high-contrast', dashboardSettings.highContrast);
            document.body.classList.toggle('dark-mode', dashboardSettings.darkMode);
            document.body.classList.toggle('font-small', dashboardSettings.fontSize === 'small');
            document.body.classList.toggle('font-large', dashboardSettings.fontSize === 'large' || dashboardSettings.fontSize === 'xlarge');
            document.body.classList.toggle('font-xlarge', dashboardSettings.fontSize === 'xlarge');
            document.body.classList.toggle('reduce-motion', dashboardSettings.reduceMotion);
            document.body.classList.toggle('phone-presentation', dashboardSettings.phonePresentation);
            document.getElementById('highContrastToggle').setAttribute('aria-checked', String(dashboardSettings.highContrast));
            const darkModeToggle = document.getElementById('darkModeToggle');
            darkModeToggle.setAttribute('aria-checked', String(dashboardSettings.darkMode));
            darkModeToggle.setAttribute('aria-label', dashboardSettings.darkMode ? 'Desativar tema noturno bordô' : 'Ativar tema noturno bordô');
            document.getElementById('highContrastToggle').setAttribute('aria-label', dashboardSettings.highContrast ? 'Desativar alto contraste' : 'Ativar alto contraste');
            document.getElementById('reduceMotionToggle').setAttribute('aria-checked', String(dashboardSettings.reduceMotion));
            document.getElementById('reduceMotionToggle').setAttribute('aria-label', dashboardSettings.reduceMotion ? 'Ativar animações' : 'Reduzir animações');
            document.getElementById('phonePresentationToggle').setAttribute('aria-checked', String(dashboardSettings.phonePresentation));
            document.getElementById('phonePresentationToggle').setAttribute('aria-label', dashboardSettings.phonePresentation ? 'Desativar apresentação em celular' : 'Ativar apresentação em celular');
            document.getElementById('textSizeSetting').value = dashboardSettings.fontSize;
            applyAccessibleFontSize();
        }

        function saveDashboardSetting(setting, value) {
            if (!Object.prototype.hasOwnProperty.call(dashboardSettings, setting)) return;
            dashboardSettings[setting] = value;
            localStorage.setItem(`${settingsStoragePrefix}${setting}`, String(value));
            applyDashboardSettings();
        }

        function toggleDashboardSetting(setting) {
            if (!Object.prototype.hasOwnProperty.call(dashboardSettings, setting)) return;
            saveDashboardSetting(setting, !dashboardSettings[setting]);
        }

        function resetDashboardSettings() {
            Object.keys(dashboardSettings).forEach(setting => {
                dashboardSettings[setting] = setting === 'fontSize' ? 'medium' : false;
                localStorage.removeItem(`${settingsStoragePrefix}${setting}`);
            });
            localStorage.removeItem(`${settingsStoragePrefix}largeText`);
            applyDashboardSettings();
        }

        function showSettingsTab(tab) {
            const validTabs = ['account', 'privacy', 'appearance', 'about'];
            const selectedTab = validTabs.includes(tab) ? tab : 'appearance';
            validTabs.forEach(name => {
                const active = name === selectedTab;
                document.getElementById(`settingsTab${name[0].toUpperCase()}${name.slice(1)}`).setAttribute('aria-selected', String(active));
                document.getElementById(`settingsPanel${name[0].toUpperCase()}${name.slice(1)}`).classList.toggle('active', active);
            });
        }

        function openSettingsModal(tab = 'appearance') {
            closeHeaderPopovers();
            applyDashboardSettings();
            showSettingsTab(tab);
            document.getElementById('settingsModal').style.display = 'flex';
            document.querySelector('#settingsModal .modal-close').focus();
        }

        function closeSettingsModal() {
            document.getElementById('settingsModal').style.display = 'none';
            document.getElementById('settingsButton').focus();
        }

        function openTermsModal() {
            document.getElementById('termsModal').style.display = 'flex';
        }

        function closeTermsModal() {
            document.getElementById('termsModal').style.display = 'none';
            document.getElementById('settingsModal').style.display = 'flex';
        }

        function setSettingsMessage(elementId, message, isError = false) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.classList.toggle('error', isError);
        }

        async function saveAccountChange(payload, messageId) {
            setSettingsMessage(messageId, 'Salvando...');
            try {
                const response = await fetch('account_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'Não foi possível atualizar sua conta.');
                }
                return result;
            } catch (error) {
                setSettingsMessage(messageId, error.message || 'Falha de comunicação. Tente novamente.', true);
                return null;
            }
        }

        async function updateAccountProfile(event) {
            event.preventDefault();
            const result = await saveAccountChange({
                action: 'update_profile',
                name: document.getElementById('accountName').value.trim(),
                email: document.getElementById('accountEmail').value.trim()
            }, 'profileSettingsMessage');
            if (result) {
                setSettingsMessage('profileSettingsMessage', `${result.message} Atualizando a página...`);
                window.setTimeout(() => window.location.reload(), 700);
            }
        }

        async function updateAccountPassword(event) {
            event.preventDefault();
            const form = event.currentTarget;
            const formData = new FormData(form);
            const currentPassword = String(formData.get('current_password') || '');
            const newPassword = formData.get('new_password');
            const confirmation = formData.get('confirm_password');
            if (typeof newPassword !== 'string' || newPassword.length < 8 || newPassword.length > 72) {
                setSettingsMessage('passwordSettingsMessage', 'A nova senha deve ter entre 8 e 72 caracteres.', true);
                form.elements.new_password.focus();
                return;
            }
            if (newPassword === currentPassword) {
                setSettingsMessage('passwordSettingsMessage', 'Escolha uma senha diferente da atual.', true);
                form.elements.new_password.focus();
                return;
            }
            if (newPassword !== confirmation) {
                setSettingsMessage('passwordSettingsMessage', 'A confirmação da nova senha não confere.', true);
                form.elements.confirm_password.focus();
                return;
            }
            const result = await saveAccountChange({
                action: 'update_password',
                current_password: currentPassword,
                new_password: newPassword
            }, 'passwordSettingsMessage');
            if (result) {
                form.reset();
                updatePasswordStrength();
                updatePasswordMatch();
                setSettingsMessage('passwordSettingsMessage', result.message);
            }
        }

        function togglePasswordVisibility(fieldName, button) {
            const input = document.querySelector(`#passwordSettingsForm [name="${fieldName}"]`);
            if (!input) return;
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', `${isVisible ? 'Mostrar' : 'Ocultar'} ${fieldName === 'current_password' ? 'senha atual' : fieldName === 'new_password' ? 'nova senha' : 'confirmação da senha'}`);
            button.innerHTML = `<i class="far ${isVisible ? 'fa-eye' : 'fa-eye-slash'}" aria-hidden="true"></i>`;
            input.focus();
        }

        function updatePasswordStrength() {
            const password = document.querySelector('#passwordSettingsForm [name="new_password"]').value;
            const panel = document.getElementById('passwordStrength');
            const label = panel.querySelector('.password-strength-label');
            const bars = panel.querySelectorAll('.password-strength-track span');
            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
            if (/\d/.test(password) || /[^A-Za-z0-9]/.test(password)) score++;
            const strength = password.length < 8 ? 'weak' : score >= 4 ? 'strong' : score >= 3 ? 'good' : 'weak';
            const labels = { empty: 'Sem senha', weak: password.length < 8 ? 'Muito curta' : 'Fraca', good: 'Boa', strong: 'Forte' };
            panel.dataset.strength = password ? strength : 'empty';
            label.textContent = labels[panel.dataset.strength];
            bars.forEach((bar, index) => bar.classList.toggle('active', index < score));
            panel.setAttribute('aria-label', `Força da senha: ${labels[panel.dataset.strength]}`);
        }

        function updatePasswordMatch() {
            const form = document.getElementById('passwordSettingsForm');
            const password = form.elements.new_password.value;
            const confirmation = form.elements.confirm_password.value;
            const message = document.getElementById('passwordMatchMessage');
            message.classList.toggle('error', Boolean(confirmation) && password !== confirmation);
            message.textContent = confirmation
                ? password === confirmation ? 'As senhas coincidem.' : 'As senhas não coincidem.'
                : '';
        }

        document.querySelector('#passwordSettingsForm [name="new_password"]').addEventListener('input', () => {
            updatePasswordStrength();
            updatePasswordMatch();
        });
        document.querySelector('#passwordSettingsForm [name="confirm_password"]').addEventListener('input', updatePasswordMatch);
        document.addEventListener('input', function (event) {
            if (event.target instanceof HTMLInputElement && event.target.id === 'healthRecordInput') {
                event.target.setCustomValidity('');
            }
        });

        function saveCookieChoice(choice) {
            localStorage.setItem('cardioweb.dashboard.cookieChoice', choice);
            document.getElementById('cookieBanner').classList.remove('visible');
        }

        function createChatId() {
            return window.crypto && typeof window.crypto.randomUUID === 'function'
                ? window.crypto.randomUUID()
                : `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        }

        function getChatConversations() {
            const conversations = JSON.parse(localStorage.getItem(chatConversationsStorageKey) || '[]');
            return Array.isArray(conversations)
                ? conversations.filter(item => item && typeof item.id === 'string' && typeof item.title === 'string')
                : [];
        }

        function saveChatConversations(conversations) {
            localStorage.setItem(chatConversationsStorageKey, JSON.stringify(conversations));
        }

        function chatMessagesStorageKey(chatId) {
            return `${chatMessagesStoragePrefix}${chatId}`;
        }

        function getChatMessages(chatId = activeChatId) {
            if (!chatId) return [];
            const messages = JSON.parse(localStorage.getItem(chatMessagesStorageKey(chatId)) || '[]');
            return Array.isArray(messages) ? messages : [];
        }

        function openChatImageDatabase() {
            if (!('indexedDB' in window)) {
                return Promise.reject(new Error('Este navegador não oferece armazenamento persistente para imagens do chat.'));
            }
            if (!chatImageDatabasePromise) {
                chatImageDatabasePromise = new Promise((resolve, reject) => {
                    const request = window.indexedDB.open(chatImageDatabaseName, 1);
                    request.onupgradeneeded = () => {
                        if (!request.result.objectStoreNames.contains('images')) {
                            request.result.createObjectStore('images', { keyPath: 'id' });
                        }
                    };
                    request.onsuccess = () => resolve(request.result);
                    request.onerror = () => reject(request.error || new Error('Não foi possível abrir o arquivo de imagens do chat.'));
                });
            }
            return chatImageDatabasePromise;
        }

        async function saveChatImage(imageId, file, conversationId, messageId) {
            const database = await openChatImageDatabase();
            return new Promise((resolve, reject) => {
                const transaction = database.transaction('images', 'readwrite');
                transaction.objectStore('images').put({
                    id: imageId,
                    conversationId,
                    messageId,
                    name: file.name,
                    type: file.type,
                    blob: file,
                    createdAt: new Date().toISOString()
                });
                transaction.oncomplete = () => resolve();
                transaction.onerror = () => reject(transaction.error || new Error('Não foi possível salvar a imagem neste navegador.'));
                transaction.onabort = () => reject(transaction.error || new Error('O armazenamento da imagem foi cancelado.'));
            });
        }

        async function getChatImage(imageId) {
            const database = await openChatImageDatabase();
            return new Promise((resolve, reject) => {
                const request = database.transaction('images', 'readonly').objectStore('images').get(imageId);
                request.onsuccess = () => resolve(request.result || null);
                request.onerror = () => reject(request.error || new Error('Não foi possível carregar esta imagem.'));
            });
        }

        async function getAllChatImages() {
            const database = await openChatImageDatabase();
            return new Promise((resolve, reject) => {
                const request = database.transaction('images', 'readonly').objectStore('images').getAll();
                request.onsuccess = () => resolve(request.result || []);
                request.onerror = () => reject(request.error || new Error('Não foi possível carregar as imagens salvas.'));
            });
        }

        function revokeChatObjectUrls() {
            chatObjectUrls.forEach(url => URL.revokeObjectURL(url));
            chatObjectUrls.clear();
        }

        function chatGreetingElement() {
            const greeting = document.createElement('div');
            greeting.className = 'message bot';
            greeting.innerHTML = '<div class="message-avatar"><i class="fas fa-headset"></i></div><div class="message-bubble">Olá! Eu sou o assistente do CardioWeb. Como posso ajudar você hoje? <i class="fas fa-heart" style="color:#78132a;"></i></div>';
            return greeting;
        }

        function renderChatGreeting() {
            const messages = document.getElementById('chatMessages');
            revokeChatObjectUrls();
            messages.replaceChildren(chatGreetingElement());
        }

        function renderChatRecentList() {
            const list = document.getElementById('chatRecentList');
            const conversations = getChatConversations().sort((a, b) => (b.updatedAt || b.createdAt || '').localeCompare(a.updatedAt || a.createdAt || ''));
            document.getElementById('chatImagesButton').classList.toggle('active', chatViewMode === 'images');
            list.replaceChildren();
            if (!conversations.length) {
                const empty = document.createElement('p');
                empty.className = 'chat-recent-empty';
                empty.textContent = 'Suas conversas recentes aparecerão aqui.';
                list.appendChild(empty);
                return;
            }
            conversations.forEach(conversation => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `chat-recent-item${conversation.id === activeChatId && chatViewMode === 'conversation' ? ' active' : ''}`;
                button.title = conversation.title;
                button.setAttribute('aria-label', `Abrir conversa: ${conversation.title}`);
                button.innerHTML = '<i class="far fa-message" aria-hidden="true"></i>';
                const title = document.createElement('span');
                title.textContent = conversation.title;
                button.appendChild(title);
                button.addEventListener('click', () => openChatConversation(conversation.id));
                list.appendChild(button);
            });
        }

        function initializeChatHistory() {
            let conversations = getChatConversations();
            if (!conversations.length) {
                const legacyMessages = JSON.parse(localStorage.getItem(chatStorageKey) || '[]');
                if (Array.isArray(legacyMessages) && legacyMessages.length) {
                    const legacyId = createChatId();
                    conversations = [{
                        id: legacyId,
                        title: 'Conversa anterior',
                        createdAt: legacyMessages[0].createdAt || new Date().toISOString(),
                        updatedAt: new Date().toISOString()
                    }];
                    localStorage.setItem(chatMessagesStorageKey(legacyId), JSON.stringify(legacyMessages));
                    localStorage.removeItem(chatStorageKey);
                    saveChatConversations(conversations);
                }
            }
            if (!conversations.length) {
                const id = createChatId();
                conversations = [{ id, title: 'Novo chat', createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() }];
                saveChatConversations(conversations);
            }
            const savedId = localStorage.getItem(activeChatStorageKey);
            activeChatId = conversations.some(item => item.id === savedId) ? savedId : conversations[0].id;
            localStorage.setItem(activeChatStorageKey, activeChatId);
            restoreChatHistory();
            renderChatRecentList();
        }

        function startNewChat() {
            const id = createChatId();
            const now = new Date().toISOString();
            const conversations = getChatConversations();
            conversations.unshift({ id, title: 'Novo chat', createdAt: now, updatedAt: now });
            saveChatConversations(conversations);
            activeChatId = id;
            localStorage.setItem(activeChatStorageKey, id);
            chatViewMode = 'conversation';
            prepareChatConversationView();
            document.getElementById('chatConversationView').hidden = false;
            document.getElementById('chatImagesView').hidden = true;
            document.getElementById('chatTitle').textContent = 'CardioChat';
            document.getElementById('chatSidebar').classList.remove('chat-navigation-open');
            document.querySelector('.chat-nav-toggle').setAttribute('aria-expanded', 'false');
            clearSelectedChatFile();
            document.getElementById('chatInput').value = '';
            renderChatGreeting();
            renderChatRecentList();
            document.getElementById('chatInput').focus();
        }

        function openChatConversation(chatId, messageId = '') {
            const conversation = getChatConversations().find(item => item.id === chatId);
            if (!conversation) return;
            activeChatId = chatId;
            localStorage.setItem(activeChatStorageKey, chatId);
            chatViewMode = 'conversation';
            prepareChatConversationView();
            document.getElementById('chatConversationView').hidden = false;
            document.getElementById('chatImagesView').hidden = true;
            document.getElementById('chatTitle').textContent = 'CardioChat';
            document.getElementById('chatSidebar').classList.remove('chat-navigation-open');
            document.querySelector('.chat-nav-toggle').setAttribute('aria-expanded', 'false');
            restoreChatHistory();
            renderChatRecentList();
            if (messageId) {
                requestAnimationFrame(() => document.querySelector(`[data-message-id="${CSS.escape(messageId)}"]`)?.scrollIntoView({ block: 'center' }));
            }
        }

        async function showChatImages() {
            chatViewMode = 'images';
            prepareChatConversationView();
            document.getElementById('chatConversationView').hidden = true;
            document.getElementById('chatImagesView').hidden = false;
            document.getElementById('chatTitle').textContent = 'CardioChat';
            document.getElementById('chatSidebar').classList.remove('chat-navigation-open');
            document.querySelector('.chat-nav-toggle').setAttribute('aria-expanded', 'false');
            renderChatRecentList();
            await renderChatImages();
        }

        function toggleChatNavigation() {
            const sidebar = document.getElementById('chatSidebar');
            const desktop = window.matchMedia('(min-width: 901px)').matches && !document.body.classList.contains('phone-presentation');
            let isOpen;
            if (desktop && sidebar.classList.contains('chat-session-open')) {
                isOpen = !sidebar.classList.contains('chat-navigation-open');
                sidebar.classList.toggle('chat-navigation-open', isOpen);
                sidebar.classList.toggle('chat-navigation-collapsed', !isOpen);
            } else {
                isOpen = sidebar.classList.toggle('chat-navigation-open');
            }
            document.querySelector('.chat-nav-toggle').setAttribute('aria-expanded', String(isOpen));
        }

        function prepareChatConversationView() {
            const sidebar = document.getElementById('chatSidebar');
            const desktop = window.matchMedia('(min-width: 901px)').matches && !document.body.classList.contains('phone-presentation');
            sidebar.classList.remove('chat-navigation-only', 'chat-navigation-open');
            sidebar.classList.toggle('chat-session-open', desktop);
            sidebar.classList.toggle('chat-navigation-collapsed', desktop);
            document.querySelector('.chat-nav-toggle').setAttribute('aria-expanded', 'false');
        }

        async function renderChatImages() {
            const library = document.getElementById('chatImageLibrary');
            revokeChatObjectUrls();
            library.replaceChildren();
            try {
                const images = (await getAllChatImages()).sort((a, b) => (b.createdAt || '').localeCompare(a.createdAt || ''));
                if (!images.length) {
                    const empty = document.createElement('p');
                    empty.className = 'chat-image-empty';
                    empty.textContent = 'As imagens que você enviar nas conversas aparecerão aqui.';
                    library.appendChild(empty);
                    return;
                }
                const conversations = getChatConversations();
                images.forEach(image => {
                    const conversation = conversations.find(item => item.id === image.conversationId);
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'chat-image-card';
                    button.addEventListener('click', () => openChatConversation(image.conversationId, image.messageId));
                    const preview = document.createElement('img');
                    preview.alt = image.name || 'Imagem enviada no chat';
                    preview.loading = 'lazy';
                    const objectUrl = URL.createObjectURL(image.blob);
                    chatObjectUrls.add(objectUrl);
                    preview.src = objectUrl;
                    const title = document.createElement('strong');
                    title.textContent = image.name || 'Imagem';
                    const context = document.createElement('span');
                    context.textContent = conversation ? conversation.title : 'Conversa';
                    button.append(preview, title, context);
                    library.appendChild(button);
                });
            } catch (error) {
                console.error('CardioWeb chat images could not be loaded:', error);
                const failure = document.createElement('p');
                failure.className = 'chat-image-empty';
                failure.textContent = 'Não foi possível carregar as imagens salvas neste navegador.';
                library.appendChild(failure);
            }
        }

        async function showMessageImage(messageElement, imageId, imageName) {
            try {
                const image = await getChatImage(imageId);
                if (!messageElement.isConnected) return;
                if (!image) throw new Error('A imagem não foi encontrada no armazenamento deste navegador.');
                const objectUrl = URL.createObjectURL(image.blob);
                chatObjectUrls.add(objectUrl);
                const preview = document.createElement('img');
                preview.className = 'message-attachment-image';
                preview.src = objectUrl;
                preview.alt = imageName || image.name || 'Imagem enviada';
                const caption = document.createElement('span');
                caption.className = 'message-image-caption';
                caption.textContent = imageName || image.name || 'Imagem enviada';
                messageElement.querySelector('.message-bubble').append(preview, caption);
            } catch (error) {
                console.error('CardioWeb chat image could not be displayed:', error);
                if (messageElement.isConnected) {
                    const message = document.createElement('span');
                    message.className = 'message-image-caption';
                    message.textContent = 'A imagem não pôde ser carregada neste navegador.';
                    messageElement.querySelector('.message-bubble').appendChild(message);
                }
            }
        }

        async function clearChatHistory() {
            if (!window.confirm('Apagar todas as conversas e imagens salvas neste navegador? Esta ação não pode ser desfeita.')) return;
            try {
                const database = await openChatImageDatabase();
                await new Promise((resolve, reject) => {
                    const transaction = database.transaction('images', 'readwrite');
                    transaction.objectStore('images').clear();
                    transaction.oncomplete = resolve;
                    transaction.onerror = () => reject(transaction.error || new Error('Não foi possível apagar as imagens salvas.'));
                    transaction.onabort = () => reject(transaction.error || new Error('A exclusão das imagens foi cancelada.'));
                });
                Object.keys(localStorage)
                    .filter(key => key.startsWith(chatMessagesStoragePrefix))
                    .forEach(key => localStorage.removeItem(key));
                localStorage.removeItem(chatStorageKey);
                localStorage.removeItem(chatConversationsStorageKey);
                localStorage.removeItem(activeChatStorageKey);
                initializeChatHistory();
                if (chatViewMode === 'images') showChatImages();
            } catch (error) {
                console.error('CardioWeb chat history could not be cleared:', error);
                alert('Não foi possível apagar o histórico completo. Tente novamente neste navegador.');
            }
        }

        function exportAccountData() {
            const data = {
                exportedAt: new Date().toISOString(),
                account: {
                    name: document.getElementById('accountName').value,
                    email: document.getElementById('accountEmail').value
                },
                conversations: getChatConversations().map(conversation => ({
                    ...conversation,
                    messages: getChatMessages(conversation.id)
                })),
                preferences: dashboardSettings
            };
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `cardioweb-dados-${new Date().toISOString().slice(0, 10)}.json`;
            link.click();
            URL.revokeObjectURL(downloadUrl);
        }

        applyDashboardSettings();
        if (localStorage.getItem('cardioweb.dashboard.cookieChoice') === null) {
            document.getElementById('cookieBanner').classList.add('visible');
        }
        if (localStorage.getItem(notificationsReadKey) === localDateKey()) {
            document.getElementById('notificationsButton').classList.add('read');
        }

        // ============================================================
        // FUNÇÕES DE NAVEGAÇÃO
        // ============================================================
        function changePage(page) {
            const normalizedPage = page === 'inicio' ? 'monitoramento' : page;
            if (normalizedPage !== 'relogio' && watchMode !== 'idle') pauseHeartMonitor();
            const url = new URL(window.location.href);
            url.searchParams.set('page', normalizedPage);
            window.history.pushState({}, '', url);
            const titles = {
                'monitoramento': 'Monitoramento',
                'relogio': 'Relógio cardíaco',
                'blog': 'Blog',
                'exames': 'Exames',
                'informacoes': 'Informações',
                'suporte': 'Suporte'
            };
            document.querySelector('.app-container').classList.toggle('blog-active', normalizedPage === 'blog');
            document.getElementById('pageTitle').innerText = titles[normalizedPage] || 'Monitoramento';
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            const activeItem = Array.from(document.querySelectorAll('.nav-item')).find(item => item.getAttribute('onclick') === `changePage('${normalizedPage}')`);
            if (activeItem) activeItem.classList.add('active');
            loadContent(normalizedPage);
        }

        function openArticle(articleId, event) {
            if (event) event.stopPropagation();
            const article = articlesData[articleId];
            if (!article) return;
            const contentArea = document.getElementById('contentArea');
            const escapeText = value => String(value || '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[character]);
            const category = article.category || 'Saúde cardiovascular';
            const gallery = Array.isArray(article.gallery) && article.gallery.length
                ? article.gallery
                : [
                    { image: 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=900&q=80', alt: 'Acompanhamento em saúde', caption: 'O cuidado cardiovascular começa com informação e acompanhamento.' },
                    { image: 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=900&q=80', alt: 'Profissional de saúde analisa informações clínicas', caption: 'Dúvidas sobre sintomas e exames devem ser discutidas com um profissional.' }
                ];
            const galleryFigures = gallery.map(image => `
                <figure class="article-visual article-visual-inline">
                    <img src="${escapeText(image.image)}" alt="${escapeText(image.alt)}" loading="lazy" onerror="this.parentElement.classList.add('image-unavailable');this.remove()">
                    <figcaption>${escapeText(image.caption)}</figcaption>
                </figure>
            `);
            const sections = article.content.split(/(?=<h3\b)/i);
            const imagesBySection = new Map();
            gallery.forEach((image, index) => {
                const sectionIndex = Math.min(sections.length - 1, Math.floor((index + 1) * sections.length / (gallery.length + 1)) - 1);
                imagesBySection.set(sectionIndex, (imagesBySection.get(sectionIndex) || '') + galleryFigures[index]);
            });
            const articleContent = sections.map((section, index) => section + (imagesBySection.get(index) || '')).join('');
            const keyPoints = Array.isArray(article.keyPoints) && article.keyPoints.length
                ? article.keyPoints
                : [article.summary];
            const keyPointsHtml = keyPoints.map(point => `<li>${escapeText(point)}</li>`).join('');
            const source = article.source || 'Conteúdo educativo do CardioWeb, elaborado a partir da fundamentação teórica do TCC.';
            contentArea.innerHTML = `
                <article class="article-reading">
                    <div class="article-reading-top">
                        <button type="button" class="article-back-btn" onclick="changePage('blog')"><i class="fas fa-arrow-left"></i> Voltar ao Blog</button>
                        <span class="article-share-note">CardioWeb · Conteúdo educativo</span>
                    </div>
                    <header class="article-feature">
                        <div class="article-feature-copy">
                            <span class="article-feature-kicker"><i class="fas fa-heart-pulse"></i> ${escapeText(category)}</span>
                            <h1>${escapeText(article.title)}</h1>
                            <p>${escapeText(article.summary)}</p>
                            <div class="article-feature-meta"><span><i class="far fa-calendar"></i>${escapeText(article.date)}</span><span><i class="fas fa-book-open"></i>Leitura educativa</span></div>
                        </div>
                        <div class="article-feature-image">
                            <img src="${escapeText(article.image)}" alt="${escapeText(article.imageAlt || article.title)}" onerror="this.parentElement.classList.add('image-unavailable');this.remove()">
                        </div>
                    </header>
                    <div class="article-reading-content">
                        <div class="article-prose">
                            ${articleContent}
                            <aside class="article-callout"><strong><i class="fas fa-lightbulb"></i> Em poucas palavras</strong><p>${escapeText(article.summary)}</p></aside>
                            <p class="article-disclaimer">Este artigo tem finalidade educativa e não substitui consulta, diagnóstico ou orientação individual de um profissional de saúde.</p>
                        </div>
                        <aside class="article-reading-aside">
                            <section class="article-aside-card article-takeaway"><h2><i class="fas fa-bookmark"></i> Para lembrar</h2><ul>${keyPointsHtml}</ul></section>
                            <section class="article-aside-card"><h2>Base teórica</h2><p class="article-source"><i class="fas fa-graduation-cap"></i><span>${escapeText(source)}</span></p></section>
                            <section class="article-aside-card"><h2>Quando buscar ajuda</h2><p>Dor forte no peito, falta de ar intensa, desmaio ou fraqueza súbita podem exigir atendimento urgente. No Brasil, ligue para o SAMU (192).</p></section>
                        </aside>
                    </div>
                    <button type="button" class="article-back-btn" onclick="changePage('blog')"><i class="fas fa-arrow-left"></i> Continuar lendo no Blog</button>
                </div>
            `;
            contentArea.scrollTop = 0;
            document.getElementById('pageTitle').innerText = 'Artigo';
        }

        function loadContent(page) {
            const contentArea = document.getElementById('contentArea');
            const normalizedPage = page === 'inicio' ? 'monitoramento' : page;
            if (normalizedPage !== 'relogio') {
                if (watchSimulationTimer) pauseHeartMonitor();
                if (watchClockTimer) {
                    clearInterval(watchClockTimer);
                    watchClockTimer = null;
                }
                closeWatchConnectionModal();
            }
            const html = sectionHtml[normalizedPage] || sectionHtml['monitoramento'];
            contentArea.innerHTML = html;
            contentArea.scrollTop = 0;
            applyAccessibleFontSize();
            if (normalizedPage === 'monitoramento' || normalizedPage === 'relogio') {
                renderAppointments();
            }
            if (normalizedPage === 'monitoramento') {
                renderHealthRecords();
                loadHealthRecordsFromDatabase();
                renderWatchSummary();
            }
            if (document.getElementById('notificationList')) checkAppointmentReminders();
            const pageTitles = {
                monitoramento: 'Monitoramento',
                relogio: 'Relógio cardíaco',
                blog: 'Blog',
                exames: 'Exames',
                informacoes: 'Informações',
                suporte: 'Suporte'
            };
            document.getElementById('pageTitle').innerText = pageTitles[normalizedPage] || pageTitles.monitoramento;
            if (normalizedPage === 'relogio') initializeHeartWatch();
            if (normalizedPage === 'blog') {
                const searchInput = document.getElementById('blogSearchInput');
                if (searchInput) searchInput.focus({ preventScroll: true });
            }
            setupScrollReveal();
        }

        window.addEventListener('focus', checkAppointmentReminders);
        window.addEventListener('storage', event => {
            if (event.key === appointmentStorageKey || event.key === watchSnapshotKey || event.key === healthRecordsStorageKey) {
                renderAppointments();
                renderWatchSummary();
                checkAppointmentReminders();
            }
        });

        function filterBlogArticles(query) {
            const normalizedQuery = String(query || '').trim().toLocaleLowerCase('pt-BR');
            let visibleCount = 0;
            document.querySelectorAll('.blog-article-card').forEach(card => {
                const matches = card.dataset.search.includes(normalizedQuery);
                card.hidden = !matches;
                if (matches) visibleCount++;
            });
            const emptyState = document.getElementById('blogEmptyState');
            if (emptyState) emptyState.hidden = visibleCount !== 0;
        }

        function showAllBlogArticles() {
            const searchInput = document.getElementById('blogSearchInput');
            if (searchInput) searchInput.value = '';
            filterBlogArticles('');
            document.getElementById('blogArticlesGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function setupScrollReveal() {
            const reveals = document.querySelectorAll('.reveal');
            if (!reveals.length) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const delay = Number(entry.target.dataset.delay || 0) * 80;
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, delay);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.15,
                rootMargin: '0px 0px -40px 0px'
            });

            reveals.forEach(el => observer.observe(el));
        }

        // ============================================================
        // CHAT
        // ============================================================
        let isChatSending = false;

        async function sendMessage() {
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            const fileInput = document.getElementById('chatFileInput');
            const filePreview = document.getElementById('chatFilePreview');
            const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

            if ((!msg && !file) || isChatSending) return;
            if (file && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                clearSelectedChatFile();
                alert('Por segurança, o chat aceita somente imagens JPG, PNG ou WebP.');
                return;
            }
            if (file && file.size > 5 * 1024 * 1024) {
                clearSelectedChatFile();
                alert('A imagem deve ter no máximo 5 MB.');
                return;
            }

            const sendButton = document.querySelector('.chat-send');
            let pendingMessage = null;
            isChatSending = true;
            if (sendButton) sendButton.disabled = true;
            try {
                let imageBase64 = '';
                if (file) {
                    imageBase64 = await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => {
                            const dataUrl = String(reader.result || '');
                            const separator = dataUrl.indexOf(',');
                            if (separator < 0) {
                                reject(new Error('Não foi possível ler esta imagem.'));
                                return;
                            }
                            resolve(dataUrl.slice(separator + 1));
                        };
                        reader.onerror = () => reject(new Error('Não foi possível ler esta imagem.'));
                        reader.readAsDataURL(file);
                    });
                }

                const userText = msg || 'Pode conversar comigo sobre esta imagem e me ajudar com minha dúvida ou sintoma?';
                const conversationId = activeChatId;
                const messageId = createChatId();
                const imageId = file ? createChatId() : '';
                if (file) await saveChatImage(imageId, file, conversationId, messageId);
                addMessage(userText, 'user', '', true, file ? file.name : '', { imageId, messageId, chatId: conversationId });
                const savedHistory = getChatMessages(conversationId);
                const history = savedHistory.slice(-12, -1)
                    .filter(item => item && ['user', 'bot'].includes(item.sender) && typeof item.text === 'string')
                    .map(item => ({ sender: item.sender, text: item.text }));
                input.value = '';
                fileInput.value = '';
                filePreview.classList.remove('visible');
                filePreview.replaceChildren();
                pendingMessage = addMessage(file ? 'Estou analisando a imagem e sua mensagem…' : 'Estou preparando uma resposta…', 'bot', '', false);

                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const response = await fetch('api/chat-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        message: userText,
                        history,
                        image: file ? { base64: imageBase64 } : null
                    })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'O assistente não conseguiu responder.');
                if (pendingMessage) pendingMessage.remove();
                addMessage(result.reply, 'bot', '', true, '', { chatId: conversationId });
            } catch (error) {
                console.error('CardioWeb AI chat request failed:', error);
                const message = error instanceof Error ? error.message : 'Falha inesperada ao conversar com o assistente.';
                if (pendingMessage) {
                    const bubble = pendingMessage.querySelector('.message-bubble');
                    if (bubble) bubble.textContent = message;
                } else {
                    addMessage(message, 'bot', '', false);
                }
            } finally {
                isChatSending = false;
                if (sendButton) sendButton.disabled = false;
            }
        }

        function toggleMobileChat(open = null) {
            const chat = document.getElementById('chatSidebar');
            const shouldOpen = open === null ? !chat.classList.contains('mobile-open') : open;
            chat.classList.toggle('mobile-open', shouldOpen);
            if (shouldOpen) {
                chat.classList.remove('chat-session-open', 'chat-navigation-collapsed', 'chat-navigation-open');
                chat.querySelector('.chat-nav-toggle').setAttribute('aria-expanded', 'false');
                chatViewMode = 'conversation';
                document.getElementById('chatConversationView').hidden = false;
                document.getElementById('chatImagesView').hidden = true;
                document.getElementById('chatTitle').textContent = 'CardioChat';
                restoreChatHistory();
            }
            document.body.classList.toggle('mobile-chat-open', shouldOpen);
            document.getElementById('mobileChatToggle').setAttribute('aria-expanded', String(shouldOpen));
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderAssistantMarkdown(text) {
            const lines = String(text || '').replace(/\r\n?/g, '\n').split('\n');
            const inline = value => escapeHtml(value)
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/__(.+?)__/g, '<strong>$1</strong>')
                .replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
                .replace(/(^|[^_])_([^_\n]+)_(?!_)/g, '$1<em>$2</em>');
            const blocks = [];
            let paragraph = [];
            let list = null;
            const flushParagraph = () => {
                if (paragraph.length) blocks.push(`<p>${paragraph.map(inline).join('<br>')}</p>`);
                paragraph = [];
            };
            const flushList = () => {
                if (!list) return;
                blocks.push(`<${list.type}>${list.items.map(item => `<li>${inline(item)}</li>`).join('')}</${list.type}>`);
                list = null;
            };
            lines.forEach(line => {
                const heading = line.match(/^\s{0,3}(#{1,3})\s+(.+)$/);
                const unordered = line.match(/^\s*[-*•]\s+(.+)$/);
                const ordered = line.match(/^\s*\d+[.)]\s+(.+)$/);
                if (!line.trim()) {
                    flushParagraph();
                    flushList();
                } else if (heading) {
                    flushParagraph();
                    flushList();
                    const tag = heading[1].length === 1 ? 'h2' : 'h3';
                    blocks.push(`<${tag}>${inline(heading[2])}</${tag}>`);
                } else if (unordered || ordered) {
                    flushParagraph();
                    const type = ordered ? 'ol' : 'ul';
                    if (list && list.type !== type) flushList();
                    if (!list) list = { type, items: [] };
                    list.items.push((unordered || ordered)[1]);
                } else {
                    flushList();
                    paragraph.push(line.trim());
                }
            });
            flushParagraph();
            flushList();
            return `<div class="assistant-markdown">${blocks.join('')}</div>`;
        }

        function addMessage(text, sender, extraHtml = '', persist = true, attachmentName = '', metadata = {}) {
            const targetChatId = metadata.chatId || activeChatId;
            const messageId = metadata.messageId || createChatId();
            if (persist) {
                const history = getChatMessages(targetChatId);
                history.push({
                    id: messageId,
                    text: String(text || ''),
                    sender,
                    attachmentName: String(attachmentName || ''),
                    imageId: String(metadata.imageId || '')
                });
                localStorage.setItem(chatMessagesStorageKey(targetChatId), JSON.stringify(history));
                const conversations = getChatConversations();
                const conversation = conversations.find(item => item.id === targetChatId);
                if (conversation) {
                    const firstUserMessage = history.find(item => item.sender === 'user');
                    if (firstUserMessage && conversation.title === 'Novo chat') {
                        conversation.title = firstUserMessage.text.trim().replace(/\s+/g, ' ').slice(0, 42) || 'Novo chat';
                    }
                    conversation.updatedAt = new Date().toISOString();
                    saveChatConversations(conversations);
                    if (targetChatId === activeChatId) {
                        renderChatRecentList();
                    }
                }
            }
            if (metadata.chatId && metadata.chatId !== activeChatId) return null;
            const container = document.getElementById('chatMessages');
            const div = document.createElement('div');
            div.className = `message ${sender}`;
            div.dataset.messageId = messageId;
            const avatar = sender === 'user' ? '<div class="message-avatar"><i class="fas fa-user"></i></div>' : '<div class="message-avatar"><i class="fas fa-headset"></i></div>';
            const content = sender === 'bot' ? renderAssistantMarkdown(text) : escapeHtml(text || '');
            div.innerHTML = avatar + `<div class="message-bubble">${content}${extraHtml}</div>`;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            if (metadata.imageId) showMessageImage(div, metadata.imageId, attachmentName);
            else if (attachmentName) {
                const caption = document.createElement('span');
                caption.className = 'message-image-caption';
                caption.textContent = attachmentName;
                div.querySelector('.message-bubble').appendChild(caption);
            }
            return div;
        }

        function restoreChatHistory() {
            const history = getChatMessages(activeChatId);
            revokeChatObjectUrls();
            document.getElementById('chatMessages').replaceChildren();
            if (!history.length) {
                document.getElementById('chatMessages').appendChild(chatGreetingElement());
                return;
            }
            history.forEach(message => {
                if (!message || !['user', 'bot'].includes(message.sender)) return;
                const attachmentName = typeof message.attachmentName === 'string' ? message.attachmentName : '';
                addMessage(message.text, message.sender, '', false, attachmentName, {
                    chatId: activeChatId,
                    imageId: typeof message.imageId === 'string' ? message.imageId : '',
                    messageId: typeof message.id === 'string' ? message.id : createChatId()
                });
            });
        }

        document.getElementById('chatFileInput').addEventListener('change', function () {
            const file = this.files && this.files[0];
            const preview = document.getElementById('chatFilePreview');
            if (!file) {
                preview.classList.remove('visible');
                preview.replaceChildren();
                return;
            }
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                clearSelectedChatFile();
                alert('Por segurança, o chat aceita somente imagens JPG, PNG ou WebP.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                clearSelectedChatFile();
                alert('A imagem deve ter no máximo 5 MB.');
                return;
            }
            const fileName = document.createElement('span');
            fileName.textContent = `${file.name} · ${formatFileSize(file.size)}`;
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'popover-action';
            removeButton.textContent = 'Remover';
            removeButton.addEventListener('click', clearSelectedChatFile);
            preview.replaceChildren(fileName, removeButton);
            preview.classList.add('visible');
        });

        function formatFileSize(bytes) {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
            return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
        }

        function clearSelectedChatFile() {
            const fileInput = document.getElementById('chatFileInput');
            fileInput.value = '';
            const preview = document.getElementById('chatFilePreview');
            preview.classList.remove('visible');
            preview.replaceChildren();
        }
        // ============================================================
        // MODAL DE AGENDAMENTO
        // ============================================================
        function openConsultaModal() {
            const modal = document.getElementById('consultaModal');
            modal.style.display = 'flex';
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dataConsulta').min = today;
            const horaSelect = document.getElementById('horaConsulta');
            horaSelect.innerHTML = '<option value="">Selecione um horário</option>';
            document.getElementById('medicSelect').onchange = carregarHorariosDisponiveis;
            document.getElementById('dataConsulta').onchange = carregarHorariosDisponiveis;
        }

        function closeConsultaModal() {
            document.getElementById('consultaModal').style.display = 'none';
            document.getElementById('consultaForm').reset();
            document.getElementById('horaConsulta').innerHTML = '<option value="">Selecione um horário</option>';
        }

        function carregarHorariosDisponiveis() {
            const medicoSelect = document.getElementById('medicSelect');
            const data = document.getElementById('dataConsulta').value;
            const horaSelect = document.getElementById('horaConsulta');
            horaSelect.innerHTML = '<option value="">Selecione um horário</option>';
            if (!medicoSelect.value || !data) return;

            const medicoNome = medicoSelect.value.split('|')[0];
            const todosHorarios = ['08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00'];

            fetch(`api/horarios_ocupados.php?medico=${encodeURIComponent(medicoNome)}&data=${data}`)
                .then(response => response.json())
                .then(ocupados => {
                    const disponiveis = todosHorarios.filter(h => !ocupados.includes(h));
                    if (disponiveis.length === 0) {
                        horaSelect.innerHTML = '<option value="">Nenhum horário disponível nesta data</option>';
                        return;
                    }
                    disponiveis.forEach(h => {
                        const opt = document.createElement('option');
                        opt.value = h;
                        opt.textContent = h;
                        horaSelect.appendChild(opt);
                    });
                })
                .catch(() => {
                    todosHorarios.forEach(h => {
                        const opt = document.createElement('option');
                        opt.value = h;
                        opt.textContent = h;
                        horaSelect.appendChild(opt);
                    });
                });
        }

        document.addEventListener('click', function(event) {
            const modal = document.getElementById('consultaModal');
            if (modal && event.target === modal) closeConsultaModal();
            ['settingsModal', 'termsModal'].forEach(modalId => {
                const overlay = document.getElementById(modalId);
                if (overlay && event.target === overlay) {
                    overlay.style.display = 'none';
                }
            });
            if (!event.target.closest('.header-control')) closeHeaderPopovers();
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeHeaderPopovers();
                closeAppointmentModal();
                closeHealthRecordModal();
                closeDashboardInsight();
                closeHealthHistory();
                ['settingsModal', 'termsModal', 'consultaModal', 'watchConnectionModal'].forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (modal && modal.style.display === 'flex') modal.style.display = 'none';
                });
                if (watchPairTimer) closeWatchConnectionModal();
                if (document.body.classList.contains('mobile-chat-open')) toggleMobileChat(false);
            }
        });

        function agendarConsulta(event) {
            event.preventDefault();
            const medicSelect = document.getElementById('medicSelect');
            const dataConsulta = document.getElementById('dataConsulta');
            const horaConsulta = document.getElementById('horaConsulta');
            const tipoConsulta = document.getElementById('tipoConsulta');
            const obsConsulta = document.getElementById('obsConsulta');

            if (!medicSelect.value || !dataConsulta.value || !horaConsulta.value || !tipoConsulta.value) {
                alert('Por favor, preencha todos os campos obrigatórios!');
                return;
            }

            const [nomeMedico, especialidade] = medicSelect.value.split('|');
            const idPaciente = '<?php echo $user_id; ?>';
            const nomePaciente = '<?php echo htmlspecialchars($user_name); ?>';

            fetch('api/solicitar_consulta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_paciente: idPaciente,
                    nome_paciente: nomePaciente,
                    medico: nomeMedico,
                    especialidade: especialidade,
                    data: dataConsulta.value,
                    hora: horaConsulta.value,
                    tipo: tipoConsulta.value,
                    observacoes: obsConsulta.value || ''
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(result) {
                if (result.success) {
                    alert('✓ Consulta solicitada com sucesso!');
                    closeConsultaModal();
                    location.reload();
                } else {
                    alert('❌ Erro: ' + (result.error || 'Erro desconhecido'));
                }
            })
            .catch(function(error) {
                alert('Erro ao solicitar consulta: ' + error.message);
            });
        }

        // ============================================================
        // CARREGAR CONTEÚDO INICIAL
        // ============================================================
        initializeChatHistory();
        if (window.matchMedia('(max-width: 900px)').matches || dashboardSettings.phonePresentation) {
            toggleMobileChat(true);
        }
        loadContent('<?php echo htmlspecialchars($page, ENT_QUOTES); ?>');
        checkAppointmentReminders();
        window.setInterval(checkAppointmentReminders, 60 * 1000);
    </script>
</body>
</html>