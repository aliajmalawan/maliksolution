<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$x  = exam_find($id);
if (!$x) { flash_set('error', 'Exam not found.'); redirect(exam_url('index.php')); }
$page_title = 'Edit Exam'; $active = 'exams';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Edit — <?= e($x['title']) ?></h1>
  <p><a href="<?= exam_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Exams</a></p></div></div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
