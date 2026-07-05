<?php
// ============================================================
//  TEST STORE — Central Configuration
//  Edit ONLY this file when deploying to cPanel
// ============================================================

// ── Database ────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_store');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── Site Identity ────────────────────────────────────────────
define('SITE_NAME',    'TEST STORE');
define('SITE_TAGLINE', 'Download Free & Premium Android Apps');
define('SITE_URL',     'http://localhost/Test%20Store');   // no trailing slash
define('ADMIN_EMAIL',  'admin@teststore.com');

// ── Storage ──────────────────────────────────────────────────
define('STORAGE',      __DIR__ . '/storage/');
define('UPLOAD_URL',   SITE_URL . '/storage/');

// ── Upload Limits ────────────────────────────────────────────
define('MAX_APK_MB',   100);   // megabytes
define('MAX_ICON_MB',  2);
define('MAX_SS_MB',    5);

// ── Security ─────────────────────────────────────────────────
define('HASH_ALGO', PASSWORD_BCRYPT);
define('SESSION_NAME', 'ts_session');

// ── Start session ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('UTC');

// ── Error display (set false in production) ──────────────────
ini_set('display_errors', 1);
error_reporting(E_ALL);
