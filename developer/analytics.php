<?php
// ── developer/analytics.php — Charts & Stats ─────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('developer');

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

// Daily downloads (last 30 days) across all apps
$dlStmt = $db->prepare(
    "SELECT DATE(d.downloaded_at) as dl_date, COUNT(*) as cnt
     FROM downloads d JOIN apps a ON d.app_id=a.id
     WHERE a.developer_id=? AND d.downloaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY dl_date ORDER BY dl_date ASC"
);
$dlStmt->execute([$uid]);
$dailyDLs = $dlStmt->fetchAll();

// Fill in missing days
$dlByDate = [];
foreach ($dailyDLs as $row) $dlByDate[$row['dl_date']] = (int)$row['cnt'];
$labels = []; $dlCounts = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[]   = date('M d', strtotime($date));
    $dlCounts[] = $dlByDate[$date] ?? 0;
}

// Rating distribution across all apps
$ratingStmt = $db->prepare(
    "SELECT r.rating, COUNT(*) as cnt
     FROM reviews r JOIN apps a ON r.app_id=a.id
     WHERE a.developer_id=?
     GROUP BY r.rating ORDER BY r.rating ASC"
);
$ratingStmt->execute([$uid]);
$ratingRows = $ratingStmt->fetchAll();
$ratingDist = array_fill(1, 5, 0);
foreach ($ratingRows as $row) $ratingDist[(int)$row['rating']] = (int)$row['cnt'];

// Per-app stats
$perApp = $db->prepare(
    "SELECT app_name, total_downloads, avg_rating, total_reviews, status, category
     FROM apps WHERE developer_id=? ORDER BY total_downloads DESC"
);
$perApp->execute([$uid]);
$perApp = $perApp->fetchAll();

$pageTitle = 'Analytics';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ts-page-header">
    <div class="container-xl">
        <h1 class="ts-page-title"><span class="material-icons ts-section-icon">bar_chart</span> Analytics</h1>
        <div class="ts-breadcrumb"><a href="<?= SITE_URL ?>/developer/dashboard.php">Dashboard</a> <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span> <span>Analytics</span></div>
    </div>
</div>
<div class="container-xl pb-5">
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <div class="row g-4 mb-4">
        <!-- Downloads chart -->
        <div class="col-lg-8">
            <div class="ts-chart-wrap">
                <div class="ts-chart-title">Daily Downloads — Last 30 Days</div>
                <canvas id="dlChart" height="280"></canvas>
            </div>
        </div>
        <!-- Rating distribution -->
        <div class="col-lg-4">
            <div class="ts-chart-wrap">
                <div class="ts-chart-title">Rating Distribution</div>
                <canvas id="ratingChart" height="280"></canvas>
            </div>
        </div>
    </div>

    <!-- Per-app table -->
    <div class="ts-table-wrap">
        <div style="padding:18px 18px 0;border-bottom:1px solid var(--ts-border);font-size:.82rem;font-weight:700;color:var(--ts-text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:0">Per-App Breakdown</div>
        <table class="ts-table">
            <thead><tr><th>App</th><th>Status</th><th>Downloads</th><th>Avg Rating</th><th>Reviews</th><th>Category</th></tr></thead>
            <tbody>
            <?php if (empty($perApp)): ?>
            <tr><td colspan="6" class="text-center text-secondary-ts py-4">No apps yet. <a href="<?= SITE_URL ?>/developer/publish.php" style="color:var(--ts-primary)">Publish your first app</a></td></tr>
            <?php else: ?>
            <?php foreach ($perApp as $a): ?>
            <tr>
                <td style="font-weight:600"><?= htmlspecialchars($a['app_name']) ?></td>
                <td><span class="ts-status ts-status-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                <td><?= number_format($a['total_downloads']) ?></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <span class="material-icons ts-star filled" style="font-size:.85rem">star</span>
                        <?= number_format((float)$a['avg_rating'], 1) ?>
                    </div>
                </td>
                <td><?= number_format($a['total_reviews']) ?></td>
                <td><?= htmlspecialchars($a['category']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraJS = <<<'JSEND'
<script>
const chartDefaults = {
    responsive: true,
    plugins: { legend: { labels: { color: '#94a3b8', font: { family: 'Poppins' } } } },
};
const gridColor = 'rgba(255,255,255,0.06)';
const tickColor = '#64748b';

// Downloads line chart
new Chart(document.getElementById('dlChart'), {
    type: 'line',
    data: {
        labels: LABELS_PLACEHOLDER,
        datasets: [{
            label: 'Downloads',
            data: DL_PLACEHOLDER,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#6366f1',
        }]
    },
    options: {
        ...chartDefaults,
        scales: {
            x: { ticks: { color: tickColor, maxRotation: 45, font: { family: 'Poppins', size: 11 } }, grid: { color: gridColor } },
            y: { ticks: { color: tickColor, font: { family: 'Poppins', size: 11 } }, grid: { color: gridColor }, beginAtZero: true },
        }
    }
});

// Rating doughnut
new Chart(document.getElementById('ratingChart'), {
    type: 'doughnut',
    data: {
        labels: ['★1','★2','★3','★4','★5'],
        datasets: [{
            data: RATING_PLACEHOLDER,
            backgroundColor: ['#ef4444','#f97316','#f59e0b','#84cc16','#10b981'],
            borderWidth: 0,
        }]
    },
    options: {
        ...chartDefaults,
        cutout: '60%',
    }
});
</script>
JSEND;
$extraJS = str_replace(
    ['LABELS_PLACEHOLDER', 'DL_PLACEHOLDER', 'RATING_PLACEHOLDER'],
    [
        json_encode($labels),
        json_encode(array_values($dlCounts)),
        json_encode(array_values($ratingDist)),
    ],
    $extraJS
);
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

