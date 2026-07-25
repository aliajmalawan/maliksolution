<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/notifications.php');
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'mark_all_read') {
        $pdo->query('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
        flash_set('success', 'All notifications marked as read.');
    } elseif ($action === 'clear_all') {
        $pdo->query('DELETE FROM notifications');
        flash_set('success', 'All notifications cleared.');
    } elseif ($action === 'mark_read') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    }
    redirect(BASE_URL . '/admin/notifications.php');
}

$notifications = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100')->fetchAll();
$notifIcons = ['inquiry' => '🎓', 'message' => '✉️', 'system' => '⚙️'];

$pageTitle = 'Notifications';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>All Notifications (<?= count($notifications) ?>)</h2>
    <div style="display:flex;gap:8px;">
      <form method="post" action="notifications.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn btn-outline btn-sm">Mark All Read</button>
      </form>
      <form method="post" action="notifications.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="clear_all">
        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete all notifications?">Clear All</button>
      </form>
    </div>
  </div>

  <?php if (empty($notifications)): ?>
    <div class="empty-state">No notifications yet. New admission inquiries and contact messages will appear here.</div>
  <?php else: ?>
    <div class="notif-list" style="max-height:none;">
      <?php foreach ($notifications as $n): ?>
        <a href="<?= e($n['link']) ?>" class="notif-item<?= $n['is_read'] ? '' : ' unread' ?>">
          <span class="n-ico"><?= $notifIcons[$n['type']] ?? '🔔' ?></span>
          <span style="flex:1;">
            <strong><?= e($n['title']) ?></strong>
            <small><?= e($n['message']) ?></small>
            <span class="n-time"><?= e(time_ago($n['created_at'])) ?></span>
          </span>
          <?php if (!$n['is_read']): ?>
            <form method="post" action="notifications.php" onclick="event.stopPropagation();">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm" onclick="event.preventDefault();this.form.submit();">Mark read</button>
            </form>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
