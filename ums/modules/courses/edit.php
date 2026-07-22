<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$c  = crs_find($id);
if (!$c) { flash_set('error', 'Course not found.'); redirect(crs_url('index.php')); }
$page_title = 'Edit Course';
$active     = 'courses';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Edit — <?= e($c['title']) ?></h1>
    <p><a href="<?= crs_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Courses</a></p></div>
</div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
