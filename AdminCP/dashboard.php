<?php
declare(strict_types=1);
$page_title = 'Dashboard';
$active_nav = 'dashboard';
include 'header.php';
require_once __DIR__ . '/charts.php';

/*
 * Website CMS Dashboard — shows ONLY website content statistics
 * (pages, news, gallery, downloads, events, contacts).
 * Academic/ERP data (admissions processing, teachers, students,
 * attendance, fees, exams) lives entirely in /ums/admin/ and never
 * appears here.
 */

// ── KPI stats ──────────────────────────────────────────────
$active_pages = 0; $total_news = 0; $total_gallery = 0; $total_downloads = 0;
$total_contacts = 0; $con_this_month = 0; $con_last_month = 0;
$upcoming_events = 0;
$recent_contacts = false; $recent_activity = false; $recent_news = false; $recent_updates = [];

try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(DISTINCT page) FROM page_content"));
    $active_pages = (int)($r[0] ?? 0);
} catch (Exception $e) {}
try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM news WHERE status='active'"));
    $total_news = (int)($r[0] ?? 0);
    $recent_news = mysqli_query($conn, "SELECT * FROM news ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) {}
try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM gallery WHERE status='active'"));
    $total_gallery = (int)($r[0] ?? 0);
} catch (Exception $e) {}
try {
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM downloads WHERE status='active'"));
    $total_downloads = (int)($r[0] ?? 0);
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
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM events WHERE status='active' AND event_date >= CURDATE()"));
    $upcoming_events = (int)($r[0] ?? 0);
} catch (Exception $e) {}
try {
    $recent_activity = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8");
} catch (Exception $e) {}

