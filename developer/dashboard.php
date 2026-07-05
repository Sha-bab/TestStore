<?php
// ── developer/dashboard.php ───────────────────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('developer');

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

// Stats
$stApps     = $db->prepare("SELECT COUNT(*) FROM apps WHERE developer_id=?"); $stApps->execute([$uid]); $totalApps = (int)$stApps->fetchColumn();
$stDLs      = $db->prepare("SELECT COALESCE(SUM(total_downloads),0) FROM apps WHERE developer_id=?"); $stDLs->execute([$uid]); $totalDLs = (int)$stDLs->fetchColumn();
$stRating   = $db->prepare("SELECT COALESCE(AVG(avg_rating),0) FROM apps WHERE developer_id=? AND total_reviews>0"); $stRating->execute([$uid]); $avgRating = number_format((float)$stRating->fetchColumn(), 2);
$stPending  = $db->prepare("SELECT COUNT(*) FROM apps WHERE developer_id=? AND status='pending'"); $stPending->execute([$uid]); $pending = (int)$stPending->fetchColumn();

// Recent apps
$myApps = $db->prepare("SELECT * FROM apps WHERE developer_id=? ORDER BY created_at DESC LIMIT 5");
$myApps->execute([$uid]);
$myApps = $myApps->fetchAll();

// Recent downloads (last 5)
$recentDLs = $db->prepare(
    "SELECT d.downloaded_at, a.app_name, u.username as user_name
     FROM downloads d
     JOIN apps a ON d.app_id=a.id
     LEFT JOIN users u ON d.user_id=u.id
     WHERE a.developer_id=?
     ORDER BY d.downloaded_at DESC LIMIT 8"
);
$recentDLs->execute([$uid]);
$recentDLs = $recentDLs->fetchAll();

