<?php
// ── review.php — Standalone reviews for an app ──────────────
// This page shows all reviews. Review submit is handled in app.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$stmt = $db->prepare("SELECT a.app_name, a.avg_rating, a.total_reviews FROM apps a WHERE a.id=? AND a.status='approved' LIMIT 1");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$total   = (int)$app['total_reviews'];
$pages   = max(1, ceil($total / $perPage));

$offset = ($page - 1) * $perPage;
$stmt   = $db->prepare(
    "SELECT r.*, u.username, u.avatar FROM reviews r JOIN users u ON r.user_id=u.id
     WHERE r.app_id=? AND r.status='visible' ORDER BY r.created_at DESC LIMIT ? OFFSET ?"
);
$stmt->bindValue(1, $id,      PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset,  PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

$pageTitle = 'Reviews: ' . $app['app_name'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<div class="ts-page-header">
    <div class="container-xl">
        <h1 class="ts-page-title">Reviews: <?= htmlspecialchars($app['app_name']) ?></h1>
        <div class="ts-breadcrumb"><a href="<?= SITE_URL ?>/app.php?id=<?= $id ?>">← Back to App</a></div>
    </div>
</div>
<main class="container-xl pb-5" style="max-width:700px">
    <?php include __DIR__ . '/includes/alerts.php'; ?>
    <div class="d-flex align-items-center gap-3 mb-4 ts-panel">
        <div style="font-size:3rem;font-weight:800;color:var(--ts-warning)"><?= number_format((float)$app['avg_rating'],1) ?></div>
        <div>
            <div class="ts-stars" style="font-size:1.3rem">
                <?php $r=round((float)$app['avg_rating']); for($i=1;$i<=5;$i++) echo '<span class="material-icons ts-star '.($i<=$r?'filled':'').'">'.($i<=$r?'star':'star_border').'</span>'; ?>
            </div>
            <div style="color:var(--ts-text-muted);font-size:.85rem"><?= number_format($app['total_reviews']) ?> review<?= $app['total_reviews']!=1?'s':'' ?></div>
        </div>
        <div class="ms-auto">
            <a href="<?= SITE_URL ?>/app.php?id=<?= $id ?>" class="ts-btn-primary" style="border-radius:8px">Write a Review</a>
        </div>
    </div>

    <?php if (empty($reviews)): ?>
    <div class="ts-empty ts-panel"><span class="material-icons ts-empty-icon">chat_bubble_outline</span><div class="ts-empty-title">No reviews yet</div></div>
    <?php else: ?>
    <?php foreach ($reviews as $rev): ?>
    <div class="ts-glass p-3 mb-3" style="border-radius:var(--ts-radius)">
        <div class="d-flex align-items-center gap-3 mb-2">
            <img src="<?= $rev['avatar'] ? UPLOAD_URL . 'avatars/' . $rev['avatar'] : SITE_URL . '/assets/images/default-avatar.svg' ?>" class="ts-avatar-sm" style="width:38px;height:38px">
            <div>
                <div style="font-weight:600"><?= htmlspecialchars($rev['username']) ?></div>
                <div class="ts-stars"><?php for($i=1;$i<=5;$i++) echo '<span class="material-icons ts-star '.($i<=$rev['rating']?'filled':'').'" style="font-size:.85rem">'.($i<=$rev['rating']?'star':'star_border').'</span>'; ?> <span style="font-size:.75rem;color:var(--ts-text-muted);margin-left:4px"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span></div>
            </div>
        </div>
        <?php if ($rev['title']): ?><div style="font-weight:600;margin-bottom:4px"><?= htmlspecialchars($rev['title']) ?></div><?php endif; ?>
        <?php if ($rev['body']): ?><p style="color:var(--ts-text-secondary);font-size:.88rem;margin:0"><?= nl2br(htmlspecialchars($rev['body'])) ?></p><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if ($pages > 1): ?>
    <div class="ts-pagination">
        <?php for ($i=1;$i<=$pages;$i++): ?><a href="?id=<?= $id ?>&page=<?= $i ?>" class="ts-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
