<?php
// ── download.php — Secure APK Download Handler ───────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$stmt = $db->prepare("SELECT * FROM apps WHERE id=? AND status='approved' LIMIT 1");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) { http_response_code(404); die('App not found.'); }

$filePath = STORAGE . 'apk/' . basename($app['apk_file']);

if (!file_exists($filePath)) {
    http_response_code(404);
    // Redirect back with error
    setFlash('error', 'APK file not found on server. Please contact the developer.');
    header('Location: ' . SITE_URL . '/app.php?id=' . $id);
    exit;
}

// Log download
$userId = isLoggedIn() && isUser() ? (int)$_SESSION['user_id'] : null;
$rawIp  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip     = trim(explode(',', $rawIp)[0]); // take only the first IP (proxy chains send multiples)
$ua     = $_SERVER['HTTP_USER_AGENT'] ?? null;

try {
    $ins = $db->prepare("INSERT INTO downloads (app_id, user_id, ip_address, user_agent) VALUES (?,?,?,?)");
    $ins->execute([$id, $userId, substr($ip, 0, 45), $ua]);
    $db->prepare("UPDATE apps SET total_downloads = total_downloads + 1 WHERE id=?")->execute([$id]);
} catch (Exception $e) {
    // Don't fail download if logging fails
}

// Stream APK — clean any buffered output first to avoid corrupting binary stream
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $app['app_name']) . '_v' . $app['version'] . '.apk';

// Discard any accidental output (e.g., from session cookie headers or whitespace in includes)
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache');
header('Pragma: no-cache');

readfile($filePath);
exit;
