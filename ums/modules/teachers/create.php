<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Add Teacher'; $active = 'teachers';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Add Teacher</h1>
  <p><a href="<?= tch_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Teachers</a></p></div></div>
<?php $t = []; $isEdit = false; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
