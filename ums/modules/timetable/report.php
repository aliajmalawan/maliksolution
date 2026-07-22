<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Teacher Workload'; $active = 'timetable';
$db = ums_db(); $campus = (int)$user['campus_id'];

// Periods + weekly teaching hours per teacher
$rows = [];
$res = $db->query('SELECT t.name, COUNT(*) periods,
        SUM(TIME_TO_SEC(TIMEDIFF(tt.end_time, tt.start_time)))/3600 AS hours
    FROM ' . tbl('timetable') . ' tt
    JOIN ' . tbl('teachers') . ' t ON t.id = tt.teacher_id
    WHERE tt.campus_id = ' . $campus . ' AND tt.teacher_id > 0
    GROUP BY tt.teacher_id ORDER BY hours DESC');
while ($x = $res->fetch_assoc()) $rows[] = $x;

$totPeriods = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('timetable') . ' WHERE campus_id=' . $campus)->fetch_assoc()['c'];
$unassigned = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('timetable') . ' WHERE campus_id=' . $campus . ' AND teacher_id=0')->fetch_assoc()['c'];
$maxHours = 0.0; foreach ($rows as $r) $maxHours = max($maxHours, (float)$r['hours']);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Teacher Workload</h1><p>Weekly teaching periods &amp; hours · <?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= tt_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-table-cells"></i></span><div><small>Total Periods</small><strong><?= $totPeriods ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-user-clock"></i></span><div><small>Teachers Scheduled</small><strong><?= count($rows) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-user-slash"></i></span><div><small>Periods w/o Teacher</small><strong><?= $unassigned ?></strong></div></div>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-user-clock" style="color:var(--primary)"></i> Workload by Teacher</h2></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-user-clock"></i><p>No periods assigned to teachers yet.</p></div>
  <?php else: ?>
    <table class="u-table">
      <thead><tr><th>Teacher</th><th style="text-align:right">Periods / Week</th><th style="text-align:right">Hours / Week</th><th style="width:30%">Load</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $h = round((float)$r['hours'], 1); $w = $maxHours > 0 ? (int)round($h / $maxHours * 100) : 0; ?>
          <tr>
            <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($r['name'])) ?></span><strong><?= e($r['name']) ?></strong></span></td>
            <td style="text-align:right;font-weight:700"><?= (int)$r['periods'] ?></td>
            <td style="text-align:right;font-weight:700"><?= $h ?></td>
            <td><div class="u-prog-track"><div class="u-prog-fill <?= $h > 20 ? 'g-amber' : '' ?>" style="width:<?= $w ?>%"></div></div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
