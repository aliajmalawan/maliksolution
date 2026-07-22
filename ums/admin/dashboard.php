<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login(['super_admin', 'admin', 'accountant', 'librarian']);
$user = ums_user();

$page_title = 'Dashboard';
$active     = 'dashboard';

/*
 * ERP Admin Dashboard — UI ONLY (Phase 1).
 * Design language inspired by Microsoft Dynamics / Oracle / Zoho / Odoo / Freshworks.
 * Every figure below is CLEARLY-BADGED sample data; each widget will bind to
 * live queries as its module ships in later phases. No module logic here.
 */

/** Initials from a full name, e.g. "Ayesha Khan" → "AK". */
function ini2(string $name): string {
    $p = preg_split('/\s+/', trim($name));
    return strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[count($p) - 1] ?? '', 0, 1));
}

// ── Academic calendar (real month grid + sample events) ──
$today     = new DateTimeImmutable('today');
$monthName = $today->format('F Y');
$daysInMon = (int)$today->format('t');
$startDow  = (int)$today->modify('first day of this month')->format('N'); // 1=Mon
$calEvents = [3 => 'Fee deadline', 9 => 'Faculty meeting', 15 => 'Mid-terms begin', 22 => 'Sports gala', 28 => 'Result day'];

// ── Sample datasets (replaced by module queries later) ──
$recentAdmissions = [
    ['Ayesha Khan', 'BS Computer Science', 'Fall 2026', 'approved', '2h ago'],
    ['Hamza Tariq', 'BS Software Engineering', 'Fall 2026', 'pending', '4h ago'],
    ['Fatima Noor', 'BBA', 'Fall 2026', 'approved', '6h ago'],
    ['Ali Rehman', 'BS Data Science', 'Fall 2026', 'pending', 'Yesterday'],
    ['Zainab Shah', 'BS Information Technology', 'Fall 2026', 'approved', 'Yesterday'],
];
$recentStudents = [
    ['Bilal Ahmed', 'MS-2026-0148', 'BSCS · 1st Sem'],
    ['Sara Yousuf', 'MS-2026-0147', 'BBA · 3rd Sem'],
    ['Usman Ali', 'MS-2026-0146', 'BSSE · 5th Sem'],
    ['Hira Malik', 'MS-2026-0145', 'BSIT · 1st Sem'],
    ['Danish Raza', 'MS-2026-0144', 'BSDS · 3rd Sem'],
];
$recentTeachers = [
    ['Dr. Imran Qureshi', 'Computer Science', 'Professor'],
    ['Ms. Nadia Aslam', 'Business Admin', 'Lecturer'],
    ['Mr. Kashif Javed', 'Mathematics', 'Assistant Prof.'],
    ['Dr. Sana Tariq', 'Data Science', 'Associate Prof.'],
];
$recentPayments = [
    ['Ayesha Khan', 'Tuition — Fall 2026', 'Rs 45,000', 'Bank', '10:24 AM'],
    ['Usman Ali', 'Hostel Fee', 'Rs 18,000', 'Card', '09:51 AM'],
    ['Sara Yousuf', 'Tuition — Fall 2026', 'Rs 45,000', 'Cash', 'Yesterday'],
    ['Danish Raza', 'Transport Fee', 'Rs 6,500', 'Bank', 'Yesterday'],
    ['Hira Malik', 'Admission Fee', 'Rs 25,000', 'Card', '2 days ago'],
];
$attendanceByProgram = [
    ['BS Computer Science', 93, 'g-cyan'],
    ['BS Software Engg.', 90, ''],
    ['BBA', 88, 'g-green'],
    ['BS Data Science', 86, 'g-amber'],
];
$notices = [
    ['fa-user-plus', 'ic-indigo', 'New admission application', 'Ayesha Khan — BS Computer Science', '5 min ago'],
    ['fa-money-bill-wave', 'ic-green', 'Fee payment received', 'Challan #10382 — Rs 45,000', '32 min ago'],
    ['fa-triangle-exclamation', 'ic-amber', 'Low attendance alert', 'BSDS-3A dropped below 75% this week', '2 hrs ago'],
    ['fa-calendar-check', 'ic-cyan', 'Exam schedule published', 'Mid-term datesheet for Fall 2026 is live', 'Yesterday'],
];

