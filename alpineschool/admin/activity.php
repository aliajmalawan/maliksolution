<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/activity.php');
    }
    if (($_POST['action'] ?? '') === 'clear') {
        $pdo->query('DELETE FROM activity_logs');
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'log_clear', 'Cleared the activity log');
        flash_set('success', 'Activity log cleared.');
    }
    redirect(BASE_URL . '/admin/activity.php');
}

$logs = $pdo->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 200')->fetchAll();

$pageTitle = 'Activity Log';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Activity Log (last <?= count($logs) ?> entries)</h2>
    <form method="post" action="activity.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="clear">
      <button type="submit" class="btn btn-danger btn-sm" data-confirm="Clear the entire activity log?">Clear Log</button>
    </form>
  </div>
  <div class="table-wrap">
    <?php if (empty($logs)): ?>
      <div class="empty-state">No activity has been recorded yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td style="white-space:nowrap;"><?= e(date('d M Y, H:i', strtotime($log['created_at']))) ?></td>
          <td><?= e($log['admin_name']) ?></td>
          <td><span class="badge badge-draft"><?= e($log['action']) ?></span></td>
          <td><?= e($log['details']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
