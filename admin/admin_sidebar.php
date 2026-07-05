<?php
// ── Admin shared sidebar include ────────────────────────────
// Usage: $activeMenu = 'dashboard'; include 'admin_sidebar.php';
$activeMenu = $activeMenu ?? '';
$siteName = defined('SITE_NAME') ? SITE_NAME : 'TEST STORE';
?>
<!-- Admin Sidebar -->
<aside class="ts-sidebar">
    <div class="ts-sidebar-brand">
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="ts-brand text-decoration-none d-flex align-items-center gap-2">
            <span class="material-icons ts-brand-icon">android</span>
            <div>
                <div class="ts-brand-text" style="font-size:1rem"><?= htmlspecialchars($siteName) ?></div>
                <div style="font-size:.65rem;color:var(--ts-text-muted);margin-top:-2px">Administration</div>
            </div>
        </a>
    </div>
    <nav class="ts-sidebar-nav">
        <div class="ts-sidebar-section">Overview</div>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="ts-sidebar-link <?= $activeMenu==='dashboard'?'active':'' ?>">
            <span class="material-icons">dashboard</span>Dashboard
        </a>

        <div class="ts-sidebar-section">Content</div>
        <a href="<?= SITE_URL ?>/admin/app-approval.php" class="ts-sidebar-link <?= $activeMenu==='approvals'?'active':'' ?>">
            <span class="material-icons">approval</span>App Approvals
        </a>
        <a href="<?= SITE_URL ?>/admin/apps.php" class="ts-sidebar-link <?= $activeMenu==='apps'?'active':'' ?>">
            <span class="material-icons">apps</span>All Apps
        </a>

        <div class="ts-sidebar-section">Users</div>
        <a href="<?= SITE_URL ?>/admin/developers.php" class="ts-sidebar-link <?= $activeMenu==='developers'?'active':'' ?>">
            <span class="material-icons">code</span>Developers
        </a>

        <div class="ts-sidebar-section">System</div>
        <a href="<?= SITE_URL ?>/admin/settings.php" class="ts-sidebar-link <?= $activeMenu==='settings'?'active':'' ?>">
            <span class="material-icons">settings</span>Site Settings
        </a>
        <a href="<?= SITE_URL ?>/index.php" class="ts-sidebar-link">
            <span class="material-icons">public</span>View Store
        </a>
        <a href="<?= SITE_URL ?>/auth/logout.php" class="ts-sidebar-link" style="color:var(--ts-danger)!important">
            <span class="material-icons">logout</span>Sign Out
        </a>
    </nav>
</aside>
<!-- Main Wrapper -->
<div class="ts-main-content">
<div class="ts-admin-topbar">
    <button id="sidebarToggle" class="d-lg-none ts-btn-ghost" style="padding:6px 10px">
        <span class="material-icons">menu</span>
    </button>
    <div class="d-flex align-items-center gap-3 ms-auto">
        <span style="font-size:.85rem;color:var(--ts-text-muted)">Logged in as <strong style="color:var(--ts-text-primary)"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
        <img src="<?= isset($_SESSION['avatar']) && $_SESSION['avatar'] ? UPLOAD_URL . $_SESSION['avatar'] : SITE_URL . '/assets/images/default-avatar.svg' ?>"
             class="ts-avatar-sm" alt="admin avatar">
    </div>
</div>
