<?php
// ── developer/profile.php — Edit Developer Profile ───────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('developer');

$db  = getDB();
$uid = (int)$_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM developers WHERE id=? LIMIT 1");
$stmt->execute([$uid]);
$dev = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $country  = trim($_POST['country'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $devType  = in_array($_POST['developer_type']??'', ['individual','company']) ? $_POST['developer_type'] : 'individual';
    $email    = trim($_POST['email'] ?? '');

    if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';

    // Password change
    $newPass = $_POST['new_password'] ?? '';
    $curPass = $_POST['current_password'] ?? '';
    if ($newPass !== '') {
        if (!password_verify($curPass, $dev['password'])) $errors[] = 'Current password is incorrect.';
        if (strlen($newPass) < 8) $errors[] = 'New password must be at least 8 characters.';
    }

    $photoPath = $dev['profile_photo'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) { $errors[] = 'Photo must be JPG/PNG/WEBP.'; }
        else {
            $newPhoto = 'dev_' . $uid . '.' . $ext;
            $dir = STORAGE . 'icons/'; // re-use icons dir for avatars
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dir . $newPhoto);
            $photoPath = $newPhoto;
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE developers SET username=?, email=?, bio=?, country=?, mobile=?, developer_type=?, profile_photo=?";
        $params = [$username, $email, $bio ?: null, $country ?: null, $mobile ?: null, $devType, $photoPath];
        if ($newPass !== '') { $sql .= ", password=?"; $params[] = password_hash($newPass, HASH_ALGO); }
        $sql .= " WHERE id=?";
        $params[] = $uid;
        $db->prepare($sql)->execute($params);

        $_SESSION['username'] = $username;
        $_SESSION['avatar']   = $photoPath;
        setFlash('success', 'Profile updated successfully!');
        header('Location: ' . SITE_URL . '/developer/profile.php'); exit;
    }
}

$countries = ['Afghanistan','Albania','Algeria','Argentina','Australia','Austria','Bangladesh','Belgium','Brazil','Canada','Chile','China','Colombia','Denmark','Egypt','Finland','France','Germany','Ghana','Greece','India','Indonesia','Iran','Ireland','Israel','Italy','Japan','Kenya','Malaysia','Mexico','Morocco','Netherlands','New Zealand','Nigeria','Norway','Pakistan','Philippines','Poland','Portugal','Russia','Saudi Arabia','Singapore','South Africa','South Korea','Spain','Sri Lanka','Sweden','Switzerland','Thailand','Turkey','Ukraine','United Kingdom','United States','Vietnam','Other'];

$pageTitle = 'Edit Profile';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<div class="ts-page-header">
    <div class="container-xl">
        <h1 class="ts-page-title"><i class="ri-user-settings-fill ts-section-icon"></i> Edit Profile</h1>
        <div class="ts-breadcrumb"><a href="<?= SITE_URL ?>/developer/dashboard.php">Dashboard</a> <span class="ts-breadcrumb-sep" style="font-size:.9rem">›</span> <span>Profile</span></div>
    </div>
