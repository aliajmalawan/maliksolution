<?php
declare(strict_types=1);
$page_title = 'Change Password';
$active_nav = 'change_password';
include 'header.php';

$err_map = [
    'wrong' => 'Current password is incorrect.',
    'short' => 'New password must be at least 6 characters.',
    'match' => 'New passwords do not match.',
];
$err = !empty($_GET['err']) ? ($err_map[$_GET['err']] ?? '') : '';
?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-key me-2 text-primary"></i>Change Password</h1><p>Update your Teacher Portal login password.</p></div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="cardx">
      <?php if (!empty($_GET['msg'])): ?>
      <div class="alert alert-success mb-3"><i class="fa-solid fa-circle-check me-2"></i>Password changed successfully!</div>
      <?php endif; ?>
      <?php if ($err): ?>
      <div class="alert alert-danger mb-3"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>
      <form method="POST" action="action.php">
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

<?php include 'footer.php'; ?>
