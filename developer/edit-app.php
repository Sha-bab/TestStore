<?php
// ── developer/edit-app.php — Edit existing app ───────────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('developer');

$db  = getDB();
$uid = (int)$_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM apps WHERE id=? AND developer_id=? LIMIT 1");
$stmt->execute([$id, $uid]);
$app = $stmt->fetch();
if (!$app) { setFlash('error', 'App not found.'); header('Location: ' . SITE_URL . '/developer/my-apps.php'); exit; }

$errors = [];
$categories = ['Games','Tools','Social','Entertainment','Education','Productivity','Finance','Health','Photography','Music','News','Travel','Food','Sports','Shopping','Business','Lifestyle','Weather','Books','Medical'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $appName   = trim($_POST['app_name'] ?? '');
    $category  = $_POST['category'] ?? '';
    $appType   = in_array($_POST['app_type']??'', ['free','paid']) ? $_POST['app_type'] : 'free';
    $version   = trim($_POST['version'] ?? '');
    $shortDesc = trim($_POST['short_description'] ?? '');
    $fullDesc  = trim($_POST['full_description'] ?? '');
    $keywords  = trim($_POST['keywords'] ?? '');
    $minAndroid = trim($_POST['min_android_version'] ?? '');
    $targetSdk = (int)($_POST['target_sdk'] ?? 33);
    $privacyUrl= trim($_POST['privacy_policy_url'] ?? '');
    $termsUrl  = trim($_POST['terms_url'] ?? '');
    $contentRating = trim($_POST['content_rating'] ?? 'Everyone');
    $releaseNotes = trim($_POST['release_notes'] ?? '');
    $promoVideo = trim($_POST['promo_video_url'] ?? '');

    if (strlen($appName) < 2) $errors[] = 'App name required.';
    if (strlen($version) < 1) $errors[] = 'Version required.';
    if (strlen($shortDesc) < 10) $errors[] = 'Short description too short.';
    if (strlen($fullDesc) < 20)  $errors[] = 'Full description too short.';

    // Validate replacement APK (if provided)
    if (isset($_FILES['apk_file']) && $_FILES['apk_file']['error'] === UPLOAD_ERR_OK) {
        $apkExt = strtolower(pathinfo($_FILES['apk_file']['name'], PATHINFO_EXTENSION));
        if ($apkExt !== 'apk') $errors[] = 'Replacement file must be an .apk file.';
        if ($_FILES['apk_file']['size'] > MAX_APK_MB * 1048576) $errors[] = 'APK exceeds ' . MAX_APK_MB . ' MB limit.';
    }

    // Validate replacement icon (if provided)
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $allowedIconMime = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array($_FILES['icon']['type'], $allowedIconMime)) $errors[] = 'Icon must be PNG, JPEG, or WEBP.';
        if ($_FILES['icon']['size'] > MAX_ICON_MB * 1048576) $errors[] = 'Icon exceeds ' . MAX_ICON_MB . ' MB limit.';
    }

    $iconPath = $app['icon'];
    $apkPath  = $app['apk_file'];
    $apkSize  = $app['apk_size'];

    if (empty($errors)) {
        // New icon?
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
            $newIcon = $app['package_name'] . '_new.' . $ext;
            $iconDir = STORAGE . 'icons/';
            if (!is_dir($iconDir)) mkdir($iconDir, 0755, true);
            move_uploaded_file($_FILES['icon']['tmp_name'], $iconDir . $newIcon);
            $iconPath = $newIcon;
        }

        // New APK?
        if (isset($_FILES['apk_file']) && $_FILES['apk_file']['error'] === UPLOAD_ERR_OK) {
            $newApk = $app['package_name'] . '_' . $version . '.apk';
            $apkDir = STORAGE . 'apk/';
            if (!is_dir($apkDir)) mkdir($apkDir, 0755, true);
            move_uploaded_file($_FILES['apk_file']['tmp_name'], $apkDir . $newApk);
            $apkPath = $newApk;
            $apkSize = $_FILES['apk_file']['size'];

            // Version record — only insert after files are safely moved
            $db->prepare(
                "INSERT INTO app_versions (app_id, version, apk_file, apk_size, release_notes, min_android) VALUES (?,?,?,?,?,?)"
            )->execute([$id, $version, $newApk, $apkSize, $releaseNotes ?: null, $minAndroid]);
        }

        $db->prepare(
            "UPDATE apps SET app_name=?, category=?, app_type=?, version=?, release_notes=?, icon=?,
             short_description=?, full_description=?, keywords=?, apk_file=?, apk_size=?,
             min_android_version=?, target_sdk=?, privacy_policy_url=?, terms_url=?,
             content_rating=?, promo_video_url=?, status='pending'
             WHERE id=? AND developer_id=?"
        )->execute([
            $appName, $category, $appType, $version, $releaseNotes ?: null, $iconPath,
            $shortDesc, $fullDesc, $keywords ?: null, $apkPath, $apkSize,
            $minAndroid, $targetSdk, $privacyUrl ?: null, $termsUrl ?: null,
            $contentRating, $promoVideo ?: null, $id, $uid,
        ]);
        setFlash('success', 'App updated and resubmitted for review.');
        header('Location: ' . SITE_URL . '/developer/my-apps.php'); exit;
    }
}

