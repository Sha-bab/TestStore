<?php
// ── admin/apps.php — All Apps List ──────────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $appId  = (int)($_POST['app_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($appId) {
        if ($action === 'approve') {
            $db->prepare("UPDATE apps SET status='approved', rejection_reason=NULL WHERE id=?")->execute([$appId]);
            setFlash('success', 'App approved and published to the store!');
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? 'Rejected by admin.');
            $db->prepare("UPDATE apps SET status='rejected', rejection_reason=? WHERE id=?")->execute([$reason, $appId]);
            setFlash('success', 'App rejected.');
        } elseif ($action === 'remove') {
            $db->prepare("UPDATE apps SET status='removed' WHERE id=?")->execute([$appId]);
            setFlash('success', 'App removed from store.');
        } elseif ($action === 'restore') {
            $db->prepare("UPDATE apps SET status='approved' WHERE id=?")->execute([$appId]);
            setFlash('success', 'App restored to store.');
        }
    }
    $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ' . SITE_URL . '/admin/apps.php' . $qs); exit;
}

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$cat    = $_GET['cat'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where = ['1=1']; $params = [];
if ($q !== '')     { $where[] = "(a.app_name LIKE ? OR a.package_name LIKE ?)"; $like="%$q%"; $params = array_merge($params, [$like, $like]); }
if ($status !== '') { $where[] = "a.status=?"; $params[] = $status; }
if ($cat !== '')    { $where[] = "a.category=?"; $params[] = $cat; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM apps a $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT a.*, d.username as dev_name FROM apps a
     JOIN developers d ON a.developer_id=d.id
     $whereSQL ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$apps = $stmt->fetchAll();

$cats = $db->query("SELECT DISTINCT category FROM apps ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$activeMenu = 'apps';
$pageTitle  = 'All Apps';
include __DIR__ . '/../includes/header.php';
?>
<div class="ts-admin-layout">
<?php include __DIR__ . '/admin_sidebar.php'; ?>

    <?php /* ts-main-content opened by admin_sidebar.php */ ?>
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="ts-alert ts-alert-<?= $flash['type']==='success'?'success':'danger' ?> fade-in"
         style="border-radius:0;border-left:none;border-right:none;border-top:none;margin:0">
        <span class="material-icons"><?= $flash['type']==='success'?'check_circle':'error' ?></span>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <div class="ts-content-area">

        <!-- Page Header -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <div>
                <h1 class="ts-page-title mb-1" style="font-size:1.4rem">
                    <span class="material-icons ts-section-icon">apps</span>
                    All Apps
                    <span style="font-weight:400;font-size:1rem;color:var(--ts-text-muted)">(<?= number_format($total) ?>)</span>
                </h1>
                <p class="mb-0" style="color:var(--ts-text-muted);font-size:.85rem">
                    Manage all apps across the platform
                </p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="ts-glass p-3 mb-4 d-flex gap-3 flex-wrap align-items-end" style="border-radius:var(--ts-radius)">
            <div style="flex:1;min-width:160px">
                <label class="ts-label">Search</label>
                <input type="search" name="q" class="ts-input" placeholder="App name, package…" value="<?= htmlspecialchars($q) ?>">
            </div>
            <div>
                <label class="ts-label">Status</label>
                <select name="status" class="ts-select" style="min-width:130px">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','approved','rejected','removed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="ts-label">Category</label>
                <select name="cat" class="ts-select" style="min-width:140px">
                    <option value="">All Categories</option>
                    <?php foreach ($cats as $c): ?>
                    <option value="<?= $c ?>" <?= $cat===$c?'selected':'' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="ts-btn-primary" style="border-radius:8px;padding:10px 20px">
                <span class="material-icons me-1">search</span>Search
            </button>
            <?php if ($q || $status || $cat): ?>
            <a href="?" class="ts-btn-ghost" style="border-radius:8px;padding:10px 16px">
                <span class="material-icons me-1">clear</span>Clear
            </a>
            <?php endif; ?>
        </form>

        <!-- Reject modal (shared) -->
        <div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:9000;align-items:center;justify-content:center">
            <div style="background:var(--ts-bg-secondary);border:1px solid var(--ts-border);border-radius:var(--ts-radius);padding:28px;width:100%;max-width:480px;margin:16px">
                <h3 style="font-size:1.1rem;margin-bottom:4px">Reject App</h3>
                <p style="color:var(--ts-text-muted);font-size:.88rem;margin-bottom:16px" id="rejectModalAppName"></p>
                <form method="POST" id="rejectForm">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="app_id" id="rejectAppId">
                    <input type="hidden" name="action" value="reject">
                    <label class="ts-label">Rejection reason <span style="color:var(--ts-danger)">*</span></label>
                    <textarea name="rejection_reason" id="rejectReason" class="ts-input" rows="3" style="resize:vertical"
                              placeholder="Explain why this app doesn't meet guidelines…" required></textarea>
                    <div style="display:flex;gap:10px;margin-top:16px">
                        <button type="submit" class="ts-btn-danger" style="border-radius:8px;padding:10px 20px">
                            <span class="material-icons me-1">cancel</span>Confirm Reject
                        </button>
                        <button type="button" onclick="closeRejectModal()" class="ts-btn-ghost" style="border-radius:8px;padding:10px 16px">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($apps)): ?>
        <div class="ts-empty ts-panel">
            <span class="material-icons ts-empty-icon">apps</span>
            <div class="ts-empty-title">No apps found</div>
            <p>Try adjusting your search filters.</p>
        </div>
        <?php else: ?>
        <div class="ts-table-wrap">
            <table class="ts-table">
                <thead>
                    <tr>
                        <th>App</th>
                        <th>Developer</th>
                        <th>Status</th>
                        <th>Downloads</th>
                        <th>Rating</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($apps as $a): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <img src="<?= !empty($a['icon']) ? UPLOAD_URL.'icons/'.$a['icon'] : SITE_URL.'/assets/images/default-icon.svg' ?>"
                                 alt="" style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid var(--ts-border);flex-shrink:0">
                            <div>
                                <div style="font-weight:600"><?= htmlspecialchars($a['app_name']) ?></div>
                                <div style="font-size:.75rem;color:var(--ts-text-muted)"><?= htmlspecialchars($a['category']) ?> · v<?= htmlspecialchars($a['version']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--ts-text-secondary)"><?= htmlspecialchars($a['dev_name']) ?></td>
                    <td><span class="ts-status ts-status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td><?= number_format($a['total_downloads']) ?></td>
                    <td><?= number_format((float)$a['avg_rating'], 1) ?> ⭐ (<?= $a['total_reviews'] ?>)</td>
                    <td style="font-size:.78rem;color:var(--ts-text-muted)"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">

                            <?php if ($a['status'] === 'pending'): ?>
                            <!-- Approve -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="ts-btn-success"
                                        style="padding:5px 12px;font-size:.78rem;border-radius:6px"
                                        data-confirm="Approve '<?= addslashes($a['app_name']) ?>'?">
                                    <span class="material-icons" style="font-size:.9rem">check</span> Approve
                                </button>
                            </form>
                            <!-- Reject (opens modal) -->
                            <button type="button" class="ts-btn-danger"
                                    style="padding:5px 12px;font-size:.78rem;border-radius:6px"
                                    onclick="openRejectModal(<?= $a['id'] ?>, '<?= addslashes($a['app_name']) ?>')">
                                <span class="material-icons" style="font-size:.9rem">close</span> Reject
                            </button>

                            <?php elseif ($a['status'] === 'approved'): ?>
                            <!-- View -->
                            <a href="<?= SITE_URL ?>/app.php?id=<?= $a['id'] ?>" target="_blank"
                               class="ts-btn-ghost" style="padding:5px 12px;font-size:.78rem;border-radius:6px">
                                <span class="material-icons" style="font-size:.9rem">open_in_new</span> View
                            </a>
                            <!-- Remove -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="ts-btn-danger"
                                        style="padding:5px 12px;font-size:.78rem;border-radius:6px"
                                        data-confirm="Remove '<?= addslashes($a['app_name']) ?>' from the store?">
                                    <span class="material-icons" style="font-size:.9rem">delete</span> Remove
                                </button>
                            </form>

                            <?php elseif ($a['status'] === 'rejected'): ?>
                            <!-- Re-approve rejected -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="ts-btn-success"
                                        style="padding:5px 12px;font-size:.78rem;border-radius:6px"
                                        data-confirm="Override and approve '<?= addslashes($a['app_name']) ?>'?">
                                    <span class="material-icons" style="font-size:.9rem">undo</span> Re-approve
                                </button>
                            </form>

                            <?php elseif ($a['status'] === 'removed'): ?>
                            <!-- Restore -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="action" value="restore">
                                <button type="submit" class="ts-btn-success"
                                        style="padding:5px 12px;font-size:.78rem;border-radius:6px"
                                        data-confirm="Restore '<?= addslashes($a['app_name']) ?>' to the store?">
                                    <span class="material-icons" style="font-size:.9rem">restore</span> Restore
                                </button>
                            </form>
                            <?php endif; ?>

                            <!-- Review link (always shown for pending) -->
                            <?php if ($a['status'] === 'pending'): ?>
                            <a href="<?= SITE_URL ?>/admin/app-approval.php?id=<?= $a['id'] ?>"
                               class="ts-btn-ghost" style="padding:5px 12px;font-size:.78rem;border-radius:6px">
                                <span class="material-icons" style="font-size:.9rem">preview</span> Review
                            </a>
                            <?php endif; ?>

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
            <a href="?q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&cat=<?= urlencode($cat) ?>&page=<?= $i ?>"
               class="ts-page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div><!-- /ts-content-area -->
</div><!-- /ts-main-content -->
</div><!-- /ts-admin-layout -->

<script>
function openRejectModal(appId, appName) {
    document.getElementById('rejectAppId').value = appId;
    document.getElementById('rejectModalAppName').textContent = 'Rejecting: ' + appName;
    document.getElementById('rejectReason').value = '';
    const m = document.getElementById('rejectModal');
    m.style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