// ── Recent Website Updates — latest edit across every content type ──
try {
    $res = mysqli_query($conn, "
        (SELECT 'Gallery' kind, 'fa-images' ic, title, created_at FROM gallery)
        UNION ALL (SELECT 'News', 'fa-newspaper', title, created_at FROM news)
        UNION ALL (SELECT 'Event', 'fa-calendar-days', title, created_at FROM events)
        UNION ALL (SELECT 'Download', 'fa-file-arrow-down', title, created_at FROM downloads)
        ORDER BY created_at DESC LIMIT 8
    ");
    if ($res) while ($row = mysqli_fetch_assoc($res)) $recent_updates[] = $row;
} catch (Exception $e) {}

// ── Chart data — real, tracked website data only ────────────
$con_daily = ms_daily_series($conn, 'contacts', 30);
$dl_daily  = ms_daily_series($conn, 'downloads', 30);
$trend_labels = array_map(fn($d) => date('M j', strtotime($d)), array_keys($con_daily));
$con_series = [['label' => 'Contact Enquiries', 'class' => 'series-a', 'data' => array_values($con_daily)]];
$dl_data = [];
foreach ($dl_daily as $d => $c) $dl_data[date('M j', strtotime($d))] = $c;
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
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card">
      <div>
        <p>Active Pages</p>
        <h3><?= $active_pages ?></h3>
        <span class="stat-sub">editable website pages</span>
      </div>
      <div class="stat-icon si-blue"><i class="fa-solid fa-file-lines"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card">
      <div>
        <p>News &amp; Announcements</p>
        <h3><?= $total_news ?></h3>
        <span class="stat-sub">published posts</span>
      </div>
      <div class="stat-icon si-purple"><i class="fa-solid fa-newspaper"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card">
      <div>
        <p>Gallery Images</p>
        <h3><?= $total_gallery ?></h3>
        <span class="stat-sub">active photos</span>
      </div>
      <div class="stat-icon si-pink"><i class="fa-solid fa-images"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card">
      <div>
        <p>Downloads</p>
        <h3><?= $total_downloads ?></h3>
        <span class="stat-sub">files available</span>
      </div>
      <div class="stat-icon si-teal"><i class="fa-solid fa-file-arrow-down"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card">
      <div>
        <p>Contact Messages</p>
        <h3><?= $con_this_month ?><?= delta_html($con_this_month, $con_last_month) ?></h3>
        <span class="stat-sub"><?= $total_contacts ?> total received</span>
      </div>
      <div class="stat-icon si-red"><i class="fa-solid fa-envelope"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card">
      <div>
        <p>Upcoming Events</p>
        <h3><?= $upcoming_events ?></h3>
        <span class="stat-sub">scheduled ahead</span>
      </div>
      <div class="stat-icon si-orange"><i class="fa-solid fa-calendar-days"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card">
      <div>
        <p>Website Visitors</p>
        <h3>—</h3>
        <span class="stat-sub">analytics not connected</span>
      </div>
      <div class="stat-icon si-cyan"><i class="fa-solid fa-chart-line"></i></div>
    </div>
  </div>
</div>

<!-- ── Contact enquiries trend ──────────────────────────────── -->
<div class="cardx mb-3">
  <div class="section-hd">
    <h2><i class="fa-solid fa-arrow-trend-up me-2 text-primary"></i>Contact Enquiries — Last 30 Days</h2>
    <a href="analytics.php" class="btn btn-sm btn-outline-primary">Full Analytics</a>
  </div>
  <?= svg_line_chart($con_series, $trend_labels, 'Daily contact enquiries over the last 30 days') ?>
</div>

<!-- ── Downloads trend + Website analytics placeholder ─────── -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="cardx">
      <div class="section-hd"><h2><i class="fa-solid fa-file-arrow-down me-2 text-primary"></i>New Downloads Added — Last 30 Days</h2></div>
      <?php if (array_sum($dl_data) > 0): ?>
        <?= svg_columns($dl_data, 'New download files added per day over the last 30 days') ?>
      <?php else: ?>
        <div class="empty-state" style="padding:2rem">
          <i class="fa-solid fa-file-arrow-down" style="font-size:1.75rem"></i>
          <p class="small">No downloads added in the last 30 days.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="cardx">
      <div class="section-hd"><h2><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Website Analytics</h2></div>
      <div class="empty-state" style="padding:1.5rem">
        <i class="fa-solid fa-chart-simple" style="font-size:1.75rem"></i>
        <p class="small">Visitor and top-page tracking isn't connected yet.<br>
        Hook up an analytics provider to see visitor trends and your most-viewed pages here.</p>
      </div>
    </div>
  </div>
</div>

<!-- ── Recent website updates + Quick Actions ──────────────── -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="cardx">
      <div class="section-hd">
        <h2><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Recent Website Updates</h2>
      </div>
      <?php if ($recent_updates): ?>
        <div class="feed">
          <?php foreach ($recent_updates as $u): ?>
            <div class="feed-item">
              <span class="f-ico"><i class="fa-solid <?= htmlspecialchars($u['ic']) ?> text-primary" style="font-size:.75rem"></i></span>
              <span>
                <strong><?= htmlspecialchars($u['kind']) ?></strong> — <?= htmlspecialchars($u['title'] ?: 'Untitled') ?>
                <small><?= date('d M Y, h:i A', strtotime($u['created_at'])) ?></small>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <p>No website content added yet.<br>Start by adding a gallery photo, news post, or event.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <!-- Quick Actions -->
    <div class="cardx" style="height:auto">
      <div class="section-hd"><h2><i class="fa-solid fa-bolt me-2 text-warning"></i>Quick Actions</h2></div>
      <div class="d-grid gap-2">
        <a href="pages.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-file-lines me-2"></i>Edit Website Pages
        </a>
        <a href="manage_news.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-newspaper me-2"></i>Add News / Announcement
        </a>
        <a href="manage_gallery.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-images me-2"></i>Upload Gallery Image
        </a>
        <a href="manage_downloads.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-file-arrow-down me-2"></i>Upload Download
        </a>
        <a href="manage_events.php" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-calendar-days me-2"></i>Create Event
        </a>
        <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-sm">
          <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>View Website
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ── Recent contacts + recent news + activity feed ───────── -->
<div class="row g-3">
  <div class="col-xl-4">
    <div class="cardx">
      <div class="section-hd">
        <h2><i class="fa-solid fa-comments me-2 text-primary"></i>Recent Contact Messages</h2>
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

  <div class="col-xl-4">
    <div class="cardx">
      <div class="section-hd">
        <h2><i class="fa-solid fa-newspaper me-2 text-primary"></i>Recent News</h2>
        <a href="manage_news.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <?php if ($recent_news && mysqli_num_rows($recent_news) > 0): ?>
        <?php while ($n = mysqli_fetch_assoc($recent_news)): ?>
          <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
            <div class="stat-icon si-purple" style="width:36px;height:36px;font-size:.85rem;flex-shrink:0">
              <i class="fa-solid <?= $n['category'] === 'announcement' ? 'fa-bullhorn' : 'fa-newspaper' ?>"></i>
            </div>
            <div style="min-width:0">
              <div class="fw-bold small text-truncate"><?= htmlspecialchars($n['title']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= ucfirst($n['category']) ?></div>
            </div>
            <div class="ms-auto text-muted" style="font-size:.7rem;white-space:nowrap">
              <?= date('d M', strtotime($n['created_at'])) ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:1.5rem">
          <i class="fa-solid fa-newspaper" style="font-size:1.75rem"></i>
          <p class="small">No news or announcements yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
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
          <p class="small">No activity recorded yet.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
