<?php
// ── includes/card.php — Reusable App Card ────────────────────
// Usage: $app must be an associative array from the apps table
// Include via: include __DIR__ . '/card.php';
// Or call directly: renderAppCard($app);

function renderAppCard(array $app, bool $showDev = false): void {
    $icon    = !empty($app['icon']) ? UPLOAD_URL . 'icons/' . $app['icon'] : SITE_URL . '/assets/images/default-icon.svg';
    $appUrl  = SITE_URL . '/app.php?id=' . (int)$app['id'];
    $catUrl  = SITE_URL . '/category.php?cat=' . urlencode($app['category'] ?? '');
    $rating  = number_format((float)($app['avg_rating'] ?? 0), 1);
    $downloads = formatDownloads((int)($app['total_downloads'] ?? 0));
    $sizeStr = formatBytes((int)($app['apk_size'] ?? 0));
    $isPaid  = ($app['app_type'] ?? 'free') === 'paid';
    ?>
<div class="ts-card" data-app-id="<?= (int)$app['id'] ?>">
    <a href="<?= $appUrl ?>" class="ts-card-inner text-decoration-none d-block">
        <div class="ts-card-header">
            <img src="<?= htmlspecialchars($icon) ?>"
                 alt="<?= htmlspecialchars($app['app_name'] ?? '') ?> icon"
                 class="ts-card-icon" loading="lazy"
                 onerror="this.src='<?= SITE_URL ?>/assets/images/default-icon.svg'">
            <?php if ($isPaid): ?>
            <span class="ts-badge-paid">PAID</span>
            <?php else: ?>
            <span class="ts-badge-free">FREE</span>
            <?php endif; ?>
        </div>
        <div class="ts-card-body">
            <h3 class="ts-card-title"><?= htmlspecialchars($app['app_name'] ?? '') ?></h3>
            <p class="ts-card-desc"><?= htmlspecialchars($app['short_description'] ?? '') ?></p>
            <div class="ts-card-meta d-flex align-items-center gap-2 flex-wrap">
                <div class="ts-stars" title="<?= $rating ?> stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= round((float)$rating) ? 'ri-star-fill' : 'ri-star-line' ?> ts-star <?= $i <= round((float)$rating) ? 'filled' : '' ?>"></i>
                    <?php endfor; ?>
                    <span class="ts-rating-num"><?= $rating ?></span>
                </div>
                <span class="ts-meta-sep">·</span>
                <span class="ts-dl-count">
                    <i class="ri-download-2-fill" style="font-size:.85rem"></i>
                    <?= $downloads ?>
                </span>
            </div>
        </div>
        <div class="ts-card-footer d-flex justify-content-between align-items-center">
            <a href="<?= $catUrl ?>" class="ts-cat-tag" onclick="event.stopPropagation()">
                <?= htmlspecialchars($app['category'] ?? '') ?>
            </a>
            <span class="ts-file-size"><?= $sizeStr ?></span>
        </div>
    </a>
</div>
    <?php
}

function formatDownloads(int $n): string {
    if ($n >= 1000000) return number_format($n / 1000000, 1) . 'M';
    if ($n >= 1000)    return number_format($n / 1000, 1) . 'K';
    return (string)$n;
}
function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
