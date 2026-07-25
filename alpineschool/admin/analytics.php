<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Analytics';

$totalInquiries = (int)$pdo->query('SELECT COUNT(*) c FROM inquiries')->fetch()['c'];
$totalMessages = (int)$pdo->query('SELECT COUNT(*) c FROM contact_messages')->fetch()['c'];
$unreadMessages = (int)$pdo->query('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0')->fetch()['c'];

$statusCounts = ['new' => 0, 'contacted' => 0, 'enrolled' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM inquiries GROUP BY status')->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int)$row['c'];
}
$enrollmentRate = $totalInquiries > 0 ? round($statusCounts['enrolled'] / $totalInquiries * 100, 1) : 0.0;

$months = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-{$i} months");
    $months[date('Y-m', $ts)] = ['label' => date('M Y', $ts), 'count' => 0];
}
$monthlyRows = $pdo->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') ym, COUNT(*) c FROM inquiries
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym"
)->fetchAll();
foreach ($monthlyRows as $row) {
    if (isset($months[$row['ym']])) {
        $months[$row['ym']]['count'] = (int)$row['c'];
    }
}

$contentCounts = [
    'News Articles' => (int)$pdo->query('SELECT COUNT(*) c FROM news')->fetch()['c'],
    'Events' => (int)$pdo->query('SELECT COUNT(*) c FROM events')->fetch()['c'],
    'Gallery Photos' => (int)$pdo->query('SELECT COUNT(*) c FROM gallery_images')->fetch()['c'],
    'Faculty Members' => (int)$pdo->query('SELECT COUNT(*) c FROM faculty')->fetch()['c'],
];

$readMessages = $totalMessages - $unreadMessages;

$maxMonthly = max(1, ...array_column($months, 'count'));
$maxStatus = max(1, ...array_values($statusCounts));
$maxContent = max(1, ...array_values($contentCounts));
$maxMessages = max(1, $totalMessages);

