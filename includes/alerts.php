<?php
// ── includes/alerts.php — Flash message display ──────────────
require_once __DIR__ . '/auth.php';
$flash = getFlash();
if ($flash):
    $type  = $flash['type']; // success | error | info | warning
    $icons = ['success'=>'check_circle','error'=>'error','info'=>'info','warning'=>'warning'];
    $mdbClass = ['success'=>'success','error'=>'danger','info'=>'info','warning'=>'warning'];
    $icon = $icons[$type] ?? 'notifications';
    $cls  = $mdbClass[$type] ?? 'info';
?>
<div class="ts-alert ts-alert-<?= $cls ?> d-flex align-items-center gap-2 mb-3 fade-in" role="alert">
    <span class="material-icons"><?= $icon ?></span>
    <span><?= htmlspecialchars($flash['message']) ?></span>
    <button type="button" class="ms-auto ts-alert-close" onclick="this.closest('.ts-alert').remove()">
        <span class="material-icons" style="font-size:1.1rem">close</span>
    </button>
</div>
<?php endif; ?>
