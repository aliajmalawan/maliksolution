<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Change Password'; $active_nav = 'change_password';
require __DIR__ . '/header.php';
?>

<?php if ($f = flash_get()): ?>
<div class="alert alert-<?= $f['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
  <i class="fa-solid fa-circle-<?= $f['type']==='success'?'check':'exclamation' ?> me-2"></i><?= e($f['message']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-key me-2 text-primary"></i>Change Password</h1><p>Update your Student Portal login password.</p></div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="cardx">
      <form method="POST" action="action.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="mb-3">
          <label class="form-label fw-bold">Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">New Password</label>
          <input type="password" name="new_password" class="form-control" required minlength="6">
          <div class="form-text">At least 6 characters.</div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-lock me-1"></i>Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
