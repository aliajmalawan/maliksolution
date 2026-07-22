<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$c  = fee_find($id);
if (!$c) { flash_set('error', 'Challan not found.'); redirect(fee_url('index.php')); }
$page_title = 'Edit Challan'; $active = 'fees';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Edit — <?= e($c['challan_no']) ?></h1>
  <p><a href="<?= fee_url('view.php?id='.$id) ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Challan</a></p></div></div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
