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
                    <span class="material-icons ts-brand-icon">android</span>
                    <span class="ts-brand-text"><?= htmlspecialchars($siteName) ?></span>
                </a>
                <p class="ts-footer-desc">The premier destination for Android APK downloads. Safe, fast, and developer-friendly.</p>
                <div class="ts-social-links d-flex gap-2 mt-3">
                    <a href="#" class="ts-social-btn" aria-label="Twitter"><span class="material-icons">public</span></a>
                    <a href="#" class="ts-social-btn" aria-label="Instagram"><span class="material-icons">photo</span></a>
                    <a href="#" class="ts-social-btn" aria-label="GitHub"><span class="material-icons">code</span></a>
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
            </div>
        </div>

        <hr class="ts-footer-divider">

        <div class="d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
            <p class="ts-footer-copy mb-0">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.
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
