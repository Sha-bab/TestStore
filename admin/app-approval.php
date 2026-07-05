<?php
// ── admin/app-approval.php — Review Pending Apps ─────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/card.php';
requireRole('admin');

$db = getDB();

// Handle approve / reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $appId  = (int)($_POST['app_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($appId) {
        if ($action === 'approve') {
            $db->prepare("UPDATE apps SET status='approved', rejection_reason=NULL WHERE id=?")->execute([$appId]);
            setFlash('success', 'App approved and published to the store!');
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? '');
            if (!$reason) { setFlash('error', 'Please provide a rejection reason.'); }
            else {
                $db->prepare("UPDATE apps SET status='rejected', rejection_reason=? WHERE id=?")->execute([$reason, $appId]);
                setFlash('success', 'App rejected. Developer will be notified.');
            }
        } elseif ($action === 'remove') {
            $db->prepare("UPDATE apps SET status='removed' WHERE id=?")->execute([$appId]);
            setFlash('success', 'App removed from store.');
        }
    }
    header('Location: ' . SITE_URL . '/admin/app-approval.php'); exit;
}

// Single app focus
$focusId  = (int)($_GET['id'] ?? 0);
$focusApp = null;
if ($focusId) {
    $stmt = $db->prepare(
        "SELECT a.*, d.username as dev_name, d.email as dev_email
         FROM apps a JOIN developers d ON a.developer_id=d.id
         WHERE a.id=? LIMIT 1"
    );
    $stmt->execute([$focusId]);
    $focusApp = $stmt->fetch();
}

// All pending
$pendingApps = $db->query(
    "SELECT a.*, d.username as dev_name FROM apps a
     JOIN developers d ON a.developer_id=d.id
     WHERE a.status='pending' ORDER BY a.created_at ASC"
)->fetchAll();

