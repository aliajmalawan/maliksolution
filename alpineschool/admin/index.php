<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/charts.php';

$pageTitle = 'Dashboard';
$isAdmin = has_role($currentAdmin, 'admin');
$isSuper = has_role($currentAdmin, 'super_admin');

// ---- KPI stat cards ----
$totalInquiries = (int)$pdo->query('SELECT COUNT(*) c FROM inquiries')->fetch()['c'];
$inqThisMonth = (int)$pdo->query('SELECT COUNT(*) c FROM inquiries WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())')->fetch()['c'];
$inqLastMonth = (int)$pdo->query("SELECT COUNT(*) c FROM inquiries WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')")->fetch()['c'];

$viewsToday = (int)$pdo->query('SELECT COUNT(*) c FROM page_views WHERE DATE(created_at) = CURDATE()')->fetch()['c'];
$viewsYesterday = (int)$pdo->query('SELECT COUNT(*) c FROM page_views WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY')->fetch()['c'];
$onlineNow = (int)$pdo->query('SELECT COUNT(DISTINCT ip) c FROM page_views WHERE created_at >= NOW() - INTERVAL 5 MINUTE')->fetch()['c'];
$visitors30 = (int)$pdo->query('SELECT COUNT(DISTINCT ip) c FROM page_views WHERE created_at >= NOW() - INTERVAL 30 DAY')->fetch()['c'];

$unreadMessages = (int)$pdo->query('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0')->fetch()['c'];
$totalMessages = (int)$pdo->query('SELECT COUNT(*) c FROM contact_messages')->fetch()['c'];

// ---- Traffic & Visitors: 30-day daily series (two series, one axis) ----
if ($isAdmin) {
    $pv = daily_series($pdo, 'page_views', 30);
    $uv = daily_series($pdo, 'page_views', 30, 'created_at', 'COUNT(DISTINCT ip)');
    $trafficLabels = array_map(fn($d) => date('M j', strtotime($d)), array_keys($pv));
    $trafficSeries = [
        ['label' => 'Page Views', 'class' => 'series-a', 'data' => array_values($pv)],
        ['label' => 'Unique Visitors', 'class' => 'series-b', 'data' => array_values($uv)],
    ];

    // Admissions
    $admissionsMonthly = months_series($pdo, 'inquiries');
    $statusCounts = ['new' => 0, 'contacted' => 0, 'enrolled' => 0];
    foreach ($pdo->query('SELECT status, COUNT(*) c FROM inquiries GROUP BY status')->fetchAll() as $row) {
        $statusCounts[$row['status']] = (int)$row['c'];
    }

    // Messages
    $messagesMonthly = months_series($pdo, 'contact_messages');
}

// ---- Content: News & Blog posts per month (editor+) ----
$newsMonthly = months_series($pdo, 'news', 'published_at');
$blogMonthly = months_series($pdo, 'blogs', 'published_at');
$contentMonthly = [];
foreach ($newsMonthly as $label => $count) {
    $contentMonthly[$label] = $count + ($blogMonthly[$label] ?? 0);
}

// ---- Gallery: photos by category (editor+) ----
$galleryByCat = [];
foreach ($pdo->query(
    'SELECT COALESCE(gc.name, "Uncategorized") name, COUNT(gi.id) c
     FROM gallery_images gi LEFT JOIN gallery_categories gc ON gi.category_id = gc.id
     GROUP BY gc.id ORDER BY c DESC LIMIT 8'
)->fetchAll() as $row) {
    $galleryByCat[$row['name']] = (int)$row['c'];
}

// ---- Users by role (super admin) ----
if ($isSuper) {
    $roleLabels = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'editor' => 'Editor'];
    $roleSliceClass = ['super_admin' => 'slice-5', 'admin' => 'slice-1', 'editor' => 'slice-2'];
    $usersByRole = ['super_admin' => 0, 'admin' => 0, 'editor' => 0];
    foreach ($pdo->query('SELECT role, COUNT(*) c FROM admins GROUP BY role')->fetchAll() as $row) {
        $usersByRole[$row['role']] = (int)$row['c'];
    }
    $userSlices = [];
    foreach ($usersByRole as $role => $count) {
        $userSlices[] = ['label' => $roleLabels[$role] ?? $role, 'value' => $count, 'class' => $roleSliceClass[$role] ?? 'slice-3'];
    }
    $loginsDaily = daily_series($pdo, 'activity_logs', 14, 'created_at', 'COUNT(*)', "action = 'login'");
    $loginsColumns = [];
    foreach ($loginsDaily as $d => $c) {
        $loginsColumns[date('j', strtotime($d))] = $c;
    }
}

$recentInquiries = $pdo->query('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recentActivity = $pdo->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8')->fetchAll();

