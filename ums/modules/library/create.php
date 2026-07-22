<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Add Book'; $active = 'library';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Add Book</h1>
  <p><a href="<?= lib_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Catalog</a></p></div></div>
<?php $b = []; $isEdit = false; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
