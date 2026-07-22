<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Financial Report'; $active = 'accounts';
$db = ums_db(); $campus = (int)$user['campus_id'];

[$income, $expense, $net] = acc_totals($db, $campus);
$hasFees = acc_has_fees();

// Expense by category (manual expenses + paid salaries)
$expByCat = [];
$res = $db->query('SELECT category, COALESCE(SUM(amount),0) a FROM ' . tbl('transactions') . ' WHERE campus_id=' . $campus . ' AND type="expense" GROUP BY category ORDER BY a DESC');
while ($x = $res->fetch_assoc()) $expByCat[$x['category']] = (float)$x['a'];
if (acc_has_payroll()) {
    $sal = (float)$db->query('SELECT COALESCE(SUM(net_salary),0) s FROM ' . tbl('payslips') . ' WHERE campus_id=' . $campus . ' AND status="paid"')->fetch_assoc()['s'];
    if ($sal > 0) $expByCat['Salaries'] = ($expByCat['Salaries'] ?? 0) + $sal;
}
arsort($expByCat);

// Income by category (manual income + fee collection)
$incByCat = [];
$res = $db->query('SELECT category, COALESCE(SUM(amount),0) a FROM ' . tbl('transactions') . ' WHERE campus_id=' . $campus . ' AND type="income" GROUP BY category');
while ($x = $res->fetch_assoc()) $incByCat[$x['category']] = (float)$x['a'];
if ($hasFees) {
    $fc = (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('fee_payments') . ' WHERE campus_id=' . $campus)->fetch_assoc()['s'];
    if ($fc > 0) $incByCat['Fee Collection'] = ($incByCat['Fee Collection'] ?? 0) + $fc;
}
if (acc_has_library()) {
    $lf = (float)$db->query('SELECT COALESCE(SUM(fine),0) s FROM ' . tbl('book_issues') . ' WHERE campus_id=' . $campus . ' AND fine>0 AND fine_paid=1')->fetch_assoc()['s'];
    if ($lf > 0) $incByCat['Library Fine'] = ($incByCat['Library Fine'] ?? 0) + $lf;
}
if (acc_has_hostel()) {
    $hf = (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('hostel_payments') . ' WHERE campus_id=' . $campus)->fetch_assoc()['s'];
    if ($hf > 0) $incByCat['Hostel Fee'] = ($incByCat['Hostel Fee'] ?? 0) + $hf;
}
if (acc_has_transport()) {
    $tf = (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('transport_payments') . ' WHERE campus_id=' . $campus)->fetch_assoc()['s'];
    if ($tf > 0) $incByCat['Transport Fee'] = ($incByCat['Transport Fee'] ?? 0) + $tf;
}
arsort($incByCat);

