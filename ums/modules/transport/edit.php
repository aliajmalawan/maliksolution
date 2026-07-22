<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$r  = transport_route_find($id);
if (!$r) { flash_set('error', 'Route not found.'); redirect(transport_url('index.php')); }
$page_title = 'Edit Route'; $active = 'transport';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>Edit — <?= e($r['route_name']) ?></h1>
  <p><a href="<?= transport_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Routes</a></p></div></div>
<?php $isEdit = true; require __DIR__ . '/_form.php';
require __DIR__ . '/../../includes/footer.php';
