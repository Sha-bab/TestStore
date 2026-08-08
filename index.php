<?php
// ── index.php — Home Page ────────────────────────────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/card.php';

$db = getDB();

$pageTitle = 'Home';
$metaDesc  = 'Discover and download the best Android APK apps on TEST STORE — free, safe, and fast.';

// ── Fetch stats ───────────────────────────────────────────────
$totalApps = (int)$db->query("SELECT COUNT(*) FROM apps WHERE status='approved'")->fetchColumn();
$totalDevs = (int)$db->query("SELECT COUNT(*) FROM developers WHERE role='developer'")->fetchColumn();
$totalDLs  = (int)$db->query("SELECT COALESCE(SUM(total_downloads),0) FROM apps WHERE status='approved'")->fetchColumn();

// ── Featured apps (most downloaded) ─────────────────────────
$featured = $db->query(
    "SELECT a.*, d.username as dev_name
     FROM apps a JOIN developers d ON a.developer_id = d.id
     WHERE a.status='approved'
     ORDER BY a.total_downloads DESC LIMIT 8"
)->fetchAll();

// ── Newest apps ───────────────────────────────────────────────
$newest = $db->query(
    "SELECT a.*, d.username as dev_name
     FROM apps a JOIN developers d ON a.developer_id = d.id
     WHERE a.status='approved'
     ORDER BY a.created_at DESC LIMIT 8"
)->fetchAll();

// ── Top rated ────────────────────────────────────────────────
$topRated = $db->query(
    "SELECT a.*, d.username as dev_name
     FROM apps a JOIN developers d ON a.developer_id = d.id
     WHERE a.status='approved' AND a.total_reviews >= 0
     ORDER BY a.avg_rating DESC, a.total_reviews DESC LIMIT 8"
)->fetchAll();

// ── Category counts ───────────────────────────────────────────
$catRows = $db->query(
    "SELECT category, COUNT(*) as cnt FROM apps WHERE status='approved' GROUP BY category ORDER BY cnt DESC LIMIT 10"
)->fetchAll();

$categories = [
    'Games'         => 'ri-gamepad-fill',
    'Tools'         => 'ri-tools-fill',
    'Social'        => 'ri-group-fill',
    'Entertainment' => 'ri-film-fill',
    'Education'     => 'ri-book-open-fill',
    'Productivity'  => 'ri-flashlight-fill',
    'Finance'       => 'ri-coins-fill',
    'Health'        => 'ri-heart-pulse-fill',
    'Photography'   => 'ri-camera-fill',
    'Music'         => 'ri-music-2-fill',
];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/alerts.php';
?>

<!-- ── Hero Banner ────────────────────────────────────────── -->
<section class="ts-hero">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-lg-8 fade-in-up">
                <h1 class="ts-hero-title">
                    Download  &amp; Test <br>
                    Your Android Apps
                </h1>
                <p class="ts-hero-sub mt-3">
                    Your trusted platform for safe, fast Test and Download
                    Thousands of apps reviewed and approved by our team.
                </p>

                <!-- Hero Search -->
                <form action="<?= SITE_URL ?>/search.php" method="GET">
                    <div class="ts-hero-search-wrap">
                        <i class="ri-search-2-line" style="color:var(--ts-text-muted);margin-right:6px;font-size:1.1rem"></i>
                        <input class="ts-hero-search" type="search" name="q"
                               placeholder="Search apps, games, tools…" autocomplete="off">
                        <button type="submit" class="ts-hero-search-btn">Search</button>
                    </div>
                </form>

                <!-- Stats -->
                <div class="ts-hero-stats">
                    <div class="ts-hero-stat">
                        <div class="ts-hero-stat-num" data-count="<?= $totalApps ?>"><?= number_format($totalApps) ?></div>
                        <div class="ts-hero-stat-label">Total Apps</div>
                    </div>
                    <div class="ts-hero-stat">
                        <div class="ts-hero-stat-num" data-count="<?= $totalDevs ?>"><?= number_format($totalDevs) ?></div>
                        <div class="ts-hero-stat-label">Developers</div>
                    </div>
                    <div class="ts-hero-stat">
                        <div class="ts-hero-stat-num" data-count="<?= $totalDLs ?>"><?= number_format($totalDLs) ?></div>
                        <div class="ts-hero-stat-label">Downloads</div>
                    </div>
                </div>
            </div>

    </div>
</section>

