<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

ensure_column($conn, 'admins', 'name', "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'admins', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'create') {
        $name  = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'A valid email and a password of at least 6 characters are required.'];
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hash);
            if (mysqli_stmt_execute($stmt)) {
                log_activity($conn, 'user_create', "Added admin user $email");
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Admin \"$email\" added."];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not add admin — that email may already exist.'];
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === 'update') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        if ($id < 1 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'A valid email is required.'];
        } elseif ($pass !== '' && strlen($pass) < 6) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'New password must be at least 6 characters.'];
        } else {
            if ($pass !== '') {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE admins SET name=?, email=?, password=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $hash, $id);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE admins SET name=?, email=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'ssi', $name, $email, $id);
            }
            if (mysqli_stmt_execute($stmt)) {
                log_activity($conn, 'user_update', "Updated admin user $email");
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Admin updated.'];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not update admin — that email may already exist.'];
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $r  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admins"));
        $total = (int)($r[0] ?? 0);
        if ($id === (int)$_SESSION['admin_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'You cannot delete your own account.'];
        } elseif ($total <= 1) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'At least one admin account must remain.'];
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM admins WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            log_activity($conn, 'user_delete', "Deleted admin user #$id");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Admin deleted.'];
        }
    }

    header('Location: users.php');
    exit;
}

$page_title = 'Admin Users';
$active_nav = 'users';
include 'header.php';

$admins = mysqli_query($conn, "SELECT id, name, email, created_at FROM admins ORDER BY id ASC");
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-users me-2 text-primary"></i>Admin Users</h1>
    <p>Accounts that can sign in to the AdminCP</p>
  </div>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
    <i class="fa-solid fa-user-plus me-1"></i>Add Admin
  </button>
</div>

<div class="cardx">
  <div class="table-wrap">
    <table class="table align-middle">
      <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Created</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php while ($a = mysqli_fetch_assoc($admins)): ?>
          <tr>
            <td class="text-muted"><?= (int)$a['id'] ?></td>
            <td>
              <strong><?= htmlspecialchars($a['name'] ?: 'Administrator') ?></strong>
              <?php if ((int)$a['id'] === (int)$_SESSION['admin_id']): ?>
                <span class="badge bg-primary-subtle text-primary ms-1">You</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($a['email']) ?></td>
            <td class="text-muted small"><?= $a['created_at'] ? date('d M Y', strtotime($a['created_at'])) : '—' ?></td>
            <td class="text-end">
              <button class="btn btn-outline-primary btn-xs" data-bs-toggle="modal" data-bs-target="#editAdminModal"
                      data-id="<?= (int)$a['id'] ?>" data-name="<?= htmlspecialchars($a['name']) ?>" data-email="<?= htmlspecialchars($a['email']) ?>">
                <i class="fa-solid fa-pen"></i> Edit
              </button>
              <?php if ((int)$a['id'] !== (int)$_SESSION['admin_id']): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this admin account?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <button class="btn btn-outline-danger btn-xs"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Add Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" name="name" placeholder="Full name">
        </div>
        <div class="mb-3">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" class="form-control" name="email" required placeholder="admin@example.com">
        </div>
        <div class="mb-0">
          <label class="form-label">Password <span class="text-danger">*</span></label>
          <input type="password" class="form-control" name="password" required minlength="6" placeholder="At least 6 characters">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk me-1"></i>Add Admin</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-pen me-2 text-primary"></i>Edit Admin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" class="form-control" name="name" id="edit-name">
        </div>
        <div class="mb-3">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <input type="email" class="form-control" name="email" id="edit-email" required>
        </div>
        <div class="mb-0">
          <label class="form-label">New Password</label>
          <input type="password" class="form-control" name="password" minlength="6" placeholder="Leave blank to keep current password">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk me-1"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('editAdminModal')?.addEventListener('show.bs.modal', function (ev) {
  const btn = ev.relatedTarget;
  this.querySelector('#edit-id').value    = btn.dataset.id;
  this.querySelector('#edit-name').value  = btn.dataset.name;
  this.querySelector('#edit-email').value = btn.dataset.email;
});
</script>

<?php include 'footer.php'; ?>