/*
 * ── Live integration with the Admissions module ──
 * The Pending Admissions KPI and Recent Admissions widget bind to real
 * ums_admissions data when the module is in use; otherwise the sample
 * data above stands in (so the dashboard never looks broken on a fresh
 * install). No other widget is touched here.
 */
$admLive = false;
$pendingAdmissions = 36; // sample fallback
$agoFn = function (string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 3600)  return max(1, (int)($diff / 60)) . ' min ago';
    if ($diff < 86400) return (int)($diff / 3600) . 'h ago';
    if ($diff < 172800) return 'Yesterday';
    return date('d M', strtotime($ts));
};
try {
    $conn   = ums_db();
    $campus = (int)$user['campus_id'];

    $ls = $conn->prepare('SELECT student_name, program, session, status, applied_at
                          FROM ' . tbl('admissions') . ' WHERE campus_id = ? ORDER BY id DESC LIMIT 5');
    $ls->bind_param('i', $campus);
    $ls->execute();
    $liveRows = $ls->get_result()->fetch_all(MYSQLI_ASSOC);
    $ls->close();

    if ($liveRows) {
        $recentAdmissions = array_map(
            fn($r) => [$r['student_name'], $r['program'] ?: '—', $r['session'] ?: '—', $r['status'], $agoFn($r['applied_at'])],
            $liveRows
        );
        $admLive = true;
    }

    $pc = $conn->prepare('SELECT COUNT(*) c FROM ' . tbl('admissions') . ' WHERE campus_id = ? AND status = "pending"');
    $pc->bind_param('i', $campus);
    $pc->execute();
    $pendingAdmissions = (int)$pc->get_result()->fetch_assoc()['c'];
    $pc->close();
} catch (Throwable $t) { /* module not initialised yet — keep sample data */ }

// ── Live integration: Students module ──
$studentsLive = false;
$totalStudents = 2847; // sample fallback
try {
    $conn = ums_db(); $campus = (int)$user['campus_id'];
    $totalStudents = (int)$conn->query('SELECT COUNT(*) c FROM ' . tbl('students') . ' WHERE campus_id = ' . $campus)->fetch_assoc()['c'];
    $studentsLive = true;
    $rs = $conn->query('SELECT name, registration_no, program FROM ' . tbl('students') . ' WHERE campus_id = ' . $campus . ' ORDER BY id DESC LIMIT 5');
    $live = [];
    while ($r = $rs->fetch_assoc()) $live[] = [$r['name'], $r['registration_no'], $r['program'] ?: '—'];
    if ($live) $recentStudents = $live;
} catch (Throwable $t) { /* Students module not initialised yet */ }

// ── Live integration: Teachers module ──
$teachersLive = false;
$totalFaculty = 148; // sample fallback
try {
    $conn = ums_db(); $campus = (int)$user['campus_id'];
    $totalFaculty = (int)$conn->query('SELECT COUNT(*) c FROM ' . tbl('teachers') . ' WHERE campus_id = ' . $campus)->fetch_assoc()['c'];
    $teachersLive = true;
    $rt = $conn->query('SELECT t.name, t.designation, COALESCE(d.name,"—") dept
        FROM ' . tbl('teachers') . ' t LEFT JOIN ' . tbl('departments') . ' d ON d.id = t.department_id
        WHERE t.campus_id = ' . $campus . ' ORDER BY t.id DESC LIMIT 4');
    $live = [];
    while ($r = $rt->fetch_assoc()) $live[] = [$r['name'], $r['dept'], $r['designation']];
    if ($live) $recentTeachers = $live;
} catch (Throwable $t) { /* Teachers module not initialised yet */ }

// ── Live integration: Attendance (today) ──
$attLive = false;
$attPct = 91.4;                    // sample fallback
$attDonut = [91.4, 5.8, 2.8];      // present, absent, leave (%)
try {
    $conn = ums_db(); $campus = (int)$user['campus_id'];
    $c = ['present' => 0, 'absent' => 0, 'leave' => 0, 'late' => 0];
    $res = $conn->query("SELECT status, COUNT(*) c FROM " . tbl('attendance') . " WHERE a_date = CURDATE() AND campus_id = $campus GROUP BY status");
    while ($r = $res->fetch_assoc()) $c[$r['status']] = (int)$r['c'];
    $tot = array_sum($c);
    if ($tot > 0) {
        $present  = $c['present'] + $c['late'];
        $attPct   = round($present / $tot * 100, 1);
        $attDonut = [round($present / $tot * 100, 1), round($c['absent'] / $tot * 100, 1), round($c['leave'] / $tot * 100, 1)];
        $attLive  = true;
        // per-program breakdown for today
        $bp = []; $clss = ['g-cyan', '', 'g-green', 'g-amber']; $i = 0;
        $r2 = $conn->query("SELECT sec.program, COUNT(*) total, SUM(a.status IN ('present','late')) present
            FROM " . tbl('attendance') . " a JOIN " . tbl('sections') . " sec ON sec.id = a.section_id
            WHERE a.a_date = CURDATE() AND a.campus_id = $campus GROUP BY sec.program ORDER BY total DESC LIMIT 4");
        while ($x = $r2->fetch_assoc()) {
            $p = (int)$x['total'] > 0 ? round((int)$x['present'] / (int)$x['total'] * 100) : 0;
            $bp[] = [$x['program'], $p, $clss[$i++] ?? ''];
        }
        if ($bp) $attendanceByProgram = $bp;
    }
} catch (Throwable $t) { /* Attendance module not initialised yet */ }

// ── Live integration: Fees ──
$feeLive = false;
$feeThisMonth = 'Rs 8.4M'; $feeCollected = 'Rs 8.4M'; $feeOutstanding = 'Rs 1.2M'; $feeRate = '87.5';
try {
    $conn = ums_db(); $campus = (int)$user['campus_id'];
    $ch = $conn->query('SELECT COALESCE(SUM(total_amount - discount + fine),0) billed, COALESCE(SUM(paid_amount),0) collected FROM ' . tbl('fee_challans') . ' WHERE campus_id = ' . $campus)->fetch_assoc();
    $billed = (float)$ch['billed']; $coll = (float)$ch['collected'];
    if ($billed > 0 || $coll > 0) {
        $feeLive = true;
        $feeCollected   = 'Rs ' . number_format($coll);
        $feeOutstanding = 'Rs ' . number_format(max(0, $billed - $coll));
        $feeRate        = $billed > 0 ? number_format($coll / $billed * 100, 1) : '0';
        $tm = (float)$conn->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('fee_payments') . '
            WHERE campus_id = ' . $campus . ' AND YEAR(paid_on) = YEAR(CURDATE()) AND MONTH(paid_on) = MONTH(CURDATE())')->fetch_assoc()['s'];
        $feeThisMonth = 'Rs ' . number_format($tm);
    }
} catch (Throwable $t) { /* Fees module not initialised yet */ }

require __DIR__ . '/../includes/header.php';
?>

<!-- Page head -->
<div class="u-page-head">
  <div>
    <h1>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>
    <p><?= e($today->format('l, d F Y')) ?> · Academic Session: <strong>Fall 2026</strong></p>
  </div>
  <span class="u-chip-demo"><i class="fa-solid fa-flask"></i> Sample data — widgets bind to modules in later phases</span>
</div>

<!-- ═══ KPI CARDS ═══ -->
<div class="u-grid g-kpi5" style="margin-bottom:1.1rem">
  <div class="u-card u-kpi">
    <span class="ic ic-indigo"><i class="fa-solid fa-user-graduate"></i></span>
    <div><small>Total Students</small><strong><?= number_format($totalStudents) ?></strong>
      <span class="delta up"><?= $studentsLive ? '<i class="fa-solid fa-circle" style="font-size:.5rem"></i> live' : '&#9650; 128 this session' ?></span></div>
  </div>
  <div class="u-card u-kpi">
    <span class="ic ic-cyan"><i class="fa-solid fa-person-chalkboard"></i></span>
    <div><small>Faculty</small><strong><?= number_format($totalFaculty) ?></strong>
      <span class="delta up"><?= $teachersLive ? '<i class="fa-solid fa-circle" style="font-size:.5rem"></i> live' : '&#9650; 6 new hires' ?></span></div>
  </div>
  <div class="u-card u-kpi">
    <span class="ic ic-green"><i class="fa-solid fa-clipboard-user"></i></span>
    <div><small>Today's Attendance</small><strong><?= $attPct ?>%</strong>
      <span class="delta up"><?= $attLive ? '<i class="fa-solid fa-circle" style="font-size:.5rem"></i> live' : '&#9650; 2.1%' ?></span></div>
  </div>
  <div class="u-card u-kpi">
    <span class="ic ic-amber"><i class="fa-solid fa-money-bill-wave"></i></span>
    <div><small>Fees (This Month)</small><strong><?= e($feeThisMonth) ?></strong>
      <span class="delta <?= $feeLive ? 'up' : 'down' ?>"><?= $feeLive ? '<i class="fa-solid fa-circle" style="font-size:.5rem"></i> live' : '&#9660; Rs 1.2M due' ?></span></div>
  </div>
  <div class="u-card u-kpi">
    <span class="ic ic-indigo"><i class="fa-solid fa-user-plus"></i></span>
    <div>
      <small>Pending Admissions</small>
      <strong><?= $pendingAdmissions ?></strong>
      <span class="delta up"><?= $admLive ? '<i class="fa-solid fa-circle" style="font-size:.5rem"></i> live' : '&#9650; 9 today' ?></span>
    </div>
  </div>
</div>

<!-- ═══ ENROLLMENT TREND + QUICK ACTIONS ═══ -->
<div class="u-grid g-main" style="margin-bottom:1.1rem">
  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-chart-line" style="color:var(--primary)"></i> Enrollment Trend</h2>
      <span class="hint">Last 8 semesters</span>
    </div>
    <div class="u-chart"><canvas id="chEnroll"></canvas></div>
  </div>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-bolt" style="color:var(--warning)"></i> Quick Actions</h2></div>
    <div class="u-qa">
      <a href="#"><i class="fa-solid fa-user-plus"></i>New Admission</a>
      <a href="#"><i class="fa-solid fa-clipboard-user"></i>Mark Attendance</a>
      <a href="#"><i class="fa-solid fa-money-check-dollar"></i>Collect Fee</a>
      <a href="#"><i class="fa-solid fa-file-pen"></i>Enter Marks</a>
      <a href="#"><i class="fa-solid fa-bullhorn"></i>Send Notice</a>
      <a href="#"><i class="fa-solid fa-chart-pie"></i>View Reports</a>
    </div>
    <p style="color:var(--muted);font-size:.72rem;margin:.9rem 0 0;text-align:center">Actions activate as their modules ship — Phase 2 onwards.</p>
  </div>
</div>

<!-- ═══ FEE SUMMARY + TODAY'S ATTENDANCE ═══ -->
<div class="u-grid g-two" style="margin-bottom:1.1rem">
  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-money-bill-trend-up" style="color:var(--success)"></i> Fee Summary</h2>
      <span class="hint">Fall 2026</span>
    </div>
    <div class="u-fee-top">
      <div class="u-fee-box ok"><small>Collected</small><strong><?= e($feeCollected) ?></strong></div>
      <div class="u-fee-box due"><small>Outstanding</small><strong><?= e($feeOutstanding) ?></strong></div>
      <div class="u-fee-box"><small>Collection Rate</small><strong><?= e($feeRate) ?>%</strong></div>
    </div>
    <div class="u-chart sm" style="height:180px"><canvas id="chFees"></canvas></div>
  </div>
  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-clipboard-check" style="color:var(--info)"></i> Today's Attendance</h2>
      <span class="hint">All programs</span>
    </div>
    <div class="row g-3 align-items-center">
      <div class="col-5"><div class="u-chart sm" style="height:150px"><canvas id="chAtt"></canvas></div></div>
      <div class="col-7">
        <div class="u-prog">
          <?php foreach ($attendanceByProgram as [$prog, $pct, $cls]): ?>
            <div>
              <div class="u-prog-row"><span class="lbl" style="width:auto;font-size:.74rem"><?= e($prog) ?></span><span class="val"><?= $pct ?>%</span></div>
              <div class="u-prog-track"><div class="u-prog-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ RECENT ADMISSIONS + ACADEMIC CALENDAR ═══ -->
<div class="u-grid g-main" style="margin-bottom:1.1rem">
  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-user-plus" style="color:var(--primary)"></i> Recent Admissions
        <?php if ($admLive): ?><span class="st st-approved" style="font-size:.6rem;margin-left:.4rem">LIVE</span><?php endif; ?>
      </h2>
      <a href="<?= UMS_URL ?>/modules/admissions/index.php" class="u-btn u-btn-soft" style="padding:.4rem .9rem;font-size:.74rem">View All</a>
    </div>
    <div style="overflow-x:auto">
      <?php if (!$recentAdmissions): ?>
        <div class="u-empty"><i class="fa-solid fa-inbox"></i><p>No admissions yet. <a href="<?= UMS_URL ?>/modules/admissions/create.php" style="color:var(--primary)">Add the first application</a>.</p></div>
      <?php else: ?>
      <table class="u-table">
        <thead><tr><th>Student</th><th>Program</th><th>Session</th><th>Status</th><th>Applied</th></tr></thead>
        <tbody>
          <?php foreach ($recentAdmissions as [$name, $prog, $sess, $st, $ago]): ?>
            <tr>
              <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($name)) ?></span><strong><?= e($name) ?></strong></span></td>
              <td style="color:var(--muted)"><?= e($prog) ?></td>
              <td style="color:var(--muted)"><?= e($sess) ?></td>
              <td><span class="st <?= in_array($st, ['approved', 'enrolled'], true) ? 'st-ok' : 'st-wait' ?>"><?= e($st) ?></span></td>
              <td style="color:var(--muted)"><?= e($ago) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-calendar-days" style="color:var(--primary-2)"></i> Academic Calendar</h2>
      <span class="hint"><?= e($monthName) ?></span>
    </div>
    <table class="u-cal">
      <thead><tr><th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th><th>Su</th></tr></thead>
      <tbody><tr>
        <?php
        $cell = 1;
        for ($i = 1; $i < $startDow; $i++, $cell++) echo '<td></td>';
        for ($d = 1; $d <= $daysInMon; $d++, $cell++) {
            $cls = 'd';
            if ($d === (int)$today->format('j')) $cls .= ' today';
            if (isset($calEvents[$d])) $cls .= ' ev';
            echo '<td><span class="' . $cls . '">' . $d . '</span></td>';
            if ($cell % 7 === 0 && $d < $daysInMon) echo '</tr><tr>';
        }
        while ($cell % 7 !== 1) { echo '<td></td>'; $cell++; }
        ?>
      </tr></tbody>
    </table>
    <div style="border-top:1px solid var(--line);margin-top:.7rem;padding-top:.5rem">
      <?php foreach ($calEvents as $d => $label): ?>
        <div class="u-cal-ev"><span class="dt"><?= e($today->format('M')) ?> <?= $d ?></span> <?= e($label) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ═══ RECENT STUDENTS + RECENT TEACHERS + NOTIFICATIONS ═══ -->
<div class="u-grid g-thirds" style="margin-bottom:1.1rem">
  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-user-graduate" style="color:var(--primary)"></i> Recent Students
        <?php if ($studentsLive): ?><span class="st st-approved" style="font-size:.6rem;margin-left:.35rem">LIVE</span><?php endif; ?></h2>
      <a href="<?= UMS_URL ?>/modules/students/index.php" class="u-btn u-btn-soft" style="padding:.4rem .9rem;font-size:.74rem">All</a>
    </div>
    <div class="u-list">
      <?php foreach ($recentStudents as [$name, $id, $prog]): ?>
        <div class="u-list-item">
          <span class="u-mini-av"><?= e(ini2($name)) ?></span>
          <div><div class="nm"><?= e($name) ?></div><div class="sub"><?= e($prog) ?></div></div>
          <div class="meta"><span class="tag"><?= e($id) ?></span></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-person-chalkboard" style="color:var(--info)"></i> Recent Teachers
        <?php if ($teachersLive): ?><span class="st st-approved" style="font-size:.6rem;margin-left:.35rem">LIVE</span><?php endif; ?></h2>
      <a href="<?= UMS_URL ?>/modules/teachers/index.php" class="u-btn u-btn-soft" style="padding:.4rem .9rem;font-size:.74rem">All</a>
    </div>
    <div class="u-list">
      <?php foreach ($recentTeachers as [$name, $dept, $desig]): ?>
        <div class="u-list-item">
          <span class="u-mini-av" style="background:rgba(6,182,212,.14);color:var(--info)"><?= e(ini2($name)) ?></span>
          <div><div class="nm"><?= e($name) ?></div><div class="sub"><?= e($dept) ?></div></div>
          <div class="meta"><small><?= e($desig) ?></small></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="u-card">
    <div class="u-card-head">
      <h2><i class="fa-solid fa-bell" style="color:var(--warning)"></i> Notifications</h2>
      <span class="hint"><?= count($notices) ?> new</span>
    </div>
    <div class="u-feed">
      <?php foreach ($notices as [$ic, $cls, $title, $sub, $when]): ?>
        <div class="u-feed-item">
          <span class="fico <?= $cls ?>"><i class="fa-solid <?= $ic ?>"></i></span>
          <div><strong><?= e($title) ?></strong><span style="font-size:.74rem;color:var(--muted)"><?= e($sub) ?></span><small><?= e($when) ?></small></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ═══ RECENT PAYMENTS ═══ -->
<div class="u-card">
  <div class="u-card-head">
    <h2><i class="fa-solid fa-receipt" style="color:var(--success)"></i> Recent Payments</h2>
    <a href="#" class="u-btn u-btn-soft" style="padding:.4rem .9rem;font-size:.74rem">View All Transactions</a>
  </div>
  <div style="overflow-x:auto">
    <table class="u-table">
      <thead><tr><th>Student</th><th>Fee Type</th><th>Method</th><th>Time</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($recentPayments as [$name, $type, $amt, $method, $time]): ?>
          <tr>
            <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($name)) ?></span><strong><?= e($name) ?></strong></span></td>
            <td style="color:var(--muted)"><?= e($type) ?></td>
            <td><span class="st st-ok" style="background:rgba(99,102,241,.1);color:var(--primary)"><?= e($method) ?></span></td>
            <td style="color:var(--muted)"><?= e($time) ?></td>
            <td style="text-align:right;font-weight:800;color:var(--success)"><?= e($amt) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
/* ── Charts: theme-aware Chart.js (redraw on dark-mode toggle) ── */
$page_scripts = <<<'JS'
<script>
(function () {
  var charts = [];
  function palette() {
    var s = getComputedStyle(document.documentElement);
    return { ink: s.getPropertyValue('--muted').trim(), line: s.getPropertyValue('--line').trim(),
      primary: '#6366f1', violet: '#8b5cf6', green: '#10b981', amber: '#f59e0b', cyan: '#06b6d4', red: '#ef4444' };
  }
  function build() {
    charts.forEach(function (c) { c.destroy(); }); charts = [];
    var p = palette();
    Chart.defaults.color = p.ink; Chart.defaults.borderColor = p.line; Chart.defaults.font.family = 'Inter, sans-serif';

    var g = document.getElementById('chEnroll').getContext('2d');
    var grad = g.createLinearGradient(0, 0, 0, 240);
    grad.addColorStop(0, 'rgba(99,102,241,.35)'); grad.addColorStop(1, 'rgba(99,102,241,0)');
    charts.push(new Chart(g, { type: 'line',
      data: { labels: ['Sp 23','Fa 23','Sp 24','Fa 24','Sp 25','Fa 25','Sp 26','Fa 26'],
        datasets: [{ label: 'Enrolled', data: [1620,1810,1930,2140,2360,2540,2695,2847],
          borderColor: p.primary, backgroundColor: grad, fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: p.primary, borderWidth: 2.5 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
        scales: { y: { grid: { color: p.line } }, x: { grid: { display: false } } } } }));

    charts.push(new Chart(document.getElementById('chFees'), { type: 'bar',
      data: { labels: ['Feb','Mar','Apr','May','Jun','Jul'],
        datasets: [
          { label: 'Collected', data: [6.2,7.1,6.8,7.9,8.6,8.4], backgroundColor: p.green, borderRadius: 6, maxBarThickness: 22 },
          { label: 'Outstanding', data: [1.8,1.4,1.9,1.2,0.9,1.2], backgroundColor: p.amber, borderRadius: 6, maxBarThickness: 22 } ] },
      options: { responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 11, boxHeight: 11, borderRadius: 3, useBorderRadius: true, font: { size: 10 } } } },
        scales: { y: { grid: { color: p.line } }, x: { grid: { display: false } } } } }));

    charts.push(new Chart(document.getElementById('chAtt'), { type: 'doughnut',
      data: { labels: ['Present','Absent','Leave'], datasets: [{ data: (window.UMS_ATT || [91.4,5.8,2.8]), backgroundColor: [p.primary, p.red, p.amber], borderWidth: 0, hoverOffset: 5 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } } }));
  }
  build();
  document.addEventListener('ums:theme', build);
})();
</script>
JS;

// Expose today's live attendance to the donut chart (falls back to sample)
$page_scripts = '<script>window.UMS_ATT = ' . json_encode($attDonut) . ';</script>' . $page_scripts;

require __DIR__ . '/../includes/footer.php';