// Recent reviews
$recentRevs = $db->prepare(
    "SELECT r.rating, r.title, r.created_at, a.app_name, u.username
     FROM reviews r JOIN apps a ON r.app_id=a.id JOIN users u ON r.user_id=u.id
     WHERE a.developer_id=? ORDER BY r.created_at DESC LIMIT 5"
);
$recentRevs->execute([$uid]);
$recentRevs = $recentRevs->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/alerts.php';
?>
<div class="container-xl py-4">

    <!-- Breadcrumb -->
    <div class="ts-breadcrumb mb-4">
        <a href="<?= SITE_URL ?>/index.php">Home</a>
        <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span>
        <span>Developer Dashboard</span>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="ts-page-title" style="font-size:1.6rem">
                <span class="material-icons ts-section-icon">dashboard</span>
                Dashboard
            </h1>
            <p class="text-secondary-ts mb-0">Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        </div>
        <a href="<?= SITE_URL ?>/developer/publish.php" class="ts-btn-primary">
            <span class="material-icons me-2">upload</span>Publish New App
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4 stagger">
        <div class="col-6 col-md-3">
            <div class="ts-stat-card">
                <div class="ts-stat-icon ts-stat-icon-primary"><span class="material-icons">apps</span></div>
                <div class="ts-stat-num" data-count="<?= $totalApps ?>"><?= $totalApps ?></div>
                <div class="ts-stat-label">Total Apps</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ts-stat-card">
                <div class="ts-stat-icon ts-stat-icon-accent"><span class="material-icons">download</span></div>
                <div class="ts-stat-num" data-count="<?= $totalDLs ?>"><?= number_format($totalDLs) ?></div>
                <div class="ts-stat-label">Total Downloads</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ts-stat-card">
                <div class="ts-stat-icon ts-stat-icon-warning"><span class="material-icons">star</span></div>
                <div class="ts-stat-num"><?= $avgRating ?></div>
                <div class="ts-stat-label">Avg. Rating</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ts-stat-card">
                <div class="ts-stat-icon ts-stat-icon-warning"><span class="material-icons">pending</span></div>
                <div class="ts-stat-num" data-count="<?= $pending ?>"><?= $pending ?></div>
                <div class="ts-stat-label">Pending Review</div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-3 mb-4">
        <?php $links = [
            ['url'=>'my-apps.php',  'icon'=>'view_list',  'label'=>'My Apps',   'color'=>'primary'],
            ['url'=>'publish.php',  'icon'=>'upload',     'label'=>'Publish',   'color'=>'accent'],
            ['url'=>'analytics.php','icon'=>'bar_chart',  'label'=>'Analytics', 'color'=>'success'],
            ['url'=>'profile.php',  'icon'=>'manage_accounts','label'=>'Profile','color'=>'warning'],
        ]; ?>
        <?php foreach ($links as $l): ?>
        <div class="col-6 col-md-3">
            <a href="<?= SITE_URL ?>/developer/<?= $l['url'] ?>" class="ts-glass d-flex align-items-center gap-3 p-3 text-decoration-none ts-stat-card"
               style="height:auto">
                <div class="ts-stat-icon ts-stat-icon-<?= $l['color'] ?>" style="width:42px;height:42px;margin:0;font-size:1.2rem;flex-shrink:0">
                    <span class="material-icons"><?= $l['icon'] ?></span>
                </div>
                <span style="font-weight:600;color:var(--ts-text-primary)"><?= $l['label'] ?></span>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- My Apps list -->
        <div class="col-lg-7">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="ts-section-title"><span class="material-icons ts-section-icon">apps</span> My Apps</h2>
                <a href="<?= SITE_URL ?>/developer/my-apps.php" class="ts-see-all">View All</a>
            </div>
            <?php if (empty($myApps)): ?>
            <div class="ts-empty ts-panel">
                <span class="material-icons ts-empty-icon">rocket_launch</span>
                <div class="ts-empty-title">No apps yet</div>
                <p>Publish your first Android app!</p>
                <a href="<?= SITE_URL ?>/developer/publish.php" class="ts-btn-primary mt-2">Publish Now</a>
            </div>
            <?php else: ?>
            <div class="ts-table-wrap">
                <table class="ts-table">
                    <thead><tr><th>App</th><th>Status</th><th>Downloads</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($myApps as $a): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($a['app_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--ts-text-muted)">v<?= htmlspecialchars($a['version']) ?> · <?= htmlspecialchars($a['category']) ?></div>
                        </td>
                        <td><span class="ts-status ts-status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td><?= number_format($a['total_downloads']) ?></td>
                        <td>
                            <?php if ($a['status']==='approved'): ?>
                            <a href="<?= SITE_URL ?>/app.php?id=<?= $a['id'] ?>" class="ts-btn-ghost" style="padding:4px 10px;font-size:.78rem">View</a>
                            <?php endif; ?>
                            <a href="<?= SITE_URL ?>/developer/edit-app.php?id=<?= $a['id'] ?>" class="ts-btn-ghost ms-1" style="padding:4px 10px;font-size:.78rem">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Activity Feed -->
        <div class="col-lg-5">
            <h2 class="ts-section-title mb-3"><span class="material-icons ts-section-icon">notifications</span> Recent Activity</h2>
            <div class="ts-panel" style="padding:0">
                <?php if (empty($recentDLs) && empty($recentRevs)): ?>
                <div class="ts-empty" style="padding:30px">
                    <span class="material-icons ts-empty-icon">notifications_none</span>
                    <div class="ts-empty-title">No activity yet</div>
                </div>
                <?php else: ?>
                <?php foreach (array_slice($recentDLs, 0, 4) as $dl): ?>
                <div class="d-flex align-items-center gap-3 p-3" style="border-bottom:1px solid var(--ts-border)">
                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(6,182,212,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <span class="material-icons" style="font-size:1rem;color:var(--ts-accent)">download</span>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($dl['app_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--ts-text-muted)"><?= $dl['user_name'] ? 'by @' . htmlspecialchars($dl['user_name']) : 'Guest' ?> · <?= date('M d', strtotime($dl['downloaded_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($recentRevs as $rv): ?>
                <div class="d-flex align-items-center gap-3 p-3" style="border-bottom:1px solid var(--ts-border)">
                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(245,158,11,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <span class="material-icons" style="font-size:1rem;color:var(--ts-warning)">star</span>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.85rem;font-weight:600"><?= str_repeat('★', (int)$rv['rating']) ?> on <?= htmlspecialchars($rv['app_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--ts-text-muted)">by @<?= htmlspecialchars($rv['username']) ?> · <?= date('M d', strtotime($rv['created_at'])) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
