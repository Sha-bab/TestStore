<?php
// ── auth/login.php — Unified Login ────────────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php'); exit;
}

$db    = getDB();
$error = '';
$tab   = $_GET['role'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab   = $_POST['login_type'] ?? 'user';
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Email and password are required.';
    } elseif ($tab === 'user') {
        $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND status='active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            loginUser($user, 'user');
            setFlash('success', 'Welcome back, ' . $user['username'] . '!');
            header('Location: ' . SITE_URL . '/index.php'); exit;
        }
        $error = 'Invalid credentials or account blocked.';
    } else {
        $stmt = $db->prepare("SELECT * FROM developers WHERE email=? AND status='active' AND role IN ('developer', 'admin') LIMIT 1");
        $stmt->execute([$email]);
        $dev = $stmt->fetch();
        if ($dev && password_verify($pass, $dev['password'])) {
            $dev['avatar'] = $dev['profile_photo'] ?? null;
            $userRole = $dev['role'] ?? 'developer';
            loginUser($dev, $userRole);
            setFlash('success', $userRole === 'admin' ? 'Welcome, Administrator!' : 'Welcome back, ' . $dev['username'] . '!');
            header('Location: ' . SITE_URL . ($userRole === 'admin' ? '/admin/dashboard.php' : '/developer/dashboard.php')); exit;
        }
        $error = 'Invalid credentials or account blocked.';
    }
}

