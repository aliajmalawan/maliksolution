<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/career.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM careers WHERE id = ?')->execute([$id]);
        flash_set('success', 'Job listing deleted.');
        redirect(BASE_URL . '/admin/career.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $job_type = (string)($_POST['job_type'] ?? 'full-time');
    if (!in_array($job_type, ['full-time', 'part-time', 'contract'], true)) {
        $job_type = 'full-time';
    }
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE careers SET title=?, description=?, location=?, job_type=?, is_active=? WHERE id=?');
        $stmt->execute([$title, $description, $location, $job_type, $is_active, $id]);
        flash_set('success', 'Job listing updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO careers (title, description, location, job_type, is_active) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$title, $description, $location, $job_type, $is_active]);
        flash_set('success', 'Job listing added.');
    }
    redirect(BASE_URL . '/admin/career.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM careers WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$careersList = $pdo->query('SELECT * FROM careers ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Career';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Job Listing' : 'Add Job Listing' ?></h2></div>
  <form method="post" action="career.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-group">
      <label for="title">Job Title</label>
      <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"><?= e($editing['description'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($editing['location'] ?? '') ?>" placeholder="e.g. Haroonabad Campus">
      </div>
      <div class="form-group">
        <label for="job_type">Job Type</label>
        <select id="job_type" name="job_type">
          <?php foreach (['full-time' => 'Full-Time', 'part-time' => 'Part-Time', 'contract' => 'Contract'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($editing['job_type'] ?? 'full-time') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>> Active (visible on site)</label>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Listing' : 'Add Listing' ?></button>
    <?php if ($editing): ?><a href="career.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Job Listings</h2></div>
  <div class="table-wrap">
    <?php if (empty($careersList)): ?>
      <div class="empty-state">No job listings yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Title</th><th>Location</th><th>Type</th><th>Status</th><th>Posted</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($careersList as $job): ?>
        <tr>
          <td><?= e($job['title']) ?></td>
          <td><?= e($job['location']) ?></td>
          <td><?= e(ucwords(str_replace('-', ' ', $job['job_type']))) ?></td>
          <td><span class="badge <?= $job['is_active'] ? 'badge-enrolled' : 'badge-draft' ?>"><?= $job['is_active'] ? 'Active' : 'Closed' ?></span></td>
          <td><?= format_date($job['created_at']) ?></td>
          <td class="actions-cell">
            <a href="career.php?edit=<?= (int)$job['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="career.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$job['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this job listing?">Delete</button>
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
