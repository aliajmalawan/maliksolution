<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Transcript'; $active = 'results';
$db = ums_db(); $campus = (int)$user['campus_id'];

// Student picker
$students = [];
$sr = $db->query('SELECT id, name, registration_no FROM ' . tbl('students') . ' WHERE campus_id = ' . $campus . ' ORDER BY name LIMIT 500');
while ($x = $sr->fetch_assoc()) $students[(int)$x['id']] = $x['name'] . ' · ' . $x['registration_no'];

$stuId = (int)($_GET['student'] ?? 0);
$student = null; $tr = null;
if ($stuId) {
    $q = $db->prepare('SELECT * FROM ' . tbl('students') . ' WHERE id = ? AND campus_id = ? LIMIT 1');
    $q->bind_param('ii', $stuId, $campus); $q->execute();
    $student = $q->get_result()->fetch_assoc(); $q->close();
    if ($student) $tr = results_student($db, $campus, $stuId);
}

// Institute name — UMS setting, then website CMS, then default
$inst = ums_inst_name($db);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Academic Transcript</h1><p>Semester-wise results with CGPA</p></div>
  <?php if ($student): ?><button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button><?php endif; ?>
</div>

<div class="u-card no-print" style="margin-bottom:1.1rem">
  <form method="get" class="att-picker">
    <div class="u-fld" style="min-width:320px"><label>Student</label>
      <select name="student" class="u-select" onchange="this.form.submit()">
        <option value="0">— Select student —</option>
        <?php foreach ($students as $id => $label): ?><option value="<?= $id ?>" <?= $stuId === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select></div>
    <div class="u-fld"><button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-file-lines"></i> View Transcript</button></div>
  </form>
</div>

<?php if ($student && $tr): ?>
  <div class="u-card">
    <!-- Transcript header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;border-bottom:2px solid var(--line);padding-bottom:1rem;margin-bottom:1.2rem">
      <div>
        <h2 style="margin:0;font-size:1.2rem;font-weight:800"><?= e($inst) ?></h2>
        <p style="margin:.2rem 0 0;color:var(--muted);font-size:.82rem">Official Academic Transcript</p>
      </div>
      <div style="text-align:right">
        <div style="font-weight:800;font-size:1.05rem"><?= e($student['name']) ?></div>
        <div style="color:var(--muted);font-size:.82rem"><?= e($student['registration_no']) ?> · <?= e($student['program']) ?></div>
      </div>
    </div>

    <?php if (!$tr['semesters']): ?>
      <div class="u-empty"><i class="fa-solid fa-file-lines"></i><p>No results recorded for this student yet.</p></div>
    <?php else: ?>
      <?php foreach ($tr['semesters'] as $sem): ?>
        <div style="margin-bottom:1.4rem">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
            <strong style="font-size:.95rem"><i class="fa-solid fa-graduation-cap" style="color:var(--primary)"></i> Semester <?= (int)$sem['semester'] ?> <span style="color:var(--muted);font-weight:600">· <?= e($sem['session']) ?></span></strong>
            <span class="st st-approved">Semester GPA: <?= number_format($sem['gpa'], 2) ?></span>
          </div>
          <table class="u-table">
            <thead><tr><th>Code</th><th>Course</th><th style="text-align:center">Cr.Hr</th><th style="text-align:center">%</th><th style="text-align:center">Grade</th><th style="text-align:center">Points</th><th style="text-align:right">Quality Pts</th></tr></thead>
            <tbody>
              <?php foreach ($sem['courses'] as $c): ?>
                <tr>
                  <td style="color:var(--muted);font-weight:700"><?= e($c['code'] ?: '—') ?></td>
                  <td><strong><?= e($c['title']) ?></strong></td>
                  <td style="text-align:center"><?= $c['credits'] ?></td>
                  <td style="text-align:center;color:var(--muted)"><?= $c['pct'] ?>%</td>
                  <td style="text-align:center"><?= grade_badge($c['grade'], $c['point']) ?></td>
                  <td style="text-align:center;font-weight:700"><?= number_format($c['point'], 2) ?></td>
                  <td style="text-align:right"><?= number_format($c['qp'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endforeach; ?>

      <!-- Summary -->
      <div style="display:flex;justify-content:flex-end;gap:2.5rem;border-top:2px solid var(--line);padding-top:1rem;margin-top:.5rem">
        <div style="text-align:right"><small style="color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.05em">Total Credit Hours</small><div style="font-size:1.3rem;font-weight:800"><?= $tr['total_credits'] ?></div></div>
        <div style="text-align:right"><small style="color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.05em">CGPA</small><div style="font-size:1.3rem;font-weight:800;color:var(--primary)"><?= number_format($tr['cgpa'], 2) ?></div></div>
        <div style="text-align:right"><small style="color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.05em">Standing</small><div style="font-size:1.05rem;font-weight:800;margin-top:.25rem"><?= e(results_standing($tr['cgpa'])) ?></div></div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.no-print,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
