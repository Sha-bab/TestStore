<?php
// ── developer/my-apps.php — List developer's apps ────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('developer');

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

$status = $_GET['status'] ?? '';
$validStatuses = ['pending','approved','rejected','removed'];
$where = 'developer_id=?';
$params = [$uid];
if (in_array($status, $validStatuses)) { $where .= " AND status=?"; $params[] = $status; }

$apps = $db->prepare("SELECT * FROM apps WHERE $where ORDER BY created_at DESC");
$apps->execute($params);
$apps = $apps->fetchAll();

$pageTitle = 'My Apps';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ts-page-header">
    <div class="container-xl">
        <h1 class="ts-page-title"><span class="material-icons ts-section-icon">view_list</span> My Apps</h1>
        <div class="ts-breadcrumb"><a href="<?= SITE_URL ?>/developer/dashboard.php">Dashboard</a> <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span> <span>My Apps</span></div>
    </div>
</div>
<div class="container-xl pb-5">
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <!-- Status filter -->
        <div class="d-flex gap-2 flex-wrap">
            <a href="?status=" class="ts-page-btn <?= $status===''?'active':'' ?>">All</a>
            <?php foreach (['pending'=>'⏳ Pending','approved'=>'✅ Approved','rejected'=>'❌ Rejected','removed'=>'🚫 Removed'] as $s=>$label): ?>
            <a href="?status=<?= $s ?>" class="ts-page-btn <?= $status===$s?'active':'' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
        <a href="<?= SITE_URL ?>/developer/publish.php" class="ts-btn-primary"><span class="material-icons me-2">add</span>Publish New</a>
    </div>

    <?php if (empty($apps)): ?>
    <div class="ts-empty ts-panel">
        <span class="material-icons ts-empty-icon">apps</span>
        <div class="ts-empty-title">No apps found</div>
        <p>Start publishing your Android apps today!</p>
        <a href="<?= SITE_URL ?>/developer/publish.php" class="ts-btn-primary mt-2">Publish First App</a>
    </div>
    <?php else: ?>
    <div class="ts-table-wrap">
        <table class="ts-table">
            <thead><tr>
                <th>App</th><th>Status</th><th>Type</th><th>Downloads</th><th>Rating</th><th>Submitted</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($apps as $a): ?>
            <tr>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($a['app_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--ts-text-muted)"><?= htmlspecialchars($a['package_name']) ?> · v<?= htmlspecialchars($a['version']) ?></div>
                    <?php if ($a['status']==='rejected' && $a['rejection_reason']): ?>
                    <div style="font-size:.75rem;color:var(--ts-danger);margin-top:2px">
                        <span class="material-icons" style="font-size:.8rem;vertical-align:middle">error</span>
                        <?= htmlspecialchars(mb_substr($a['rejection_reason'], 0, 60)) ?>…
                    </div>
                    <?php endif; ?>
                </td>
                <td><span class="ts-status ts-status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                <td><?= ucfirst($a['app_type']) ?></td>
                <td><?= number_format($a['total_downloads']) ?></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <span class="material-icons ts-star filled" style="font-size:.9rem">star</span>
                        <?= number_format((float)$a['avg_rating'],1) ?>
                        <span style="font-size:.75rem;color:var(--ts-text-muted)">(<?= $a['total_reviews'] ?>)</span>
                    </div>
                </td>
                <td style="font-size:.8rem;color:var(--ts-text-muted)"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if ($a['status']==='approved'): ?>
                        <a href="<?= SITE_URL ?>/app.php?id=<?= $a['id'] ?>" class="ts-btn-ghost" style="padding:4px 10px;font-size:.75rem">View</a>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>/developer/edit-app.php?id=<?= $a['id'] ?>" class="ts-btn-primary" style="padding:4px 10px;font-size:.75rem;border-radius:6px">Edit</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