<main class="container-xl py-5">

    <!-- ── Category Strip ─────────────────────────────────── -->
    <section class="mb-5 fade-in-up" style="animation-delay:.1s">
        <div class="ts-section-head">
            <h2 class="ts-section-title">
                <i class="ri-layout-grid-fill ts-section-icon"></i>
                Browse Categories
            </h2>
            <a href="<?= SITE_URL ?>/search.php" class="ts-see-all">
                All Apps <i class="ri-arrow-right-s-line" style="font-size:1rem"></i>
            </a>
        </div>
        <div class="ts-cat-strip">
            <?php foreach ($categories as $name => $icon): ?>
            <a href="<?= SITE_URL ?>/category.php?cat=<?= urlencode($name) ?>" class="ts-cat-pill">
                <div class="ts-cat-pill-icon">
                    <i class="<?= $icon ?>"></i>
                </div>
                <span class="ts-cat-pill-label"><?= htmlspecialchars($name) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ── Featured / Trending ────────────────────────────── -->
    <section class="mb-5 fade-in-up" style="animation-delay:.15s">
        <div class="ts-section-head">
            <h2 class="ts-section-title">
                <i class="ri-fire-fill ts-section-icon"></i>
                Most Downloaded
            </h2>
            <a href="<?= SITE_URL ?>/search.php?sort=downloads" class="ts-see-all">
                See All <i class="ri-arrow-right-s-line" style="font-size:1rem"></i>
            </a>
        </div>
        <?php if (empty($featured)): ?>
        <div class="ts-empty">
            <i class="ri-apps-2-line ts-empty-icon"></i>
            <div class="ts-empty-title">No apps yet</div>
            <p>Be the first to publish an app!</p>
            <?php if (!isDeveloper() && !isAdmin()): ?>
            <a href="<?= SITE_URL ?>/auth/register.php?role=developer" class="ts-btn-primary mt-2">Become a Developer</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="ts-apps-grid stagger">
            <?php foreach ($featured as $app): renderAppCard($app); endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Newest Apps ────────────────────────────────────── -->
    <section class="mb-5 fade-in-up" style="animation-delay:.2s">
        <div class="ts-section-head">
            <h2 class="ts-section-title">
                <i class="ri-sparkling-fill ts-section-icon"></i>
                Newest Apps
            </h2>
            <a href="<?= SITE_URL ?>/search.php?sort=newest" class="ts-see-all">
                See All <i class="ri-arrow-right-s-line" style="font-size:1rem"></i>
            </a>
        </div>
        <?php if (empty($newest)): ?>
        <div class="ts-empty">
            <i class="ri-box-3-line ts-empty-icon"></i>
            <div class="ts-empty-title">No apps published yet</div>
        </div>
        <?php else: ?>
        <div class="ts-apps-grid stagger">
            <?php foreach ($newest as $app): renderAppCard($app); endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Top Rated ─────────────────────────────────────── -->
    <section class="mb-5 fade-in-up" style="animation-delay:.25s">
        <div class="ts-section-head">
            <h2 class="ts-section-title">
                <i class="ri-medal-fill ts-section-icon"></i>
                Top Rated
            </h2>
            <a href="<?= SITE_URL ?>/search.php?sort=rating" class="ts-see-all">
                See All <i class="ri-arrow-right-s-line" style="font-size:1rem"></i>
            </a>
        </div>
        <?php if (empty($topRated)): ?>
        <div class="ts-empty">
            <i class="ri-star-line ts-empty-icon"></i>
            <div class="ts-empty-title">No rated apps yet</div>
        </div>
        <?php else: ?>
        <div class="ts-apps-grid stagger">
            <?php foreach ($topRated as $app): renderAppCard($app); endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Developer CTA ─────────────────────────────────── -->
    <?php if (!isDeveloper() && !isAdmin()): ?>
    <section class="fade-in-up" style="animation-delay:.3s">
        <div class="ts-glass p-4 p-md-5 text-center" style="border-radius:var(--ts-radius-lg);background:linear-gradient(135deg,rgba(99,102,241,0.08),rgba(6,182,212,0.05))">
            <i class="ri-rocket-2-fill" style="font-size:3.5rem;color:var(--ts-primary);margin-bottom:1rem;display:block"></i>
            <h2 style="font-size:1.8rem;font-weight:800;margin-bottom:.5rem">Publish Your App</h2>
            <p style="color:var(--ts-text-secondary);max-width:480px;margin:0 auto 1.5rem">
                Join thousands of developers distributing their Android apps on TEST STORE.
                Free to publish, easy to manage.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="<?= SITE_URL ?>/auth/register.php?role=developer" class="ts-btn-primary" style="padding:12px 30px;font-size:1rem">
                    <i class="ri-user-add-fill me-2" style="font-size:1rem"></i>
                    Join as Developer
                </a>
                <a href="<?= SITE_URL ?>/auth/login.php" class="ts-btn-ghost" style="padding:12px 28px;font-size:1rem">Sign In</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
