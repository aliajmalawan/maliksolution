<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Results & GPA'; $active = 'results';
$db = ums_db(); $campus = (int)$user['campus_id'];
$sections = results_section_options($campus);

$secId = (int)($_GET['section'] ?? 0);
if ($secId && !isset($sections[$secId])) $secId = 0;

$data = null; $avgGpa = 0.0; $passCount = 0;
if ($secId) {
    $data = results_section($db, $campus, $secId);
    $withGpa = array_filter($data['students'], fn($s) => $s['credits'] > 0);
    if ($withGpa) {
        $avgGpa = round(array_sum(array_map(fn($s) => $s['gpa'], $withGpa)) / count($withGpa), 2);
        $passCount = count(array_filter($withGpa, fn($s) => $s['gpa'] >= 2.0));
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Results &amp; GPA</h1><p>Weighted course results, semester GPA, and transcripts</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= results_url('transcript.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-file-lines"></i> Transcript</a>
    <a href="<?= results_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
  </div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="att-picker">
    <div class="u-fld"><label>Section</label>
      <select name="section" class="u-select" onchange="this.form.submit()">
        <option value="0">— Select section —</option>
        <?php foreach ($sections as $id => $label): ?><option value="<?= $id ?>" <?= $secId === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select></div>
    <div class="u-fld"><button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-calculator"></i> Compute Result</button></div>
    <?php if ($secId): ?><div class="u-fld"><button type="button" onclick="window.print()" class="u-btn u-btn-soft"><i class="fa-solid fa-print"></i> Print</button></div><?php endif; ?>
  </form>
</div>

<?php if ($secId && $data): ?>
  <?php if (!$data['courses'] || !$data['students']): ?>
    <div class="u-card"><div class="u-empty"><i class="fa-solid fa-square-poll-vertical"></i>
      <p><?= !$data['students'] ? 'No students in this section.' : 'No exams with marks for this section yet.' ?> Create exams and enter marks in <a href="<?= UMS_URL ?>/modules/examination/index.php" style="color:var(--primary)">Examination</a>.</p></div></div>
  <?php else: ?>
    <div class="u-chips">
      <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-users"></i></span><div><small>Students</small><strong><?= count($data['students']) ?></strong></div></div>
      <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-book-open"></i></span><div><small>Courses</small><strong><?= count($data['courses']) ?></strong></div></div>
      <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-chart-line"></i></span><div><small>Average GPA</small><strong><?= number_format($avgGpa, 2) ?></strong></div></div>
      <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-award"></i></span><div><small>GPA &ge; 2.0</small><strong><?= $passCount ?></strong></div></div>
    </div>

    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-table-cells" style="color:var(--primary)"></i> Tabulation Sheet — <?= e($sections[$secId]) ?></h2></div>
      <div style="overflow-x:auto"><table class="u-table">
        <thead><tr>
          <th>Student</th>
          <?php foreach ($data['courses'] as $c): ?><th title="<?= e($c['title']) ?>"><?= e($c['code'] ?: $c['title']) ?><br><small style="color:var(--muted);font-weight:600"><?= $c['credits'] ?> CH</small></th><?php endforeach; ?>
          <th style="text-align:right">GPA</th><th>Standing</th>
        </tr></thead>
        <tbody>
          <?php foreach ($data['students'] as $s): ?>
            <tr>
              <td><a href="<?= results_url('transcript.php?student=' . $s['id']) ?>" style="display:flex;align-items:center;gap:.6rem;color:inherit">
                <span class="u-mini-av"><?= e(ini2($s['name'])) ?></span>
                <span><strong><?= e($s['name']) ?></strong><br><small style="color:var(--muted)"><?= e($s['reg']) ?></small></span></a></td>
              <?php foreach ($data['courses'] as $cid => $c): $res = $s['results'][$cid] ?? null; ?>
                <td style="text-align:center"><?= $res ? grade_badge($res['grade'], $res['point']) . '<br><small style="color:var(--muted)">' . $res['pct'] . '%</small>' : '<span style="color:var(--muted)">—</span>' ?></td>
              <?php endforeach; ?>
              <td style="text-align:right;font-weight:800"><?= $s['credits'] > 0 ? number_format($s['gpa'], 2) : '—' ?></td>
              <td><small style="color:var(--muted)"><?= $s['credits'] > 0 ? e(results_standing($s['gpa'])) : '—' ?></small></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  <?php endif; ?>
<?php endif; ?>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.att-picker .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
