<?php
// ── includes/navbar.php — Role-aware navigation ──────────────
require_once __DIR__ . '/../includes/auth.php';
$cu = getCurrentUser();
$siteName = getSetting('site_name', defined('SITE_NAME') ? SITE_NAME : 'TEST STORE');
$currentPage = basename($_SERVER['PHP_SELF']);
function navAvatarUrl(?string $avatar, bool $isDev = false): string {
    if (!$avatar) return SITE_URL . '/assets/images/default-avatar.svg';
    $sub = $isDev ? 'icons/' : 'avatars/';
    return UPLOAD_URL . $sub . $avatar;
}
?>
<style>
/* ── Vanilla dropdown system (replaces MDB dropdowns) ── */
.ts-dd-wrap { position: relative; }
.ts-dd-toggle { cursor: pointer; user-select: none; }
.ts-dd-menu {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 190px;
    background: #e2efe6;
    border: 1px solid var(--ts-border);
    border-radius: var(--ts-radius);
    box-shadow: 0 12px 40px rgba(0,0,0,0.6);
    padding: 6px;
    z-index: 9999;
    animation: slideDown 0.18s var(--ts-ease);
    list-style: none;
    margin: 0;
}
.ts-dd-menu.ts-dd-left { right: auto; left: 0; }
.ts-dd-menu.open { display: block; }
.ts-dd-menu li a,
.ts-dd-menu li .ts-dd-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: var(--ts-radius-sm);
    color: var(--ts-text-secondary) !important;
    text-decoration: none !important;
    font-size: .88rem;
    font-weight: 500;
    transition: all .18s;
    white-space: nowrap;
    background: transparent;
}
.ts-dd-menu li a:hover { background: rgba(255,255,255,0.07); color: var(--ts-text-primary) !important; }
.ts-dd-menu .ts-dd-divider { height: 1px; background: var(--ts-border); margin: 4px 0; }
.ts-dd-menu .ts-dd-danger { color: var(--ts-danger) !important; }
.ts-dd-menu .ts-dd-danger:hover { background: rgba(239,68,68,0.1) !important; }
/* Caret arrow on toggle buttons */
.ts-dd-toggle .ts-caret {
    display: inline-block;
    font-size: .65rem;
    margin-left: 3px;
    transition: transform .2s;
    vertical-align: middle;
}
.ts-dd-wrap.open .ts-caret { transform: rotate(180deg); }
</style>

