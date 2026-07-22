<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Add Route'; $active = 'transport';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Add Route</h1>
  <p><a href="<?= transport_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Routes</a></p></div></div>
<?php $r = []; $isEdit = false; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
