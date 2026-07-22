<?php
declare(strict_types=1);
$page_title = 'Dashboard';
$active_nav = 'dashboard';
include 'header.php';
require_once __DIR__ . '/charts.php';

// ── KPI stats ──────────────────────────────────────────────
$total_admissions = 0; $pending_count = 0;
$adm_this_month = 0; $adm_last_month = 0;
$total_contacts = 0; $con_this_month = 0; $con_last_month = 0;
$total_courses = 0; $total_teachers = 0;
$recent_admissions = false; $recent_contacts = false; $recent_activity = false;
try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions"));
    $total_admissions = (int)($r[0] ?? 0);
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions WHERE status='pending'"));
    $pending_count = (int)($r[0] ?? 0);
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())"));
    $adm_this_month = (int)($r[0] ?? 0);
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')"));
    $adm_last_month = (int)($r[0] ?? 0);
    $recent_admissions = mysqli_query($conn, "SELECT * FROM admissions ORDER BY created_at DESC LIMIT 6");
} catch (Exception $e) {}
try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM contacts"));
    $total_contacts = (int)($r[0] ?? 0);
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM contacts WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())"));
    $con_this_month = (int)($r[0] ?? 0);
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM contacts WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')"));
    $con_last_month = (int)($r[0] ?? 0);
    $recent_contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) {}
try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM courses WHERE status='active'"));
    $total_courses = (int)($r[0] ?? 0);
} catch (Exception $e) {}
try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teachers WHERE status='active'"));
    $total_teachers = (int)($r[0] ?? 0);
} catch (Exception $e) {}
try {
    $recent_activity = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8");
} catch (Exception $e) {}

// ── Chart data ─────────────────────────────────────────────
$adm_daily = ms_daily_series($conn, 'admissions', 30);
$con_daily = ms_daily_series($conn, 'contacts', 30);
$trend_labels = array_map(fn($d) => date('M j', strtotime($d)), array_keys($adm_daily));
$trend_series = [
    ['label' => 'Admissions', 'class' => 'series-a', 'data' => array_values($adm_daily)],
    ['label' => 'Contact Queries', 'class' => 'series-b', 'data' => array_values($con_daily)],
];

$adm_monthly = ms_months_series($conn, 'admissions');

$statuses = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
try {
    $res = mysqli_query($conn, "SELECT status, COUNT(*) c FROM admissions GROUP BY status");
    while ($res && ($s = mysqli_fetch_assoc($res))) $statuses[$s['status']] = (int)$s['c'];
} catch (Exception $e) {}

$programs = [];
try {
    $res = mysqli_query($conn, "SELECT program, COUNT(*) c FROM admissions WHERE program != '' GROUP BY program ORDER BY c DESC LIMIT 7");
    while ($res && ($p = mysqli_fetch_assoc($res))) $programs[$p['program']] = (int)$p['c'];
} catch (Exception $e) {}

$approval_rate = $total_admissions > 0 ? round($statuses['approved'] / $total_admissions * 100, 1) : 0;
?>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-gauge me-2 text-primary"></i>Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($current_admin['name']) ?> — <?= date('l, d F Y') ?></p>
  </div>
  <a href="analytics.php" class="btn btn-primary btn-sm">
    <i class="fa-solid fa-chart-line me-1"></i>Full Analytics
  </a>
</div>

<!-- ── Stat Cards ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div>
        <p>Admissions This Month</p>
        <h3><?= $adm_this_month ?><?= delta_html($adm_this_month, $adm_last_month) ?></h3>
        <span class="stat-sub"><?= $total_admissions ?> all time</span>
      </div>
      <div class="stat-icon si-blue"><i class="fa-solid fa-user-graduate"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div>
        <p>Pending Review</p>
        <h3><?= $pending_count ?></h3>
        <span class="stat-sub">awaiting a decision</span>
      </div>
      <div class="stat-icon si-orange"><i class="fa-solid fa-clock"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div>
        <p>Contacts This Month</p>
        <h3><?= $con_this_month ?><?= delta_html($con_this_month, $con_last_month) ?></h3>
        <span class="stat-sub"><?= $total_contacts ?> total received</span>
      </div>
      <div class="stat-icon si-red"><i class="fa-solid fa-envelope"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div>
        <p>Active Courses</p>
        <h3><?= $total_courses ?></h3>
        <span class="stat-sub">open for admission</span>
      </div>
      <div class="stat-icon si-green"><i class="fa-solid fa-book"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div>
        <p>Active Teachers</p>
        <h3><?= $total_teachers ?></h3>
        <span class="stat-sub">on faculty</span>
      </div>
      <div class="stat-icon si-purple"><i class="fa-solid fa-chalkboard-user"></i></div>
    </div>
  </div>
</div>

<!-- ── 30-day trend ────────────────────────────────────────── -->
<div class="cardx mb-3">
  <div class="section-hd">
    <h2><i class="fa-solid fa-arrow-trend-up me-2 text-primary"></i>Admissions &amp; Contact Queries — Last 30 Days</h2>
    <a href="analytics.php" class="btn btn-sm btn-outline-primary">Full Analytics</a>
  </div>
  <?= svg_line_chart($trend_series, $trend_labels, 'Daily admissions and contact queries over the last 30 days') ?>
</div>

