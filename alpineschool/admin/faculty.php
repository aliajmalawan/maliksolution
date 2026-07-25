<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/faculty.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM faculty WHERE id = ?')->execute([$id]);
        flash_set('success', 'Faculty member removed.');
        redirect(BASE_URL . '/admin/faculty.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $designation = trim((string)($_POST['designation'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $bio = trim((string)($_POST['bio'] ?? ''));
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $uploaded = upload_image($_FILES['photo'], 'faculty');
        if ($uploaded) {
            $photoPath = 'uploads/' . $uploaded;
        }
    }

    if ($id > 0) {
        if ($photoPath) {
            $stmt = $pdo->prepare('UPDATE faculty SET name=?, designation=?, department=?, bio=?, sort_order=?, photo_path=? WHERE id=?');
            $stmt->execute([$name, $designation, $department, $bio, $sort_order, $photoPath, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE faculty SET name=?, designation=?, department=?, bio=?, sort_order=? WHERE id=?');
            $stmt->execute([$name, $designation, $department, $bio, $sort_order, $id]);
        }
        flash_set('success', 'Faculty profile updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO faculty (name, designation, department, bio, sort_order, photo_path) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $designation, $department, $bio, $sort_order, $photoPath ?? 'assets/images/logo.jpg']);
        flash_set('success', 'Faculty member added.');
    }
    redirect(BASE_URL . '/admin/faculty.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$facultyList = $pdo->query('SELECT * FROM faculty ORDER BY sort_order, name')->fetchAll();

$pageTitle = 'Faculty';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Faculty Member' : 'Add Faculty Member' ?></h2></div>
  <form method="post" action="faculty.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <?php if ($editing && $editing['photo_path']): ?>
      <img src="<?= BASE_URL ?>/<?= e($editing['photo_path']) ?>" class="current-image" alt="Current photo">
    <?php endif; ?>
    <div class="form-group">
      <label for="photo">Photo</label>
      <input type="file" id="photo" name="photo" accept="image/*">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="designation">Designation</label>
        <input type="text" id="designation" name="designation" value="<?= e($editing['designation'] ?? '') ?>" placeholder="e.g. Senior Teacher">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="department">Department</label>
        <input type="text" id="department" name="department" value="<?= e($editing['department'] ?? '') ?>" placeholder="e.g. Primary Section">
      </div>
      <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="bio">Short Bio</label>
      <textarea id="bio" name="bio"><?= e($editing['bio'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Profile' : 'Add Faculty Member' ?></button>
    <?php if ($editing): ?><a href="faculty.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Faculty (<?= count($facultyList) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($facultyList)): ?>
      <div class="empty-state">No faculty profiles yet. Add your first team member above.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Photo</th><th>Name</th><th>Designation</th><th>Department</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($facultyList as $member): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/<?= e($member['photo_path']) ?>" class="thumb"></td>
          <td><?= e($member['name']) ?></td>
          <td><?= e($member['designation']) ?></td>
          <td><?= e($member['department']) ?></td>
          <td class="actions-cell">
            <a href="faculty.php?edit=<?= (int)$member['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="faculty.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$member['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Remove this faculty member?">Delete</button>
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
