<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/results.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM results WHERE id = ?')->execute([$id]);
        flash_set('success', 'Result deleted.');
        redirect(BASE_URL . '/admin/results.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $class_name = trim((string)($_POST['class_name'] ?? ''));

    $filePath = null;
    if (!empty($_FILES['file']['name'])) {
        $uploaded = upload_file($_FILES['file'], 'results', document_mime_map());
        if ($uploaded) {
            $filePath = 'uploads/' . $uploaded['path'];
        }
    }

    if ($id > 0) {
        if ($filePath) {
            $stmt = $pdo->prepare('UPDATE results SET title=?, class_name=?, file_path=? WHERE id=?');
            $stmt->execute([$title, $class_name, $filePath, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE results SET title=?, class_name=? WHERE id=?');
            $stmt->execute([$title, $class_name, $id]);
        }
        flash_set('success', 'Result updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO results (title, class_name, file_path) VALUES (?, ?, ?)');
        $stmt->execute([$title, $class_name, $filePath]);
        flash_set('success', 'Result published.');
    }
    redirect(BASE_URL . '/admin/results.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM results WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$resultsList = $pdo->query('SELECT * FROM results ORDER BY published_at DESC')->fetchAll();

$pageTitle = 'Results';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Result' : 'Add Result' ?></h2></div>
  <form method="post" action="results.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" placeholder="e.g. Mid-Term Examination 2026" required>
    </div>
    <div class="form-group">
      <label for="class_name">Class / Grade</label>
      <input type="text" id="class_name" name="class_name" value="<?= e($editing['class_name'] ?? '') ?>" placeholder="e.g. Grade 5">
    </div>
    <div class="form-group">
      <label for="file">Result File (PDF) <?= $editing ? '(leave empty to keep current)' : '' ?></label>
      <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">
      <?php if ($editing && $editing['file_path']): ?>
        <p class="form-hint"><a href="<?= BASE_URL ?>/<?= e($editing['file_path']) ?>" target="_blank">View current file</a></p>
      <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Result' : 'Publish Result' ?></button>
    <?php if ($editing): ?><a href="results.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Results</h2></div>
  <div class="table-wrap">
    <?php if (empty($resultsList)): ?>
      <div class="empty-state">No results published yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Title</th><th>Class</th><th>File</th><th>Published</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($resultsList as $result): ?>
        <tr>
          <td><?= e($result['title']) ?></td>
          <td><?= e($result['class_name']) ?></td>
          <td><?php if ($result['file_path']): ?><a href="<?= BASE_URL ?>/<?= e($result['file_path']) ?>" target="_blank">View</a><?php else: ?>—<?php endif; ?></td>
          <td><?= format_date($result['published_at']) ?></td>
          <td class="actions-cell">
            <a href="results.php?edit=<?= (int)$result['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="results.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$result['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this result?">Delete</button>
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
