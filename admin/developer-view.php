<?php
// ── admin/developer-view.php — Developer Profile View ────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM developers WHERE id=? AND role='developer' LIMIT 1");
$stmt->execute([$id]);
$dev = $stmt->fetch();
if (!$dev) { setFlash('error', 'Developer not found.'); header('Location: ' . SITE_URL . '/admin/developers.php'); exit; }

$apps = $db->prepare("SELECT * FROM apps WHERE developer_id=? ORDER BY created_at DESC");
$apps->execute([$id]);
$apps = $apps->fetchAll();

$require_once = __DIR__ . '/../includes/card.php';
require_once $require_once;

$activeMenu = 'developers';
$pageTitle  = 'Developer: ' . $dev['username'];
include __DIR__ . '/../includes/header.php';
?>
<div class="ts-admin-layout">
<?php include __DIR__ . '/admin_sidebar.php'; ?>
<div class="ts-content-area">

    <div class="ts-breadcrumb mb-4">
        <a href="<?= SITE_URL ?>/admin/developers.php">Developers</a>
        <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span>
        <span><?= htmlspecialchars($dev['username']) ?></span>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="ts-panel text-center mb-4">
                <img src="<?= $dev['profile_photo'] ? UPLOAD_URL . 'icons/' . $dev['profile_photo'] : SITE_URL . '/assets/images/default-avatar.svg' ?>"
                     class="ts-avatar-lg d-block mx-auto mb-3" alt="avatar">
                <h2 style="font-size:1.2rem"><?= htmlspecialchars($dev['username']) ?></h2>
                <p style="color:var(--ts-text-muted);font-size:.85rem"><?= htmlspecialchars($dev['email']) ?></p>
                <span class="ts-status ts-status-<?= $dev['status'] ?>"><?= ucfirst($dev['status']) ?></span>
                <span class="ms-2 ts-status" style="background:var(--ts-bg-glass)"><?= ucfirst($dev['developer_type']) ?></span>
                <hr class="ts-divider">
                <dl class="text-start">
                    <?php if ($dev['country']): ?><dt style="font-size:.78rem;color:var(--ts-text-muted)">Country</dt><dd><?= htmlspecialchars($dev['country']) ?></dd><?php endif; ?>
                    <?php if ($dev['mobile']): ?><dt style="font-size:.78rem;color:var(--ts-text-muted)">Mobile</dt><dd><?= htmlspecialchars($dev['mobile']) ?></dd><?php endif; ?>
                    <dt style="font-size:.78rem;color:var(--ts-text-muted)">Joined</dt><dd><?= date('M d, Y', strtotime($dev['created_at'])) ?></dd>
                </dl>
                <?php if ($dev['bio']): ?><p style="font-size:.83rem;color:var(--ts-text-secondary);text-align:left"><?= nl2br(htmlspecialchars($dev['bio'])) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="col-lg-8">
            <h2 class="ts-section-title mb-3"><span class="material-icons ts-section-icon">apps</span> Apps (<?= count($apps) ?>)</h2>
            <?php if (empty($apps)): ?>
            <div class="ts-empty ts-panel"><span class="material-icons ts-empty-icon">apps</span><div class="ts-empty-title">No apps published</div></div>
            <?php else: ?>
            <div class="ts-table-wrap">
                <table class="ts-table">
                    <thead><tr><th>App</th><th>Status</th><th>Downloads</th><th>Rating</th><th>Submitted</th></tr></thead>
                    <tbody>
                    <?php foreach ($apps as $a): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($a['app_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--ts-text-muted)"><?= htmlspecialchars($a['package_name']) ?></div>
                        </td>
                        <td><span class="ts-status ts-status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td><?= number_format($a['total_downloads']) ?></td>
                        <td><?= number_format((float)$a['avg_rating'],1) ?> ⭐</td>
                        <td style="font-size:.78rem;color:var(--ts-text-muted)"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body></html>