$pageTitle = 'Edit: ' . $app['app_name'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ts-page-header">
    <div class="container-xl">
        <h1 class="ts-page-title"><span class="material-icons ts-section-icon">edit</span> Edit App</h1>
        <div class="ts-breadcrumb">
            <a href="<?= SITE_URL ?>/developer/my-apps.php">My Apps</a>
            <span class="ts-breadcrumb-sep material-icons" style="font-size:.9rem">chevron_right</span>
            <span><?= htmlspecialchars($app['app_name']) ?></span>
        </div>
    </div>
</div>
<div class="container-xl pb-5">
    <?php if (!empty($errors)): ?>
    <div class="ts-alert ts-alert-danger mb-4"><span class="material-icons">error</span> <?= implode(' · ', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($app['status']==='rejected'): ?>
    <div class="ts-alert ts-alert-danger mb-4">
        <span class="material-icons">cancel</span>
        <div><strong>Rejection Reason:</strong> <?= htmlspecialchars($app['rejection_reason'] ?? 'Not specified') ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="ts-panel mb-4">
                    <h2 class="ts-section-title mb-4"><span class="material-icons ts-section-icon">info</span> App Info</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="ts-label">App Name *</label><input type="text" name="app_name" class="ts-input" required value="<?= htmlspecialchars($app['app_name']) ?>"></div>
                        <div class="col-md-6"><label class="ts-label">Package Name</label><input type="text" class="ts-input" disabled value="<?= htmlspecialchars($app['package_name']) ?>"><div class="ts-input-help">Package name cannot be changed</div></div>
                        <div class="col-md-4">
                            <label class="ts-label">Category *</label>
                            <select name="category" class="ts-select" required>
                                <?php foreach ($categories as $c): ?><option value="<?= $c ?>" <?= $app['category']===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="ts-label">App Type</label><select name="app_type" class="ts-select"><option value="free" <?= $app['app_type']==='free'?'selected':'' ?>>Free</option><option value="paid" <?= $app['app_type']==='paid'?'selected':'' ?>>Paid</option></select></div>
                        <div class="col-md-4"><label class="ts-label">Version *</label><input type="text" name="version" class="ts-input" required value="<?= htmlspecialchars($app['version']) ?>"></div>
                        <div class="col-12"><label class="ts-label">Short Description *</label><input type="text" name="short_description" class="ts-input" required maxlength="200" value="<?= htmlspecialchars($app['short_description']) ?>"></div>
                        <div class="col-12"><label class="ts-label">Full Description *</label><textarea name="full_description" class="ts-textarea" required rows="5"><?= htmlspecialchars($app['full_description']) ?></textarea></div>
                        <div class="col-12"><label class="ts-label">Keywords</label><input type="text" name="keywords" class="ts-input" value="<?= htmlspecialchars($app['keywords'] ?? '') ?>"></div>
                        <div class="col-12"><label class="ts-label">Release Notes</label><textarea name="release_notes" class="ts-textarea" rows="3"><?= htmlspecialchars($app['release_notes'] ?? '') ?></textarea></div>
                        <div class="col-md-4"><label class="ts-label">Min Android Version *</label><select name="min_android_version" class="ts-select" required><?php foreach (['5.0','6.0','7.0','8.0','9.0','10.0','11.0','12.0','13.0','14.0'] as $av): ?><option value="<?= $av ?>" <?= $app['min_android_version']===$av?'selected':'' ?>><?= $av ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="ts-label">Target SDK</label><input type="number" name="target_sdk" class="ts-input" value="<?= $app['target_sdk'] ?>"></div>
                        <div class="col-md-4"><label class="ts-label">Content Rating</label><select name="content_rating" class="ts-select"><?php foreach (['Everyone','Everyone 10+','Teen','Mature 17+','Adults only 18+'] as $cr): ?><option value="<?= $cr ?>" <?= $app['content_rating']===$cr?'selected':'' ?>><?= $cr ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="ts-label">Privacy Policy URL</label><input type="url" name="privacy_policy_url" class="ts-input" value="<?= htmlspecialchars($app['privacy_policy_url'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="ts-label">Promo Video URL</label><input type="url" name="promo_video_url" class="ts-input" value="<?= htmlspecialchars($app['promo_video_url'] ?? '') ?>"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="ts-panel mb-4">
                    <h2 class="ts-section-title mb-4"><span class="material-icons ts-section-icon">file_upload</span> Replace Files</h2>
                    <div class="ts-form-group">
                        <label class="ts-label">New APK (optional)</label>
                        <label class="ts-file-input d-block" for="apk_file"><span class="material-icons d-block mb-1" style="font-size:2rem">android</span><span id="apkLabel">Upload new .apk</span><input type="file" id="apk_file" name="apk_file" accept=".apk" style="display:none" onchange="document.getElementById('apkLabel').textContent=this.files[0].name"></label>
                    </div>
                    <div class="ts-form-group">
                        <label class="ts-label">New Icon (optional)</label>
                        <label class="ts-file-input d-block" for="icon_file"><span class="material-icons d-block mb-1" style="font-size:2rem">image</span><span>Upload new icon</span><input type="file" id="icon_file" name="icon" accept="image/*" style="display:none"></label>
                    </div>
                </div>
                <div class="ts-panel">
                    <div class="ts-alert ts-alert-warning mb-3"><span class="material-icons">info</span> Saving will resubmit app for review.</div>
                    <button type="submit" class="ts-btn-primary w-100" style="border-radius:8px;justify-content:center;padding:12px"><span class="material-icons me-2">save</span>Save &amp; Resubmit</button>
                    <a href="<?= SITE_URL ?>/developer/my-apps.php" class="ts-btn-ghost w-100 mt-2" style="border-radius:8px;justify-content:center;padding:10px">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
