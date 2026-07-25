<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/downloads.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM downloads WHERE id = ?')->execute([$id]);
        flash_set('success', 'Download deleted.');
        redirect(BASE_URL . '/admin/downloads.php');
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $category = trim((string)($_POST['category'] ?? '')) ?: 'General';

    if (empty($_FILES['file']['name'])) {
        flash_set('error', 'Please choose a file to upload.');
        redirect(BASE_URL . '/admin/downloads.php');
    }

    $uploaded = upload_file($_FILES['file'], 'downloads', document_mime_map());
    if (!$uploaded) {
        flash_set('error', 'Upload failed. Allowed types: PDF, DOC, DOCX, XLS, XLSX, ZIP (max 15MB).');
        redirect(BASE_URL . '/admin/downloads.php');
    }

    $stmt = $pdo->prepare('INSERT INTO downloads (title, category, file_path, file_size) VALUES (?, ?, ?, ?)');
    $stmt->execute([$title, $category, 'uploads/' . $uploaded['path'], $uploaded['size']]);
    flash_set('success', 'File uploaded.');
    redirect(BASE_URL . '/admin/downloads.php');
}

$downloadsList = $pdo->query('SELECT * FROM downloads ORDER BY uploaded_at DESC')->fetchAll();

$pageTitle = 'Downloads';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2>Add Download</h2></div>
  <form method="post" action="downloads.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" required>
    </div>
    <div class="form-group">
      <label for="category">Category</label>
      <input type="text" id="category" name="category" placeholder="e.g. Admission Forms, Syllabus, Circulars">
    </div>
    <div class="form-group">
      <label for="file">File *</label>
      <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip" required>
      <p class="form-hint">Allowed: PDF, DOC, DOCX, XLS, XLSX, ZIP (max 15MB).</p>
    </div>
    <button type="submit" class="btn btn-primary">Upload</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Downloads (<?= count($downloadsList) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($downloadsList)): ?>
      <div class="empty-state">No files uploaded yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Title</th><th>Category</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($downloadsList as $item): ?>
        <tr>
          <td><?= e($item['title']) ?></td>
          <td><?= e($item['category']) ?></td>
          <td><?= human_filesize((int)$item['file_size']) ?></td>
          <td><?= format_date($item['uploaded_at']) ?></td>
          <td class="actions-cell">
            <a href="<?= BASE_URL ?>/<?= e($item['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">Download</a>
            <form method="post" action="downloads.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this file?">Delete</button>
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
