<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$d  = dept_find($id);
if (!$d) { flash_set('error', 'Department not found.'); redirect(dept_url('index.php')); }
$page_title = 'Edit Department';
$active     = 'academic';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Edit — <?= e($d['name']) ?></h1>
    <p><a href="<?= dept_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Departments</a></p></div>
</div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
