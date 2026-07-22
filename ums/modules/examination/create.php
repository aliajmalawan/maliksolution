<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'New Exam'; $active = 'exams';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>New Exam</h1>
  <p><a href="<?= exam_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Exams</a></p></div></div>
<?php $x = []; $isEdit = false; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
