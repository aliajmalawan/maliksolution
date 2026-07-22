<?php
declare(strict_types=1);

/**
 * Reports — cross-module hub.
 * A read-only consolidated dashboard that pulls headline figures from every
 * module (admissions → transport) into one printable overview. It never
 * writes; each figure is a defensive read so a missing module simply shows 0.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();
$db = ums_db(); $campus = (int)$user['campus_id'];

if (!function_exists('money')) {
    function money(float $n): string { return 'Rs ' . number_format($n, 0); }
}

/** Scalar read that never throws — returns $default if the query/table is missing. */
function rscalar(mysqli $db, string $sql, float $default = 0.0): float
{
    try { $r = $db->query($sql); return $r ? (float)array_values($r->fetch_assoc() ?? [0])[0] : $default; }
    catch (Throwable $t) { return $default; }
}

$C = $campus;
// --- Academics ---
$admTotal    = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('admissions') . ' WHERE campus_id=' . $C);
$admPending  = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('admissions') . ' WHERE campus_id=' . $C . ' AND status="pending"');
$admEnrolled = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('admissions') . ' WHERE campus_id=' . $C . ' AND status="enrolled"');
$depts       = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('departments') . ' WHERE campus_id=' . $C);
$courses     = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('courses') . ' WHERE campus_id=' . $C);
$sections    = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('sections') . ' WHERE campus_id=' . $C);
$examTotal   = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('exams') . ' WHERE campus_id=' . $C);
$examDone    = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('exams') . ' WHERE campus_id=' . $C . ' AND status="completed"');
$attTotal    = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('attendance') . ' WHERE campus_id=' . $C);
$attPresent  = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('attendance') . ' WHERE campus_id=' . $C . ' AND status IN ("present","late")');
$attRate     = $attTotal > 0 ? round($attPresent / $attTotal * 100) : null;

// --- People ---
$students    = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('students') . ' WHERE campus_id=' . $C . ' AND status="active"');
$teachers    = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('teachers') . ' WHERE campus_id=' . $C . ' AND status="active"');
$ratio       = $teachers > 0 ? round($students / $teachers, 1) : null;

// --- Finance (mirrors Accounts acc_totals, read-only) ---
$feeIncome   = rscalar($db, 'SELECT COALESCE(SUM(amount),0) FROM ' . tbl('fee_payments') . ' WHERE campus_id=' . $C);
$hostelInc   = rscalar($db, 'SELECT COALESCE(SUM(amount),0) FROM ' . tbl('hostel_payments') . ' WHERE campus_id=' . $C);
$transInc    = rscalar($db, 'SELECT COALESCE(SUM(amount),0) FROM ' . tbl('transport_payments') . ' WHERE campus_id=' . $C);
$libFines    = rscalar($db, 'SELECT COALESCE(SUM(fine),0) FROM ' . tbl('book_issues') . ' WHERE campus_id=' . $C . ' AND fine>0 AND fine_paid=1');
$manualInc   = rscalar($db, 'SELECT COALESCE(SUM(amount),0) FROM ' . tbl('transactions') . ' WHERE campus_id=' . $C . ' AND type="income"');
$manualExp   = rscalar($db, 'SELECT COALESCE(SUM(amount),0) FROM ' . tbl('transactions') . ' WHERE campus_id=' . $C . ' AND type="expense"');
$salaries    = rscalar($db, 'SELECT COALESCE(SUM(net_salary),0) FROM ' . tbl('payslips') . ' WHERE campus_id=' . $C . ' AND status="paid"');
$totalIncome = $feeIncome + $hostelInc + $transInc + $libFines + $manualInc;
$totalExpense = $manualExp + $salaries;
$net         = $totalIncome - $totalExpense;

$feeBilled   = rscalar($db, 'SELECT COALESCE(SUM(total_amount + fine - discount),0) FROM ' . tbl('fee_challans') . ' WHERE campus_id=' . $C);
$feeCollected = rscalar($db, 'SELECT COALESCE(SUM(paid_amount),0) FROM ' . tbl('fee_challans') . ' WHERE campus_id=' . $C);
$feeOutstanding = max(0, $feeBilled - $feeCollected);

// --- Campus services ---
$books       = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('books') . ' WHERE campus_id=' . $C);
$booksOut    = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('book_issues') . ' WHERE campus_id=' . $C . ' AND status="issued"');
$rooms       = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('hostel_rooms') . ' WHERE campus_id=' . $C . ' AND status="active"');
$residents   = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('hostel_allotments') . ' WHERE campus_id=' . $C . ' AND status="active"');
$routes      = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('transport_routes') . ' WHERE campus_id=' . $C . ' AND status="active"');
$riders      = (int)rscalar($db, 'SELECT COUNT(*) FROM ' . tbl('transport_assignments') . ' WHERE campus_id=' . $C . ' AND status="active"');

