<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Attendance Report'; $active = 'attendance';
$db = ums_db(); $campus = (int)$user['campus_id'];

// Overall
$ov = $db->query("SELECT COUNT(*) total, SUM(status IN ('present','late')) present FROM " . tbl('attendance') . " WHERE campus_id = $campus")->fetch_assoc();
$overallPct = (int)$ov['total'] > 0 ? round((int)$ov['present'] / (int)$ov['total'] * 100, 1) : 0;

// By section
$bySection = [];
$res = $db->query("SELECT section_id, COUNT(*) total, SUM(status IN ('present','late')) present
    FROM " . tbl('attendance') . " WHERE campus_id = $campus GROUP BY section_id ORDER BY total DESC");
$sections = att_section_options($campus);
while ($x = $res->fetch_assoc()) {
    $pct = (int)$x['total'] > 0 ? round((int)$x['present'] / (int)$x['total'] * 100, 1) : 0;
    $bySection[] = ['label' => $sections[(int)$x['section_id']] ?? ('Section #' . (int)$x['section_id']), 'pct' => $pct, 'total' => (int)$x['total']];
}

// Low attendance students (< 75% overall, at least 3 records)
$low = [];
$res = $db->query("SELECT a.student_id, s.name, s.registration_no, COUNT(*) total, SUM(a.status IN ('present','late')) present
    FROM " . tbl('attendance') . " a JOIN " . tbl('students') . " s ON s.id = a.student_id
    WHERE a.campus_id = $campus GROUP BY a.student_id HAVING total >= 3");
while ($x = $res->fetch_assoc()) {
    $pct = round((int)$x['present'] / (int)$x['total'] * 100, 1);
    if ($pct < 75) $low[] = ['name' => $x['name'], 'reg' => $x['registration_no'], 'pct' => $pct, 'total' => (int)$x['total']];
}
usort($low, fn($a, $b) => $a['pct'] <=> $b['pct']);
$low = array_slice($low, 0, 10);

// 14-day trend (% present)
$trend = [];
for ($i = 13; $i >= 0; $i--) $trend[date('Y-m-d', strtotime("-$i days"))] = null;
$res = $db->query("SELECT a_date, COUNT(*) total, SUM(status IN ('present','late')) present
    FROM " . tbl('attendance') . " WHERE campus_id = $campus AND a_date >= (CURDATE() - INTERVAL 13 DAY) GROUP BY a_date");
while ($x = $res->fetch_assoc()) {
    if (isset($trend[$x['a_date']])) $trend[$x['a_date']] = (int)$x['total'] > 0 ? round((int)$x['present'] / (int)$x['total'] * 100, 1) : 0;
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Attendance Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= att_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-user-check"></i></span><div><small>Records Marked</small><strong><?= (int)$ov['total'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-percent"></i></span><div><small>Overall Attendance</small><strong><?= $overallPct ?>%</strong></div></div>
  <div class="u-chip"><span class="ci" style="background:linear-gradient(135deg,#ef4444,#f87171)"><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Below 75%</small><strong><?= count($low) ?></strong></div></div>
</div>

<div class="u-grid g-two" style="margin-bottom:1.1rem">
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-chart-line" style="color:var(--primary)"></i> Attendance Trend — 14 Days</h2></div>
    <div class="u-chart"><canvas id="chTrend"></canvas></div></div>
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-chalkboard" style="color:var(--primary)"></i> By Section</h2></div>
    <?php if (!$bySection): ?><div class="u-empty"><i class="fa-solid fa-chalkboard"></i><p>No data yet.</p></div>
    <?php else: ?><div class="u-prog">
      <?php foreach ($bySection as $b): ?><div>
        <div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($b['label']) ?></span><span class="val"><?= $b['pct'] ?>%</span></div>
        <div class="u-prog-track"><div class="u-prog-fill <?= $b['pct'] >= 75 ? 'g-green' : ($b['pct'] >= 50 ? 'g-amber' : '') ?>" style="width:<?= $b['pct'] ?>%"></div></div></div><?php endforeach; ?>
    </div><?php endif; ?>
  </div>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-triangle-exclamation" style="color:var(--danger)"></i> Low Attendance (below 75%)</h2></div>
  <?php if (!$low): ?>
    <div class="u-empty"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i><p>No students below 75%. </p></div>
  <?php else: ?>
    <table class="u-table">
      <thead><tr><th>Student</th><th>Reg. No.</th><th style="text-align:right">Records</th><th style="text-align:right">Attendance</th></tr></thead>
      <tbody>
        <?php foreach ($low as $l): ?>
          <tr><td><strong><?= e($l['name']) ?></strong></td><td style="color:var(--muted)"><?= e($l['reg']) ?></td>
            <td style="text-align:right;color:var(--muted)"><?= $l['total'] ?></td>
            <td style="text-align:right"><span class="st st-rejected"><?= $l['pct'] ?>%</span></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php
$tLabels = json_encode(array_map(fn($d) => date('M j', strtotime($d)), array_keys($trend)));
$tData = json_encode(array_map(fn($v) => $v === null ? null : $v, array_values($trend)));
$page_scripts = <<<JS
<script>
(function(){ var ch;
  function pal(){var s=getComputedStyle(document.documentElement);return{ink:s.getPropertyValue('--muted').trim(),line:s.getPropertyValue('--line').trim(),primary:'#6366f1'};}
  function build(){ if(ch)ch.destroy(); var p=pal(); Chart.defaults.color=p.ink; Chart.defaults.borderColor=p.line; Chart.defaults.font.family='Inter, sans-serif';
    var g=document.getElementById('chTrend').getContext('2d'); var gr=g.createLinearGradient(0,0,0,240);
    gr.addColorStop(0,'rgba(99,102,241,.35)'); gr.addColorStop(1,'rgba(99,102,241,0)');
    ch=new Chart(g,{type:'line',data:{labels:$tLabels,datasets:[{label:'Attendance %',data:$tData,borderColor:p.primary,backgroundColor:gr,fill:true,tension:.4,spanGaps:true,pointRadius:2,borderWidth:2.5}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{min:0,max:100,grid:{color:p.line},ticks:{callback:function(v){return v+'%';}}},x:{grid:{display:false}}}}});
  }
  build(); document.addEventListener('ums:theme', build);
})();
</script>
JS;
require __DIR__ . '/../../includes/footer.php';