<nav class="ts-navbar navbar navbar-expand-lg fixed-top">
    <div class="container-xl">
        <!-- Brand -->
        <a class="navbar-brand ts-brand" href="<?= SITE_URL ?>/index.php">
            <img src="<?= SITE_URL ?>/assets/images/logo.svg" alt="Test Store Logo" width="34" height="34" style="display:inline-block;vertical-align:middle;margin-right:4px;filter:drop-shadow(0 0 6px rgba(16,185,129,0.4))">
            <span class="ts-brand-text"><?= htmlspecialchars($siteName) ?></span>
        </a>

        <!-- Mobile toggle (MDB collapse is fine for this) -->
        <button class="navbar-toggler ts-toggler" type="button"
                data-mdb-toggle="collapse" data-mdb-collapse-init
                data-mdb-target="#mainNav" aria-controls="mainNav" aria-expanded="false">
            <i class="ri-menu-4-line" style="font-size:1.4rem"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Center Search -->
            <form class="ts-search-form d-flex mx-auto" action="<?= SITE_URL ?>/search.php" method="GET">
                <div class="ts-search-wrap">
                    <i class="ri-search-2-line ts-search-icon"></i>
                    <input class="ts-search-input" type="search" name="q"
                           placeholder="Search apps, games, tools…"
                           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                           autocomplete="off" id="navSearchInput">
                </div>
            </form>

            <!-- Nav Links -->
            <ul class="navbar-nav ms-auto align-items-center gap-1">

                <li class="nav-item">
                    <a class="nav-link ts-nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
                       href="<?= SITE_URL ?>/index.php">
                        <i class="ri-home-4-fill me-1" style="font-size:1rem"></i>Home
                    </a>
                </li>

                <!-- Categories dropdown -->
                <li class="nav-item ts-dd-wrap" id="ddWrapCat">
                    <a class="nav-link ts-nav-link ts-dd-toggle" href="#"
                       onclick="tsToggleDd('ddWrapCat'); return false;">
                        <i class="ri-apps-2-fill me-1" style="font-size:1rem"></i>
                        Categories
                        <i class="ri-arrow-down-s-fill ts-caret" style="font-size:.85rem"></i>
                    </a>
                    <ul class="ts-dd-menu ts-dd-left">
                        <?php 
                        $navCatIcons = [
                            'Games'=>'ri-gamepad-fill','Tools'=>'ri-tools-fill','Social'=>'ri-group-fill',
                            'Entertainment'=>'ri-film-fill','Education'=>'ri-book-open-fill','Productivity'=>'ri-flashlight-fill',
                            'Finance'=>'ri-coins-fill','Health'=>'ri-heart-pulse-fill','Photography'=>'ri-camera-fill','Music'=>'ri-music-2-fill',
                        ];
                        foreach ($navCatIcons as $navCat => $navIcon): ?>
                        <li><a href="<?= SITE_URL ?>/category.php?cat=<?= urlencode($navCat) ?>">
                            <i class="<?= $navIcon ?> me-2"></i><?= htmlspecialchars($navCat) ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>

                <?php if (!isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="ts-btn-ghost nav-link" href="<?= SITE_URL ?>/auth/login.php">Sign In</a>
                </li>
                <li class="nav-item">
                    <a class="ts-btn-primary ms-1" href="<?= SITE_URL ?>/auth/register.php">Join Free</a>
                </li>

                <?php elseif (isUser()): ?>
                <li class="nav-item ts-dd-wrap" id="ddWrapUser">
                    <a class="nav-link ts-avatar-btn d-flex align-items-center gap-2 ts-dd-toggle"
                       href="#" onclick="tsToggleDd('ddWrapUser'); return false;">
                        <img src="<?= navAvatarUrl($cu['avatar'], false) ?>"
                             class="ts-avatar-sm" alt="avatar">
                        <span><?= htmlspecialchars($cu['username']) ?></span>
                        <i class="ri-arrow-down-s-fill ts-caret" style="font-size:.85rem"></i>
                    </a>
                    <ul class="ts-dd-menu">
                        <li><a href="<?= SITE_URL ?>/auth/logout.php">
                            <i class="ri-logout-box-r-line" style="font-size:1rem"></i>Sign Out
                        </a></li>
                    </ul>
                </li>

                <?php elseif (isDeveloper()): ?>
                <li class="nav-item">
                    <a class="nav-link ts-nav-link" href="<?= SITE_URL ?>/developer/dashboard.php">
                        <i class="ri-dashboard-2-fill me-1" style="font-size:1rem"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="ts-btn-primary ms-1" href="<?= SITE_URL ?>/developer/publish.php">
                        <i class="ri-upload-cloud-2-fill me-1" style="font-size:.9rem"></i>Publish App
                    </a>
                </li>
                <li class="nav-item ts-dd-wrap ms-2" id="ddWrapDev">
                    <a class="nav-link ts-avatar-btn d-flex align-items-center gap-2 ts-dd-toggle"
                       href="#" onclick="tsToggleDd('ddWrapDev'); return false;">
                        <img src="<?= navAvatarUrl($cu['avatar'], true) ?>"
                             class="ts-avatar-sm" alt="avatar">
                        <i class="ri-arrow-down-s-fill ts-caret" style="font-size:.85rem"></i>
                    </a>
                    <ul class="ts-dd-menu">
                        <li><a href="<?= SITE_URL ?>/developer/my-apps.php">
                            <i class="ri-apps-line" style="font-size:1rem"></i>My Apps
                        </a></li>
                        <li><a href="<?= SITE_URL ?>/developer/analytics.php">
                            <i class="ri-bar-chart-2-fill" style="font-size:1rem"></i>Analytics
                        </a></li>
                        <li><a href="<?= SITE_URL ?>/developer/profile.php">
                            <i class="ri-user-settings-fill" style="font-size:1rem"></i>Profile
                        </a></li>
                        <li><div class="ts-dd-divider"></div></li>
                        <li><a href="<?= SITE_URL ?>/auth/logout.php" class="ts-dd-danger">
                            <i class="ri-logout-box-r-line" style="font-size:1rem"></i>Sign Out
                        </a></li>
                    </ul>
                </li>

                <?php elseif (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link ts-nav-link" href="<?= SITE_URL ?>/admin/dashboard.php">
                        <i class="ri-shield-star-fill me-1" style="font-size:1rem"></i>Admin Panel
                    </a>
                </li>
                <li class="nav-item ms-2">
                    <a class="ts-btn-ghost" href="<?= SITE_URL ?>/auth/logout.php">Sign Out</a>
                </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
<div class="ts-navbar-spacer"></div>

<script>
// ── Vanilla dropdown system ──────────────────────────────────
function tsToggleDd(wrapperId) {
    const wrap = document.getElementById(wrapperId);
    if (!wrap) return;
    const menu = wrap.querySelector('.ts-dd-menu');
    const isOpen = wrap.classList.contains('open');

    // Close ALL open dropdowns first
    tsCloseAllDd();

    // Toggle this one (if it was closed, open it)
    if (!isOpen) {
        wrap.classList.add('open');
        menu.classList.add('open');
    }
}

function tsCloseAllDd() {
    document.querySelectorAll('.ts-dd-wrap.open').forEach(w => {
        w.classList.remove('open');
        const m = w.querySelector('.ts-dd-menu');
        if (m) m.classList.remove('open');
    });
}

// Close when clicking outside any dropdown
document.addEventListener('click', function(e) {
    if (!e.target.closest('.ts-dd-wrap')) {
        tsCloseAllDd();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tsCloseAllDd();
});
</script>
