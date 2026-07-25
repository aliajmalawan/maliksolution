<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/departments.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
        flash_set('success', 'Department deleted.');
        redirect(BASE_URL . '/admin/departments.php');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if ($name === '') {
        flash_set('error', 'Department name is required.');
        redirect(BASE_URL . '/admin/departments.php');
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE departments SET name=?, description=?, sort_order=? WHERE id=?')
            ->execute([$name, $description, $sortOrder, $id]);
        flash_set('success', 'Department updated.');
    } else {
        $pdo->prepare('INSERT INTO departments (name, description, sort_order) VALUES (?, ?, ?)')
            ->execute([$name, $description, $sortOrder]);
        flash_set('success', 'Department added.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'department_save', 'Saved department "' . $name . '"');
    redirect(BASE_URL . '/admin/departments.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM departments WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$departments = $pdo->query('SELECT * FROM departments ORDER BY sort_order, id')->fetchAll();
$facultyCounts = [];
foreach ($pdo->query('SELECT department, COUNT(*) c FROM faculty GROUP BY department')->fetchAll() as $row) {
    $facultyCounts[$row['department']] = (int)$row['c'];
}

$pageTitle = 'Departments';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Department' : 'Add Department' ?></h2></div>
  <form method="post" action="departments.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-row">
      <div class="form-group">
        <label for="name">Department Name *</label>
        <input type="text" id="name" name="name" required value="<?= e($editing['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <input type="text" id="description" name="description" value="<?= e($editing['description'] ?? '') ?>">
      <p class="form-hint">Faculty members are matched to a department by the "Department" field on the Faculty page.</p>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?> Department</button>
    <?php if ($editing): ?><a href="departments.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Departments (<?= count($departments) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($departments)): ?>
      <div class="empty-state">No departments yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Name</th><th>Description</th><th>Faculty Members</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($departments as $dept): ?>
        <tr>
          <td><?= e($dept['name']) ?></td>
          <td><?= e($dept['description']) ?></td>
          <td><?= $facultyCounts[$dept['name']] ?? 0 ?></td>
          <td><?= (int)$dept['sort_order'] ?></td>
          <td class="actions-cell">
            <a href="departments.php?edit=<?= (int)$dept['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="departments.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$dept['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this department?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
