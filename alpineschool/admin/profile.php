<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/profile.php');
    }

    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $username === '') {
        flash_set('error', 'Full name and username are required.');
        redirect(BASE_URL . '/admin/profile.php');
    }

    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? AND id != ?');
    $stmt->execute([$username, (int)$currentAdmin['id']]);
    if ($stmt->fetch()) {
        flash_set('error', 'That username is already taken.');
        redirect(BASE_URL . '/admin/profile.php');
    }

    if ($newPassword !== '') {
        if (!password_verify($currentPassword, $currentAdmin['password_hash'])) {
            flash_set('error', 'Your current password is incorrect.');
            redirect(BASE_URL . '/admin/profile.php');
        }
        if (strlen($newPassword) < 8) {
            flash_set('error', 'New password must be at least 8 characters.');
            redirect(BASE_URL . '/admin/profile.php');
        }
        if ($newPassword !== $confirmPassword) {
            flash_set('error', 'New password and confirmation do not match.');
            redirect(BASE_URL . '/admin/profile.php');
        }
        $pdo->prepare('UPDATE admins SET full_name=?, username=?, password_hash=? WHERE id=?')
            ->execute([$fullName, $username, password_hash($newPassword, PASSWORD_DEFAULT), (int)$currentAdmin['id']]);
        log_activity($pdo, (int)$currentAdmin['id'], $fullName, 'profile_update', 'Updated profile and changed password');
        flash_set('success', 'Profile and password updated.');
    } else {
        $pdo->prepare('UPDATE admins SET full_name=?, username=? WHERE id=?')
            ->execute([$fullName, $username, (int)$currentAdmin['id']]);
        log_activity($pdo, (int)$currentAdmin['id'], $fullName, 'profile_update', 'Updated profile details');
        flash_set('success', 'Profile updated.');
    }
    $_SESSION['admin_name'] = $fullName;
    redirect(BASE_URL . '/admin/profile.php');
}

$roleLabels = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'editor' => 'Editor'];

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><h2>Profile Details</h2></div>
    <form method="post" action="profile.php">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name" required value="<?= e($currentAdmin['full_name']) ?>">
      </div>
      <div class="form-group">
        <label for="username">Username *</label>
        <input type="text" id="username" name="username" required value="<?= e($currentAdmin['username']) ?>">
      </div>
      <div class="form-group">
        <label>Role</label>
        <p><span class="badge role-badge-<?= e($currentAdmin['role']) ?>"><?= e($roleLabels[$currentAdmin['role']] ?? $currentAdmin['role']) ?></span></p>
        <p class="form-hint">Roles are managed by a Super Admin on the Admin Users page.</p>
      </div>

      <div class="card-header" style="margin-top:26px;"><h2>Change Password</h2></div>
      <div class="form-group">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" autocomplete="new-password">
          <p class="form-hint">At least 8 characters. Leave empty to keep your current password.</p>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><h2>Account</h2></div>
    <div class="feed">
      <div class="feed-item"><span class="f-ico">👤</span><span><strong>Signed in as</strong><small><?= e($currentAdmin['full_name']) ?> (@<?= e($currentAdmin['username']) ?>)</small></span></div>
      <div class="feed-item"><span class="f-ico">🛡️</span><span><strong>Role</strong><small><?= e($roleLabels[$currentAdmin['role']] ?? $currentAdmin['role']) ?></small></span></div>
      <div class="feed-item"><span class="f-ico">🕒</span><span><strong>Last login</strong><small><?= $currentAdmin['last_login_at'] ? e(date('d M Y, H:i', strtotime($currentAdmin['last_login_at']))) : 'First session' ?></small></span></div>
      <div class="feed-item"><span class="f-ico">📅</span><span><strong>Account created</strong><small><?= format_date($currentAdmin['created_at']) ?></small></span></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
