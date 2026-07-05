<?php
// ── auth/logout.php ───────────────────────────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

$_SESSION = [];

// Delete session cookie from browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();
header('Location: ' . SITE_URL . '/auth/login.php');
exit;