// 6-month income vs expense
$months = [];
for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("-$i months"))] = ['inc' => 0.0, 'exp' => 0.0];
$res = $db->query('SELECT DATE_FORMAT(txn_date,"%Y-%m") ym, type, COALESCE(SUM(amount),0) a FROM ' . tbl('transactions') . '
    WHERE campus_id=' . $campus . ' AND txn_date >= (CURDATE() - INTERVAL 5 MONTH) GROUP BY ym, type');
while ($x = $res->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']][$x['type'] === 'income' ? 'inc' : 'exp'] += (float)$x['a'];
if ($hasFees) {
    $res = $db->query('SELECT DATE_FORMAT(paid_on,"%Y-%m") ym, COALESCE(SUM(amount),0) a FROM ' . tbl('fee_payments') . '
        WHERE campus_id=' . $campus . ' AND paid_on >= (CURDATE() - INTERVAL 5 MONTH) GROUP BY ym');
    while ($x = $res->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']]['inc'] += (float)$x['a'];
}
if (acc_has_payroll()) {
    $res = $db->query('SELECT DATE_FORMAT(paid_on,"%Y-%m") ym, COALESCE(SUM(net_salary),0) a FROM ' . tbl('payslips') . '
        WHERE campus_id=' . $campus . ' AND status="paid" AND paid_on >= (CURDATE() - INTERVAL 5 MONTH) GROUP BY ym');
    while ($x = $res->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']]['exp'] += (float)$x['a'];
}
if (acc_has_library()) {
    $res = $db->query('SELECT DATE_FORMAT(return_date,"%Y-%m") ym, COALESCE(SUM(fine),0) a FROM ' . tbl('book_issues') . '
        WHERE campus_id=' . $campus . ' AND fine>0 AND fine_paid=1 AND return_date >= (CURDATE() - INTERVAL 5 MONTH) GROUP BY ym');
    while ($x = $res->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']]['inc'] += (float)$x['a'];
}
if (acc_has_hostel()) {
    $res = $db->query('SELECT DATE_FORMAT(paid_on,"%Y-%m") ym, COALESCE(SUM(amount),0) a FROM ' . tbl('hostel_payments') . '
        WHERE campus_id=' . $campus . ' AND paid_on >= (CURDATE() - INTERVAL 5 MONTH) GROUP BY ym');
    while ($x = $res->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']]['inc'] += (float)$x['a'];
}
if (acc_has_transport()) {
    $res = $db->query('SELECT DATE_FORMAT(paid_on,"%Y-%m") ym, COALESCE(SUM(amount),0) a FROM ' . tbl('transport_payments') . '
        WHERE campus_id=' . $campus . ' AND paid_on >= (CURDATE() - INTERVAL 5 MONTH) GROUP BY ym');
    while ($x = $res->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']]['inc'] += (float)$x['a'];
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Financial Report</h1><p>All-time income, expense &amp; net · <?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= acc_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-grid g-thirds" style="margin-bottom:1.1rem">
  <div class="u-card u-kpi"><span class="ic ic-green"><i class="fa-solid fa-arrow-down"></i></span><div><small>Total Income</small><strong style="color:var(--success)"><?= money($income) ?></strong></div></div>
  <div class="u-card u-kpi"><span class="ic" style="background:linear-gradient(135deg,#ef4444,#f87171)"><i class="fa-solid fa-arrow-up"></i></span><div><small>Total Expense</small><strong style="color:var(--danger)"><?= money($expense) ?></strong></div></div>
  <div class="u-card u-kpi"><span class="ic ic-indigo"><i class="fa-solid fa-scale-balanced"></i></span><div><small>Net Balance</small><strong style="color:<?= $net>=0?'var(--success)':'var(--danger)' ?>"><?= money($net) ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <div class="u-card-head"><h2><i class="fa-solid fa-chart-column" style="color:var(--primary)"></i> Income vs Expense — 6 Months</h2></div>
  <div class="u-chart"><canvas id="chIE"></canvas></div>
</div>

<div class="u-grid g-two">
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-arrow-down" style="color:var(--success)"></i> Income by Category</h2></div>
    <?php if (!array_sum($incByCat)): ?><div class="u-empty"><i class="fa-solid fa-arrow-down"></i><p>No income yet.</p></div>
    <?php else: $mx = max($incByCat); ?><div class="u-prog"><?php foreach ($incByCat as $c => $v): if ($v<=0) continue; ?>
      <div><div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($c) ?></span><span class="val"><?= money($v) ?></span></div>
        <div class="u-prog-track"><div class="u-prog-fill g-green" style="width:<?= (int)round($v/$mx*100) ?>%"></div></div></div>
    <?php endforeach; ?></div><?php endif; ?></div>
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-arrow-up" style="color:var(--danger)"></i> Expense by Category</h2></div>
    <?php if (!array_sum($expByCat)): ?><div class="u-empty"><i class="fa-solid fa-arrow-up"></i><p>No expenses yet.</p></div>
    <?php else: $mx = max($expByCat); ?><div class="u-prog"><?php foreach ($expByCat as $c => $v): if ($v<=0) continue; ?>
      <div><div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($c) ?></span><span class="val"><?= money($v) ?></span></div>
        <div class="u-prog-track"><div class="u-prog-fill low" style="width:<?= (int)round($v/$mx*100) ?>%"></div></div></div>
    <?php endforeach; ?></div><?php endif; ?></div>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}
.u-prog-fill.low{background:linear-gradient(90deg,#ef4444,#f97316)}</style>
<?php
$labels = json_encode(array_map(fn($m) => date('M', strtotime($m . '-01')), array_keys($months)));
$incD = json_encode(array_map(fn($m) => round($m['inc'], 2), array_values($months)));
$expD = json_encode(array_map(fn($m) => round($m['exp'], 2), array_values($months)));
$page_scripts = <<<JS
<script>
(function(){ var ch;
  function pal(){var s=getComputedStyle(document.documentElement);return{ink:s.getPropertyValue('--muted').trim(),line:s.getPropertyValue('--line').trim()};}
  function build(){ if(ch)ch.destroy(); var p=pal(); Chart.defaults.color=p.ink; Chart.defaults.borderColor=p.line; Chart.defaults.font.family='Inter, sans-serif';
    ch=new Chart(document.getElementById('chIE'),{type:'bar',
      data:{labels:$labels,datasets:[
        {label:'Income',data:$incD,backgroundColor:'#10b981',borderRadius:6,maxBarThickness:26},
        {label:'Expense',data:$expD,backgroundColor:'#ef4444',borderRadius:6,maxBarThickness:26}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:12,boxHeight:12,borderRadius:3,useBorderRadius:true}}},scales:{y:{grid:{color:p.line}},x:{grid:{display:false}}}}});
  }
  build(); document.addEventListener('ums:theme', build);
})();
</script>
JS;
require __DIR__ . '/../../includes/footer.php';
