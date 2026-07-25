<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/messages.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        flash_set('success', 'Message deleted.');
    } elseif ($action === 'mark_read') {
        $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
        flash_set('success', 'Marked as read.');
    }
    redirect(BASE_URL . '/admin/messages.php');
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Contact Messages';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2>All Contact Messages (<?= count($messages) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($messages)): ?>
      <div class="empty-state">No contact messages have been submitted yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($messages as $msg): ?>
        <tr>
          <td><?= e($msg['name']) ?></td>
          <td><?= e($msg['email']) ?></td>
          <td><?= e($msg['phone']) ?></td>
          <td><?= e($msg['subject']) ?></td>
          <td style="max-width:220px;"><?= e($msg['message']) ?></td>
          <td><span class="badge <?= $msg['is_read'] ? 'badge-enrolled' : 'badge-new' ?>"><?= $msg['is_read'] ? 'Read' : 'Unread' ?></span></td>
          <td><?= format_date($msg['created_at']) ?></td>
          <td class="actions-cell">
            <?php if (!$msg['is_read']): ?>
            <form method="post" action="messages.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm">Mark Read</button>
            </form>
            <?php endif; ?>
            <form method="post" action="messages.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this message?">Delete</button>
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
