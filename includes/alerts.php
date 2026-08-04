<?php
// ── includes/alerts.php — Flash message display ──────────────
require_once __DIR__ . '/auth.php';
$flash = getFlash();
if ($flash):
    $type  = $flash['type']; // success | error | info | warning
    $icons = ['success'=>'ri-checkbox-circle-fill','error'=>'ri-error-warning-fill','info'=>'ri-information-fill','warning'=>'ri-alert-fill'];
    $mdbClass = ['success'=>'success','error'=>'danger','info'=>'info','warning'=>'warning'];
    $icon = $icons[$type] ?? 'ri-notification-3-fill';
    $cls  = $mdbClass[$type] ?? 'info';
?>
<div class="ts-alert ts-alert-<?= $cls ?> d-flex align-items-center gap-2 mb-3 fade-in" role="alert">
    <i class="<?= $icon ?>"></i>
    <span><?= htmlspecialchars($flash['message']) ?></span>
    <button type="button" class="ms-auto ts-alert-close" onclick="this.closest('.ts-alert').remove()">
        <i class="ri-close-line" style="font-size:1.1rem"></i>
    </button>
</div>
<?php endif; ?>
