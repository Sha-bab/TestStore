<?php
// ── admin/developers.php — Manage Developers ─────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $devId  = (int)($_POST['dev_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($devId && in_array($action, ['block','unblock','delete'])) {
        if ($action === 'block')   $db->prepare("UPDATE developers SET status='blocked' WHERE id=? AND role='developer'")->execute([$devId]);
        if ($action === 'unblock') $db->prepare("UPDATE developers SET status='active'  WHERE id=? AND role='developer'")->execute([$devId]);
        if ($action === 'delete')  $db->prepare("DELETE FROM developers WHERE id=? AND role='developer'")->execute([$devId]);
        setFlash('success', ucfirst($action) . 'ed successfully.');
    }
    header('Location: ' . SITE_URL . '/admin/developers.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')); exit;
}

// Filters
$q       = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? '';
$devType = $_GET['type'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = ["d.role='developer'"];
$params = [];
if ($q !== '') { $where[] = "(d.username LIKE ? OR d.email LIKE ?)"; $like = "%$q%"; $params = array_merge($params, [$like, $like]); }
if ($status !== '') { $where[] = "d.status=?"; $params[] = $status; }
if ($devType !== '') { $where[] = "d.developer_type=?"; $params[] = $devType; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$countStmt = $db->prepare("SELECT COUNT(*) FROM developers d $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $db->prepare(
    "SELECT d.*, (SELECT COUNT(*) FROM apps a WHERE a.developer_id=d.id) as app_count
     FROM developers d $whereSQL ORDER BY d.created_at DESC LIMIT $perPage OFFSET " . (($page-1)*$perPage)
);
$stmt->execute($params);
$devs = $stmt->fetchAll();

$activeMenu = 'developers';
$pageTitle  = 'Manage Developers';
include __DIR__ . '/../includes/header.php';
?>
<div class="ts-admin-layout">
<?php include __DIR__ . '/admin_sidebar.php'; ?>
<div class="ts-content-area">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="ts-alert ts-alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-4 fade-in">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <h1 class="ts-page-title mb-4" style="font-size:1.5rem">
        <span class="material-icons ts-section-icon">code</span> Developers
        <span class="text-secondary-ts" style="font-weight:400;font-size:1rem">(<?= number_format($total) ?> total)</span>
    </h1>

    <!-- Filter bar -->
    <form method="GET" class="ts-glass p-3 mb-4 d-flex gap-3 flex-wrap align-items-end" style="border-radius:var(--ts-radius)">
        <div style="flex:1;min-width:180px">
            <label class="ts-label">Search</label>
            <input type="search" name="q" class="ts-input" placeholder="Name or email…" value="<?= htmlspecialchars($q) ?>">
        </div>
        <div>
            <label class="ts-label">Status</label>
            <select name="status" class="ts-select" style="min-width:120px">
                <option value="">All</option>
                <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
                <option value="blocked" <?= $status==='blocked'?'selected':'' ?>>Blocked</option>
            </select>
        </div>
        <div>
            <label class="ts-label">Type</label>
            <select name="type" class="ts-select" style="min-width:130px">
                <option value="">All Types</option>
                <option value="individual" <?= $devType==='individual'?'selected':'' ?>>Individual</option>
                <option value="company" <?= $devType==='company'?'selected':'' ?>>Company</option>
            </select>
        </div>
        <button type="submit" class="ts-btn-primary" style="border-radius:8px;padding:10px 20px">
            <span class="material-icons me-1">search</span>Search
        </button>
        <?php if ($q||$status||$devType): ?>
        <a href="?" class="ts-btn-ghost" style="border-radius:8px;padding:10px 16px">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($devs)): ?>
    <div class="ts-empty ts-panel"><span class="material-icons ts-empty-icon">code</span><div class="ts-empty-title">No developers found</div></div>
    <?php else: ?>
    <div class="ts-table-wrap">
        <table class="ts-table">
            <thead><tr><th>Developer</th><th>Type</th><th>Status</th><th>Apps</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($devs as $d): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img src="<?= $d['profile_photo'] ? UPLOAD_URL . 'icons/' . $d['profile_photo'] : SITE_URL . '/assets/images/default-avatar.svg' ?>"
                             style="width:34px;height:34px;border-radius:50%;object-fit:cover">
                        <div>
                            <div style="font-weight:600"><?= htmlspecialchars($d['username']) ?></div>
                            <div style="font-size:.75rem;color:var(--ts-text-muted)"><?= htmlspecialchars($d['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= ucfirst($d['developer_type']) ?></td>
                <td><span class="ts-status ts-status-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
                <td>
                    <a href="<?= SITE_URL ?>/admin/developer-view.php?id=<?= $d['id'] ?>" style="color:var(--ts-accent)">
                        <?= (int)$d['app_count'] ?> app<?= $d['app_count']!=1?'s':'' ?>
                    </a>
                </td>
                <td style="font-size:.8rem;color:var(--ts-text-muted)"><?= date('M d, Y', strtotime($d['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="<?= SITE_URL ?>/admin/developer-view.php?id=<?= $d['id'] ?>" class="ts-btn-ghost" style="padding:4px 10px;font-size:.75rem">View</a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="dev_id" value="<?= $d['id'] ?>">
                            <input type="hidden" name="action" value="<?= $d['status']==='active'?'block':'unblock' ?>">
                            <button type="submit" class="<?= $d['status']==='active'?'ts-btn-danger':'ts-btn-success' ?>"
                                    style="padding:4px 10px;font-size:.75rem;border-radius:6px"
                                    data-confirm="<?= $d['status']==='active'?'Block':'Unblock' ?> this developer?">
                                <?= $d['status']==='active'?'Block':'Unblock' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="dev_id" value="<?= $d['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="ts-btn-danger"
                                    style="padding:4px 10px;font-size:.75rem;border-radius:6px;background:rgba(239,68,68,0.15);color:var(--ts-danger);border:1px solid var(--ts-danger)"
                                    data-confirm="DELETE developer '<?= addslashes($d['username']) ?>'? This will also delete all their apps!">
                                <span class="material-icons" style="font-size:.8rem">delete</span>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="ts-pagination mt-3">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($devType) ?>&page=<?= $i ?>"
           class="ts-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body></html>
