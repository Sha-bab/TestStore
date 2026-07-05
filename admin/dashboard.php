<?php
// ── admin/dashboard.php — Admin Overview ─────────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

// Platform stats
$stats = [
    'users'     => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'developers'=> (int)$db->query("SELECT COUNT(*) FROM developers WHERE role='developer'")->fetchColumn(),
    'apps'      => (int)$db->query("SELECT COUNT(*) FROM apps WHERE status='approved'")->fetchColumn(),
    'pending'   => (int)$db->query("SELECT COUNT(*) FROM apps WHERE status='pending'")->fetchColumn(),
    'downloads' => (int)$db->query("SELECT COALESCE(SUM(total_downloads),0) FROM apps")->fetchColumn(),
    'reviews'   => (int)$db->query("SELECT COUNT(*) FROM reviews WHERE status='visible'")->fetchColumn(),
];

// Recent pending apps
$pending = $db->query(
    "SELECT a.*, d.username as dev_name FROM apps a JOIN developers d ON a.developer_id=d.id
     WHERE a.status='pending' ORDER BY a.created_at ASC LIMIT 5"
)->fetchAll();

// Recent registrations (users + devs combined)
$recentUsers = $db->query("SELECT username, email, 'user' as role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentDevs  = $db->query("SELECT username, email, 'developer' as role, created_at FROM developers WHERE role='developer' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent = array_slice(
    array_merge($recentUsers, $recentDevs),
    0, 8
);
usort($recent, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));

// Downloads last 7 days
$dlTrend = $db->query(
    "SELECT DATE(downloaded_at) as d, COUNT(*) as cnt FROM downloads
     WHERE downloaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY d ORDER BY d ASC"
)->fetchAll();
$dlByDate = [];
foreach ($dlTrend as $r) $dlByDate[$r['d']] = (int)$r['cnt'];
$dlLabels = []; $dlData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dlLabels[] = date('D', strtotime($date));
    $dlData[]   = $dlByDate[$date] ?? 0;
}

$activeMenu = 'dashboard';
$pageTitle  = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="ts-admin-layout">
<?php include __DIR__ . '/admin_sidebar.php'; ?>
<div class="ts-content-area">

    <?php
    // Show flash using a local display (no navbar include in admin)
    $flash = getFlash();
    if ($flash):
    ?>
    <div class="ts-alert ts-alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-4 fade-in">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="ts-page-title" style="font-size:1.5rem">Platform Dashboard</h1>
            <p class="text-secondary-ts mb-0" style="font-size:.85rem">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? '') ?>!</p>
        </div>
        <?php if ($stats['pending'] > 0): ?>
        <a href="<?= SITE_URL ?>/admin/app-approval.php" class="ts-btn-primary" style="border-radius:8px">
            <span class="material-icons me-2">approval</span>
            <?= $stats['pending'] ?> App<?= $stats['pending']!==1?'s':'' ?> Pending
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats Grid -->
    <div class="row g-3 mb-4 stagger">
        <?php $statCards = [
            ['label'=>'Total Users',      'num'=>$stats['users'],     'icon'=>'person',      'color'=>'primary'],
            ['label'=>'Developers',       'num'=>$stats['developers'],'icon'=>'code',        'color'=>'accent'],
            ['label'=>'Approved Apps',    'num'=>$stats['apps'],      'icon'=>'apps',        'color'=>'success'],
            ['label'=>'Pending Review',   'num'=>$stats['pending'],   'icon'=>'pending',     'color'=>'warning'],
            ['label'=>'Total Downloads',  'num'=>$stats['downloads'], 'icon'=>'download',    'color'=>'primary'],
            ['label'=>'Total Reviews',    'num'=>$stats['reviews'],   'icon'=>'rate_review', 'color'=>'accent'],
        ]; ?>
        <?php foreach ($statCards as $sc): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ts-stat-card">
                <div class="ts-stat-icon ts-stat-icon-<?= $sc['color'] ?>"><span class="material-icons"><?= $sc['icon'] ?></span></div>
                <div class="ts-stat-num" data-count="<?= $sc['num'] ?>"><?= number_format($sc['num']) ?></div>
                <div class="ts-stat-label"><?= $sc['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- 7-day download chart -->
        <div class="col-lg-5">
            <div class="ts-chart-wrap">
                <div class="ts-chart-title">Downloads — Last 7 Days</div>
                <canvas id="weekChart" height="200"></canvas>
            </div>
        </div>

        <!-- Pending approvals -->
        <div class="col-lg-7">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="ts-section-title"><span class="material-icons ts-section-icon">approval</span> Pending Approvals</h2>
                <a href="<?= SITE_URL ?>/admin/app-approval.php" class="ts-see-all">View All</a>
            </div>
            <?php if (empty($pending)): ?>
            <div class="ts-empty ts-panel" style="padding:30px">
                <span class="material-icons ts-empty-icon">check_circle</span>
                <div class="ts-empty-title">All clear! No pending apps.</div>
            </div>
            <?php else: ?>
            <div class="ts-table-wrap">
                <table class="ts-table">
                    <thead><tr><th>App</th><th>Developer</th><th>Submitted</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($pending as $a): ?>
                    <tr>
                        <td><div style="font-weight:600"><?= htmlspecialchars($a['app_name']) ?></div><div style="font-size:.75rem;color:var(--ts-text-muted)"><?= htmlspecialchars($a['category']) ?></div></td>
                        <td><?= htmlspecialchars($a['dev_name']) ?></td>
                        <td style="font-size:.78rem;color:var(--ts-text-muted)"><?= date('M d, Y', strtotime($a['created_at'])) ?></td>
                        <td><a href="<?= SITE_URL ?>/admin/app-approval.php?id=<?= $a['id'] ?>" class="ts-btn-primary" style="padding:4px 12px;font-size:.75rem;border-radius:6px">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Registrations -->
    <div class="mt-4">
        <h2 class="ts-section-title mb-3"><span class="material-icons ts-section-icon">person_add</span> Recent Registrations</h2>
        <div class="ts-table-wrap">
            <table class="ts-table">
                <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $u): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($u['username']) ?></td>
                    <td style="color:var(--ts-text-secondary)"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="ts-status ts-status-approved" style="text-transform:capitalize"><?= $u['role'] ?></span></td>
                    <td style="font-size:.8rem;color:var(--ts-text-muted)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /ts-content-area -->
</div><!-- /ts-admin-layout -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

<script>
new Chart(document.getElementById('weekChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dlLabels) ?>,
        datasets: [{
            label: 'Downloads',
            data: <?= json_encode($dlData) ?>,
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderColor: '#6366f1',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#64748b', font: { family: 'Poppins', size: 11 } }, grid: { display: false } },
            y: { ticks: { color: '#64748b', font: { family: 'Poppins', size: 11 } }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
        }
    }
});
</script>
</body></html>
