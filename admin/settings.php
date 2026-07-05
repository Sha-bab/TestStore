<?php
// ── admin/settings.php — Site Settings Editor ────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    // Fetch allowed keys from DB to prevent arbitrary key injection
    $allowedKeys = array_column($db->query("SELECT setting_key FROM site_settings")->fetchAll(), 'setting_key');
    $stmt = $db->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key=?");
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $allowedKeys, true)) continue;
        $stmt->execute([trim($value), $key]);
    }
    setFlash('success', 'Settings saved successfully!');
    header('Location: ' . SITE_URL . '/admin/settings.php'); exit;
}

$settings = $db->query("SELECT * FROM site_settings ORDER BY id ASC")->fetchAll();

$activeMenu = 'settings';
$pageTitle  = 'Site Settings';
include __DIR__ . '/../includes/header.php';
?>
<div class="ts-admin-layout">
<?php include __DIR__ . '/admin_sidebar.php'; ?>
<div class="ts-content-area">

    <?php $flash = getFlash(); if ($flash): ?><div class="ts-alert ts-alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-4 fade-in"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>

    <h1 class="ts-page-title mb-4" style="font-size:1.5rem"><span class="material-icons ts-section-icon">settings</span> Site Settings</h1>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="ts-panel" style="max-width:700px">
            <?php foreach ($settings as $s): ?>
            <div class="ts-form-group" style="border-bottom:1px solid var(--ts-border);padding-bottom:1.2rem;margin-bottom:1.2rem">
                <label class="ts-label" for="setting_<?= htmlspecialchars($s['setting_key']) ?>"><?= htmlspecialchars($s['setting_key']) ?></label>
                <?php if (str_contains($s['setting_key'], 'allow_') || $s['setting_value'] === '0' || $s['setting_value'] === '1'): ?>
                <select name="<?= htmlspecialchars($s['setting_key']) ?>" id="setting_<?= htmlspecialchars($s['setting_key']) ?>" class="ts-select">
                    <option value="1" <?= $s['setting_value']==='1'?'selected':'' ?>>Enabled (1)</option>
                    <option value="0" <?= $s['setting_value']==='0'?'selected':'' ?>>Disabled (0)</option>
                </select>
                <?php else: ?>
                <input type="text" name="<?= htmlspecialchars($s['setting_key']) ?>" id="setting_<?= htmlspecialchars($s['setting_key']) ?>"
                       class="ts-input" value="<?= htmlspecialchars($s['setting_value'] ?? '') ?>">
                <?php endif; ?>
                <?php if ($s['description']): ?>
                <div class="ts-input-help"><?= htmlspecialchars($s['description']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="d-flex gap-3">
                <button type="submit" name="_submit" class="ts-btn-primary" style="border-radius:8px;padding:11px 28px">
                    <span class="material-icons me-2">save</span>Save Settings
                </button>
            </div>
        </div>
    </form>

</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
