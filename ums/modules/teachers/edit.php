<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$t  = tch_find($id);
if (!$t) { flash_set('error', 'Teacher not found.'); redirect(tch_url('index.php')); }
$page_title = 'Edit Teacher'; $active = 'teachers';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Edit — <?= e($t['name']) ?></h1>
  <p><a href="<?= tch_url('view.php?id='.$id) ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a></p></div></div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
