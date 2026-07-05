<?php
// ── developer/publish.php — Publish New APK ──────────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('developer');

$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$errors = [];
$success = false;

// Categories list
$categories = ['Games','Tools','Social','Entertainment','Education','Productivity','Finance','Health','Photography','Music','News','Travel','Food','Sports','Shopping','Business','Lifestyle','Weather','Books','Medical'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $appName    = trim($_POST['app_name'] ?? '');
    $pkg        = trim($_POST['package_name'] ?? '');
    $category   = $_POST['category'] ?? '';
    $appType    = in_array($_POST['app_type'] ?? '', ['free','paid']) ? $_POST['app_type'] : 'free';
    $version    = trim($_POST['version'] ?? '');
    $shortDesc  = trim($_POST['short_description'] ?? '');
    $fullDesc   = trim($_POST['full_description'] ?? '');
    $keywords   = trim($_POST['keywords'] ?? '');
    $minAndroid = trim($_POST['min_android_version'] ?? '');
    $targetSdk  = (int)($_POST['target_sdk'] ?? 33);
    $privacyUrl = trim($_POST['privacy_policy_url'] ?? '');
    $termsUrl   = trim($_POST['terms_url'] ?? '');
    $contentRating = trim($_POST['content_rating'] ?? 'Everyone');
    $releaseNotes = trim($_POST['release_notes'] ?? '');
    $promoVideo = trim($_POST['promo_video_url'] ?? '');

    // Validate
    if (strlen($appName) < 2)    $errors[] = 'App name is required.';
    if (!preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $pkg)) $errors[] = 'Invalid package name (e.g. com.example.myapp).';
    if (!in_array($category, $categories)) $errors[] = 'Select a valid category.';
    if (strlen($version) < 1)   $errors[] = 'Version is required (e.g. 1.0.0).';
    if (strlen($shortDesc) < 10) $errors[] = 'Short description must be at least 10 characters.';
    if (strlen($fullDesc) < 20)  $errors[] = 'Full description must be at least 20 characters.';
    if (strlen($minAndroid) < 1) $errors[] = 'Minimum Android version required.';

    // APK file
    $apkPath = ''; $apkSize = 0;
    if (!isset($_FILES['apk_file']) || $_FILES['apk_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'APK file is required.';
    } else {
        $maxBytes = MAX_APK_MB * 1048576;
        if ($_FILES['apk_file']['size'] > $maxBytes) $errors[] = 'APK exceeds ' . MAX_APK_MB . ' MB limit.';
        $ext = strtolower(pathinfo($_FILES['apk_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'apk') $errors[] = 'Only .apk files allowed.';
        $apkSize = $_FILES['apk_file']['size'];
        $apkPath = $pkg . '_' . $version . '.apk';
    }

    // Icon
    $iconPath = '';
    if (!isset($_FILES['icon']) || $_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'App icon is required.';
    } else {
        $maxBytes = MAX_ICON_MB * 1048576;
        if ($_FILES['icon']['size'] > $maxBytes) $errors[] = 'Icon exceeds ' . MAX_ICON_MB . ' MB limit.';
        $allowedImg = ['image/png','image/jpeg','image/webp'];
        if (!in_array($_FILES['icon']['type'], $allowedImg)) $errors[] = 'Icon must be PNG/JPEG/WEBP.';
        $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
        $iconPath = $pkg . '.' . $ext;
    }

    // Screenshots (optional, up to 5)
    $ssPaths = []; // [ ['filename' => '...', 'idx' => N], ... ]
    if (!empty($_FILES['screenshots']['name'][0])) {
        foreach ($_FILES['screenshots']['error'] as $i => $err) {
            if ($err === UPLOAD_ERR_OK && count($ssPaths) < 5) {
                $ssExt = strtolower(pathinfo($_FILES['screenshots']['name'][$i], PATHINFO_EXTENSION));
                $ssPaths[] = ['filename' => $pkg . '_ss' . ($i+1) . '.' . $ssExt, 'idx' => $i];
            }
        }
    }

    // Check duplicate package
    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM apps WHERE package_name=?");
        $chk->execute([$pkg]);
        if ($chk->fetch()) $errors[] = 'Package name already exists.';
    }

    if (empty($errors)) {
        // Move APK
        $apkDir = STORAGE . 'apk/';
        if (!is_dir($apkDir)) mkdir($apkDir, 0755, true);
        move_uploaded_file($_FILES['apk_file']['tmp_name'], $apkDir . $apkPath);

        // Move icon
        $iconDir = STORAGE . 'icons/';
        if (!is_dir($iconDir)) mkdir($iconDir, 0755, true);
        move_uploaded_file($_FILES['icon']['tmp_name'], $iconDir . $iconPath);

        // Move screenshots — use original file index for each entry
        $ssDir = STORAGE . 'screenshots/';
        if (!is_dir($ssDir)) mkdir($ssDir, 0755, true);
        foreach ($ssPaths as $ssEntry) {
            move_uploaded_file($_FILES['screenshots']['tmp_name'][$ssEntry['idx']], $ssDir . $ssEntry['filename']);
        }

        $ssJSON = json_encode(array_column($ssPaths, 'filename'));
        $ins = $db->prepare(
            "INSERT INTO apps (developer_id, app_name, package_name, category, app_type, version,
             release_notes, icon, screenshots, promo_video_url, short_description, full_description,
             keywords, apk_file, apk_size, min_android_version, target_sdk,
             privacy_policy_url, terms_url, content_rating, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')"
        );
        $ins->execute([
            $uid, $appName, $pkg, $category, $appType, $version,
            $releaseNotes ?: null, $iconPath, $ssJSON ?: null, $promoVideo ?: null,
            $shortDesc, $fullDesc, $keywords ?: null, $apkPath, $apkSize,
            $minAndroid, $targetSdk, $privacyUrl ?: null, $termsUrl ?: null, $contentRating,
        ]);
        $newAppId = $db->lastInsertId();

        // Save first version record
        $db->prepare(
            "INSERT INTO app_versions (app_id, version, apk_file, apk_size, release_notes, min_android)
             VALUES (?,?,?,?,?,?)"
        )->execute([$newAppId, $version, $apkPath, $apkSize, $releaseNotes ?: null, $minAndroid]);

        setFlash('success', 'App submitted! It will be reviewed by our team.');
        header('Location: ' . SITE_URL . '/developer/my-apps.php'); exit;
    }
}

$pageTitle = 'Publish App';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ts-page-header">
    <div class="container-xl">
        <h1 class="ts-page-title"><span class="material-icons ts-section-icon">upload</span> Publish New App</h1>
        <div class="ts-breadcrumb">
            <a href="<?= SITE_URL ?>/developer/dashboard.php">Dashboard</a>
            <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span>
            <span>Publish App</span>
        </div>
    </div>
</div>

<div class="container-xl pb-5">
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <?php if (!empty($errors)): ?>
    <div class="ts-alert ts-alert-danger mb-4 fade-in">
        <div><span class="material-icons me-2">error</span><strong>Please fix the following:</strong></div>
        <ul class="mb-0 mt-2 ps-4" style="font-size:.85rem">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="row g-4">
            <!-- Left column -->
            <div class="col-lg-8">

                <!-- App Info -->
                <div class="ts-panel mb-4">
                    <h2 class="ts-section-title mb-4"><span class="material-icons ts-section-icon">info</span> App Information</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="ts-label">App Name *</label>
                            <input type="text" name="app_name" class="ts-input" required placeholder="My Awesome App"
                                   value="<?= htmlspecialchars($_POST['app_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ts-label">Package Name *</label>
                            <input type="text" name="package_name" class="ts-input" required placeholder="com.example.myapp"
                                   value="<?= htmlspecialchars($_POST['package_name'] ?? '') ?>">
                            <div class="ts-input-help">Unique Android package name</div>
                        </div>
                        <div class="col-md-4">
                            <label class="ts-label">Category *</label>
                            <select name="category" class="ts-select" required>
                                <option value="">Choose category</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c ?>" <?= ($_POST['category']??'')===$c?'selected':'' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ts-label">App Type *</label>
                            <select name="app_type" class="ts-select" required>
                                <option value="free" <?= ($_POST['app_type']??'free')==='free'?'selected':'' ?>>Free</option>
                                <option value="paid" <?= ($_POST['app_type']??'')==='paid'?'selected':'' ?>>Paid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ts-label">Version *</label>
                            <input type="text" name="version" class="ts-input" required placeholder="1.0.0"
                                   value="<?= htmlspecialchars($_POST['version'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="ts-label">Short Description * <span style="color:var(--ts-text-muted)">(max 200 chars)</span></label>
                            <input type="text" name="short_description" class="ts-input" required maxlength="200"
                                   placeholder="One-line summary of your app"
                                   value="<?= htmlspecialchars($_POST['short_description'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="ts-label">Full Description *</label>
                            <textarea name="full_description" class="ts-textarea" required rows="5" placeholder="Describe your app in detail…"><?= htmlspecialchars($_POST['full_description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="ts-label">Keywords</label>
                            <input type="text" name="keywords" class="ts-input" placeholder="keyword1, keyword2, keyword3"
                                   value="<?= htmlspecialchars($_POST['keywords'] ?? '') ?>">
                            <div class="ts-input-help">Comma-separated, improves search visibility</div>
                        </div>
                        <div class="col-12">
                            <label class="ts-label">Release Notes</label>
                            <textarea name="release_notes" class="ts-textarea" rows="3" placeholder="What's new in this version…"><?= htmlspecialchars($_POST['release_notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Technical Details -->
                <div class="ts-panel mb-4">
                    <h2 class="ts-section-title mb-4"><span class="material-icons ts-section-icon">settings</span> Technical Details</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="ts-label">Min Android Version *</label>
                            <select name="min_android_version" class="ts-select" required>
                                <option value="">Select version</option>
                                <?php foreach (['5.0','6.0','7.0','8.0','9.0','10.0','11.0','12.0','13.0','14.0'] as $av): ?>
                                <option value="<?= $av ?>" <?= ($_POST['min_android_version']??'')===$av?'selected':'' ?>><?= $av ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="ts-label">Target SDK</label>
                            <input type="number" name="target_sdk" class="ts-input" min="21" max="35" placeholder="34"
                                   value="<?= htmlspecialchars($_POST['target_sdk'] ?? '34') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="ts-label">Content Rating</label>
                            <select name="content_rating" class="ts-select">
                                <?php foreach (['Everyone','Everyone 10+','Teen','Mature 17+','Adults only 18+'] as $cr): ?>
                                <option value="<?= $cr ?>" <?= ($_POST['content_rating']??'Everyone')===$cr?'selected':'' ?>><?= $cr ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="ts-label">Privacy Policy URL</label>
                            <input type="url" name="privacy_policy_url" class="ts-input" placeholder="https://"
                                   value="<?= htmlspecialchars($_POST['privacy_policy_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="ts-label">Terms of Service URL</label>
                            <input type="url" name="terms_url" class="ts-input" placeholder="https://"
                                   value="<?= htmlspecialchars($_POST['terms_url'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="ts-label">Promo Video URL (optional)</label>
                            <input type="url" name="promo_video_url" class="ts-input" placeholder="https://youtube.com/..."
                                   value="<?= htmlspecialchars($_POST['promo_video_url'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Media uploads -->
            <div class="col-lg-4">
                <div class="ts-panel mb-4">
                    <h2 class="ts-section-title mb-4"><span class="material-icons ts-section-icon">photo_camera</span> Media</h2>

                    <!-- APK Upload -->
                    <div class="ts-form-group">
                        <label class="ts-label">APK File * <span style="color:var(--ts-text-muted)">(max <?= MAX_APK_MB ?>MB)</span></label>
                        <label class="ts-file-input d-block" for="apk_file">
                            <span class="material-icons d-block mb-2" style="font-size:2rem">android</span>
                            <span id="apkLabel">Click to upload .apk file</span>
                            <input type="file" id="apk_file" name="apk_file" accept=".apk" required style="display:none"
                                   onchange="document.getElementById('apkLabel').textContent=this.files[0].name">
                        </label>
                    </div>

                    <!-- Icon Upload -->
                    <div class="ts-form-group">
                        <label class="ts-label">App Icon * <span style="color:var(--ts-text-muted)">(PNG/JPG, max <?= MAX_ICON_MB ?>MB)</span></label>
                        <label class="ts-file-input d-block" for="icon_file" style="position:relative">
                            <img id="iconPreview" src="" alt="" style="display:none;width:80px;height:80px;border-radius:12px;object-fit:cover;margin:0 auto 8px;">
                            <span class="material-icons d-block mb-1" style="font-size:2rem" id="iconPlaceholder">image</span>
                            <span id="iconLabel">Click to upload icon (512×512 recommended)</span>
                            <input type="file" id="icon_file" name="icon" accept="image/png,image/jpeg,image/webp"
                                   required style="display:none" data-preview="#iconPreview"
                                   onchange="previewImg(this,'iconPreview','iconPlaceholder','iconLabel')">
                        </label>
                    </div>

                    <!-- Screenshots -->
                    <div class="ts-form-group">
                        <label class="ts-label">Screenshots <span style="color:var(--ts-text-muted)">(up to 5)</span></label>
                        <label class="ts-file-input d-block" for="screenshots">
                            <span class="material-icons d-block mb-2" style="font-size:2rem">photo_library</span>
                            <span id="ssLabel">Click to select screenshots</span>
                            <input type="file" id="screenshots" name="screenshots[]" accept="image/*"
                                   multiple style="display:none"
                                   onchange="document.getElementById('ssLabel').textContent=this.files.length+' file(s) selected'">
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="ts-panel">
                    <h3 style="font-size:1rem;font-weight:700;margin-bottom:.75rem">Submit for Review</h3>
                    <p style="font-size:.82rem;color:var(--ts-text-secondary);margin-bottom:1rem">
                        Your app will be reviewed by our team. Approved apps appear in the store within 24–48 hours.
                    </p>
                    <button type="submit" class="ts-btn-primary w-100" style="border-radius:8px;justify-content:center;padding:12px">
                        <span class="material-icons me-2">send</span>Submit for Review
                    </button>
                    <a href="<?= SITE_URL ?>/developer/my-apps.php" class="ts-btn-ghost w-100 mt-2" style="border-radius:8px;justify-content:center;padding:10px">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewImg(input, previewId, placeholderId, labelId) {
    const preview = document.getElementById(previewId);
    const placeholder = document.getElementById(placeholderId);
    const label = document.getElementById(labelId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
            if (label) label.textContent = input.files[0].name;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