$pageTitle = 'Sign In';
$metaDesc  = 'Sign in to your ' . SITE_NAME . ' account';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(SITE_NAME) ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <!-- Remix Icons (used project-wide) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css">

    <!-- Project CSS (brings in all --ts-* variables) -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom.css?v=<?= filemtime(__DIR__ . '/../assets/css/custom.css') ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/images/favicon.svg?v=<?= filemtime(__DIR__ . '/../assets/images/favicon.svg') ?>">

    <style>
        /* ── Page reset for full-viewport auth shell ─── */
        html, body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        body {
            display: grid;
            place-items: center;
            padding: 32px 20px;
            background: radial-gradient(ellipse at 70% 20%, #b2c9b0 0%, transparent 50%),
                        linear-gradient(145deg, #8aac8a 0%, #a8bfa6 35%, #c5d8c1 70%, #ddebd8 100%);
            font-family: 'Poppins', sans-serif;
            flex-direction: unset; /* override global flex-direction:column */
        }

        /* ── Auth Shell (split-panel card) ──────────────── */
        .auth-shell {
            width: min(1040px, 100%);
            min-height: 620px;
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            overflow: hidden;
            border: 1px solid rgba(52, 211, 153, 0.18);
            border-radius: 26px;
            box-shadow: 0 32px 80px rgba(1, 25, 18, 0.55);
            animation: fadeInUp 0.45s var(--ts-ease) both;
        }

        /* ── Left Welcome Panel ─────────────────────────── */
        .welcome-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 50px 44px;
            color: #fff;
            background: linear-gradient(148deg, rgba(10, 65, 48, 0.96), rgba(3, 43, 32, 0.99));
        }

        .welcome-panel::before,
        .welcome-panel::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            border: 34px solid rgba(52, 211, 153, 0.18);
            transform: rotate(45deg);
            pointer-events: none;
        }
        .welcome-panel::before { top: -155px; left: -75px; }
        .welcome-panel::after  { right: -160px; bottom: -115px; border-color: rgba(52, 211, 153, 0.12); }

        .panel-lines {
            position: absolute;
            inset: 0;
            opacity: 0.3;
            background: repeating-linear-gradient(
                135deg,
                transparent 0 76px,
                rgba(52, 211, 153, 0.14) 77px 80px,
                transparent 81px 148px
            );
            pointer-events: none;
        }

        .panel-content { position: relative; z-index: 1; }

        /* Brand in panel */
        .panel-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
        }
        .panel-brand-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, 0.38);
            border-radius: 11px;
            background: rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .panel-brand-mark img {
            width: 24px;
            height: 24px;
            display: block;
            filter: drop-shadow(0 0 5px rgba(16, 185, 129, 0.5));
        }
        .panel-brand-name {
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: #fff;
        }

        /* Copy block */
        .panel-copy { margin: auto 0; }
        .panel-copy h2 {
            max-width: 280px;
            margin: 0 0 14px;
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 3.8vw, 3.1rem);
            line-height: 1.07;
            color: #fff;
        }
        .panel-copy p {
            max-width: 280px;
            margin: 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.94rem;
            line-height: 1.72;
        }

        /* Feature pills */
        .panel-pills { display: grid; gap: 11px; }
        .panel-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 13px 16px;
            border: 1px solid rgba(52, 211, 153, 0.22);
            border-radius: 13px;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 0.88rem;
        }
        .panel-pill i { font-size: 1.1rem; color: #6ee7b7; flex-shrink: 0; }

        /* ── Right Form Panel ───────────────────────────── */
        .form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px clamp(28px, 5.5vw, 70px);
            background: linear-gradient(160deg, #fff 0%, #f4faf5 65%, #e2f0e6 100%);
        }

        /* Avatar icon at top */
        .form-avatar-wrap { display: flex; justify-content: center; margin-bottom: 16px; }
        .form-avatar {
            width: 68px;
            height: 68px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #245b46, var(--ts-primary-dark));
            color: #a7f3d0;
            box-shadow: 0 10px 24px rgba(5, 150, 105, 0.32);
            font-size: 2rem;
        }

        .form-title {
            margin: 0;
            color: var(--ts-text-primary);
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2.1rem;
            font-weight: 700;
        }
        .form-subtitle {
            margin: 8px 0 24px;
            color: var(--ts-text-muted);
            text-align: center;
            font-size: 0.9rem;
        }

        /* Tab toggle (overrides base .ts-tab-toggle for a slightly different look) */
        .auth-tab-toggle {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            padding: 5px;
            margin-bottom: 22px;
            border-radius: 13px;
            background: rgba(5, 150, 105, 0.09);
            border: 1px solid var(--ts-border);
        }
        .auth-tab-btn {
            border: 0;
            border-radius: 9px;
            padding: 10px;
            background: transparent;
            color: var(--ts-text-muted);
            font: 600 0.875rem 'Poppins', sans-serif;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }
        .auth-tab-btn.active {
            background: var(--ts-primary);
            box-shadow: 0 5px 14px rgba(5, 150, 105, 0.28);
            color: #fff;
        }

        /* Alert overrides to look native to this page */
        .auth-alert {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 16px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 500;
            animation: fadeIn 0.3s ease;
        }
        .auth-alert-danger  { background: #fdeaea; border: 1px solid #fca5a5; color: #b91c1c; }
        .auth-alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
        .auth-alert i { font-size: 1rem; flex-shrink: 0; }

        /* Form fields */
        .auth-field { position: relative; margin-bottom: 16px; }
        .auth-field label {
            display: block;
            margin: 0 0 6px;
            color: var(--ts-text-secondary);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .auth-field-icon {
            position: absolute;
            left: 13px;
            bottom: 12px;
            color: var(--ts-text-muted);
            font-size: 1rem;
            pointer-events: none;
        }
        .auth-input {
            width: 100%;
            padding: 12px 44px 12px 40px;
            border: 1.5px solid var(--ts-border);
            border-radius: 11px;
            outline: 0;
            background: rgba(255, 255, 255, 0.78);
            color: var(--ts-text-primary);
            font: 400 0.93rem 'Poppins', sans-serif;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .auth-input::placeholder { color: var(--ts-text-muted); }
        .auth-input:focus {
            border-color: var(--ts-primary);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.13);
            background: #fff;
        }
        .pass-toggle {
            position: absolute;
            right: 11px;
            bottom: 9px;
            padding: 4px;
            border: 0;
            background: transparent;
            color: var(--ts-text-muted);
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            transition: color 0.2s;
        }
        .pass-toggle:hover { color: var(--ts-primary); }

        .forgot-link {
            display: block;
            margin: -4px 0 20px;
            color: var(--ts-primary);
            text-align: right;
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .forgot-link:hover { opacity: 0.75; }

        /* Submit button */
        .auth-submit {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 14px;
            background: linear-gradient(108deg, #0a4b37, var(--ts-primary-dark));
            box-shadow: 0 10px 22px rgba(5, 150, 105, 0.26);
            color: #fff;
            font: 700 0.95rem 'Poppins', sans-serif;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .auth-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(5, 150, 105, 0.35);
        }

        /* Divider & footer links */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0 16px;
            color: var(--ts-text-muted);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .auth-divider::before,
        .auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--ts-border); }

        .auth-footer-links {
            margin: 0;
            color: var(--ts-text-muted);
            text-align: center;
            font-size: 0.86rem;
        }
        .auth-footer-links a {
            color: var(--ts-primary);
            font-weight: 700;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .auth-footer-links a:hover { opacity: 0.75; }

        .admin-link {
            display: block;
            margin-top: 12px;
            text-align: center;
            font-size: 0.78rem;
            color: var(--ts-text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .admin-link:hover { color: var(--ts-primary); }

        /* ── Responsive ─────────────────────────────────── */
        @media (max-width: 780px) {
            body { padding: 16px; }
            .auth-shell {
                grid-template-columns: 1fr;
                min-height: auto;
                border-radius: 20px;
            }
            .welcome-panel {
                min-height: 230px;
                padding: 30px 28px;
            }
            .panel-copy  { margin: 38px 0 0; }
            .panel-copy h2 { font-size: 2rem; }
            .panel-pills { display: none; }
            .form-panel  { padding: 36px 28px; }
        }
    </style>
</head>
<body>

<main class="auth-shell" role="main">

    <!-- ── Left: Welcome / Branding Panel ──────────────────────── -->
    <section class="welcome-panel" aria-label="Welcome branding">
        <div class="panel-lines"></div>

        <!-- Brand -->
        <div class="panel-content">
            <a class="panel-brand" href="<?= SITE_URL ?>/index.php">
                <span class="panel-brand-mark">
                    <img src="<?= SITE_URL ?>/assets/images/favicon.svg?v=<?= filemtime(__DIR__ . '/../assets/images/favicon.svg') ?>" alt="<?= htmlspecialchars(SITE_NAME) ?> Logo" width="24" height="24">
                </span>
                <span class="panel-brand-name"><?= htmlspecialchars(SITE_NAME) ?></span>
            </a>
        </div>

        <!-- Copy -->
        <div class="panel-content panel-copy">
            <h2>Your Android Hub. Sign in &amp; explore.</h2>
            <p>Access thousands of reviewed Android APKs, manage your apps, and connect with the developer community.</p>
        </div>

        <!-- Feature pills -->
        <div class="panel-content panel-pills">
            <div class="panel-pill"><i class="ri-shield-check-fill"></i> Secure, verified account access</div>
            <div class="panel-pill"><i class="ri-apps-fill"></i> Browse &amp; download APKs instantly</div>
            <div class="panel-pill"><i class="ri-code-s-slash-fill"></i> Developer tools &amp; dashboard</div>
        </div>
    </section>

    <!-- ── Right: Form Panel ────────────────────────────────────── -->
    <section class="form-panel" aria-label="Sign in form">

        <!-- Avatar icon -->
        <div class="form-avatar-wrap">
            <div class="form-avatar"><i class="ri-user-fill"></i></div>
        </div>

        <h1 class="form-title">Welcome back</h1>
        <p class="form-subtitle">Sign in to continue to your account</p>

        <!-- User / Developer tab toggle -->
        <div class="auth-tab-toggle" role="tablist" aria-label="Login type">
            <button type="button"
                    id="tabBtnUser"
                    class="auth-tab-btn <?= $tab === 'user' ? 'active' : '' ?>"
                    onclick="switchTab(event, 'user')"
                    role="tab"
                    aria-selected="<?= $tab === 'user' ? 'true' : 'false' ?>">
                <i class="ri-user-line me-1"></i> User
            </button>
            <button type="button"
                    id="tabBtnDev"
                    class="auth-tab-btn <?= $tab === 'developer' ? 'active' : '' ?>"
                    onclick="switchTab(event, 'developer')"
                    role="tab"
                    aria-selected="<?= $tab === 'developer' ? 'true' : 'false' ?>">
                <i class="ri-code-s-slash-line me-1"></i> Developer
            </button>
        </div>

        <!-- Error alert -->
        <?php if ($error): ?>
        <div class="auth-alert auth-alert-danger" role="alert">
            <i class="ri-error-warning-fill"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Flash message -->
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="auth-alert auth-alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>" role="alert">
            <i class="ri-information-fill"></i>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" id="loginForm" novalidate>
            <input type="hidden" name="login_type" id="login_type" value="<?= htmlspecialchars($tab) ?>">

            <!-- Email -->
            <div class="auth-field">
                <label for="email">Email Address</label>
                <i class="ri-mail-line auth-field-icon"></i>
                <input class="auth-input"
                       type="email"
                       id="email"
                       name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com"
                       autocomplete="email"
                       required>
            </div>

            <!-- Password -->
            <div class="auth-field">
                <label for="password">Password</label>
                <i class="ri-lock-line auth-field-icon"></i>
                <input class="auth-input"
                       type="password"
                       id="password"
                       name="password"
                       placeholder="Enter your password"
                       autocomplete="current-password"
                       required>
                <button type="button" class="pass-toggle" id="passToggle" onclick="togglePass()" aria-label="Toggle password visibility">
                    <i class="ri-eye-line" id="passEye"></i>
                </button>
            </div>

            <a class="forgot-link" href="#">Forgot password?</a>

            <button class="auth-submit" type="submit" id="loginSubmitBtn">
                <i class="ri-login-circle-fill"></i> SIGN IN
            </button>
        </form>

        <div class="auth-divider">New to <?= htmlspecialchars(SITE_NAME) ?>?</div>

        <p class="auth-footer-links">
            Create an account and get started. <br>
            <a href="<?= SITE_URL ?>/auth/register.php">Create your account</a>
        </p>

        <a class="admin-link" href="<?= SITE_URL ?>/admin/login.php">
            <i class="ri-shield-user-line"></i> Administrator login →
        </a>

    </section>

</main>

<script>
// Switch tab (User / Developer)
function switchTab(event, tab) {
    document.getElementById('login_type').value = tab;
    document.querySelectorAll('.auth-tab-btn').forEach(function (btn) {
        btn.classList.remove('active');
        btn.setAttribute('aria-selected', 'false');
    });
    event.currentTarget.classList.add('active');
    event.currentTarget.setAttribute('aria-selected', 'true');
}

// Toggle password visibility
function togglePass() {
    var input = document.getElementById('password');
    var icon  = document.getElementById('passEye');
    var isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    icon.className = isPass ? 'ri-eye-off-line' : 'ri-eye-line';
}

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function () {
    var btn = document.getElementById('loginSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line" style="animation:spin 1s linear infinite"></i> Signing In…';
});
</script>

</body>
</html>
