<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Courses Report';
$active     = 'courses';
$db         = ums_db();
$campus     = (int)$user['campus_id'];

// Totals
$agg = $db->prepare('SELECT COUNT(*) n, COALESCE(SUM(credit_hours),0) cr FROM ' . tbl('courses') . ' WHERE campus_id=?');
$agg->bind_param('i', $campus); $agg->execute();
$a = $agg->get_result()->fetch_assoc(); $agg->close();

// By semester (1..8)
$bySem = array_fill(1, CRS_SEMESTERS, 0);
$s = $db->prepare('SELECT semester, COUNT(*) c FROM ' . tbl('courses') . ' WHERE campus_id=? GROUP BY semester');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) if (isset($bySem[(int)$x['semester']])) $bySem[(int)$x['semester']] = (int)$x['c'];
$s->close();

// By department
$byDept = [];
$s = $db->prepare('SELECT COALESCE(d.name,"Unassigned") name, COUNT(c.id) n, COALESCE(SUM(c.credit_hours),0) cr
    FROM ' . tbl('courses') . ' c LEFT JOIN ' . tbl('departments') . ' d ON d.id=c.department_id
    WHERE c.campus_id=? GROUP BY c.department_id ORDER BY n DESC');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byDept[] = $x;
$s->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Courses Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= crs_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-book-open"></i></span><div><small>Total Courses</small><strong><?= (int)$a['n'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-clock"></i></span><div><small>Total Credit Hours</small><strong><?= (int)$a['cr'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-layer-group"></i></span><div><small>Avg. Cr / Course</small><strong><?= (int)$a['n'] > 0 ? round((int)$a['cr'] / (int)$a['n'], 1) : 0 ?></strong></div></div>
</div>

<div class="u-grid g-two" style="margin-bottom:1.1rem">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-chart-column" style="color:var(--primary)"></i> Courses by Semester</h2></div>
    <div class="u-chart"><canvas id="chSem"></canvas></div>
  </div>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-sitemap" style="color:var(--primary)"></i> Courses by Department</h2></div>
    <?php if (!$byDept): ?>
      <div class="u-empty"><i class="fa-solid fa-sitemap"></i><p>No courses yet.</p></div>
    <?php else: $mx = max(1, max(array_map(fn($d) => (int)$d['n'], $byDept))); ?>
      <div class="u-prog">
        <?php foreach ($byDept as $d): ?>
          <div>
            <div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($d['name']) ?></span><span class="val"><?= (int)$d['n'] ?></span></div>
            <div class="u-prog-track"><div class="u-prog-fill" style="width:<?= (int)round((int)$d['n'] / $mx * 100) ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-table" style="color:var(--primary)"></i> Credit Hours by Department</h2></div>
  <?php if (!$byDept): ?>
    <div class="u-empty"><i class="fa-solid fa-table"></i><p>No data yet.</p></div>
  <?php else: ?>
    <table class="u-table">
      <thead><tr><th>Department</th><th style="text-align:right">Courses</th><th style="text-align:right">Credit Hours</th></tr></thead>
      <tbody>
        <?php foreach ($byDept as $d): ?>
          <tr><td><strong><?= e($d['name']) ?></strong></td><td style="text-align:right"><?= (int)$d['n'] ?></td><td style="text-align:right;color:var(--muted)"><?= (int)$d['cr'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<style>@media print { .u-side, .u-top, .u-page-head .u-btn, .u-side-backdrop { display:none !important; } .u-main { margin:0 !important; } body { background:#fff; } }</style>
<?php
$semData = json_encode(array_values($bySem));
$page_scripts = <<<JS
<script>
(function () {
  var chart;
  function palette(){ var s=getComputedStyle(document.documentElement); return { ink:s.getPropertyValue('--muted').trim(), line:s.getPropertyValue('--line').trim(), primary:'#6366f1' }; }
  function build(){
    if (chart) chart.destroy();
    var p = palette(); Chart.defaults.color=p.ink; Chart.defaults.borderColor=p.line; Chart.defaults.font.family='Inter, sans-serif';
    chart = new Chart(document.getElementById('chSem'), { type:'bar',
      data:{ labels:['Sem 1','Sem 2','Sem 3','Sem 4','Sem 5','Sem 6','Sem 7','Sem 8'],
        datasets:[{ label:'Courses', data: $semData, backgroundColor:p.primary, borderRadius:6, maxBarThickness:34 }] },
      options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
        scales:{ y:{ grid:{color:p.line}, ticks:{precision:0} }, x:{ grid:{display:false} } } } });
  }
  build(); document.addEventListener('ums:theme', build);
})();
</script>
JS;
require __DIR__ . '/../../includes/footer.php';
