<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Payroll Report'; $active = 'payroll';
$db = ums_db(); $campus = (int)$user['campus_id'];

// All-time
$all = $db->query('SELECT COUNT(*) slips, COALESCE(SUM(net_salary),0) total,
    COALESCE(SUM(CASE WHEN status="paid" THEN net_salary END),0) paid,
    SUM(status="unpaid") unpaid_n
    FROM ' . tbl('payslips') . ' WHERE campus_id=' . $campus)->fetch_assoc();
$pending = (float)$all['total'] - (float)$all['paid'];

// Monthly disbursed (last 6 months)
$months = [];
for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("-$i months"))] = 0.0;
$res = $db->query('SELECT month, COALESCE(SUM(CASE WHEN status="paid" THEN net_salary END),0) a
    FROM ' . tbl('payslips') . ' WHERE campus_id=' . $campus . ' GROUP BY month');
while ($x = $res->fetch_assoc()) if (isset($months[$x['month']])) $months[$x['month']] = (float)$x['a'];

// By designation (all-time net)
$byDesig = [];
$res = $db->query('SELECT t.designation, COALESCE(SUM(p.net_salary),0) a, COUNT(*) n
    FROM ' . tbl('payslips') . ' p JOIN ' . tbl('teachers') . ' t ON t.id = p.teacher_id
    WHERE p.campus_id=' . $campus . ' GROUP BY t.designation ORDER BY a DESC');
while ($x = $res->fetch_assoc()) $byDesig[] = $x;

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Payroll Report</h1><p>All-time salary disbursement · <?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= pay_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-grid g-kpi" style="margin-bottom:1.1rem">
  <div class="u-card u-kpi"><span class="ic ic-indigo"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><small>Total Payslips</small><strong><?= (int)$all['slips'] ?></strong></div></div>
  <div class="u-card u-kpi"><span class="ic ic-green"><i class="fa-solid fa-hand-holding-dollar"></i></span><div><small>Disbursed</small><strong><?= money((float)$all['paid']) ?></strong></div></div>
  <div class="u-card u-kpi"><span class="ic ic-amber"><i class="fa-solid fa-clock"></i></span><div><small>Pending</small><strong><?= money($pending) ?></strong></div></div>
  <div class="u-card u-kpi"><span class="ic ic-cyan"><i class="fa-solid fa-list"></i></span><div><small>Unpaid Slips</small><strong><?= (int)$all['unpaid_n'] ?></strong></div></div>
</div>

<div class="u-grid g-two">
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-chart-column" style="color:var(--primary)"></i> Disbursed — 6 Months</h2></div>
    <div class="u-chart"><canvas id="chPay"></canvas></div></div>
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-user-tie" style="color:var(--primary)"></i> By Designation</h2></div>
    <?php if (!$byDesig): ?><div class="u-empty"><i class="fa-solid fa-user-tie"></i><p>No payslips yet.</p></div>
    <?php else: $mx = max(array_map(fn($d)=>(float)$d['a'], $byDesig)); ?><div class="u-prog">
      <?php foreach ($byDesig as $d): ?><div>
        <div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($d['designation'] ?: '—') ?> <span style="color:var(--muted)">(<?= (int)$d['n'] ?>)</span></span><span class="val"><?= money((float)$d['a']) ?></span></div>
        <div class="u-prog-track"><div class="u-prog-fill" style="width:<?= $mx>0?(int)round((float)$d['a']/$mx*100):0 ?>%"></div></div></div><?php endforeach; ?>
    </div><?php endif; ?></div>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php
$labels = json_encode(array_map(fn($m) => date('M', strtotime($m . '-01')), array_keys($months)));
$data = json_encode(array_map(fn($v) => round($v, 2), array_values($months)));
$page_scripts = <<<JS
<script>
(function(){ var ch;
  function pal(){var s=getComputedStyle(document.documentElement);return{ink:s.getPropertyValue('--muted').trim(),line:s.getPropertyValue('--line').trim()};}
  function build(){ if(ch)ch.destroy(); var p=pal(); Chart.defaults.color=p.ink; Chart.defaults.borderColor=p.line; Chart.defaults.font.family='Inter, sans-serif';
    ch=new Chart(document.getElementById('chPay'),{type:'bar',data:{labels:$labels,datasets:[{label:'Disbursed',data:$data,backgroundColor:'#6366f1',borderRadius:6,maxBarThickness:30}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{grid:{color:p.line}},x:{grid:{display:false}}}}});
  }
  build(); document.addEventListener('ums:theme', build);
})();
</script>
JS;
require __DIR__ . '/../../includes/footer.php';
