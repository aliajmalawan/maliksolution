<?php
declare(strict_types=1);
$page_title = 'Activity Log';
$active_nav = 'activity';
include 'header.php';

// Make sure the table exists even before the first logged action
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_email VARCHAR(255) DEFAULT '',
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip VARCHAR(45) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$action_labels = [
    ''             => 'All actions',
    'login'        => 'Logins',
    'login_failed' => 'Failed logins',
    'settings'     => 'Settings changes',
    'user_create'  => 'Admin added',
    'user_update'  => 'Admin updated',
    'user_delete'  => 'Admin deleted',
    'profile'      => 'Profile updates',
    'backup'       => 'Backups',
];
$filter = trim((string)($_GET['action'] ?? ''));
if (!array_key_exists($filter, $action_labels)) $filter = '';

if ($filter !== '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM activity_logs WHERE action = ? ORDER BY created_at DESC LIMIT 200");
    mysqli_stmt_bind_param($stmt, 's', $filter);
    mysqli_stmt_execute($stmt);
    $logs = mysqli_stmt_get_result($stmt);
} else {
    $logs = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 200");
}

$badge_class = [
    'login'        => 'bg-success-subtle text-success',
    'login_failed' => 'bg-danger-subtle text-danger',
    'settings'     => 'bg-primary-subtle text-primary',
    'user_create'  => 'bg-primary-subtle text-primary',
    'user_update'  => 'bg-warning-subtle text-warning-emphasis',
    'user_delete'  => 'bg-danger-subtle text-danger',
    'profile'      => 'bg-info-subtle text-info-emphasis',
    'backup'       => 'bg-secondary-subtle text-secondary-emphasis',
];
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Activity Log</h1>
    <p>Who did what, and when — last 200 entries</p>
  </div>
  <form method="get" class="d-flex align-items-center gap-2">
    <select class="form-select form-select-sm" name="action" onchange="this.form.submit()" style="min-width:170px">
      <?php foreach ($action_labels as $val => $label): ?>
        <option value="<?= $val ?>" <?= $filter === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="cardx">
  <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr><th>When</th><th>Admin</th><th>Action</th><th>Details</th><th>IP</th></tr>
        </thead>
        <tbody>
          <?php while ($log = mysqli_fetch_assoc($logs)): ?>
            <tr>
              <td class="text-muted small" style="white-space:nowrap"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
              <td class="small"><strong><?= htmlspecialchars($log['admin_email'] ?: '—') ?></strong></td>
              <td>
                <span class="badge <?= $badge_class[$log['action']] ?? 'bg-secondary-subtle text-secondary-emphasis' ?>">
                  <?= htmlspecialchars($log['action']) ?>
                </span>
              </td>
              <td class="small"><?= htmlspecialchars($log['details']) ?></td>
              <td class="text-muted small"><?= htmlspecialchars($log['ip']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <p>No activity recorded<?= $filter !== '' ? ' for this filter' : ' yet' ?>.<br>
         Logins, settings changes, admin management, and backups are logged automatically.</p>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