// Income-by-source for the chart
$incSources = array_filter([
    'Tuition Fee'   => $feeIncome,
    'Hostel Fee'    => $hostelInc,
    'Transport Fee' => $transInc,
    'Library Fine'  => $libFines,
    'Other Income'  => $manualInc,
], fn($v) => $v > 0);

$page_title = 'Reports'; $active = 'reports';
require __DIR__ . '/../../includes/header.php';

/** Render one KPI card. */
function kpi(string $ic, string $tone, string $label, string $value, ?string $sub = null): void { ?>
  <div class="u-card u-kpi"><span class="ic <?= $tone ?>"><i class="fa-solid <?= $ic ?>"></i></span>
    <div><small><?= e($label) ?></small><strong><?= $value ?></strong>
      <?php if ($sub !== null): ?><span style="color:var(--muted);font-size:.74rem;font-weight:600"><?= $sub ?></span><?php endif; ?></div></div>
<?php }
?>
<div class="u-page-head">
  <div><h1>Reports</h1><p>Consolidated overview across all modules · <?= e(date('d M Y')) ?></p></div>
  <div><button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button></div>
</div>

<!-- Finance headline -->
<div class="u-grid g-thirds" style="margin-bottom:.4rem">
  <?php
    kpi('fa-arrow-down', 'ic-green', 'Total Income', money($totalIncome));
    kpi('fa-arrow-up', '', 'Total Expense', '<span style="color:var(--danger)">' . money($totalExpense) . '</span>');
    kpi('fa-scale-balanced', 'ic-indigo', 'Net Balance', '<span style="color:' . ($net >= 0 ? 'var(--success)' : 'var(--danger)') . '">' . money($net) . '</span>');
  ?>
</div>

<div class="u-grid g-two" style="margin-bottom:1.1rem">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-chart-pie" style="color:var(--primary)"></i> Income by Source</h2>
      <a href="<?= UMS_URL ?>/modules/accounts/report.php" class="hint" style="text-decoration:none">Full report →</a></div>
    <?php if (!$incSources): ?><div class="u-empty"><i class="fa-solid fa-chart-pie"></i><p>No income recorded yet.</p></div>
    <?php else: ?><div class="u-chart" style="max-height:280px"><canvas id="chInc"></canvas></div><?php endif; ?>
  </div>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-money-bill-wave" style="color:var(--primary)"></i> Fee Collection</h2>
      <a href="<?= UMS_URL ?>/modules/fees/index.php" class="hint" style="text-decoration:none">Fees →</a></div>
    <div style="padding:.4rem 0">
      <div class="u-prog-row"><span class="lbl" style="width:auto">Billed</span><span class="val"><?= money($feeBilled) ?></span></div>
      <div class="u-prog-track" style="margin:.3rem 0 1rem"><div class="u-prog-fill g-green" style="width:<?= $feeBilled>0?(int)round($feeCollected/$feeBilled*100):0 ?>%"></div></div>
      <div style="display:flex;justify-content:space-between;gap:1rem">
        <div><small style="color:var(--muted)">Collected</small><div style="font-weight:800;color:var(--success);font-size:1.1rem"><?= money($feeCollected) ?></div></div>
        <div style="text-align:right"><small style="color:var(--muted)">Outstanding</small><div style="font-weight:800;color:var(--danger);font-size:1.1rem"><?= money($feeOutstanding) ?></div></div>
      </div>
    </div>
  </div>
</div>

<!-- Academics -->
<h2 class="rpt-sec"><i class="fa-solid fa-graduation-cap"></i> Academics</h2>
<div class="u-grid g-kpi5" style="margin-bottom:1.1rem">
  <?php
    kpi('fa-file-signature', 'ic-indigo', 'Admissions', (string)$admTotal, $admPending . ' pending · ' . $admEnrolled . ' enrolled');
    kpi('fa-building', 'ic-cyan', 'Departments', (string)$depts);
    kpi('fa-book-open', 'ic-amber', 'Courses', (string)$courses, $sections . ' sections');
    kpi('fa-file-pen', 'ic-indigo', 'Exams', (string)$examTotal, $examDone . ' completed');
    kpi('fa-user-check', 'ic-green', 'Attendance', $attRate === null ? '—' : $attRate . '%', $attTotal ? number_format($attTotal) . ' records' : 'no data');
  ?>
</div>

