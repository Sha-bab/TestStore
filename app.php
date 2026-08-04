<?php
// ── app.php — APK Detail Page ─────────────────────────────────
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/card.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: ' . SITE_URL . '/index.php'); exit; }

// Fetch app + developer
$stmt = $db->prepare(
    "SELECT a.*, d.username as dev_name, d.bio as dev_bio, d.profile_photo as dev_photo,
            d.country as dev_country, d.developer_type, d.id as dev_id
     FROM apps a JOIN developers d ON a.developer_id=d.id
     WHERE a.id=? AND a.status='approved' LIMIT 1"
);
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) {
    http_response_code(404);
    $pageTitle = 'App Not Found';
    include __DIR__ . '/includes/header.php';
    include __DIR__ . '/includes/navbar.php';
    echo '<div class="container py-5 text-center ts-empty"><span class="material-icons ts-empty-icon">search_off</span><div class="ts-empty-title">App not found</div><a href="' . SITE_URL . '/index.php" class="ts-btn-primary mt-3">Go Home</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Screenshots
$screenshots = [];
if ($app['screenshots']) {
    $decoded = json_decode($app['screenshots'], true);
    if (is_array($decoded)) $screenshots = $decoded;
}

// Version history
$versions = $db->prepare("SELECT * FROM app_versions WHERE app_id=? ORDER BY released_at DESC LIMIT 10");
$versions->execute([$id]);
$versions = $versions->fetchAll();

// Reviews
$reviews = $db->prepare(
    "SELECT r.*, u.username, u.avatar FROM reviews r JOIN users u ON r.user_id=u.id
     WHERE r.app_id=? AND r.status='visible' ORDER BY r.created_at DESC LIMIT 20"
);
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

// Handle review submission
$reviewError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isUser()) {
        setFlash('error', 'You must be logged in as a user to leave a review.');
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
    verifyCsrf();
    $uid    = (int)$_SESSION['user_id'];
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');

    if ($rating < 1 || $rating > 5) { $reviewError = 'Please select a star rating.'; }
    else {
        try {
            $ins = $db->prepare("INSERT INTO reviews (app_id,user_id,rating,title,body) VALUES (?,?,?,?,?)");
            $ins->execute([$id, $uid, $rating, $title ?: null, $body ?: null]);
            // Recalculate avg
            $db->prepare("UPDATE apps SET avg_rating=(SELECT AVG(rating) FROM reviews WHERE app_id=? AND status='visible'), total_reviews=(SELECT COUNT(*) FROM reviews WHERE app_id=? AND status='visible') WHERE id=?")->execute([$id,$id,$id]);
            setFlash('success', 'Review submitted! Thank you.');
            header('Location: ' . SITE_URL . '/app.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            $reviewError = 'You have already reviewed this app.';
        }
    }
}

// Similar apps
$similar = $db->prepare(
    "SELECT a.*, d.username as dev_name FROM apps a JOIN developers d ON a.developer_id=d.id
     WHERE a.status='approved' AND a.category=? AND a.id!=? ORDER BY a.total_downloads DESC LIMIT 6"
);
$similar->execute([$app['category'], $id]);
$similar = $similar->fetchAll();

$icon = !empty($app['icon']) ? UPLOAD_URL . 'icons/' . $app['icon'] : SITE_URL . '/assets/images/default-icon.svg';
$pageTitle = $app['app_name'];
$metaDesc  = $app['short_description'];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="ts-page-header">
    <div class="container-xl">
        <div class="ts-breadcrumb">
            <a href="<?= SITE_URL ?>/index.php">Home</a>
            <i class="ri-arrow-right-s-line ts-breadcrumb-sep" style="font-size:.9rem"></i>
            <a href="<?= SITE_URL ?>/category.php?cat=<?= urlencode($app['category']) ?>"><?= htmlspecialchars($app['category']) ?></a>
            <i class="ri-arrow-right-s-line ts-breadcrumb-sep" style="font-size:.9rem"></i>
            <span><?= htmlspecialchars($app['app_name']) ?></span>
        </div>
    </div>
</div>

<main class="container-xl pb-5">
    <?php include __DIR__ . '/includes/alerts.php'; ?>

    <div class="row g-4">
        <!-- Left: Main Content -->
        <div class="col-lg-8">

            <!-- App Header -->
            <div class="ts-panel mb-4 fade-in-up">
                <div class="d-flex gap-4 flex-wrap">
                    <img src="<?= htmlspecialchars($icon) ?>"
                         alt="<?= htmlspecialchars($app['app_name']) ?> icon"
                         class="ts-app-icon-lg flex-shrink-0">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                            <div>
                                <h1 style="font-size:1.6rem;margin-bottom:4px"><?= htmlspecialchars($app['app_name']) ?></h1>
                                <p style="color:var(--ts-text-muted);font-size:.88rem;margin:0">
                                    by <a href="<?= SITE_URL ?>/search.php?q=<?= urlencode($app['dev_name']) ?>" style="color:var(--ts-accent)"><?= htmlspecialchars($app['dev_name']) ?></a>
                                </p>
                            </div>
                            <span class="<?= $app['app_type']==='paid' ? 'ts-badge-paid' : 'ts-badge-free' ?>" style="font-size:.85rem;padding:4px 12px">
                                <?= strtoupper($app['app_type']) ?>
                            </span>
                        </div>

                        <p class="mt-3 mb-3" style="color:var(--ts-text-secondary)"><?= htmlspecialchars($app['short_description']) ?></p>

                        <!-- Metrics row -->
                        <div class="d-flex gap-4 flex-wrap mb-3">
                            <div class="text-center">
                                <div class="ts-stars mb-1" style="justify-content:center">
                                    <?php $r = round((float)$app['avg_rating']); for($i=1;$i<=5;$i++) echo '<i class="ri-star' . ($i<=$r?'-fill':'-line') . ' ts-star ' . ($i<=$r?'filled':'') . '"></i>'; ?>
                                </div>
                                <div style="font-size:.78rem;color:var(--ts-text-muted)"><?= number_format((float)$app['avg_rating'],1) ?> (<?= number_format($app['total_reviews']) ?> reviews)</div>
                            </div>
                            <div class="vr" style="border-color:var(--ts-border)"></div>
                            <div>
                                <div style="font-size:1.1rem;font-weight:700"><?= formatDownloads((int)$app['total_downloads']) ?></div>
                                <div style="font-size:.78rem;color:var(--ts-text-muted)">Downloads</div>
                            </div>
                            <div class="vr" style="border-color:var(--ts-border)"></div>
                            <div>
                                <div style="font-size:1.1rem;font-weight:700"><?= htmlspecialchars($app['version']) ?></div>
                                <div style="font-size:.78rem;color:var(--ts-text-muted)">Version</div>
                            </div>
                            <div class="vr" style="border-color:var(--ts-border)"></div>
                            <div>
                                <div style="font-size:1.1rem;font-weight:700"><?= formatBytes((int)$app['apk_size']) ?></div>
                                <div style="font-size:.78rem;color:var(--ts-text-muted)">Size</div>
                            </div>
                        </div>

                        <a href="<?= SITE_URL ?>/download.php?id=<?= $id ?>" class="ts-btn-accent">
                            <i class="ri-download-cloud-2-fill me-2"></i>
                            Download APK — <?= formatBytes((int)$app['apk_size']) ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Screenshots -->
            <?php if (!empty($screenshots)): ?>
            <div class="ts-panel mb-4 fade-in-up" style="animation-delay:.1s">
                <h2 class="ts-section-title mb-3"><i class="ri-image-2-fill ts-section-icon"></i> Screenshots</h2>
                <div class="row g-2">
                    <?php foreach ($screenshots as $ss): ?>
                    <div class="col-6 col-md-4">
                        <img src="<?= htmlspecialchars(UPLOAD_URL . 'screenshots/' . $ss) ?>"
                             alt="Screenshot" class="ts-screenshot w-100"
                             style="height:200px;object-fit:cover;border-radius:var(--ts-radius-sm)"
                             onerror="this.style.display='none'">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabs: Description / Version History -->
            <div class="ts-panel mb-4 fade-in-up" style="animation-delay:.15s">
                <div class="ts-tabs mb-0" data-ts-tabs="">
                    <button class="ts-tab active" data-ts-tab="tab-desc">Description</button>
                    <button class="ts-tab" data-ts-tab="tab-versions">Version History</button>
                    <?php if ($app['release_notes']): ?>
                    <button class="ts-tab" data-ts-tab="tab-notes">Release Notes</button>
                    <?php endif; ?>
                </div>

                <div id="tab-desc" class="ts-tab-content active">
                    <div style="line-height:1.8;color:var(--ts-text-secondary);white-space:pre-line">
                        <?= nl2br(htmlspecialchars($app['full_description'])) ?>
                    </div>
                    <?php if ($app['keywords']): ?>
                    <div class="mt-3">
                        <?php foreach (explode(',', $app['keywords']) as $kw): ?>
                        <span style="display:inline-block;background:var(--ts-bg-glass);border:1px solid var(--ts-border);border-radius:50px;padding:2px 10px;font-size:.75rem;margin:3px;color:var(--ts-text-muted)"><?= htmlspecialchars(trim($kw)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="tab-versions" class="ts-tab-content">
                    <?php if (empty($versions)): ?>
                    <p class="text-muted-ts">No version history available.</p>
                    <?php else: ?>
                    <div class="ts-table-wrap">
                        <table class="ts-table">
                            <thead><tr>
                                <th>Version</th><th>Size</th><th>Min Android</th><th>Released</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($versions as $v): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($v['version']) ?></strong></td>
                                <td><?= formatBytes((int)$v['apk_size']) ?></td>
                                <td>Android <?= htmlspecialchars($v['min_android']) ?>+</td>
                                <td><?= date('M d, Y', strtotime($v['released_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($app['release_notes']): ?>
                <div id="tab-notes" class="ts-tab-content">
                    <div style="color:var(--ts-text-secondary);white-space:pre-line;line-height:1.7">
                        <?= nl2br(htmlspecialchars($app['release_notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Reviews Section -->
            <div class="ts-panel mb-4 fade-in-up" style="animation-delay:.2s">
                <h2 class="ts-section-title mb-4">
                    <i class="ri-star-smile-fill ts-section-icon"></i>
                    Reviews &amp; Ratings
                </h2>

                <!-- Submit Review Form -->
                <?php if (isUser()): ?>
                <div class="ts-glass p-3 mb-4" style="border-radius:var(--ts-radius)">
                    <h3 style="font-size:1rem;margin-bottom:1rem">Write a Review</h3>
                    <?php if ($reviewError): ?>
                    <div class="ts-alert ts-alert-danger mb-3"><?= htmlspecialchars($reviewError) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="submit_review" value="1">
                        <div class="ts-form-group">
                            <label class="ts-label">Your Rating</label>
                            <div class="ts-star-input-wrap d-flex gap-1 mb-1" style="font-size:1.8rem" id="starPicker">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="ri-star-line ts-star-pick"
                                      data-value="<?= $i ?>"
                                      style="cursor:pointer;color:var(--ts-text-muted);transition:color .15s"
                                      onclick="setRating(<?= $i ?>)"
                                      onmouseenter="previewRating(<?= $i ?>)"
                                      onmouseleave="resetPreview()"></i>
                                <?php endfor; ?>
                                <input type="hidden" name="rating" id="ratingInput" value="0">
                            </div>
                            <div id="ratingMsg" style="font-size:.78rem;color:var(--ts-text-muted);min-height:1.2em"></div>
                        </div>
                        <div class="ts-form-group">
                            <input type="text" name="title" class="ts-input" placeholder="Review title (optional)">
                        </div>
                        <div class="ts-form-group">
                            <textarea name="body" class="ts-textarea" placeholder="Tell others about this app…" rows="3"></textarea>
                        </div>
                        <button type="submit" class="ts-btn-primary" style="border-radius:8px" id="reviewSubmitBtn">Submit Review</button>
                    </form>
                    <script>
                    const ratingLabels = ['','Terrible','Poor','Average','Good','Excellent'];
                    let selectedRating = 0;
                    function renderStars(val, selector) {
                        document.querySelectorAll('#starPicker .ts-star-pick').forEach((s, i) => {
                            s.className = (i < val ? 'ri-star-fill' : 'ri-star-line') + ' ts-star-pick';
                            s.style.color  = i < val ? 'var(--ts-warning)' : 'var(--ts-text-muted)';
                        });
                    }
                    function setRating(val) {
                        selectedRating = val;
                        document.getElementById('ratingInput').value = val;
                        renderStars(val);
                        document.getElementById('ratingMsg').textContent = ratingLabels[val] || '';
                    }
                    function previewRating(val) { renderStars(val); }
                    function resetPreview()     { renderStars(selectedRating); }
                    document.getElementById('reviewSubmitBtn').closest('form').addEventListener('submit', function(e) {
                        if (selectedRating < 1) {
                            e.preventDefault();
                            document.getElementById('ratingMsg').textContent = '⚠ Please select a star rating.';
                            document.getElementById('ratingMsg').style.color = 'var(--ts-danger, #ef4444)';
                        }
                    });
                    </script>
                </div>
                <?php elseif (!isLoggedIn()): ?>
                <div class="ts-glass p-3 mb-4 text-center" style="border-radius:var(--ts-radius)">
                    <p class="mb-2 text-secondary-ts">Sign in to leave a review</p>
                    <a href="<?= SITE_URL ?>/auth/login.php" class="ts-btn-primary" style="border-radius:8px">Sign In</a>
                </div>
                <?php endif; ?>

                <!-- Review list -->
                <?php if (empty($reviews)): ?>
                <div class="ts-empty" style="padding:30px">
                    <i class="ri-chat-3-line ts-empty-icon"></i>
                    <div class="ts-empty-title">No reviews yet</div>
                    <p>Be the first to review this app!</p>
                </div>
                <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                <div class="ts-glass p-3 mb-3" style="border-radius:var(--ts-radius)">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="<?= $rev['avatar'] ? UPLOAD_URL . $rev['avatar'] : SITE_URL . '/assets/images/default-avatar.svg' ?>"
                             alt="avatar" class="ts-avatar-sm" style="width:38px;height:38px">
                        <div>
                            <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($rev['username']) ?></div>
                            <div class="ts-stars">
                                <?php for ($i=1;$i<=5;$i++) echo '<i class="' . ($i<=$rev['rating']?'ri-star-fill':'ri-star-line') . ' ts-star ' . ($i<=$rev['rating']?'filled':'') . '" style="font-size:.85rem"></i>'; ?>
                                <span style="font-size:.75rem;color:var(--ts-text-muted);margin-left:4px"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if ($rev['title']): ?><div style="font-weight:600;margin-bottom:4px"><?= htmlspecialchars($rev['title']) ?></div><?php endif; ?>
                    <?php if ($rev['body']): ?><p style="color:var(--ts-text-secondary);font-size:.88rem;margin:0"><?= nl2br(htmlspecialchars($rev['body'])) ?></p><?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Sidebar -->
        <div class="col-lg-4">

            <!-- Technical Info -->
            <div class="ts-panel mb-4 fade-in-up" style="animation-delay:.05s">
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem">App Information</h3>
                <dl style="margin:0">
                    <?php $infos = [
                        ['label'=>'Package','value'=>$app['package_name']],
                        ['label'=>'Category','value'=>$app['category']],
                        ['label'=>'Version','value'=>$app['version']],
                        ['label'=>'Size','value'=>formatBytes((int)$app['apk_size'])],
                        ['label'=>'Min Android','value'=>'Android ' . $app['min_android_version'] . '+'],
                        ['label'=>'Target SDK','value'=>'API ' . $app['target_sdk']],
                        ['label'=>'Content Rating','value'=>$app['content_rating'] ?: 'Everyone'],
                        ['label'=>'Updated','value'=>date('M d, Y', strtotime($app['updated_at']))],
                    ]; ?>
                    <?php foreach ($infos as $info): ?>
                    <div class="d-flex justify-content-between align-items-start py-2" style="border-bottom:1px solid var(--ts-border)">
                        <dt style="font-size:.82rem;color:var(--ts-text-muted);font-weight:500;flex-shrink:0;margin-right:12px"><?= $info['label'] ?></dt>
                        <dd style="font-size:.85rem;text-align:right;word-break:break-all;margin:0"><?= htmlspecialchars($info['value'] ?? '—') ?></dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
                <?php if ($app['privacy_policy_url']): ?>
                <a href="<?= htmlspecialchars($app['privacy_policy_url']) ?>" target="_blank" rel="noopener"
                   class="d-flex align-items-center gap-1 mt-3" style="font-size:.82rem;color:var(--ts-accent)">
                    <i class="ri-shield-check-fill" style="font-size:.9rem"></i> Privacy Policy
                </a>
                <?php endif; ?>
                <?php if ($app['promo_video_url']): ?>
                <a href="<?= htmlspecialchars($app['promo_video_url']) ?>" target="_blank" rel="noopener"
                   class="d-flex align-items-center gap-1 mt-2" style="font-size:.82rem;color:var(--ts-accent)">
                    <i class="ri-play-circle-fill" style="font-size:.9rem"></i> Promo Video
                </a>
                <?php endif; ?>
            </div>

            <!-- Developer Info -->
            <div class="ts-panel mb-4 fade-in-up" style="animation-delay:.1s">
                <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem">Developer</h3>
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= $app['dev_photo'] ? UPLOAD_URL . 'icons/' . $app['dev_photo'] : SITE_URL . '/assets/images/default-avatar.svg' ?>"
                         alt="<?= htmlspecialchars($app['dev_name']) ?>" class="ts-avatar-sm" style="width:48px;height:48px">
                    <div>
                        <div style="font-weight:700"><?= htmlspecialchars($app['dev_name']) ?></div>
                        <?php if ($app['dev_country']): ?>
                        <div style="font-size:.8rem;color:var(--ts-text-muted)">
                            <i class="ri-map-pin-2-fill" style="font-size:.85rem"></i>
                            <?= htmlspecialchars($app['dev_country']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($app['dev_bio']): ?>
                <p style="font-size:.82rem;color:var(--ts-text-secondary);margin-top:.75rem;margin-bottom:0;line-height:1.6">
                    <?= nl2br(htmlspecialchars($app['dev_bio'])) ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Similar Apps -->
            <?php if (!empty($similar)): ?>
            <div class="fade-in-up" style="animation-delay:.15s">
                <h3 class="ts-section-title mb-3">
                    <i class="ri-layout-masonry-fill ts-section-icon"></i>
                    Similar Apps
                </h3>
                <?php foreach ($similar as $sa): ?>
                <a href="<?= SITE_URL ?>/app.php?id=<?= $sa['id'] ?>" class="ts-glass d-flex align-items-center gap-3 p-3 mb-2 text-decoration-none"
                   style="border-radius:var(--ts-radius);transition:all var(--ts-dur)">
                    <img src="<?= !empty($sa['icon']) ? UPLOAD_URL . 'icons/' . $sa['icon'] : SITE_URL . '/assets/images/default-icon.svg' ?>"
                         alt="" style="width:44px;height:44px;border-radius:10px;object-fit:cover">
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.88rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($sa['app_name']) ?></div>
                        <div class="ts-stars">
                            <?php $sr=round((float)$sa['avg_rating']); for($i=1;$i<=5;$i++) echo '<i class="' . ($i<=$sr?'ri-star-fill':'ri-star-line') . ' ts-star ' . ($i<=$sr?'filled':'') . '" style="font-size:.75rem"></i>'; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
