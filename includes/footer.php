<?php
// ── includes/footer.php — Site footer + JS ────────────────────
$siteName = defined('SITE_NAME') ? SITE_NAME : 'TEST STORE';
?>
<footer class="ts-footer mt-auto">
    <div class="container-xl">
        <div class="row g-4 py-5">
            <!-- Brand column -->
            <div class="col-lg-4">
                <a class="ts-brand d-inline-flex align-items-center gap-2 mb-3 text-decoration-none" href="<?= SITE_URL ?>/index.php">
                    <img src="<?= SITE_URL ?>/assets/images/logo.svg" alt="<?= htmlspecialchars($siteName) ?> Logo" width="32" height="32" style="filter:drop-shadow(0 0 6px rgba(16,185,129,0.4))">
                    <span class="ts-brand-text"><?= htmlspecialchars($siteName) ?></span>
                </a>
                <p class="ts-footer-desc">The premier destination for Android APK downloads. Safe, fast, and developer-friendly.</p>
                <div class="ts-social-links d-flex gap-2 mt-3">
                    <a href="#" class="ts-social-btn" aria-label="Twitter"><i class="ri-twitter-x-fill"></i></a>
                    <a href="#" class="ts-social-btn" aria-label="Instagram"><i class="ri-instagram-fill"></i></a>
                    <a href="#" class="ts-social-btn" aria-label="GitHub"><i class="ri-github-fill"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-6 col-lg-4 offset-lg-1">
                <h6 class="ts-footer-heading">Platform</h6>
                <ul class="ts-footer-links list-unstyled">
                    <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/search.php">Browse Apps</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/register.php">Create Account</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/login.php">Sign In</a></li>
                </ul>
            </div>

            <!-- For Developers -->
            <div class="col-6 col-lg-3">
                <h6 class="ts-footer-heading">For Developers</h6>
                <ul class="ts-footer-links list-unstyled">
                    <li><a href="<?= SITE_URL ?>/auth/register.php?role=developer">Register as Developer</a></li>
                    <li><a href="<?= SITE_URL ?>/developer/publish.php">Publish Your App</a></li>
                    <li><a href="<?= SITE_URL ?>/developer/dashboard.php">Developer Dashboard</a></li>
                </ul>
            </div>
        </div>

        <hr class="ts-footer-divider">

        <div class="d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
            <p class="ts-footer-copy mb-0">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. <b>All rights reserved.</b>
            </p>
              <p class="ts-footer-copy mb-0">
                Developed By Muhammad Shabab Sayem
            </p>
        </div>
    </div>
</footer>

<!-- MDBootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<!-- Custom JS (cache-busted) -->
<script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>

<?php if (isset($extraJS)) echo $extraJS; ?>
</body>
</html>
