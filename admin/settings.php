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
$extraCSS = '
<style>
/* ── Settings Page ───────────── */
.ts-settings-header {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 32px;
}
.ts-settings-header-icon {
    width: 52px; height: 52px;
    border-radius: var(--ts-radius-sm);
    background: linear-gradient(135deg, var(--ts-primary), var(--ts-accent));
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 15px var(--ts-primary-glow);
}
.ts-settings-header-icon .material-icons { color: #fff; font-size: 1.5rem; }
.ts-settings-grid {
    display: flex; flex-direction: column; gap: 1px;
    background: var(--ts-border);
    border: 1px solid var(--ts-border);
    border-radius: var(--ts-radius);
    overflow: hidden; margin-bottom: 24px;
}
.ts-setting-card {
    display: flex; align-items: center; gap: 24px;
    background: var(--ts-bg-card);
    padding: 20px 24px;
    transition: background 0.2s ease;
}
.ts-setting-card:hover { background: var(--ts-bg-card-hover); }
.ts-setting-card-left { flex: 1; min-width: 0; }
.ts-setting-label {
    font-size: .875rem; font-weight: 600;
    color: var(--ts-text-primary);
    margin-bottom: 2px; display: block;
    font-family: monospace; letter-spacing: 0.3px;
}
.ts-setting-desc { font-size: .8rem; color: var(--ts-text-muted); margin: 0; }
.ts-setting-card-right { flex-shrink: 0; width: 280px; }
.ts-setting-card-right .ts-input,
.ts-setting-card-right .ts-select { width: 100%; }
.ts-settings-footer { display: flex; justify-content: flex-end; }
.ts-save-btn { border-radius: 10px; padding: 12px 32px; font-size:.95rem; gap:8px; box-shadow: 0 4px 15px var(--ts-primary-glow); }
@media (max-width: 768px) {
    .ts-setting-card { flex-direction: column; align-items: flex-start; gap: 12px; }
    .ts-setting-card-right { width: 100%; }
}
</style>
';
include __DIR__ . '/../includes/header.php';
if (!empty($extraCSS)) echo $extraCSS;
?>
<div class="ts-admin-layout">
<?php include __DIR__ . '/admin_sidebar.php'; ?>
<div class="ts-content-area">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="ts-alert ts-alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-4 fade-in"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="ts-settings-header">
        <div class="ts-settings-header-icon">
            <span class="material-icons">settings</span>
        </div>
        <div>
            <h1 class="ts-page-title mb-0">Site Settings</h1>
            <p style="color:var(--ts-text-muted);font-size:.875rem;margin:4px 0 0">Manage your store's global configuration</p>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="ts-settings-grid">
            <?php foreach ($settings as $s):
                $key = htmlspecialchars($s['setting_key']);
                $val = htmlspecialchars($s['setting_value'] ?? '');
                $desc = htmlspecialchars($s['description'] ?? '');
                $isBool = str_contains($s['setting_key'], 'allow_') || $s['setting_value'] === '0' || $s['setting_value'] === '1';
            ?>
            <div class="ts-setting-card">
                <div class="ts-setting-card-left">
                    <label class="ts-setting-label" for="setting_<?= $key ?>"><?= $key ?></label>
                    <?php if ($desc): ?>
                    <p class="ts-setting-desc"><?= $desc ?></p>
                    <?php endif; ?>
                </div>
                <div class="ts-setting-card-right">
                    <?php if ($isBool): ?>
                    <select name="<?= $key ?>" id="setting_<?= $key ?>" class="ts-select">
                        <option value="1" <?= $s['setting_value']==='1'?'selected':'' ?>>✓ Enabled</option>
                        <option value="0" <?= $s['setting_value']==='0'?'selected':'' ?>>✗ Disabled</option>
                    </select>
                    <?php else: ?>
                    <input type="text" name="<?= $key ?>" id="setting_<?= $key ?>"
                           class="ts-input" value="<?= $val ?>">
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="ts-settings-footer">
            <button type="submit" name="_submit" class="ts-btn-primary ts-save-btn">
                <span class="material-icons">save</span>
                Save All Settings
            </button>
        </div>
    </form>

</div><!-- ts-content-area -->
</div><!-- ts-main-content -->
</div><!-- ts-admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