function delta_html(int $current, int $previous): string
{
    if ($previous === 0 && $current === 0) {
        return '';
    }
    if ($previous === 0) {
        return '<span class="stat-delta up">▲ new</span>';
    }
    $pct = (int)round(($current - $previous) / $previous * 100);
    return $pct >= 0
        ? '<span class="stat-delta up">▲ ' . $pct . '%</span>'
        : '<span class="stat-delta down">▼ ' . abs($pct) . '%</span>';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="dash-stats">
  <div class="dash-stat">
    <strong><?= $viewsToday ?><?= delta_html($viewsToday, $viewsYesterday) ?></strong>
    <span>Page Views Today</span>
    <span class="stat-sub">vs <?= $viewsYesterday ?> yesterday · <?= $onlineNow ?> online now</span>
  </div>
  <div class="dash-stat">
    <strong><?= number_format($visitors30) ?></strong>
    <span>Unique Visitors (30 days)</span>
    <span class="stat-sub">distinct IP addresses</span>
  </div>
  <div class="dash-stat">
    <strong><?= $inqThisMonth ?><?= delta_html($inqThisMonth, $inqLastMonth) ?></strong>
    <span>Inquiries This Month</span>
    <span class="stat-sub"><?= $totalInquiries ?> all time</span>
  </div>
  <div class="dash-stat">
    <strong><?= $unreadMessages ?></strong>
    <span>Unread Messages</span>
    <span class="stat-sub"><?= $totalMessages ?> total received</span>
  </div>
</div>

<?php if ($isAdmin): ?>
<!-- Traffic + Visitors -->
<div class="card">
  <div class="card-header">
    <h2>Traffic &amp; Visitors — Last 30 Days</h2>
    <a href="analytics.php" class="btn btn-outline btn-sm">Full Analytics</a>
  </div>
  <?= svg_line_chart($trafficSeries, $trafficLabels, 'Daily page views and unique visitors over the last 30 days') ?>
</div>

<!-- Admissions -->
<div class="charts-grid">
  <div class="card">
    <div class="card-header"><h2>Admissions — Inquiries (6 Months)</h2></div>
    <?= svg_columns($admissionsMonthly, 'Admission inquiries received per month over the last 6 months') ?>
  </div>
  <div class="card">
    <div class="card-header"><h2>Inquiry Pipeline</h2></div>
    <?= svg_donut([
        ['label' => 'New', 'value' => $statusCounts['new'], 'class' => 'slice-1'],
        ['label' => 'Contacted', 'value' => $statusCounts['contacted'], 'class' => 'slice-3'],
        ['label' => 'Enrolled', 'value' => $statusCounts['enrolled'], 'class' => 'slice-2'],
    ], 'Admission inquiries by pipeline status', 'Inquiries') ?>
    <?php if ($totalInquiries > 0): ?>
      <p class="form-hint" style="margin-top:16px;">Enrollment rate: <strong><?= round($statusCounts['enrolled'] / $totalInquiries * 100, 1) ?>%</strong> of all inquiries.</p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- News + Gallery (content, editor+) -->
<div class="charts-grid">
  <div class="card">
    <div class="card-header"><h2>News &amp; Blog Posts — 6 Months</h2></div>
    <?= svg_columns($contentMonthly, 'News articles and blog posts published per month', 'col-accent') ?>
  </div>
  <div class="card">
    <div class="card-header">
      <h2>Gallery — Photos by Category</h2>
      <a href="gallery.php" class="btn btn-outline btn-sm">Manage</a>
    </div>
    <?php if (empty($galleryByCat)): ?>
      <div class="empty-state">No gallery photos yet.</div>
    <?php else: ?>
      <?= svg_hbars($galleryByCat) ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($isAdmin): ?>
<!-- Messages -->
<div class="charts-grid">
  <div class="card">
    <div class="card-header"><h2>Contact Messages — 6 Months</h2></div>
    <?= svg_columns($messagesMonthly, 'Contact messages received per month over the last 6 months') ?>
  </div>
  <div class="card">
    <div class="card-header"><h2>Message Status</h2></div>
    <?= svg_donut([
        ['label' => 'Read', 'value' => $totalMessages - $unreadMessages, 'class' => 'slice-2'],
        ['label' => 'Unread', 'value' => $unreadMessages, 'class' => 'slice-4'],
    ], 'Contact messages by read status', 'Messages') ?>
  </div>
</div>
<?php endif; ?>

<?php if ($isSuper): ?>
<!-- Users -->
<div class="charts-grid">
  <div class="card">
    <div class="card-header">
      <h2>Admin Users by Role</h2>
      <a href="users.php" class="btn btn-outline btn-sm">Manage</a>
    </div>
    <?= svg_donut($userSlices, 'Admin users grouped by role', 'Users') ?>
  </div>
  <div class="card">
    <div class="card-header"><h2>Admin Logins — 14 Days</h2></div>
    <?= svg_columns($loginsColumns, 'Successful admin logins per day over the last 14 days', 'col-accent') ?>
  </div>
</div>
<?php endif; ?>

<!-- Recent activity -->
<div class="grid-2">
  <?php if ($isAdmin): ?>
  <div class="card">
    <div class="card-header">
      <h2>Recent Admission Inquiries</h2>
      <a href="inquiries.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-wrap">
      <?php if (empty($recentInquiries)): ?>
        <div class="empty-state">No admission inquiries yet.</div>
      <?php else: ?>
      <table>
        <thead><tr><th>Student</th><th>Class</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recentInquiries as $inq): ?>
          <tr>
            <td><?= e($inq['student_name']) ?></td>
            <td><?= e($inq['class_applying']) ?></td>
            <td><span class="badge badge-<?= e($inq['status']) ?>"><?= e(ucfirst($inq['status'])) ?></span></td>
            <td><?= e(time_ago($inq['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <h2>Recent Activity</h2>
      <?php if ($isSuper): ?><a href="activity.php" class="btn btn-outline btn-sm">Full Log</a><?php endif; ?>
    </div>
    <?php if (empty($recentActivity)): ?>
      <div class="empty-state">No activity recorded yet.</div>
    <?php else: ?>
    <div class="feed">
      <?php foreach ($recentActivity as $log): ?>
        <div class="feed-item">
          <span class="f-ico">🕒</span>
          <span>
            <strong><?= e($log['admin_name']) ?></strong> — <?= e($log['details'] ?: $log['action']) ?>
            <small><?= e(time_ago($log['created_at'])) ?></small>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
