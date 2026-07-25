<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/users.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        if ($id === (int)$currentAdmin['id']) {
            flash_set('error', 'You cannot delete your own account.');
        } else {
            $stmt = $pdo->prepare('SELECT full_name FROM admins WHERE id = ?');
            $stmt->execute([$id]);
            $target = $stmt->fetch();
            $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'user_delete', 'Deleted admin user "' . ($target['full_name'] ?? ('#' . $id)) . '"');
            flash_set('success', 'User deleted.');
        }
        redirect(BASE_URL . '/admin/users.php');
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $role = (string)($_POST['role'] ?? 'editor');
    $password = (string)($_POST['password'] ?? '');

    if (!in_array($role, ['super_admin', 'admin', 'editor'], true)) {
        $role = 'editor';
    }

    if ($username === '' || $fullName === '') {
        flash_set('error', 'Username and full name are required.');
        redirect(BASE_URL . '/admin/users.php');
    }

    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? AND id != ?');
    $stmt->execute([$username, $id]);
    if ($stmt->fetch()) {
        flash_set('error', 'That username is already taken.');
        redirect(BASE_URL . '/admin/users.php');
    }

    if ($id > 0) {
        if ($id === (int)$currentAdmin['id'] && $role !== $currentAdmin['role']) {
            flash_set('error', 'You cannot change your own role.');
            redirect(BASE_URL . '/admin/users.php');
        }
        if ($password !== '') {
            $pdo->prepare('UPDATE admins SET username=?, full_name=?, role=?, password_hash=? WHERE id=?')
                ->execute([$username, $fullName, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            $pdo->prepare('UPDATE admins SET username=?, full_name=?, role=? WHERE id=?')
                ->execute([$username, $fullName, $role, $id]);
        }
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'user_update', 'Updated admin user "' . $fullName . '" (' . $role . ')');
        flash_set('success', 'User updated.');
    } else {
        if ($password === '') {
            flash_set('error', 'A password is required for a new user.');
            redirect(BASE_URL . '/admin/users.php');
        }
        $pdo->prepare('INSERT INTO admins (username, full_name, role, password_hash) VALUES (?, ?, ?, ?)')
            ->execute([$username, $fullName, $role, password_hash($password, PASSWORD_DEFAULT)]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'user_create', 'Created admin user "' . $fullName . '" (' . $role . ')');
        flash_set('success', 'User created.');
    }
    redirect(BASE_URL . '/admin/users.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$users = $pdo->query('SELECT * FROM admins ORDER BY created_at')->fetchAll();
$roleLabels = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'editor' => 'Editor'];

$pageTitle = 'Admin Users';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit User' : 'Add New User' ?></h2></div>
  <form method="post" action="users.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-row">
      <div class="form-group">
        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name" required value="<?= e($editing['full_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="username">Username *</label>
        <input type="text" id="username" name="username" required value="<?= e($editing['username'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="role">Role</label>
        <select id="role" name="role" <?= ($editing && (int)$editing['id'] === (int)$currentAdmin['id']) ? 'disabled' : '' ?>>
          <?php foreach ($roleLabels as $value => $label): ?>
            <option value="<?= $value ?>" <?= ($editing['role'] ?? 'editor') === $value ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <p class="form-hint">Editor: content only · Admin: + inbox &amp; analytics · Super Admin: + settings, users, activity</p>
      </div>
      <div class="form-group">
        <label for="password">Password <?= $editing ? '(leave empty to keep current)' : '*' ?></label>
        <input type="password" id="password" name="password" <?= $editing ? '' : 'required' ?> autocomplete="new-password">
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update User' : 'Create User' ?></button>
    <?php if ($editing): ?><a href="users.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Users (<?= count($users) ?>)</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Last Login</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= e($user['full_name']) ?><?= (int)$user['id'] === (int)$currentAdmin['id'] ? ' <span class="form-hint" style="display:inline;">(you)</span>' : '' ?></td>
          <td><?= e($user['username']) ?></td>
          <td><span class="badge role-badge-<?= e($user['role']) ?>"><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span></td>
          <td><?= $user['last_login_at'] ? e(time_ago($user['last_login_at'])) : '<span class="form-hint" style="display:inline;">never</span>' ?></td>
          <td><?= format_date($user['created_at']) ?></td>
          <td class="actions-cell">
            <a href="users.php?edit=<?= (int)$user['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <?php if ((int)$user['id'] !== (int)$currentAdmin['id']): ?>
            <form method="post" action="users.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this user?">Delete</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
