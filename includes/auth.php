<?php
// ── includes/auth.php — Session & role helpers ───────────────
require_once __DIR__ . '/../config.php';

// ── Flash Messages ───────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Login State ───────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}
function isUser(): bool     { return isLoggedIn() && $_SESSION['role'] === 'user'; }
function isDeveloper(): bool{ return isLoggedIn() && $_SESSION['role'] === 'developer'; }
function isAdmin(): bool    { return isLoggedIn() && $_SESSION['role'] === 'admin'; }

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'],
        'avatar'   => $_SESSION['avatar'] ?? null,
    ];
}

// ── Role Guards ───────────────────────────────────────────────
function requireLogin(string $redirect = null): void {
    if (!isLoggedIn()) {
        $redirect = $redirect ?? SITE_URL . '/auth/login.php';
        setFlash('error', 'Please log in to continue.');
        header('Location: ' . $redirect);
        exit;
    }
}
function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        setFlash('error', 'Access denied.');
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// ── Login helper ──────────────────────────────────────────────
function loginUser(array $user, string $role): void {
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $role;
    $_SESSION['avatar']   = $user['avatar'] ?? ($user['profile_photo'] ?? null);
}

// ── CSRF ──────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
