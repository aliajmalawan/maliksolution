<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$b  = book_find($id);
if (!$b) { flash_set('error', 'Book not found.'); redirect(lib_url('index.php')); }
$page_title = 'Edit Book'; $active = 'library';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Edit — <?= e($b['title']) ?></h1>
  <p><a href="<?= lib_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Catalog</a></p></div></div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
