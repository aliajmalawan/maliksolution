<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$s  = sec_find($id);
if (!$s) { flash_set('error', 'Section not found.'); redirect(sec_url('index.php')); }
$page_title = 'Edit Section'; $active = 'classes';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Edit — <?= e(sec_label($s)) ?></h1>
  <p><a href="<?= sec_url('view.php?id='.$id) ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Section</a></p></div></div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
