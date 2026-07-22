<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

ensure_column($conn, 'admins', 'name', "VARCHAR(100) DEFAULT ''");
$my_id = (int)$_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password_confirm'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'A valid email address is required.'];
    } elseif ($pass !== '' && strlen($pass) < 6) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'New password must be at least 6 characters.'];
    } elseif ($pass !== $pass2) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'The two passwords do not match.'];
    } else {
        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE admins SET name=?, email=?, password=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $hash, $my_id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE admins SET name=?, email=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssi', $name, $email, $my_id);
        }
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['admin_email'] = $email;
            log_activity($conn, 'profile', 'Updated own profile' . ($pass !== '' ? ' (password changed)' : ''));
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated.'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not update profile — that email may already be in use.'];
        }
        mysqli_stmt_close($stmt);
    }
    header('Location: profile.php');
    exit;
}

$page_title = 'My Profile';
$active_nav = '';
include 'header.php';
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-user me-2 text-primary"></i>My Profile</h1>
    <p>Your account details and password</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="cardx">
      <form method="post">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($current_admin['name'] === 'Administrator' ? '' : $current_admin['name']) ?>" placeholder="Full name">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($current_admin['email']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="password" minlength="6" placeholder="Leave blank to keep current">
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="password_confirm" minlength="6" placeholder="Repeat new password">
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i>Save Profile
          </button>
        </div>
      </form>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="cardx">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar-circle" style="width:56px;height:56px;font-size:1.4rem"><?= htmlspecialchars($admin_initial) ?></span>
        <div>
          <div class="fw-bold text-navy"><?= htmlspecialchars($current_admin['name']) ?></div>
          <div class="text-muted small"><?= htmlspecialchars($current_admin['email']) ?></div>
          <div class="text-muted small">This email is your AdminCP login.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
