<?php
// ── search.php — Search Results ──────────────────────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/card.php';

$db = getDB();

$q        = trim($_GET['q'] ?? '');
$sort     = $_GET['sort'] ?? 'downloads';
$cat      = $_GET['cat']  ?? '';
$type     = $_GET['type'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 24;
$offset   = ($page - 1) * $perPage;

// Build query
$where  = ["a.status='approved'"];
$params = [];

if ($q !== '') {
    $where[]  = "(a.app_name LIKE ? OR a.short_description LIKE ? OR a.keywords LIKE ?)";
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
if ($cat !== '') { $where[] = 'a.category = ?'; $params[] = $cat; }
if ($type !== '') { $where[] = 'a.app_type = ?'; $params[] = $type; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$sortMap = [
    'downloads' => 'a.total_downloads DESC',
    'newest'    => 'a.created_at DESC',
    'rating'    => 'a.avg_rating DESC, a.total_reviews DESC',
    'name'      => 'a.app_name ASC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['downloads'];

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM apps a $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

// Fetch page
$stmt = $db->prepare(
    "SELECT a.*, d.username as dev_name
     FROM apps a JOIN developers d ON a.developer_id = d.id
     $whereSQL ORDER BY $orderBy LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$apps = $stmt->fetchAll();

// Category list for sidebar
$cats = $db->query(
    "SELECT category, COUNT(*) as cnt FROM apps WHERE status='approved' GROUP BY category ORDER BY cnt DESC"
)->fetchAll();

$pageTitle = $q ? 'Search: ' . $q : 'Browse Apps';
$metaDesc  = $q ? "Search results for '$q' on TEST STORE" : 'Browse all Android apps on TEST STORE';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="ts-page-header">
    <div class="container-xl">
        <h1 class="ts-page-title">
            <?php if ($q): ?>
                Search: <span style="color:var(--ts-primary)"><?= htmlspecialchars($q) ?></span>
            <?php elseif ($cat): ?>
                <?= htmlspecialchars($cat) ?> Apps
            <?php else: ?>
                Browse All Apps
            <?php endif; ?>
        </h1>
        <div class="ts-breadcrumb mt-2">
            <a href="<?= SITE_URL ?>/index.php">Home</a>
            <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span>
            <span>Search</span>
            <?php if ($total > 0): ?>
            <span class="ts-breadcrumb-sep">·</span>
            <span><?= number_format($total) ?> result<?= $total !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<main class="container-xl pb-5">
    <?php include __DIR__ . '/includes/alerts.php'; ?>

    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="ts-filter-card">
                <!-- Search form -->
                <form action="<?= SITE_URL ?>/search.php" method="GET" class="mb-4">
                    <?php if ($cat)  echo "<input type='hidden' name='cat' value='" . htmlspecialchars($cat) . "'>"; ?>
                    <?php if ($type) echo "<input type='hidden' name='type' value='" . htmlspecialchars($type) . "'>"; ?>
                    <?php if ($sort) echo "<input type='hidden' name='sort' value='" . htmlspecialchars($sort) . "'>"; ?>
                    <div style="position:relative">
                        <span class="material-icons" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--ts-text-muted);font-size:1rem">search</span>
                        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>"
                               placeholder="Search apps…" class="ts-input" style="padding-left:34px">
                    </div>
                    <button type="submit" class="ts-btn-primary w-100 mt-2" style="border-radius:8px;justify-content:center">Search</button>
                </form>

                <!-- Sort -->
                <div class="ts-filter-title">Sort By</div>
                <div class="ts-filter-list mb-4">
                    <?php foreach (['downloads'=>'Most Downloaded','newest'=>'Newest First','rating'=>'Top Rated','name'=>'Name A–Z'] as $val=>$label): ?>
                    <div class="ts-filter-item">
                        <a href="?q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>&type=<?= urlencode($type) ?>&sort=<?= $val ?>"
                           class="<?= $sort===$val?'active':'' ?>">
                            <?= $label ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Type -->
                <div class="ts-filter-title">App Type</div>
                <div class="ts-filter-list mb-4">
                    <div class="ts-filter-item">
                        <a href="?q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>&sort=<?= urlencode($sort) ?>" class="<?= $type===''?'active':'' ?>">All Types</a>
                    </div>
                    <div class="ts-filter-item">
                        <a href="?q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>&sort=<?= urlencode($sort) ?>&type=free" class="<?= $type==='free'?'active':'' ?>">Free Apps</a>
                    </div>
                    <div class="ts-filter-item">
                        <a href="?q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>&sort=<?= urlencode($sort) ?>&type=paid" class="<?= $type==='paid'?'active':'' ?>">Paid Apps</a>
                    </div>
                </div>

                <!-- Categories -->
                <div class="ts-filter-title">Categories</div>
                <ul class="ts-filter-list">
                    <li class="ts-filter-item">
                        <a href="?q=<?= urlencode($q) ?>&sort=<?= urlencode($sort) ?>&type=<?= urlencode($type) ?>" class="<?= $cat===''?'active':'' ?>">
                            All <span class="ts-filter-count"><?= array_sum(array_column($cats,'cnt')) ?></span>
                        </a>
                    </li>
                    <?php foreach ($cats as $c): ?>
                    <li class="ts-filter-item">
                        <a href="?q=<?= urlencode($q) ?>&sort=<?= urlencode($sort) ?>&type=<?= urlencode($type) ?>&cat=<?= urlencode($c['category']) ?>"
                           class="<?= $cat===$c['category']?'active':'' ?>">
                            <?= htmlspecialchars($c['category']) ?>
                            <span class="ts-filter-count"><?= $c['cnt'] ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Results -->
        <div class="col-lg-9">
            <?php if (empty($apps)): ?>
            <div class="ts-empty ts-panel">
                <span class="material-icons ts-empty-icon">search_off</span>
                <div class="ts-empty-title">No apps found</div>
                <p>Try different keywords or browse by category.</p>
                <a href="<?= SITE_URL ?>/search.php" class="ts-btn-ghost mt-2">Clear Filters</a>
            </div>
            <?php else: ?>
            <div class="ts-apps-grid stagger">
                <?php foreach ($apps as $app): renderAppCard($app); endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="ts-pagination">
                <?php if ($page > 1): ?>
                <a href="?q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>&type=<?= urlencode($type) ?>&sort=<?= $sort ?>&page=<?= $page-1 ?>" class="ts-page-btn">
                    <span class="material-icons" style="font-size:1rem">chevron_left</span>
                </a>
                <?php endif; ?>
                <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
                <a href="?q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>&type=<?= urlencode($type) ?>&sort=<?= $sort ?>&page=<?= $i ?>"
                   class="ts-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $pages): ?>
                <a href="?q=<?= urlencode($q) ?>&cat=<?= urlencode($cat) ?>&type=<?= urlencode($type) ?>&sort=<?= $sort ?>&page=<?= $page+1 ?>" class="ts-page-btn">
                    <span class="material-icons" style="font-size:1rem">chevron_right</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
