<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Examination Report'; $active = 'exams';
$db = ums_db(); $campus = (int)$user['campus_id'];
$sections = exam_section_options($campus);

// Per-exam stats (only exams that have marks)
$rows = [];
$res = $db->query('SELECT e.id, e.title, e.exam_type, e.section_id, e.total_marks, e.passing_marks,
        COUNT(m.id) AS marked,
        SUM(m.absent = 0 AND m.obtained_marks >= e.passing_marks) AS passed,
        SUM(m.absent = 1) AS absent,
        AVG(CASE WHEN m.absent = 0 THEN m.obtained_marks END) AS avg_marks,
        MAX(CASE WHEN m.absent = 0 THEN m.obtained_marks END) AS top_marks
    FROM ' . tbl('exams') . ' e
    JOIN ' . tbl('exam_marks') . ' m ON m.exam_id = e.id
    WHERE e.campus_id = ' . $campus . '
    GROUP BY e.id ORDER BY e.id DESC');
while ($x = $res->fetch_assoc()) $rows[] = $x;

$totalExams = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('exams') . ' WHERE campus_id=' . $campus)->fetch_assoc()['c'];
$totMarked = 0; $totPassed = 0;
foreach ($rows as $r) { $present = (int)$r['marked'] - (int)$r['absent']; $totMarked += $present; $totPassed += (int)$r['passed']; }
$overallPass = $totMarked > 0 ? round($totPassed / $totMarked * 100, 1) : 0;

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Examination Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= exam_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>
<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-file-pen"></i></span><div><small>Total Exams</small><strong><?= $totalExams ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-user-check"></i></span><div><small>Results Recorded</small><strong><?= $totMarked ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-percent"></i></span><div><small>Overall Pass Rate</small><strong><?= $overallPass ?>%</strong></div></div>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-square-poll-vertical" style="color:var(--primary)"></i> Results by Exam</h2></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-square-poll-vertical"></i><p>No marks recorded yet. Enter marks for an exam first.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Exam</th><th>Section</th><th style="text-align:right">Appeared</th><th style="text-align:right">Passed</th><th style="text-align:right">Avg %</th><th style="text-align:right">Top</th><th style="text-align:right">Pass Rate</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r):
          $present = (int)$r['marked'] - (int)$r['absent'];
          $passRate = $present > 0 ? round((int)$r['passed'] / $present * 100, 1) : 0;
          $avgPct = ((float)$r['total_marks'] > 0 && $r['avg_marks'] !== null) ? round((float)$r['avg_marks'] / (float)$r['total_marks'] * 100, 1) : 0; ?>
          <tr>
            <td><strong><?= e($r['title']) ?></strong><br><small style="color:var(--muted)"><?= e(EXAM_TYPES[$r['exam_type']] ?? '') ?></small></td>
            <td style="color:var(--muted)"><?= e($sections[(int)$r['section_id']] ?? '—') ?></td>
            <td style="text-align:right"><?= $present ?><?= (int)$r['absent'] ? ' <small style="color:var(--danger)">(' . (int)$r['absent'] . ' abs)</small>' : '' ?></td>
            <td style="text-align:right"><?= (int)$r['passed'] ?></td>
            <td style="text-align:right;font-weight:700"><?= $avgPct ?>%</td>
            <td style="text-align:right;color:var(--muted)"><?= $r['top_marks'] !== null ? (float)$r['top_marks'] : '—' ?></td>
            <td style="text-align:right"><span class="st <?= $passRate>=50?'st-approved':'st-rejected' ?>"><?= $passRate ?>%</span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
