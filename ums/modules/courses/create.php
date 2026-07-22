<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'New Course';
$active     = 'courses';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>New Course</h1>
    <p><a href="<?= crs_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Courses</a></p></div>
</div>
<?php $c = []; $isEdit = false; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
