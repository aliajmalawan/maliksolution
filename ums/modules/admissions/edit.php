<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id  = (int)($_GET['id'] ?? 0);
$adm = adm_find($id);
if (!$adm) { flash_set('error', 'Application not found.'); redirect(adm_url('index.php')); }

$page_title = 'Edit Application';
$active     = 'admissions';

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div>
    <h1>Edit — <?= e($adm['application_no']) ?></h1>
    <p><a href="<?= adm_url('view.php?id=' . $id) ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Application</a></p>
  </div>
</div>

<?php
$isEdit = true;
require __DIR__ . '/_form.php';

require __DIR__ . '/../../includes/footer.php';
