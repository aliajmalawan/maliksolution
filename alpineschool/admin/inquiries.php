<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/inquiries.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM inquiries WHERE id = ?')->execute([$id]);
        flash_set('success', 'Inquiry deleted.');
    } elseif ($action === 'update_status') {
        $status = (string)($_POST['status'] ?? 'new');
        if (in_array($status, ['new', 'contacted', 'enrolled'], true)) {
            $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute([$status, $id]);
            flash_set('success', 'Status updated.');
        }
    }
    redirect(BASE_URL . '/admin/inquiries.php');
}

$inquiries = $pdo->query('SELECT * FROM inquiries ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Admission Inquiries';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2>All Admission Inquiries (<?= count($inquiries) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($inquiries)): ?>
      <div class="empty-state">No admission inquiries have been submitted yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Student</th><th>Parent</th><th>Phone</th><th>Email</th><th>Class</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($inquiries as $inq): ?>
        <tr>
          <td><?= e($inq['student_name']) ?></td>
          <td><?= e($inq['parent_name']) ?></td>
          <td><a href="tel:<?= e($inq['phone']) ?>"><?= e($inq['phone']) ?></a></td>
          <td><?= e($inq['email']) ?></td>
          <td><?= e($inq['class_applying']) ?></td>
          <td style="max-width:200px;"><?= e($inq['message']) ?></td>
          <td>
            <form method="post" action="inquiries.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
              <select name="status" onchange="this.form.submit()">
                <option value="new" <?= $inq['status'] === 'new' ? 'selected' : '' ?>>New</option>
                <option value="contacted" <?= $inq['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                <option value="enrolled" <?= $inq['status'] === 'enrolled' ? 'selected' : '' ?>>Enrolled</option>
              </select>
            </form>
          </td>
          <td><?= format_date($inq['created_at']) ?></td>
          <td>
            <form method="post" action="inquiries.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this inquiry?">Delete</button>
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