$activeMenu = 'approvals';
$pageTitle  = 'App Approvals';
include __DIR__ . '/../includes/header.php';
?>
<div class="ts-admin-layout">
<?php include __DIR__ . '/admin_sidebar.php'; ?>

    <?php /* ts-main-content opened by admin_sidebar.php */ ?>
    <?php $flash = getFlash(); if ($flash): ?>
    <div style="position:sticky;top:var(--ts-topbar-h);z-index:80;margin:0">
        <div class="ts-alert ts-alert-<?= $flash['type']==='success'?'success':'danger' ?> fade-in"
             style="border-radius:0;border-left:none;border-right:none;border-top:none;margin:0">
            <span class="material-icons"><?= $flash['type']==='success'?'check_circle':'error' ?></span>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="ts-content-area" style="padding-top:24px">

        <!-- Page Header -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <div>
                <h1 class="ts-page-title mb-1" style="font-size:1.4rem">
                    <span class="material-icons ts-section-icon">approval</span>
                    App Approvals
                </h1>
                <p class="mb-0" style="color:var(--ts-text-muted);font-size:.85rem">
                    Review and moderate submitted applications
                </p>
            </div>
            <?php if (!empty($pendingApps)): ?>
            <span class="ts-status ts-status-pending ms-auto" style="font-size:.85rem;padding:6px 14px">
                <?= count($pendingApps) ?> pending
            </span>
            <?php endif; ?>
        </div>

        <!-- Two-column layout -->
        <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start">

            <!-- ── LEFT: Queue ── -->
            <div style="position:sticky;top:calc(var(--ts-topbar-h) + 24px)">
                <div class="ts-panel" style="padding:0;overflow:hidden">
                    <div style="padding:14px 16px;border-bottom:1px solid var(--ts-border);display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:.75rem;font-weight:700;color:var(--ts-text-muted);text-transform:uppercase;letter-spacing:1px">
                            Pending Queue
                        </span>
                        <?php if (!empty($pendingApps)): ?>
                        <span style="background:var(--ts-warning);color:#000;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:20px">
                            <?= count($pendingApps) ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($pendingApps)): ?>
                    <div class="ts-empty" style="padding:40px 20px">
                        <span class="material-icons ts-empty-icon" style="color:var(--ts-success)">check_circle</span>
                        <div class="ts-empty-title">All caught up!</div>
                        <p style="font-size:.85rem">No apps awaiting review.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($pendingApps as $a):
                        $isActive = ($focusId === (int)$a['id']);
                    ?>
                    <a href="?id=<?= $a['id'] ?>"
                       style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid var(--ts-border);text-decoration:none;background:<?= $isActive ? 'var(--ts-primary-glow)' : 'transparent' ?>;border-left:3px solid <?= $isActive ? 'var(--ts-primary)' : 'transparent' ?>;transition:all .2s;position:relative">
                        <img src="<?= !empty($a['icon']) ? UPLOAD_URL . 'icons/' . $a['icon'] : SITE_URL . '/assets/images/default-icon.svg' ?>"
                             alt="" style="width:42px;height:42px;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid var(--ts-border)">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.88rem;font-weight:600;color:var(--ts-text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= htmlspecialchars($a['app_name']) ?>
                            </div>
                            <div style="font-size:.75rem;color:var(--ts-text-muted);margin-top:1px">
                                by <?= htmlspecialchars($a['dev_name']) ?>
                            </div>
                            <div style="font-size:.72rem;color:var(--ts-text-muted)">
                                <?= date('M d, Y', strtotime($a['created_at'])) ?>
                            </div>
                        </div>
                        <?php if ($isActive): ?>
                        <span class="material-icons" style="font-size:1rem;color:var(--ts-primary);flex-shrink:0">chevron_right</span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── RIGHT: Detail Panel ── -->
            <div>
                <?php if ($focusApp): ?>
                <?php
                $ss = json_decode($focusApp['screenshots'] ?? '[]', true) ?: [];
                $iconUrl = !empty($focusApp['icon']) ? UPLOAD_URL . 'icons/' . $focusApp['icon'] : SITE_URL . '/assets/images/default-icon.svg';
                ?>

                <!-- App Hero Header -->
                <div class="ts-panel mb-3" style="background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(6,182,212,0.05));border-color:rgba(99,102,241,0.2)">
                    <div style="display:flex;gap:20px;align-items:flex-start">
                        <img src="<?= $iconUrl ?>" alt="icon"
                             style="width:88px;height:88px;border-radius:20px;object-fit:cover;border:2px solid var(--ts-border);flex-shrink:0;box-shadow:0 8px 24px rgba(0,0,0,0.3)">
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
                                <div>
                                    <h2 style="font-size:1.5rem;margin-bottom:4px;font-weight:700">
                                        <?= htmlspecialchars($focusApp['app_name']) ?>
                                    </h2>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:8px">
                                        <span style="color:var(--ts-text-muted);font-size:.88rem">by</span>
                                        <span style="color:var(--ts-accent);font-weight:600;font-size:.9rem"><?= htmlspecialchars($focusApp['dev_name']) ?></span>
                                        <span style="color:var(--ts-border)">·</span>
                                        <span style="background:var(--ts-bg-glass);border:1px solid var(--ts-border);border-radius:20px;padding:2px 10px;font-size:.75rem;color:var(--ts-text-secondary)"><?= htmlspecialchars($focusApp['category']) ?></span>
                                        <span style="background:<?= $focusApp['app_type']==='free'?'rgba(16,185,129,0.15)':'rgba(245,158,11,0.15)' ?>;border:1px solid <?= $focusApp['app_type']==='free'?'rgba(16,185,129,0.3)':'rgba(245,158,11,0.3)' ?>;border-radius:20px;padding:2px 10px;font-size:.75rem;color:<?= $focusApp['app_type']==='free'?'var(--ts-success)':'var(--ts-warning)' ?>;font-weight:600;text-transform:uppercase"><?= $focusApp['app_type'] ?></span>
                                    </div>
                                    <div style="display:flex;gap:16px;flex-wrap:wrap">
                                        <span style="font-size:.82rem;color:var(--ts-text-muted)"><span class="material-icons" style="font-size:.9rem;vertical-align:middle;margin-right:3px">tag</span>v<?= htmlspecialchars($focusApp['version']) ?></span>
                                        <span style="font-size:.82rem;color:var(--ts-text-muted)"><span class="material-icons" style="font-size:.9rem;vertical-align:middle;margin-right:3px">storage</span><?= formatBytes((int)$focusApp['apk_size']) ?></span>
                                        <span style="font-size:.82rem;color:var(--ts-text-muted)"><span class="material-icons" style="font-size:.9rem;vertical-align:middle;margin-right:3px">schedule</span><?= date('M d, Y \a\t H:i', strtotime($focusApp['created_at'])) ?></span>
                                    </div>
                                </div>
                                <span class="ts-status ts-status-pending" style="font-size:.8rem;padding:5px 14px;flex-shrink:0">Pending Review</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Screenshots -->
                <?php if (!empty($ss)): ?>
                <div class="ts-panel mb-3">
                    <div style="font-size:.75rem;font-weight:700;color:var(--ts-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px">
                        <span class="material-icons" style="font-size:.9rem;vertical-align:middle;margin-right:4px">photo_library</span>
                        Screenshots (<?= count($ss) ?>)
                    </div>
                    <div style="display:flex;gap:10px;overflow-x:auto;padding-bottom:6px">
                        <?php foreach ($ss as $s): ?>
                        <img src="<?= UPLOAD_URL . 'screenshots/' . htmlspecialchars($s) ?>"
                             alt="screenshot" class="ts-screenshot"
                             style="height:180px;width:auto;border-radius:10px;object-fit:cover;flex-shrink:0;border:1px solid var(--ts-border);cursor:zoom-in;transition:transform .2s"
                             onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Two-column details row -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                    <!-- Description -->
                    <div class="ts-panel">
                        <div style="font-size:.75rem;font-weight:700;color:var(--ts-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">
                            <span class="material-icons" style="font-size:.9rem;vertical-align:middle;margin-right:4px">description</span>
                            Description
                        </div>
                        <p style="font-size:.88rem;color:var(--ts-text-secondary);margin-bottom:10px;font-style:italic">
                            <?= htmlspecialchars($focusApp['short_description']) ?>
                        </p>
                        <div style="font-size:.82rem;color:var(--ts-text-secondary);white-space:pre-line;max-height:160px;overflow-y:auto;background:var(--ts-bg-glass);border-radius:8px;padding:10px;border:1px solid var(--ts-border)">
                            <?= nl2br(htmlspecialchars($focusApp['full_description'])) ?>
                        </div>
                    </div>

                    <!-- Technical Info -->
                    <div class="ts-panel">
                        <div style="font-size:.75rem;font-weight:700;color:var(--ts-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">
                            <span class="material-icons" style="font-size:.9rem;vertical-align:middle;margin-right:4px">android</span>
                            Technical Details
                        </div>
                        <?php
                        $techInfo = [
                            ['label'=>'Package Name',    'icon'=>'inventory_2',  'value'=>$focusApp['package_name']],
                            ['label'=>'Min Android',     'icon'=>'phone_android','value'=>'Android '.$focusApp['min_android_version'].'+'],
                            ['label'=>'Target SDK',      'icon'=>'api',          'value'=>'API Level '.$focusApp['target_sdk']],
                            ['label'=>'Content Rating',  'icon'=>'child_care',   'value'=>$focusApp['content_rating']],
                            ['label'=>'Developer Email', 'icon'=>'email',        'value'=>$focusApp['dev_email']],
                        ];
                        foreach ($techInfo as $t): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid var(--ts-border)">
                            <span class="material-icons" style="font-size:1rem;color:var(--ts-primary);flex-shrink:0"><?= $t['icon'] ?></span>
                            <div style="flex:1;min-width:0">
                                <div style="font-size:.7rem;color:var(--ts-text-muted)"><?= $t['label'] ?></div>
                                <div style="font-size:.82rem;font-weight:600;color:var(--ts-text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($t['value']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="ts-panel" style="background:linear-gradient(135deg,rgba(10,14,26,0.8),rgba(15,22,41,0.9));border-color:rgba(99,102,241,0.3)">
                    <div style="font-size:.75rem;font-weight:700;color:var(--ts-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px">
                        <span class="material-icons" style="font-size:.9rem;vertical-align:middle;margin-right:4px">gavel</span>
                        Review Decision
                    </div>
                    <div style="display:grid;grid-template-columns:auto 1fr;gap:16px;align-items:start">
                        <!-- Approve -->
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="app_id" value="<?= $focusApp['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="ts-btn-success"
                                    style="border-radius:10px;padding:12px 28px;font-size:.92rem;justify-content:center;white-space:nowrap"
                                    data-confirm="Approve '<?= addslashes($focusApp['app_name']) ?>' and publish to the store?">
                                <span class="material-icons me-2">check_circle</span>
                                Approve & Publish
                            </button>
                        </form>
                        <!-- Reject -->
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="app_id" value="<?= $focusApp['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <div style="display:flex;gap:10px">
                                <input type="text" name="rejection_reason" class="ts-input" style="flex:1"
                                       placeholder="Explain why this app is being rejected…" required>
                                <button type="submit" class="ts-btn-danger flex-shrink-0"
                                        style="border-radius:10px;padding:12px 20px;white-space:nowrap">
                                    <span class="material-icons me-1">cancel</span>Reject
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php else: ?>
                <!-- Empty state -->
                <div class="ts-panel" style="text-align:center;padding:60px 30px">
                    <div style="width:80px;height:80px;background:var(--ts-primary-glow);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                        <span class="material-icons" style="font-size:2.5rem;color:var(--ts-primary)">touch_app</span>
                    </div>
                    <div style="font-size:1.2rem;font-weight:700;color:var(--ts-text-primary);margin-bottom:8px">Select an app to review</div>
                    <p style="color:var(--ts-text-muted);font-size:.9rem;max-width:320px;margin:0 auto">
                        <?php if (empty($pendingApps)): ?>
                            No apps are currently pending review. 🎉
                        <?php else: ?>
                            Click any app from the queue on the left to view its details and take action.
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div><!-- /grid -->

    </div><!-- /ts-content-area -->
</div><!-- /ts-main-content (opened by admin_sidebar.php) -->
</div><!-- /ts-admin-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
