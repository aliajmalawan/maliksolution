<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Admissions Report';
$active     = 'admissions';
$db         = ums_db();
$campus     = (int)$user['campus_id'];

// ── By status ──
$byStatus = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'enrolled' => 0];
$s = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('admissions') . ' WHERE campus_id=? GROUP BY status');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byStatus[$x['status']] = (int)$x['c'];
$s->close();
$total = array_sum($byStatus);

// ── By program (top 8) ──
$byProgram = [];
$s = $db->prepare('SELECT program, COUNT(*) c FROM ' . tbl('admissions') . ' WHERE campus_id=? AND program<>"" GROUP BY program ORDER BY c DESC LIMIT 8');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byProgram[$x['program']] = (int)$x['c'];
$s->close();

// ── By session ──
$bySession = [];
$s = $db->prepare('SELECT session, COUNT(*) c FROM ' . tbl('admissions') . ' WHERE campus_id=? AND session<>"" GROUP BY session ORDER BY session');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $bySession[$x['session']] = (int)$x['c'];
$s->close();

// ── Last 14 days trend ──
$trend = [];
for ($i = 13; $i >= 0; $i--) $trend[date('Y-m-d', strtotime("-$i days"))] = 0;
$s = $db->prepare('SELECT DATE(applied_at) d, COUNT(*) c FROM ' . tbl('admissions') . ' WHERE campus_id=? AND applied_at >= (CURDATE() - INTERVAL 13 DAY) GROUP BY DATE(applied_at)');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) if (isset($trend[$x['d']])) $trend[$x['d']] = (int)$x['c'];
$s->close();

$approvalRate = $total > 0 ? round(($byStatus['approved'] + $byStatus['enrolled']) / $total * 100, 1) : 0;

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div>
    <h1>Admissions Report</h1>
    <p>Analytics across all applications · <?= e(date('d M Y')) ?></p>
  </div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= adm_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<!-- Summary chips -->
<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-inbox"></i></span><div><small>Total Applications</small><strong><?= $total ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-clock"></i></span><div><small>Pending</small><strong><?= $byStatus['pending'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Approved</small><strong><?= $byStatus['approved'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-user-graduate"></i></span><div><small>Enrolled</small><strong><?= $byStatus['enrolled'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-percent"></i></span><div><small>Approval Rate</small><strong><?= $approvalRate ?>%</strong></div></div>
</div>

<div class="u-grid g-two" style="margin-bottom:1.1rem">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-chart-line" style="color:var(--primary)"></i> Applications — Last 14 Days</h2></div>
    <div class="u-chart"><canvas id="chTrend"></canvas></div>
  </div>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-chart-pie" style="color:var(--primary)"></i> Status Breakdown</h2></div>
    <div class="u-chart"><canvas id="chStatus"></canvas></div>
  </div>
</div>

<div class="u-grid g-two">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-graduation-cap" style="color:var(--primary)"></i> By Program</h2></div>
    <?php if (!$byProgram): ?>
      <div class="u-empty"><i class="fa-solid fa-chart-simple"></i><p>No data yet.</p></div>
    <?php else: $mx = max($byProgram); ?>
      <div class="u-prog">
        <?php foreach ($byProgram as $prog => $c): ?>
          <div>
            <div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($prog) ?></span><span class="val"><?= $c ?></span></div>
            <div class="u-prog-track"><div class="u-prog-fill" style="width:<?= (int)round($c / $mx * 100) ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-calendar" style="color:var(--primary)"></i> By Session</h2></div>
    <?php if (!$bySession): ?>
      <div class="u-empty"><i class="fa-solid fa-calendar"></i><p>No data yet.</p></div>
    <?php else: ?>
      <table class="u-table">
        <thead><tr><th>Session</th><th style="text-align:right">Applications</th><th style="text-align:right">Share</th></tr></thead>
        <tbody>
          <?php foreach ($bySession as $sess => $c): ?>
            <tr><td><strong><?= e($sess) ?></strong></td><td style="text-align:right"><?= $c ?></td><td style="text-align:right;color:var(--muted)"><?= $total > 0 ? round($c / $total * 100, 1) : 0 ?>%</td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<style>@media print { .u-side, .u-top, .u-page-head .u-btn, .u-side-backdrop { display: none !important; } .u-main { margin: 0 !important; } body { background: #fff; } }</style>

<?php
$trendLabels = json_encode(array_map(fn($d) => date('M j', strtotime($d)), array_keys($trend)));
$trendData   = json_encode(array_values($trend));
$statusData  = json_encode([$byStatus['pending'], $byStatus['approved'], $byStatus['rejected'], $byStatus['enrolled']]);

$page_scripts = <<<JS
<script>
(function () {
  var charts = [];
  function palette() {
    var s = getComputedStyle(document.documentElement);
    return { ink: s.getPropertyValue('--muted').trim(), line: s.getPropertyValue('--line').trim(),
      primary:'#6366f1', green:'#10b981', amber:'#f59e0b', red:'#ef4444', cyan:'#06b6d4' };
  }
  function build() {
    charts.forEach(function (c){ c.destroy(); }); charts = [];
    var p = palette();
    Chart.defaults.color = p.ink; Chart.defaults.borderColor = p.line; Chart.defaults.font.family = 'Inter, sans-serif';

    var g = document.getElementById('chTrend').getContext('2d');
    var grad = g.createLinearGradient(0,0,0,240);
    grad.addColorStop(0,'rgba(99,102,241,.35)'); grad.addColorStop(1,'rgba(99,102,241,0)');
    charts.push(new Chart(g, { type:'line',
      data:{ labels: $trendLabels, datasets:[{ label:'Applications', data: $trendData, borderColor:p.primary, backgroundColor:grad, fill:true, tension:.4, pointRadius:2, borderWidth:2.5 }] },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ grid:{color:p.line}, ticks:{precision:0} }, x:{ grid:{display:false} } } } }));

    charts.push(new Chart(document.getElementById('chStatus'), { type:'doughnut',
      data:{ labels:['Pending','Approved','Rejected','Enrolled'], datasets:[{ data: $statusData, backgroundColor:[p.amber,p.green,p.red,p.primary], borderWidth:0, hoverOffset:6 }] },
      options:{ responsive:true, maintainAspectRatio:false, cutout:'66%', plugins:{ legend:{ position:'bottom', labels:{ boxWidth:12, boxHeight:12, borderRadius:3, useBorderRadius:true } } } } }));
  }
  build();
  document.addEventListener('ums:theme', build);
})();
</script>
JS;

require __DIR__ . '/../../includes/footer.php';
