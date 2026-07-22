<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Sections Report'; $active = 'classes';
$db = ums_db(); $campus = (int)$user['campus_id'];

$agg = $db->prepare('SELECT COUNT(*) n, COALESCE(SUM(capacity),0) cap FROM ' . tbl('sections') . ' WHERE campus_id=?');
$agg->bind_param('i', $campus); $agg->execute(); $a = $agg->get_result()->fetch_assoc(); $agg->close();

$enrolledTotal = 0;
try { $enrolledTotal = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('students') . ' WHERE section_id > 0 AND campus_id=' . $campus)->fetch_assoc()['c']; } catch (Throwable $t) {}

// Sections per program with capacity + enrolled
$rows = [];
$hasStudents = (bool)$db->query("SHOW TABLES LIKE '" . tbl('students') . "'")->num_rows;
$enrolExpr = $hasStudents ? '(SELECT COUNT(*) FROM ' . tbl('students') . ' st WHERE st.section_id = s.id)' : '0';
$res = $db->query("SELECT s.program, COUNT(*) secs, COALESCE(SUM(s.capacity),0) cap, COALESCE(SUM($enrolExpr),0) enr
    FROM " . tbl('sections') . " s WHERE s.campus_id = $campus GROUP BY s.program ORDER BY secs DESC");
$rows = $res->fetch_all(MYSQLI_ASSOC);

$fillRate = (int)$a['cap'] > 0 ? round($enrolledTotal / (int)$a['cap'] * 100, 1) : 0;

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Sections Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= sec_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>
<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-chalkboard"></i></span><div><small>Total Sections</small><strong><?= (int)$a['n'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-users"></i></span><div><small>Total Capacity</small><strong><?= (int)$a['cap'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-user-check"></i></span><div><small>Enrolled</small><strong><?= $enrolledTotal ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-percent"></i></span><div><small>Overall Fill Rate</small><strong><?= $fillRate ?>%</strong></div></div>
</div>
<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-table" style="color:var(--primary)"></i> Sections by Program</h2></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-chalkboard"></i><p>No sections yet.</p></div>
  <?php else: ?>
    <table class="u-table">
      <thead><tr><th>Program</th><th style="text-align:right">Sections</th><th style="text-align:right">Capacity</th><th style="text-align:right">Enrolled</th><th style="text-align:right">Fill %</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $fr = (int)$r['cap']>0 ? round((int)$r['enr']/(int)$r['cap']*100,1) : 0; ?>
          <tr><td><strong><?= e($r['program']) ?></strong></td><td style="text-align:right"><?= (int)$r['secs'] ?></td>
            <td style="text-align:right;color:var(--muted)"><?= (int)$r['cap'] ?></td><td style="text-align:right;color:var(--muted)"><?= (int)$r['enr'] ?></td>
            <td style="text-align:right;font-weight:700"><?= $fr ?>%</td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
