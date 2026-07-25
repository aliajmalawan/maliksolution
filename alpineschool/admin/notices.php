<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/notices.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM notices WHERE id = ?')->execute([$id]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'notice_delete', 'Deleted notice #' . $id);
        flash_set('success', 'Notice deleted.');
        redirect(BASE_URL . '/admin/notices.php');
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $body = trim((string)($_POST['body'] ?? ''));
    $starts = trim((string)($_POST['starts_at'] ?? '')) ?: null;
    $ends = trim((string)($_POST['ends_at'] ?? '')) ?: null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        flash_set('error', 'Please enter a notice title.');
        redirect(BASE_URL . '/admin/notices.php');
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE notices SET title=?, body=?, starts_at=?, ends_at=?, is_active=? WHERE id=?')
            ->execute([$title, $body, $starts, $ends, $isActive, $id]);
        flash_set('success', 'Notice updated.');
    } else {
        $pdo->prepare('INSERT INTO notices (title, body, starts_at, ends_at, is_active) VALUES (?, ?, ?, ?, ?)')
            ->execute([$title, $body, $starts, $ends, $isActive]);
        flash_set('success', 'Notice published.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'notice_save', 'Saved notice "' . $title . '"');
    redirect(BASE_URL . '/admin/notices.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM notices WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$notices = $pdo->query('SELECT * FROM notices ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Notice Board';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Notice' : 'Publish New Notice' ?></h2></div>
  <form method="post" action="notices.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-group">
      <label for="title">Notice Title *</label>
      <input type="text" id="title" name="title" required value="<?= e($editing['title'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label for="body">Details (optional)</label>
      <textarea id="body" name="body"><?= e($editing['body'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="starts_at">Show From (optional)</label>
        <input type="date" id="starts_at" name="starts_at" value="<?= e($editing['starts_at'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="ends_at">Show Until (optional)</label>
        <input type="date" id="ends_at" name="ends_at" value="<?= e($editing['ends_at'] ?? '') ?>">
        <p class="form-hint">Leave dates empty to show the notice indefinitely while active.</p>
      </div>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>> Active (shown as a bar on the website)</label>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Notice' : 'Publish Notice' ?></button>
    <?php if ($editing): ?><a href="notices.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Notices (<?= count($notices) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($notices)): ?>
      <div class="empty-state">No notices yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Title</th><th>Window</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($notices as $notice): ?>
        <tr>
          <td><?= e($notice['title']) ?></td>
          <td><?= $notice['starts_at'] ? format_date($notice['starts_at']) : '—' ?> → <?= $notice['ends_at'] ? format_date($notice['ends_at']) : '—' ?></td>
          <td><span class="badge <?= $notice['is_active'] ? 'badge-published' : 'badge-draft' ?>"><?= $notice['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td><?= format_date($notice['created_at']) ?></td>
          <td class="actions-cell">
            <a href="notices.php?edit=<?= (int)$notice['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="notices.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$notice['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this notice?">Delete</button>
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
