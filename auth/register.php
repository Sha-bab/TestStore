<?php
// ── auth/register.php — User + Developer Registration ─────────
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

if (getSetting('allow_registration', '1') === '0') {
    die('<div style="font-family:Poppins,sans-serif;background:#0a0e1a;color:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center"><p>Registration is currently closed.</p></div>');
}

$db     = getDB();
$tab    = $_GET['role'] ?? ($_POST['reg_type'] ?? 'user');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab      = $_POST['reg_type'] ?? 'user';
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    // Validation
    if (strlen($username) < 3)  $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($pass) < 8)      $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)        $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $hash = password_hash($pass, HASH_ALGO);

        if ($tab === 'user') {
            // Check duplicates
            $chk = $db->prepare("SELECT id FROM users WHERE email=? OR username=? LIMIT 1");
            $chk->execute([$email, $username]);
            if ($chk->fetch()) {
                $errors[] = 'Email or username already registered.';
            } else {
                $ins = $db->prepare("INSERT INTO users (username, email, password) VALUES (?,?,?)");
                $ins->execute([$username, $email, $hash]);
                $uid = $db->lastInsertId();
                $newUser = ['id'=>$uid,'username'=>$username,'avatar'=>null];
                loginUser($newUser, 'user');
                setFlash('success', 'Welcome to TEST STORE, ' . $username . '!');
                header('Location: ' . SITE_URL . '/index.php'); exit;
            }
        } else {
            $devType = in_array($_POST['developer_type'] ?? '', ['individual','company']) ? $_POST['developer_type'] : 'individual';
            $country = trim($_POST['country'] ?? '');
            $mobile  = trim($_POST['mobile'] ?? '');

            $chk = $db->prepare("SELECT id FROM developers WHERE email=? OR username=? LIMIT 1");
            $chk->execute([$email, $username]);
            if ($chk->fetch()) {
                $errors[] = 'Email or username already registered.';
            } else {
                $ins = $db->prepare(
                    "INSERT INTO developers (username, email, password, developer_type, country, mobile)
                     VALUES (?,?,?,?,?,?)"
                );
                $ins->execute([$username, $email, $hash, $devType, $country ?: null, $mobile ?: null]);
                $did  = $db->lastInsertId();
                $newDev = ['id'=>$did,'username'=>$username,'avatar'=>null,'profile_photo'=>null];
                loginUser($newDev, 'developer');
                setFlash('success', 'Developer account created! Welcome, ' . $username . '!');
                header('Location: ' . SITE_URL . '/developer/dashboard.php'); exit;
            }
        }
    }
}

$pageTitle = 'Create Account';
$countries = ['Afghanistan','Albania','Algeria','Argentina','Australia','Austria','Bangladesh','Belgium','Brazil','Canada','Chile','China','Colombia','Denmark','Egypt','Ethiopia','Finland','France','Germany','Ghana','Greece','Hungary','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Japan','Kenya','Malaysia','Mexico','Morocco','Netherlands','New Zealand','Nigeria','Norway','Pakistan','Philippines','Poland','Portugal','Russia','Saudi Arabia','Singapore','South Africa','South Korea','Spain','Sri Lanka','Sweden','Switzerland','Thailand','Turkey','Ukraine','United Kingdom','United States','Vietnam','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom.css">
</head>
<body>
<div class="ts-auth-wrap" style="padding:60px 16px">
    <div class="ts-auth-card" style="max-width:520px">
        <div class="ts-auth-logo">
            <a href="<?= SITE_URL ?>/index.php" class="ts-brand d-inline-flex align-items-center gap-2">
                <img src="<?= SITE_URL ?>/assets/images/logo.svg" alt="Logo" width="30" height="30" style="filter:drop-shadow(0 0 6px rgba(16,185,129,0.4))">
                <span class="ts-brand-text"><?= SITE_NAME ?></span>
            </a>
        </div>
        <h1 class="ts-auth-title">Create Account</h1>
        <p class="ts-auth-sub">Join the TEST STORE community</p>

        <!-- Tab toggle -->
        <div class="ts-tab-toggle">
            <button type="button" class="ts-tab-btn <?= $tab==='user'?'active':'' ?>" onclick="switchTab(event,'user')">
                <span class="material-icons me-1" style="font-size:.9rem">person</span>User
            </button>
            <button type="button" class="ts-tab-btn <?= $tab==='developer'?'active':'' ?>" onclick="switchTab(event,'developer')">
                <span class="material-icons me-1" style="font-size:.9rem">code</span>Developer
            </button>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="ts-alert ts-alert-danger mb-3 fade-in">
            <span class="material-icons">error</span>
            <ul class="mb-0 ps-3" style="font-size:.85rem">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="reg_type" id="reg_type" value="<?= htmlspecialchars($tab) ?>">

            <!-- Common fields -->
            <div class="ts-form-group">
                <label class="ts-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="ts-input"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="Choose a username" required minlength="3">
            </div>
            <div class="ts-form-group">
                <label class="ts-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="ts-input"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required>
            </div>
            <div class="ts-form-group">
                <label class="ts-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="ts-input"
                       placeholder="Min. 8 characters" required minlength="8">
            </div>
            <div class="ts-form-group">
                <label class="ts-label" for="password2">Confirm Password</label>
                <input type="password" id="password2" name="password2" class="ts-input"
                       placeholder="Repeat your password" required>
            </div>

            <!-- Developer-only fields -->
            <div id="devFields" style="display:<?= $tab==='developer'?'block':'none' ?>">
                <div class="ts-form-group">
                    <label class="ts-label">Developer Type</label>
                    <div class="d-flex gap-3">
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer">
                            <input type="radio" name="developer_type" value="individual"
                                   <?= ($_POST['developer_type']??'individual')==='individual'?'checked':'' ?>> Individual
                        </label>
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer">
                            <input type="radio" name="developer_type" value="company"
                                   <?= ($_POST['developer_type']??'')==='company'?'checked':'' ?>> Company
                        </label>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="ts-form-group">
                            <label class="ts-label">Country</label>
                            <select name="country" class="ts-select">
                                <option value="">Select country</option>
                                <?php foreach ($countries as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= ($_POST['country']??'')===$c?'selected':'' ?>>
                                    <?= htmlspecialchars($c) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ts-form-group">
                            <label class="ts-label">Mobile (optional)</label>
                            <input type="tel" name="mobile" class="ts-input"
                                   value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                                   placeholder="+1 234 567 8900">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="ts-btn-primary w-100 mt-2" style="border-radius:8px;justify-content:center;padding:13px">
                <span class="material-icons me-2">person_add</span>
                <span id="regBtnText"><?= $tab==='developer' ? 'Create Developer Account' : 'Create Account' ?></span>
            </button>
        </form>

        <hr class="ts-divider">
        <p class="text-center text-secondary-ts" style="font-size:.85rem">
            Already have an account?
            <a href="<?= SITE_URL ?>/auth/login.php" style="color:var(--ts-primary);font-weight:600">Sign In</a>
        </p>
    </div>
</div>

<script>
function switchTab(event, tab) {
    document.getElementById('reg_type').value = tab;
    document.getElementById('devFields').style.display = tab === 'developer' ? 'block' : 'none';
    document.getElementById('regBtnText').textContent = tab === 'developer' ? 'Create Developer Account' : 'Create Account';
    document.querySelectorAll('.ts-tab-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.3.2/mdb.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
