<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'New Section'; $active = 'classes';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>New Section</h1>
  <p><a href="<?= sec_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Sections</a></p></div></div>
<?php $s = []; $isEdit = false; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
