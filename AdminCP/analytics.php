<?php
declare(strict_types=1);
$page_title = 'Website Analytics';
$active_nav = 'analytics';
include 'header.php';

// ── Summary counts ─────────────────────────────────────────
$r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions")); $total_adm = (int)($r[0] ?? 0);
$r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM contacts"));   $total_con = (int)($r[0] ?? 0);
$r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM courses"));    $total_cou = (int)($r[0] ?? 0);
$r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions WHERE DATE(created_at)=CURDATE()")); $today_adm = (int)($r[0] ?? 0);
$r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions WHERE status='approved'")); $approved = (int)($r[0] ?? 0);

// ── Status distribution ────────────────────────────────────
$status_res = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM admissions GROUP BY status");
$statuses = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
while ($s = mysqli_fetch_assoc($status_res)) $statuses[$s['status']] = (int)$s['cnt'];

// ── Applications by program ────────────────────────────────
$prog_res = mysqli_query($conn, "SELECT program, COUNT(*) AS cnt FROM admissions WHERE program != '' GROUP BY program ORDER BY cnt DESC LIMIT 6");
$prog_labels = $prog_data = [];
while ($p = mysqli_fetch_assoc($prog_res)) { $prog_labels[] = $p['program']; $prog_data[] = (int)$p['cnt']; }

