<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Results Report'; $active = 'results';
$db = ums_db(); $campus = (int)$user['campus_id'];
$sections = results_section_options($campus);

$secId = (int)($_GET['section'] ?? 0);
if ($secId && !isset($sections[$secId])) $secId = 0;

$gradeDist = [];   // grade => count
$toppers = [];     // [name, reg, gpa]
$avgGpa = 0.0; $counted = 0;

if ($secId) {
    $data = results_section($db, $campus, $secId);
    foreach (['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F'] as $g) $gradeDist[$g] = 0;
    foreach ($data['students'] as $s) {
        foreach ($s['results'] as $res) if (isset($gradeDist[$res['grade']])) $gradeDist[$res['grade']]++;
        if ($s['credits'] > 0) { $toppers[] = ['name' => $s['name'], 'reg' => $s['reg'], 'gpa' => $s['gpa']]; $avgGpa += $s['gpa']; $counted++; }
    }
    usort($toppers, fn($a, $b) => $b['gpa'] <=> $a['gpa']);
    $toppers = array_slice($toppers, 0, 10);
    $avgGpa = $counted > 0 ? round($avgGpa / $counted, 2) : 0.0;
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Results Report</h1><p>Grade distribution &amp; top performers</p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= results_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <?php if ($secId): ?><button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button><?php endif; ?>
  </div>
</div>

<div class="u-card no-print" style="margin-bottom:1.1rem">
  <form method="get" class="att-picker">
    <div class="u-fld"><label>Section</label>
      <select name="section" class="u-select" onchange="this.form.submit()"><option value="0">— Select section —</option>
        <?php foreach ($sections as $id => $label): ?><option value="<?= $id ?>" <?= $secId === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="u-fld"><button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-chart-pie"></i> Generate</button></div>
  </form>
</div>

<?php if ($secId && array_sum($gradeDist) > 0): ?>
  <div class="u-chips">
    <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-award"></i></span><div><small>Students Graded</small><strong><?= $counted ?></strong></div></div>
    <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-chart-line"></i></span><div><small>Average GPA</small><strong><?= number_format($avgGpa, 2) ?></strong></div></div>
    <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-star"></i></span><div><small>Top GPA</small><strong><?= $toppers ? number_format($toppers[0]['gpa'], 2) : '—' ?></strong></div></div>
  </div>
  <div class="u-grid g-two">
    <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-chart-column" style="color:var(--primary)"></i> Grade Distribution</h2></div>
      <div class="u-chart"><canvas id="chGrades"></canvas></div></div>
    <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-trophy" style="color:var(--primary)"></i> Top Performers</h2></div>
      <div class="u-list">
        <?php foreach ($toppers as $i => $t): ?>
          <div class="u-list-item">
            <span class="u-mini-av" style="<?= $i === 0 ? 'background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff' : '' ?>"><?= $i + 1 ?></span>
            <div><div class="nm"><?= e($t['name']) ?></div><div class="sub"><?= e($t['reg']) ?></div></div>
            <div class="meta"><span class="tag" style="background:rgba(16,185,129,.12);color:var(--success)">GPA <?= number_format($t['gpa'], 2) ?></span></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php
  $gLabels = json_encode(array_keys($gradeDist));
  $gData = json_encode(array_values($gradeDist));
  $page_scripts = <<<JS
  <script>
  (function(){ var ch;
    function pal(){var s=getComputedStyle(document.documentElement);return{ink:s.getPropertyValue('--muted').trim(),line:s.getPropertyValue('--line').trim()};}
    function build(){ if(ch)ch.destroy(); var p=pal(); Chart.defaults.color=p.ink; Chart.defaults.borderColor=p.line; Chart.defaults.font.family='Inter, sans-serif';
      ch=new Chart(document.getElementById('chGrades'),{type:'bar',
        data:{labels:$gLabels,datasets:[{label:'Grades',data:$gData,backgroundColor:'#6366f1',borderRadius:6,maxBarThickness:34}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{grid:{color:p.line},ticks:{precision:0}},x:{grid:{display:false}}}}});
    }
    build(); document.addEventListener('ums:theme', build);
  })();
  </script>
  JS;
  ?>
<?php elseif ($secId): ?>
  <div class="u-card"><div class="u-empty"><i class="fa-solid fa-square-poll-vertical"></i><p>No graded results for this section yet.</p></div></div>
<?php endif; ?>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.no-print,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
