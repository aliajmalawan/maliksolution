<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Teachers Report'; $active = 'teachers';
$db = ums_db(); $campus = (int)$user['campus_id'];

$agg = $db->prepare('SELECT COUNT(*) n, COALESCE(SUM(salary),0) sal, SUM(status="active") act FROM ' . tbl('teachers') . ' WHERE campus_id=?');
$agg->bind_param('i', $campus); $agg->execute(); $a = $agg->get_result()->fetch_assoc(); $agg->close();

$byDesig = [];
$s = $db->prepare('SELECT designation, COUNT(*) c FROM ' . tbl('teachers') . ' WHERE campus_id=? GROUP BY designation ORDER BY c DESC');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byDesig[$x['designation']] = (int)$x['c'];
$s->close();

$byDept = [];
$s = $db->prepare('SELECT COALESCE(d.name,"Unassigned") name, COUNT(t.id) c FROM ' . tbl('teachers') . ' t
    LEFT JOIN ' . tbl('departments') . ' d ON d.id=t.department_id WHERE t.campus_id=? GROUP BY t.department_id ORDER BY c DESC');
$s->bind_param('i', $campus); $s->execute(); $r = $s->get_result();
while ($x = $r->fetch_assoc()) $byDept[$x['name']] = (int)$x['c'];
$s->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Teachers Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= tch_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>
<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-person-chalkboard"></i></span><div><small>Total Faculty</small><strong><?= (int)$a['n'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= (int)$a['act'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-money-bill-wave"></i></span><div><small>Monthly Salary Total</small><strong>Rs <?= number_format((float)$a['sal']) ?></strong></div></div>
</div>
<div class="u-grid g-two" style="margin-bottom:1.1rem">
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-chart-pie" style="color:var(--primary)"></i> By Designation</h2></div>
    <div class="u-chart"><canvas id="chDesig"></canvas></div></div>
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-sitemap" style="color:var(--primary)"></i> By Department</h2></div>
    <?php if (!$byDept): ?><div class="u-empty"><i class="fa-solid fa-sitemap"></i><p>No data yet.</p></div>
    <?php else: $mx=max(1,max($byDept)); ?><div class="u-prog">
      <?php foreach ($byDept as $name=>$c): ?><div>
        <div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($name) ?></span><span class="val"><?= $c ?></span></div>
        <div class="u-prog-track"><div class="u-prog-fill" style="width:<?= (int)round($c/$mx*100) ?>%"></div></div></div><?php endforeach; ?>
    </div><?php endif; ?>
  </div>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php
$dLabels = json_encode(array_keys($byDesig) ?: ['—']);
$dData = json_encode(array_values($byDesig) ?: [0]);
$page_scripts = <<<JS
<script>
(function(){ var ch;
  function pal(){var s=getComputedStyle(document.documentElement);return{ink:s.getPropertyValue('--muted').trim(),line:s.getPropertyValue('--line').trim()};}
  function build(){ if(ch)ch.destroy(); var p=pal(); Chart.defaults.color=p.ink; Chart.defaults.borderColor=p.line; Chart.defaults.font.family='Inter, sans-serif';
    ch=new Chart(document.getElementById('chDesig'),{type:'doughnut',
      data:{labels:$dLabels,datasets:[{data:$dData,backgroundColor:['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444'],borderWidth:0,hoverOffset:6}]},
      options:{responsive:true,maintainAspectRatio:false,cutout:'62%',plugins:{legend:{position:'bottom',labels:{boxWidth:11,boxHeight:11,borderRadius:3,useBorderRadius:true,font:{size:10}}}}}});
  }
  build(); document.addEventListener('ums:theme', build);
})();
</script>
JS;
require __DIR__ . '/../../includes/footer.php';