</div>
<div class="container-xl pb-5">
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <?php if (!empty($errors)): ?>
    <div class="ts-alert ts-alert-danger mb-4"><i class="ri-error-warning-fill me-1"></i> <?= implode(' · ', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-3">
            <div class="ts-panel text-center">
                <img src="<?= $dev['profile_photo'] ? UPLOAD_URL . 'icons/' . $dev['profile_photo'] : SITE_URL . '/assets/images/default-avatar.svg' ?>"
                     id="avatarPreview" class="ts-avatar-lg d-block mx-auto mb-3" alt="Profile photo">
                <div style="font-size:.82rem;color:var(--ts-text-muted)">
                    <?= htmlspecialchars($dev['developer_type'] === 'company' ? '🏢 Company' : '👤 Individual') ?>
                    <?php if ($dev['country']): ?> · <?= htmlspecialchars($dev['country']) ?><?php endif; ?>
                </div>
                <div style="font-weight:700;margin-top:8px"><?= htmlspecialchars($dev['username']) ?></div>
                <div style="font-size:.8rem;color:var(--ts-text-muted)"><?= htmlspecialchars($dev['email']) ?></div>
            </div>
        </div>
        <div class="col-lg-7">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="ts-panel mb-4">
                    <h2 class="ts-section-title mb-4"><i class="ri-user-fill ts-section-icon"></i> Personal Info</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="ts-label">Username *</label><input type="text" name="username" class="ts-input" required value="<?= htmlspecialchars($dev['username']) ?>"></div>
                        <div class="col-md-6"><label class="ts-label">Email *</label><input type="email" name="email" class="ts-input" required value="<?= htmlspecialchars($dev['email']) ?>"></div>
                        <div class="col-md-6"><label class="ts-label">Mobile</label><input type="tel" name="mobile" class="ts-input" value="<?= htmlspecialchars($dev['mobile'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="ts-label">Country</label><select name="country" class="ts-select"><option value="">Select country</option><?php foreach ($countries as $c): ?><option value="<?= $c ?>" <?= ($dev['country']??'')===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="ts-label">Developer Type</label><select name="developer_type" class="ts-select"><option value="individual" <?= $dev['developer_type']==='individual'?'selected':'' ?>>Individual</option><option value="company" <?= $dev['developer_type']==='company'?'selected':'' ?>>Company</option></select></div>
                        <div class="col-12"><label class="ts-label">Bio</label><textarea name="bio" class="ts-textarea" rows="3" placeholder="Tell the world about yourself…"><?= htmlspecialchars($dev['bio'] ?? '') ?></textarea></div>
                        <div class="col-12">
                            <label class="ts-label">Profile Photo</label>
                            <!-- Hidden real file input -->
                            <input type="file" name="profile_photo" id="profilePhotoInput"
                                   accept="image/jpeg,image/png,image/webp"
                                   style="display:none" onchange="previewAvatar(this)">
                            <!-- Styled upload trigger -->
                            <div onclick="document.getElementById('profilePhotoInput').click()"
                                 style="display:flex;align-items:center;gap:14px;background:rgba(255,255,255,0.04);border:2px dashed var(--ts-border);border-radius:var(--ts-radius-sm);padding:14px 16px;cursor:pointer;transition:all .25s"
                                 onmouseover="this.style.borderColor='var(--ts-primary)';this.style.background='rgba(99,102,241,0.05)'"
                                 onmouseout="this.style.borderColor='var(--ts-border)';this.style.background='rgba(255,255,255,0.04)'">
                                <img id="photoPickerPreview"
                                     src="<?= $dev['profile_photo'] ? UPLOAD_URL . 'icons/' . $dev['profile_photo'] : SITE_URL . '/assets/images/default-avatar.svg' ?>"
                                     style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--ts-border);flex-shrink:0">
                                <div>
                                    <div style="font-weight:600;color:var(--ts-text-primary);font-size:.9rem">
                                        <i class="ri-upload-cloud-2-fill me-1" style="color:var(--ts-primary)"></i>
                                        Click to upload photo
                                    </div>
                                    <div id="photoFileName" style="font-size:.78rem;color:var(--ts-text-muted);margin-top:2px">
                                        JPG, PNG or WEBP · Max 2MB
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Password — collapsed by default -->
                <div class="ts-panel mb-4">
                    <button type="button" id="togglePwBtn"
                            onclick="document.getElementById('pwSection').classList.toggle('d-none');this.querySelector('.ts-caret-pw').classList.toggle('ri-arrow-down-s-line');this.querySelector('.ts-caret-pw').classList.toggle('ri-arrow-up-s-line');"
                            style="display:flex;align-items:center;gap:8px;background:none;border:none;padding:0;cursor:pointer;width:100%">
                        <h2 class="ts-section-title mb-0" style="flex:1">
                            <i class="ri-lock-password-fill ts-section-icon"></i> Change Password
                        </h2>
                        <i class="ri-arrow-down-s-line ts-caret-pw" style="font-size:1.3rem;color:var(--ts-text-muted)"></i>
                    </button>
                    <div id="pwSection" class="d-none mt-3">
                        <div class="ts-alert ts-alert-info mb-3"><i class="ri-information-fill me-1"></i> Leave blank to keep your current password.</div>
                        <div class="row g-3">
                            <div class="col-12"><label class="ts-label">Current Password</label><input type="password" name="current_password" class="ts-input" placeholder="Enter current password"></div>
                            <div class="col-12"><label class="ts-label">New Password</label><input type="password" name="new_password" class="ts-input" placeholder="Min. 8 characters" minlength="8"></div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="ts-btn-primary" style="border-radius:8px;padding:12px 30px">
                    <i class="ri-save-3-fill me-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</div>
<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = e => {
            // Update left panel avatar
            document.getElementById('avatarPreview').src = e.target.result;
            // Update picker widget preview
            document.getElementById('photoPickerPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
        // Show filename
        document.getElementById('photoFileName').textContent = file.name + ' · ' + (file.size / 1024).toFixed(0) + ' KB';
    }
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
