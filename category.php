<?php
// ── category.php — Category Listing ─────────────────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/card.php';

$db   = getDB();
$cat  = trim($_GET['cat'] ?? '');
$sort = $_GET['sort'] ?? 'downloads';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset  = ($page - 1) * $perPage;

if ($cat === '') {
    header('Location: ' . SITE_URL . '/search.php');
    exit;
}

$sortMap = [
    'downloads' => 'a.total_downloads DESC',
    'newest'    => 'a.created_at DESC',
    'rating'    => 'a.avg_rating DESC',
    'name'      => 'a.app_name ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['downloads'];

$countStmt = $db->prepare("SELECT COUNT(*) FROM apps a WHERE a.status='approved' AND a.category=?");
$countStmt->execute([$cat]);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

$stmt = $db->prepare(
    "SELECT a.*, d.username as dev_name
     FROM apps a JOIN developers d ON a.developer_id=d.id
     WHERE a.status='approved' AND a.category=?
     ORDER BY $orderBy LIMIT $perPage OFFSET $offset"
);
$stmt->execute([$cat]);
$apps = $stmt->fetchAll();

$catIcons = [
    'Games'=>'sports_esports','Tools'=>'build','Social'=>'people',
    'Entertainment'=>'movie','Education'=>'school','Productivity'=>'work',
    'Finance'=>'attach_money','Health'=>'favorite','Photography'=>'camera_alt','Music'=>'music_note',
];
$catIcon = $catIcons[$cat] ?? 'apps';

$pageTitle = $cat . ' Apps';
$metaDesc  = "Browse the best $cat Android apps on TEST STORE";

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="ts-page-header">
    <div class="container-xl">
        <div class="d-flex align-items-center gap-3">
            <div class="ts-stat-icon ts-stat-icon-primary" style="width:56px;height:56px;font-size:1.7rem">
                <span class="material-icons"><?= $catIcon ?></span>
            </div>
            <div>
                <h1 class="ts-page-title"><?= htmlspecialchars($cat) ?></h1>
                <div class="ts-breadcrumb">
                    <a href="<?= SITE_URL ?>/index.php">Home</a>
                    <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span>
                    <span><?= htmlspecialchars($cat) ?></span>
                    <span class="ts-breadcrumb-sep">·</span>
                    <span><?= number_format($total) ?> app<?= $total!==1?'s':'' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<main class="container-xl pb-5">
    <?php include __DIR__ . '/includes/alerts.php'; ?>

    <!-- Sort Bar -->
    <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
        <p class="mb-0 text-secondary-ts" style="font-size:.88rem">
            Showing <strong style="color:var(--ts-text-primary)"><?= number_format(count($apps)) ?></strong>
            of <strong style="color:var(--ts-text-primary)"><?= number_format($total) ?></strong> apps
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <?php foreach (['downloads'=>'Most Downloaded','newest'=>'Newest','rating'=>'Top Rated','name'=>'A–Z'] as $val=>$label): ?>
            <a href="?cat=<?= urlencode($cat) ?>&sort=<?= $val ?>"
               class="ts-page-btn <?= $sort===$val?'active':'' ?>" style="padding:0 14px;white-space:nowrap">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($apps)): ?>
    <div class="ts-empty ts-panel">
        <span class="material-icons ts-empty-icon"><?= $catIcon ?></span>
        <div class="ts-empty-title">No <?= htmlspecialchars($cat) ?> apps yet</div>
        <p>Be the first to publish in this category!</p>
        <a href="<?= SITE_URL ?>/developer/publish.php" class="ts-btn-primary mt-2">Publish App</a>
    </div>
    <?php else: ?>
    <div class="ts-apps-grid stagger">
        <?php foreach ($apps as $app): renderAppCard($app); endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="ts-pagination">
        <?php if ($page > 1): ?>
        <a href="?cat=<?= urlencode($cat) ?>&sort=<?= $sort ?>&page=<?= $page-1 ?>" class="ts-page-btn">
            <span class="material-icons" style="font-size:1rem">chevron_left</span>
        </a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
        <a href="?cat=<?= urlencode($cat) ?>&sort=<?= $sort ?>&page=<?= $i ?>"
           class="ts-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
        <a href="?cat=<?= urlencode($cat) ?>&sort=<?= $sort ?>&page=<?= $page+1 ?>" class="ts-page-btn">
            <span class="material-icons" style="font-size:1rem">chevron_right</span>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
