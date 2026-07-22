<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Departments Report';
$active     = 'academic';
$db         = ums_db();
$campus     = (int)$user['campus_id'];

$byStatus = ['active' => 0, 'inactive' => 0];
$s = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('departments') . ' WHERE campus_id=? GROUP BY status');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byStatus[$x['status']] = (int)$x['c'];
$s->close();
$total = array_sum($byStatus);

// Course count per department (if Courses module is in use)
$deptCourses = [];
$hasCourses = (bool)$db->query("SHOW TABLES LIKE '" . tbl('courses') . "'")->num_rows;
if ($hasCourses) {
    $s = $db->prepare('SELECT d.name, COUNT(c.id) n FROM ' . tbl('departments') . ' d
        LEFT JOIN ' . tbl('courses') . ' c ON c.department_id = d.id
        WHERE d.campus_id = ? GROUP BY d.id ORDER BY n DESC, d.name LIMIT 10');
    $s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
    while ($x = $r->fetch_assoc()) $deptCourses[$x['name']] = (int)$x['n'];
    $s->close();
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Departments Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= dept_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-sitemap"></i></span><div><small>Total Departments</small><strong><?= $total ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= $byStatus['active'] ?></strong></div></div>
  <div class="u-chip"><span class="ci" style="background:var(--muted)"><i class="fa-solid fa-circle-pause"></i></span><div><small>Inactive</small><strong><?= $byStatus['inactive'] ?></strong></div></div>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-book-open" style="color:var(--primary)"></i> Courses per Department</h2></div>
  <?php if (!$deptCourses): ?>
    <div class="u-empty"><i class="fa-solid fa-book-open"></i><p><?= $hasCourses ? 'No courses assigned yet.' : 'The Courses module is not in use yet.' ?></p></div>
  <?php else: $mx = max(1, max($deptCourses)); ?>
    <div class="u-prog">
      <?php foreach ($deptCourses as $name => $n): ?>
        <div>
          <div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($name) ?></span><span class="val"><?= $n ?></span></div>
          <div class="u-prog-track"><div class="u-prog-fill" style="width:<?= (int)round($n / $mx * 100) ?>%"></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<style>@media print { .u-side, .u-top, .u-page-head .u-btn, .u-side-backdrop { display:none !important; } .u-main { margin:0 !important; } body { background:#fff; } }</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