// ---- Website traffic ----
$totalViews = (int)$pdo->query('SELECT COUNT(*) c FROM page_views')->fetch()['c'];
$viewsToday = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE DATE(created_at) = CURDATE()")->fetch()['c'];
$viewsYesterday = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY")->fetch()['c'];
$views7 = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetch()['c'];
$views30 = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetch()['c'];
$viewsMonth = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())")->fetch()['c'];
$viewsYear = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE YEAR(created_at) = YEAR(NOW())")->fetch()['c'];
$onlineNow = (int)$pdo->query("SELECT COUNT(DISTINCT ip) c FROM page_views WHERE created_at >= NOW() - INTERVAL 5 MINUTE")->fetch()['c'];
$uniqueVisitors30 = (int)$pdo->query("SELECT COUNT(DISTINCT ip) c FROM page_views WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetch()['c'];

$dailyCounts = [];
for ($i = 29; $i >= 0; $i--) {
    $dailyCounts[date('Y-m-d', strtotime("-{$i} days"))] = 0;
}
$dailyRows = $pdo->query(
    "SELECT DATE(created_at) day, COUNT(*) c FROM page_views
     WHERE created_at >= NOW() - INTERVAL 30 DAY GROUP BY DATE(created_at)"
)->fetchAll();
foreach ($dailyRows as $row) {
    if (isset($dailyCounts[$row['day']])) {
        $dailyCounts[$row['day']] = (int)$row['c'];
    }
}

$topPages = $pdo->query(
    'SELECT url, COUNT(*) views FROM page_views GROUP BY url ORDER BY views DESC LIMIT 10'
)->fetchAll();
$maxPageViews = max(1, ...array_column($topPages, 'views') ?: [0]);

$referrerStmt = $pdo->prepare(
    "SELECT referrer, COUNT(*) views FROM page_views
     WHERE referrer IS NOT NULL AND referrer != '' AND referrer NOT LIKE CONCAT('%', ?, '%')
     GROUP BY referrer ORDER BY views DESC LIMIT 10"
);
$referrerStmt->execute([$_SERVER['HTTP_HOST'] ?? 'localhost']);
$topReferrers = $referrerStmt->fetchAll();

$agentRows = $pdo->query(
    "SELECT user_agent, COUNT(*) c FROM page_views WHERE created_at >= NOW() - INTERVAL 30 DAY GROUP BY user_agent"
)->fetchAll();
$browserCounts = [];
$systemCounts = [];
$deviceCounts = [];
foreach ($agentRows as $row) {
    $count = (int)$row['c'];
    $parsed = classify_user_agent((string)$row['user_agent']);
    $browserCounts[$parsed['browser']] = ($browserCounts[$parsed['browser']] ?? 0) + $count;
    $systemCounts[$parsed['system']] = ($systemCounts[$parsed['system']] ?? 0) + $count;
    $deviceCounts[$parsed['device']] = ($deviceCounts[$parsed['device']] ?? 0) + $count;
}
arsort($browserCounts);
arsort($systemCounts);
arsort($deviceCounts);

$recentVisits = $pdo->query(
    'SELECT url, ip, user_agent, referrer, created_at FROM page_views ORDER BY id DESC LIMIT 20'
)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="dash-stats">
  <div class="dash-stat"><strong><?= $totalInquiries ?></strong><span>Total Admission Inquiries</span></div>
  <div class="dash-stat"><strong><?= $enrollmentRate ?>%</strong><span>Enrollment Rate</span></div>
  <div class="dash-stat"><strong><?= $totalMessages ?></strong><span>Total Contact Messages</span></div>
  <div class="dash-stat"><strong><?= $unreadMessages ?></strong><span>Unread Messages</span></div>
</div>

<div class="card">
  <div class="card-header"><h2>Admission Inquiries — Last 6 Months</h2></div>
  <div class="bar-list">
    <?php foreach ($months as $m): ?>
      <div class="bar-row">
        <span class="bar-label"><?= e($m['label']) ?></span>
        <div class="bar-track"><div class="bar-fill bar-fill-primary" style="width:<?= (int)round($m['count'] / $maxMonthly * 100) ?>%"></div></div>
        <span class="bar-value"><?= $m['count'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="analytics-grid">
  <div class="card">
    <div class="card-header"><h2>Admission Inquiries by Status</h2></div>
    <div class="bar-list">
      <?php foreach ([['new', 'New'], ['contacted', 'Contacted'], ['enrolled', 'Enrolled']] as [$key, $label]): ?>
        <div class="bar-row">
          <span class="badge badge-<?= $key ?>"><?= e($label) ?></span>
          <div class="bar-track"><div class="bar-fill bar-fill-<?= $key ?>" style="width:<?= (int)round($statusCounts[$key] / $maxStatus * 100) ?>%"></div></div>
          <span class="bar-value"><?= $statusCounts[$key] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Contact Messages</h2></div>
    <div class="bar-list">
      <div class="bar-row">
        <span class="badge badge-enrolled">Read</span>
        <div class="bar-track"><div class="bar-fill bar-fill-enrolled" style="width:<?= (int)round($readMessages / $maxMessages * 100) ?>%"></div></div>
        <span class="bar-value"><?= $readMessages ?></span>
      </div>
      <div class="bar-row">
        <span class="badge badge-new">Unread</span>
        <div class="bar-track"><div class="bar-fill bar-fill-new" style="width:<?= (int)round($unreadMessages / $maxMessages * 100) ?>%"></div></div>
        <span class="bar-value"><?= $unreadMessages ?></span>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2>Content Library Overview</h2></div>
  <div class="bar-list">
    <?php foreach ($contentCounts as $label => $count): ?>
      <div class="bar-row">
        <span class="bar-label"><?= e($label) ?></span>
        <div class="bar-track"><div class="bar-fill bar-fill-primary" style="width:<?= (int)round($count / $maxContent * 100) ?>%"></div></div>
        <span class="bar-value"><?= $count ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<h2 class="section-heading">Website Traffic <span class="badge badge-enrolled">🟢 <?= $onlineNow ?> online now</span></h2>

<div class="dash-stats">
  <div class="dash-stat"><strong><?= $viewsToday ?></strong><span>Views Today</span></div>
  <div class="dash-stat"><strong><?= $viewsYesterday ?></strong><span>Views Yesterday</span></div>
  <div class="dash-stat"><strong><?= $views7 ?></strong><span>Last 7 Days</span></div>
  <div class="dash-stat"><strong><?= $viewsMonth ?></strong><span>This Month</span></div>
  <div class="dash-stat"><strong><?= $viewsYear ?></strong><span>This Year</span></div>
  <div class="dash-stat"><strong><?= $totalViews ?></strong><span>All-Time Views</span></div>
  <div class="dash-stat"><strong><?= $uniqueVisitors30 ?></strong><span>Unique Visitors (30d)</span></div>
  <div class="dash-stat"><strong><?= $views30 > 0 && $uniqueVisitors30 > 0 ? round($views30 / $uniqueVisitors30, 1) : 0 ?></strong><span>Pages / Visitor (30d)</span></div>
</div>

<div class="card">
  <div class="card-header"><h2>Page Views — Last 30 Days</h2></div>
  <canvas id="trafficChart" height="80"></canvas>
</div>

<div class="analytics-grid">
  <div class="card">
    <div class="card-header"><h2>Top Pages</h2></div>
    <div class="bar-list">
      <?php if (empty($topPages)): ?>
        <div class="empty-state">No page views recorded yet.</div>
      <?php else: ?>
        <?php foreach ($topPages as $p): ?>
          <div class="bar-row">
            <span class="bar-label" title="<?= e($p['url']) ?>"><?= e($p['url'] ?: '/') ?></span>
            <div class="bar-track"><div class="bar-fill bar-fill-primary" style="width:<?= (int)round($p['views'] / $maxPageViews * 100) ?>%"></div></div>
            <span class="bar-value"><?= $p['views'] ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Top External Referrers</h2></div>
    <div class="table-wrap">
      <?php if (empty($topReferrers)): ?>
        <div class="empty-state">No external referrers yet — traffic so far is direct.</div>
      <?php else: ?>
      <table>
        <thead><tr><th>Referrer</th><th>Views</th></tr></thead>
        <tbody>
        <?php foreach ($topReferrers as $r): ?>
          <tr>
            <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($r['referrer']) ?>"><?= e($r['referrer']) ?></td>
            <td><?= $r['views'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="analytics-grid-3">
  <?php
  $breakdowns = [
      'Browsers (30d)' => $browserCounts,
      'Operating Systems (30d)' => $systemCounts,
      'Devices (30d)' => $deviceCounts,
  ];
  ?>
  <?php foreach ($breakdowns as $title => $data): ?>
    <div class="card">
      <div class="card-header"><h2><?= e($title) ?></h2></div>
      <div class="bar-list">
        <?php if (empty($data)): ?>
          <div class="empty-state">No data yet.</div>
        <?php else: ?>
          <?php $total = array_sum($data); $max = max($data); ?>
          <?php foreach ($data as $name => $count): ?>
            <div class="bar-row">
              <span class="bar-label"><?= e($name) ?></span>
              <div class="bar-track"><div class="bar-fill bar-fill-primary" style="width:<?= (int)round($count / $max * 100) ?>%"></div></div>
              <span class="bar-value"><?= round($count / $total * 100) ?>%</span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header"><h2>Recent Visits (last 20)</h2></div>
  <div class="table-wrap">
    <?php if (empty($recentVisits)): ?>
      <div class="empty-state">No visits recorded yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Time</th><th>Page</th><th>IP</th><th>Browser / System</th><th>Came From</th></tr></thead>
      <tbody>
      <?php foreach ($recentVisits as $v): ?>
        <?php $parsed = classify_user_agent((string)$v['user_agent']); ?>
        <tr>
          <td><?= e(date('d M, H:i', strtotime($v['created_at']))) ?></td>
          <td><?= e($v['url'] ?: '/') ?></td>
          <td><?= e($v['ip']) ?></td>
          <td><?= e($parsed['browser']) ?> · <?= e($parsed['system']) ?> · <?= e($parsed['device']) ?></td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= e($v['referrer'] ?? '') ?>"><?= $v['referrer'] ? e($v['referrer']) : 'Direct' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
  var labels = <?= json_encode(array_map(fn($d) => date('M j', strtotime($d)), array_keys($dailyCounts))) ?>;
  var data = <?= json_encode(array_values($dailyCounts)) ?>;
  var ctx = document.getElementById('trafficChart');
  if (!ctx || typeof Chart === 'undefined') return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Page Views',
        data: data,
        borderColor: '#2E1B6B',
        backgroundColor: 'rgba(46, 27, 107, 0.08)',
        fill: true,
        tension: 0.3,
        pointRadius: 2,
      }],
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
