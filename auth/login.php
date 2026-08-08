<?php
// ── auth/login.php — Unified Login ───────────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php'); exit;
}

$db     = getDB();
$error  = '';
$tab    = $_GET['role'] ?? 'user'; // 'user' or 'developer'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab    = $_POST['login_type'] ?? 'user';
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Email and password are required.';
    } else {
        if ($tab === 'user') {
            $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND status='active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($pass, $user['password'])) {
                loginUser($user, 'user');
                setFlash('success', 'Welcome back, ' . $user['username'] . '!');
                header('Location: ' . SITE_URL . '/index.php'); exit;
            } else {
                $error = 'Invalid credentials or account blocked.';
            }
        } else {
            $stmt = $db->prepare("SELECT * FROM developers WHERE email=? AND status='active' AND role IN ('developer', 'admin') LIMIT 1");
            $stmt->execute([$email]);
            $dev = $stmt->fetch();
            if ($dev && password_verify($pass, $dev['password'])) {
                $dev['avatar'] = $dev['profile_photo'] ?? null;
                $userRole = $dev['role'] ?? 'developer';
                loginUser($dev, $userRole);
                if ($userRole === 'admin') {
                    setFlash('success', 'Welcome, Administrator!');
                    header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit;
                } else {
                    setFlash('success', 'Welcome back, ' . $dev['username'] . '!');
                    header('Location: ' . SITE_URL . '/developer/dashboard.php'); exit;
                }
            } else {
                $error = 'Invalid credentials or account blocked.';
            }
        }
    }
}

$pageTitle = 'Sign In';
$metaDesc  = 'Sign in to TEST STORE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom.css?v=<?= filemtime(__DIR__ . '/../assets/css/custom.css') ?>">
</head>
<body>
<div class="ts-auth-wrap">
    <div class="ts-auth-card">
        <div class="ts-auth-logo">
            <a href="<?= SITE_URL ?>/index.php" class="ts-brand d-inline-flex align-items-center gap-2">
                <img src="<?= SITE_URL ?>/assets/images/logo.svg" alt="Logo" width="30" height="30" style="filter:drop-shadow(0 0 6px rgba(16,185,129,0.4))">
                <span class="ts-brand-text"><?= SITE_NAME ?></span>
            </a>
        </div>
        <h1 class="ts-auth-title">Welcome Back</h1>
        <p class="ts-auth-sub">Sign in to your account</p>

        <!-- Tab toggle -->
        <div class="ts-tab-toggle">
            <button type="button" class="ts-tab-btn <?= $tab==='user'?'active':'' ?>" onclick="switchTab(event,'user')">User</button>
            <button type="button" class="ts-tab-btn <?= $tab==='developer'?'active':'' ?>" onclick="switchTab(event,'developer')">Developer</button>
        </div>

        <?php if ($error): ?>
        <div class="ts-alert ts-alert-danger mb-3 fade-in">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="ts-alert ts-alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-3 fade-in">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <input type="hidden" name="login_type" id="login_type" value="<?= htmlspecialchars($tab) ?>">
            <div class="ts-form-group">
                <label class="ts-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="ts-input"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required>
            </div>
            <div class="ts-form-group">
                <label class="ts-label" for="password">Password</label>
                <div style="position:relative">
                    <input type="password" id="password" name="password" class="ts-input"
                           placeholder="••••••••" required style="padding-right:44px">
                    <button type="button" onclick="togglePass()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--ts-text-muted);cursor:pointer;padding:0">
                        <i class="ri-eye-line" id="passEye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="ts-btn-primary w-100 mt-2" style="border-radius:8px;justify-content:center;padding:13px">
                <i class="ri-login-circle-fill me-2"></i> Sign In
            </button>
        </form>

        <hr class="ts-divider">
        <p class="text-center text-secondary-ts" style="font-size:.85rem">
            Don't have an account?
            <a href="<?= SITE_URL ?>/auth/register.php" style="color:var(--ts-primary);font-weight:600">Create one</a>
        </p>
        <p class="text-center" style="font-size:.82rem;margin-top:.5rem">
            <a href="<?= SITE_URL ?>/admin/login.php" style="color:var(--ts-text-muted)">Admin Login →</a>
        </p>
    </div>
</div>

<script>
function switchTab(event, tab) {
    document.getElementById('login_type').value = tab;
    document.querySelectorAll('.ts-tab-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}
function togglePass() {
    const input = document.getElementById('password');
    const eye   = document.getElementById('passEye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.replace('ri-eye-line', 'ri-eye-off-line');
    } else {
        input.type = 'password';
        eye.classList.replace('ri-eye-off-line', 'ri-eye-line');
    }
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
