<?php
// ── admin/login.php — Admin-only Login ───────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isAdmin()) { header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit; }

$db    = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email && $pass) {
        $stmt = $db->prepare("SELECT * FROM developers WHERE email=? AND role='admin' AND status='active' LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && (password_verify($pass, $admin['password']) || ($pass === 'admin123' && password_verify('password', $admin['password'])))) {
            if (!password_verify($pass, $admin['password'])) {
                $newHash = password_hash('admin123', PASSWORD_BCRYPT);
                $db->prepare("UPDATE developers SET password=? WHERE id=?")->execute([$newHash, $admin['id']]);
            }
            $admin['avatar'] = $admin['profile_photo'] ?? null;
            loginUser($admin, 'admin');
            setFlash('success', 'Welcome, Administrator!');
            header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit;
        } else {
            $error = 'Invalid admin credentials.';
        }
    } else {
        $error = 'Email and password are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom.css">
</head>
<body>
<div class="ts-auth-wrap">
    <div class="ts-auth-card" style="max-width:400px">
        <div class="ts-auth-logo">
            <div style="width:60px;height:60px;border-radius:16px;background:linear-gradient(135deg,var(--ts-primary),var(--ts-accent));display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                <span class="material-icons" style="font-size:2rem;color:#fff">admin_panel_settings</span>
            </div>
        </div>
        <h1 class="ts-auth-title">Admin Access</h1>
        <p class="ts-auth-sub">Sign in to the administration panel</p>

        <?php if ($error): ?>
        <div class="ts-alert ts-alert-danger mb-3 fade-in"><span class="material-icons">error</span> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="ts-form-group">
                <label class="ts-label">Admin Email</label>
                <input type="email" name="email" class="ts-input" required placeholder="admin@teststore.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="ts-form-group">
                <label class="ts-label">Password</label>
                <input type="password" name="password" class="ts-input" required placeholder="••••••••">
            </div>
            <button type="submit" class="ts-btn-primary w-100 mt-1" style="border-radius:8px;justify-content:center;padding:12px">
                <span class="material-icons me-2">login</span>Admin Sign In
            </button>
        </form>

        <hr class="ts-divider">
        <p class="text-center" style="font-size:.82rem"><a href="<?= SITE_URL ?>/auth/login.php" style="color:var(--ts-text-muted)">← Back to User Login</a></p>
        <div class="ts-alert ts-alert-info mt-3" style="font-size:.75rem">
            <span class="material-icons" style="font-size:.9rem">info</span>
            Default: admin@teststore.com / admin123
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
</body>
</html>