<!-- People -->
<h2 class="rpt-sec"><i class="fa-solid fa-users"></i> People</h2>
<div class="u-grid g-thirds" style="margin-bottom:1.1rem">
  <?php
    kpi('fa-user-graduate', 'ic-indigo', 'Active Students', number_format($students));
    kpi('fa-chalkboard-user', 'ic-cyan', 'Active Teachers', number_format($teachers));
    kpi('fa-scale-balanced', 'ic-amber', 'Student : Teacher', $ratio === null ? '—' : $ratio . ' : 1');
  ?>
</div>

<!-- Campus services -->
<h2 class="rpt-sec"><i class="fa-solid fa-building-columns"></i> Campus Services</h2>
<div class="u-grid g-thirds" style="margin-bottom:1.1rem">
  <?php
    kpi('fa-book-bookmark', 'ic-indigo', 'Library Books', number_format($books), $booksOut . ' issued out');
    kpi('fa-bed', 'ic-cyan', 'Hostel Residents', (string)$residents, $rooms . ' active rooms');
    kpi('fa-bus', 'ic-amber', 'Transport Riders', (string)$riders, $routes . ' active routes');
  ?>
</div>

<!-- Module report links -->
<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-list" style="color:var(--primary)"></i> Detailed Module Reports</h2></div>
  <div class="rpt-links">
    <a href="<?= UMS_URL ?>/modules/admissions/report.php"><i class="fa-solid fa-file-signature"></i> Admissions</a>
    <a href="<?= UMS_URL ?>/modules/results/index.php"><i class="fa-solid fa-award"></i> Results &amp; GPA</a>
    <a href="<?= UMS_URL ?>/modules/fees/index.php"><i class="fa-solid fa-money-bill-wave"></i> Fees</a>
    <a href="<?= UMS_URL ?>/modules/accounts/report.php"><i class="fa-solid fa-chart-column"></i> Financial</a>
    <a href="<?= UMS_URL ?>/modules/payroll/index.php"><i class="fa-solid fa-file-invoice-dollar"></i> Payroll</a>
    <a href="<?= UMS_URL ?>/modules/library/report.php"><i class="fa-solid fa-book-bookmark"></i> Library</a>
    <a href="<?= UMS_URL ?>/modules/hostel/report.php"><i class="fa-solid fa-bed"></i> Hostel</a>
    <a href="<?= UMS_URL ?>/modules/transport/report.php"><i class="fa-solid fa-bus"></i> Transport</a>
  </div>
</div>

<style>
.rpt-sec { font-size: .82rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin: 1.4rem 0 .7rem; display: flex; align-items: center; gap: .5rem; }
.rpt-sec i { color: var(--primary); }
.rpt-links { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .6rem; }
.rpt-links a { display: flex; align-items: center; gap: .6rem; padding: .8rem 1rem; border: 1px solid var(--line); border-radius: 10px; text-decoration: none; color: var(--ink); font-weight: 600; transition: border-color .12s, background .12s; }
.rpt-links a:hover { border-color: var(--primary); background: var(--bg); }
.rpt-links a i { color: var(--primary); width: 18px; text-align: center; }
@media print { .u-side, .u-top, .u-page-head .u-btn, .u-side-backdrop { display: none !important; } .u-main { margin: 0 !important; } body { background: #fff; } .rpt-links a { break-inside: avoid; } }
</style>
<?php
if ($incSources) {
    $labels = json_encode(array_keys($incSources));
    $data   = json_encode(array_map(fn($v) => round($v, 2), array_values($incSources)));
    $page_scripts = <<<JS
<script>
(function(){ var ch;
  var colors=['#6366f1','#06b6d4','#f59e0b','#10b981','#a855f7','#ef4444'];
  function pal(){var s=getComputedStyle(document.documentElement);return{ink:s.getPropertyValue('--muted').trim(),line:s.getPropertyValue('--line').trim(),surface:s.getPropertyValue('--surface').trim()};}
  function build(){ if(ch)ch.destroy(); var p=pal(); Chart.defaults.color=p.ink; Chart.defaults.font.family='Inter, sans-serif';
    ch=new Chart(document.getElementById('chInc'),{type:'doughnut',
      data:{labels:$labels,datasets:[{data:$data,backgroundColor:colors,borderColor:p.surface,borderWidth:2}]},
      options:{responsive:true,maintainAspectRatio:false,cutout:'62%',plugins:{legend:{position:'right',labels:{boxWidth:12,boxHeight:12,borderRadius:3,useBorderRadius:true,padding:12}}}}});
  }
  build(); document.addEventListener('ums:theme', build);
})();
</script>
JS;
}
require __DIR__ . '/../../includes/footer.php';
