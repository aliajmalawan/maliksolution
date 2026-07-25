<?php
$statsStyle = '';
if (($S['bg_type'] ?? '') === 'color' && !empty($S['bg_color'])) {
    $statsStyle = 'background:' . e($S['bg_color']) . ';';
}
?>
<div class="stats-bar" style="<?= $statsStyle ?>">
  <div class="container stats-grid">
    <div class="stat-item"><strong data-count-to="<?= e((string)(int)$statsStudents) ?>" data-count-suffix="+"><?= e($statsStudents) ?>+</strong><span>Students</span></div>
    <div class="stat-item"><strong data-count-to="<?= e((string)(int)$statsFaculty) ?>" data-count-suffix="+"><?= e($statsFaculty) ?>+</strong><span>Faculty Members</span></div>
    <div class="stat-item"><strong data-count-to="<?= e((string)(int)$statsYears) ?>" data-count-suffix="+"><?= e($statsYears) ?>+</strong><span>Years of Excellence</span></div>
    <div class="stat-item"><strong data-count-to="<?= e((string)(int)$statsResults) ?>" data-count-suffix="%"><?= e($statsResults) ?>%</strong><span>Result Rate</span></div>
  </div>
</div>
