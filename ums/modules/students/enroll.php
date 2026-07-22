<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Enroll from Admissions — lists enrolled applications not yet added as students. */

$page_title = 'Enroll from Admissions'; $active = 'students';
$db = ums_db(); $campus = (int)$user['campus_id'];

$rows = [];
try {
    $res = $db->query('SELECT a.id, a.application_no, a.student_name, a.program, a.session, a.merit_score, a.total_marks
        FROM ' . tbl('admissions') . ' a
        WHERE a.campus_id = ' . $campus . ' AND a.status = "enrolled"
        AND NOT EXISTS (SELECT 1 FROM ' . tbl('students') . ' st WHERE st.admission_id = a.id)
        ORDER BY a.id DESC');
    $rows = $res->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $t) {}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Enroll from Admissions</h1><p>Approved &amp; enrolled applications ready to become student records</p></div>
  <a href="<?= stu_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back to Students</a>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-user-check" style="color:var(--primary)"></i> Ready to Enroll</h2>
    <span class="hint"><?= count($rows) ?> application<?= count($rows) === 1 ? '' : 's' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-user-check"></i>
      <p>No pending enrolments. Applications marked <strong>Enrolled</strong> in Admissions appear here.</p>
      <a href="<?= UMS_URL ?>/modules/admissions/index.php" class="u-btn u-btn-soft mt-2">Go to Admissions</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Applicant</th><th>App No.</th><th>Program</th><th>Session</th><th>Merit</th><th style="text-align:right">Action</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($r['student_name'])) ?></span><strong><?= e($r['student_name']) ?></strong></span></td>
            <td style="color:var(--muted);font-weight:700"><?= e($r['application_no']) ?></td>
            <td style="color:var(--muted)"><?= e($r['program']) ?></td>
            <td style="color:var(--muted)"><?= e($r['session']) ?></td>
            <td style="font-weight:700"><?= (int)$r['total_marks'] > 0 ? number_format((float)$r['merit_score'], 1) . '%' : '—' ?></td>
            <td style="text-align:right"><a href="<?= stu_url('create.php?from_admission=' . (int)$r['id']) ?>" class="u-btn u-btn-primary" style="padding:.4rem 1rem;font-size:.78rem"><i class="fa-solid fa-user-plus"></i> Enroll</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