<!-- ── Monthly columns + pipeline donut ────────────────────── -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="cardx">
      <div class="section-hd"><h2><i class="fa-solid fa-chart-column me-2 text-primary"></i>Admissions — Last 6 Months</h2></div>
      <?= svg_columns($adm_monthly, 'Admission applications received per month over the last 6 months') ?>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="cardx">
      <div class="section-hd"><h2><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Application Pipeline</h2></div>
      <?= svg_donut([
          ['label' => 'Pending',  'value' => $statuses['pending'],  'class' => 'slice-1'],
          ['label' => 'Approved', 'value' => $statuses['approved'], 'class' => 'slice-2'],
          ['label' => 'Rejected', 'value' => $statuses['rejected'], 'class' => 'slice-4'],
      ], 'Admission applications by status', 'Total') ?>
      <?php if ($total_admissions > 0): ?>
        <p class="text-muted small mt-3 mb-0">Approval rate: <strong class="text-navy"><?= $approval_rate ?>%</strong> of all applications.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Recent admissions + right rail ──────────────────────── -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="cardx">
      <div class="section-hd">
        <h2><i class="fa-solid fa-user-plus me-2 text-primary"></i>Recent Admissions</h2>
        <a href="admissions.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <?php if ($recent_admissions && mysqli_num_rows($recent_admissions) > 0): ?>
        <div class="table-wrap">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Student</th>
                <th>Program</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($recent_admissions)): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                  <td class="text-muted small"><?= htmlspecialchars($row['program']) ?></td>
                  <td class="small"><?= htmlspecialchars($row['phone']) ?></td>
                  <td>
                    <span class="status-badge sb-<?= $row['status'] ?>">
                      <?= ucfirst($row['status']) ?>
                    </span>
                  </td>
                  <td class="text-muted small"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-solid fa-inbox"></i>
          <p>No admission applications yet.<br>
             <a href="../admissions.php" target="_blank">Share the admission form</a> to get started.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4 d-flex flex-column gap-3">
    <!-- Quick Actions -->
    <div class="cardx" style="height:auto">
      <div class="section-hd"><h2><i class="fa-solid fa-bolt me-2 text-warning"></i>Quick Actions</h2></div>
      <div class="d-grid gap-2">
        <a href="admissions.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-user-plus me-2"></i>Review Admissions
          <?php if ($pending_count > 0): ?><span class="badge bg-danger ms-1"><?= $pending_count ?></span><?php endif; ?>
        </a>
        <a href="courses.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-book me-2"></i>Manage Courses
        </a>
        <a href="teachers.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-chalkboard-user me-2"></i>Manage Teachers
        </a>
        <a href="contacts.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-envelope me-2"></i>View Contact Queries
        </a>
        <a href="settings.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-gear me-2"></i>Site Settings
        </a>
        <a href="backup.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-database me-2"></i>Download Backup
        </a>
      </div>
    </div>

    <!-- Top Programs -->
    <div class="cardx" style="height:auto">
      <div class="section-hd"><h2><i class="fa-solid fa-ranking-star me-2 text-primary"></i>Top Programs</h2></div>
      <?php if (empty($programs)): ?>
        <div class="empty-state" style="padding:1.5rem">
          <i class="fa-solid fa-graduation-cap" style="font-size:1.75rem"></i>
          <p class="small">No applications by program yet.</p>
        </div>
      <?php else: ?>
        <?= svg_hbars($programs) ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Recent contacts + activity feed ─────────────────────── -->
<div class="row g-3">
  <div class="col-xl-6">
    <div class="cardx">
      <div class="section-hd">
        <h2><i class="fa-solid fa-comments me-2 text-primary"></i>Recent Contacts</h2>
        <a href="contacts.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <?php if ($recent_contacts && mysqli_num_rows($recent_contacts) > 0): ?>
        <?php while ($c = mysqli_fetch_assoc($recent_contacts)): ?>
          <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
            <div class="stat-icon si-blue" style="width:36px;height:36px;font-size:.85rem;flex-shrink:0">
              <i class="fa-solid fa-user"></i>
            </div>
            <div style="min-width:0">
              <div class="fw-bold small text-truncate"><?= htmlspecialchars($c['name']) ?></div>
              <div class="text-muted" style="font-size:.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($c['subject'] ?: 'No subject') ?>
              </div>
            </div>
            <div class="ms-auto text-muted" style="font-size:.7rem;white-space:nowrap">
              <?= date('d M', strtotime($c['created_at'])) ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:1.5rem">
          <i class="fa-solid fa-envelope-open-text" style="font-size:1.75rem"></i>
          <p class="small">No contact queries yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="cardx">
      <div class="section-hd">
        <h2><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Recent Activity</h2>
        <a href="activity.php" class="btn btn-sm btn-outline-primary">Full Log</a>
      </div>
      <?php if ($recent_activity && mysqli_num_rows($recent_activity) > 0): ?>
        <div class="feed">
          <?php while ($log = mysqli_fetch_assoc($recent_activity)): ?>
            <div class="feed-item">
              <span class="f-ico"><i class="fa-solid fa-circle-dot text-primary" style="font-size:.6rem"></i></span>
              <span>
                <strong><?= htmlspecialchars($log['admin_email'] ?: 'System') ?></strong> — <?= htmlspecialchars($log['details'] ?: $log['action']) ?>
                <small><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></small>
              </span>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding:1.5rem">
          <i class="fa-solid fa-clock-rotate-left" style="font-size:1.75rem"></i>
          <p class="small">No activity recorded yet. Actions like logins, settings changes, and backups will appear here.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