// ── Admissions last 14 days ────────────────────────────────
$adm_res = mysqli_query($conn, "
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM admissions
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(created_at) ORDER BY day ASC
");
$day_labels = $day_data = [];
// Build full 14-day range
$range = [];
for ($d = 13; $d >= 0; $d--) $range[date('Y-m-d', strtotime("-$d days"))] = 0;
while ($row = mysqli_fetch_assoc($adm_res)) $range[$row['day']] = (int)$row['cnt'];
foreach ($range as $k => $v) { $day_labels[] = date('d M', strtotime($k)); $day_data[] = $v; }

// ── Contact queries last 14 days ───────────────────────────
$con_res = mysqli_query($conn, "
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM contacts
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(created_at) ORDER BY day ASC
");
$con_range = array_fill_keys(array_keys($range), 0);
while ($row = mysqli_fetch_assoc($con_res)) if (isset($con_range[$row['day']])) $con_range[$row['day']] = (int)$row['cnt'];
$con_data = array_values($con_range);
?>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-chart-line me-2 text-primary"></i>Website Analytics</h1>
    <p>Overview of admissions, courses, and contact activity</p>
  </div>
  <span class="badge bg-primary-subtle text-primary px-3 py-2 fs-6 fw-semibold">
    <?= date('F Y') ?>
  </span>
</div>

<!-- ── Summary Stats ───────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="cardx stat-card">
      <div><p>Total Applications</p><h3><?= $total_adm ?></h3></div>
      <div class="stat-icon si-blue"><i class="fa-solid fa-users"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="cardx stat-card">
      <div><p>Approved</p><h3><?= $approved ?></h3></div>
      <div class="stat-icon si-green"><i class="fa-solid fa-circle-check"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="cardx stat-card">
      <div><p>Contact Queries</p><h3><?= $total_con ?></h3></div>
      <div class="stat-icon si-red"><i class="fa-solid fa-envelope"></i></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="cardx stat-card">
      <div><p>Today's Applications</p><h3><?= $today_adm ?></h3></div>
      <div class="stat-icon si-orange"><i class="fa-solid fa-calendar-day"></i></div>
    </div>
  </div>
</div>

<!-- ── Charts Row 1 ────────────────────────────────────────── -->
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="cardx">
      <div class="section-hd">
        <h2>Admissions & Contacts — Last 14 Days</h2>
      </div>
      <canvas id="lineChart" height="100"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="cardx">
      <div class="section-hd"><h2>Application Status</h2></div>
      <?php if ($total_adm > 0): ?>
        <canvas id="donutChart" height="180"></canvas>
        <div class="d-flex justify-content-around mt-3">
          <div class="text-center">
            <span class="status-badge sb-pending"><?= $statuses['pending'] ?></span>
            <div class="small text-muted mt-1">Pending</div>
          </div>
          <div class="text-center">
            <span class="status-badge sb-approved"><?= $statuses['approved'] ?></span>
            <div class="small text-muted mt-1">Approved</div>
          </div>
          <div class="text-center">
            <span class="status-badge sb-rejected"><?= $statuses['rejected'] ?></span>
            <div class="small text-muted mt-1">Rejected</div>
          </div>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding:2rem"><i class="fa-solid fa-chart-pie"></i><p class="small">No data yet</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Charts Row 2 ────────────────────────────────────────── -->
<div class="row g-3">
  <div class="col-md-6">
    <div class="cardx">
      <div class="section-hd"><h2>Applications by Program</h2></div>
      <?php if (!empty($prog_labels)): ?>
        <canvas id="programChart" height="180"></canvas>
      <?php else: ?>
        <div class="empty-state" style="padding:2rem"><i class="fa-solid fa-book"></i><p class="small">No program data yet</p></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="cardx">
      <div class="section-hd"><h2>Platform Overview</h2></div>
      <div class="row g-3">
        <div class="col-6">
          <div class="text-center p-3 rounded" style="background:var(--soft)">
            <div class="h2 fw-black text-primary mb-0"><?= $total_adm ?></div>
            <div class="small text-muted">Admissions</div>
          </div>
        </div>
        <div class="col-6">
          <div class="text-center p-3 rounded" style="background:var(--soft)">
            <div class="h2 fw-black text-success mb-0"><?= $total_cou ?></div>
            <div class="small text-muted">Courses</div>
          </div>
        </div>
        <div class="col-6">
          <div class="text-center p-3 rounded" style="background:var(--soft)">
            <div class="h2 fw-black text-danger mb-0"><?= $total_con ?></div>
            <div class="small text-muted">Contact Queries</div>
          </div>
        </div>
        <div class="col-6">
          <div class="text-center p-3 rounded" style="background:var(--soft)">
            <?php
            $rate = $total_adm > 0 ? round(($approved / $total_adm) * 100) : 0;
            ?>
            <div class="h2 fw-black text-warning mb-0"><?= $rate ?>%</div>
            <div class="small text-muted">Approval Rate</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', Arial, sans-serif";
Chart.defaults.color = '#6c757d';

/* ── Line chart ── */
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($day_labels) ?>,
    datasets: [
      {
        label: 'Admissions',
        data: <?= json_encode($day_data) ?>,
        borderColor: '#0d6efd',
        backgroundColor: 'rgba(13,110,253,.1)',
        tension: .35, fill: true, pointRadius: 4, pointHoverRadius: 6
      },
      {
        label: 'Contacts',
        data: <?= json_encode($con_data) ?>,
        borderColor: '#dc3545',
        backgroundColor: 'rgba(220,53,69,.08)',
        tension: .35, fill: true, pointRadius: 4, pointHoverRadius: 6
      }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: 'rgba(0,0,0,.04)' } },
      x: { grid: { display: false } }
    }
  }
});

/* ── Donut chart ── */
<?php if ($total_adm > 0): ?>
new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: {
    labels: ['Pending', 'Approved', 'Rejected'],
    datasets: [{
      data: [<?= $statuses['pending'] ?>, <?= $statuses['approved'] ?>, <?= $statuses['rejected'] ?>],
      backgroundColor: ['#ffc107', '#198754', '#dc3545'],
      borderWidth: 0, hoverOffset: 6
    }]
  },
  options: {
    cutout: '68%',
    plugins: { legend: { display: false } }
  }
});
<?php endif; ?>

/* ── Program bar chart ── */
<?php if (!empty($prog_labels)): ?>
new Chart(document.getElementById('programChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($prog_labels) ?>,
    datasets: [{
      label: 'Applications',
      data: <?= json_encode($prog_data) ?>,
      backgroundColor: ['rgba(13,110,253,.8)','rgba(25,135,84,.8)','rgba(253,126,20,.8)','rgba(220,53,69,.8)','rgba(111,66,193,.8)','rgba(13,202,240,.8)'],
      borderRadius: 6
    }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: 'rgba(0,0,0,.04)' } },
      y: { grid: { display: false } }
    }
  }
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
