<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Students Report'; $active = 'students';
$db = ums_db(); $campus = (int)$user['campus_id'];

$byStatus = ['active' => 0, 'graduated' => 0, 'suspended' => 0, 'dropped' => 0];
$s = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('students') . ' WHERE campus_id=? GROUP BY status');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byStatus[$x['status']] = (int)$x['c'];
$s->close();
$total = array_sum($byStatus);

$bySem = array_fill(1, STU_SEMESTERS, 0);
$s = $db->prepare('SELECT current_semester, COUNT(*) c FROM ' . tbl('students') . ' WHERE campus_id=? GROUP BY current_semester');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) if (isset($bySem[(int)$x['current_semester']])) $bySem[(int)$x['current_semester']] = (int)$x['c'];
$s->close();

$byProgram = [];
$s = $db->prepare('SELECT COALESCE(NULLIF(program,""),"Unassigned") program, COUNT(*) c FROM ' . tbl('students') . ' WHERE campus_id=? GROUP BY program ORDER BY c DESC LIMIT 10');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byProgram[$x['program']] = (int)$x['c'];
$s->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Students Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= stu_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>
<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-user-graduate"></i></span><div><small>Total Students</small><strong><?= $total ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= $byStatus['active'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-user-graduate"></i></span><div><small>Graduated</small><strong><?= $byStatus['graduated'] ?></strong></div></div>
</div>
<div class="u-grid g-two" style="margin-bottom:1.1rem">
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-chart-column" style="color:var(--primary)"></i> Students by Semester</h2></div>
    <div class="u-chart"><canvas id="chSem"></canvas></div></div>
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-graduation-cap" style="color:var(--primary)"></i> By Program</h2></div>
    <?php if (!$byProgram): ?><div class="u-empty"><i class="fa-solid fa-graduation-cap"></i><p>No students yet.</p></div>
    <?php else: $mx=max(1,max($byProgram)); ?><div class="u-prog">
      <?php foreach ($byProgram as $name=>$c): ?><div>
        <div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($name) ?></span><span class="val"><?= $c ?></span></div>
        <div class="u-prog-track"><div class="u-prog-fill" style="width:<?= (int)round($c/$mx*100) ?>%"></div></div></div><?php endforeach; ?>
    </div><?php endif; ?>
  </div>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php
$semData = json_encode(array_values($bySem));
$page_scripts = <<<JS
<script>
(function(){ var ch;
  function pal(){var s=getComputedStyle(document.documentElement);return{ink:s.getPropertyValue('--muted').trim(),line:s.getPropertyValue('--line').trim(),primary:'#6366f1'};}
  function build(){ if(ch)ch.destroy(); var p=pal(); Chart.defaults.color=p.ink; Chart.defaults.borderColor=p.line; Chart.defaults.font.family='Inter, sans-serif';
    ch=new Chart(document.getElementById('chSem'),{type:'bar',
      data:{labels:['Sem 1','Sem 2','Sem 3','Sem 4','Sem 5','Sem 6','Sem 7','Sem 8'],datasets:[{label:'Students',data:$semData,backgroundColor:p.primary,borderRadius:6,maxBarThickness:34}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{grid:{color:p.line},ticks:{precision:0}},x:{grid:{display:false}}}}});
  }
  build(); document.addEventListener('ums:theme', build);
})();
</script>
JS;
require __DIR__ . '/../../includes/footer.php';
