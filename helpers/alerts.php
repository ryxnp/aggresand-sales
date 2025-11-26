<?php
// helpers/alerts.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['alert'])):
    $type = $_SESSION['alert']['type'] ?? 'info';
    $message = $_SESSION['alert']['message'] ?? '';

    $bsType = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info'
    ][$type] ?? 'alert-info';
?>
    <div class="alert <?= $bsType ?> alert-dismissible fade show mt-3" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
    unset($_SESSION['alert']);
endif;
