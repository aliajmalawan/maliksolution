<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$downloads = $pdo->query('SELECT * FROM downloads ORDER BY category, uploaded_at DESC')->fetchAll();

$byCategory = [];
foreach ($downloads as $item) {
    $byCategory[$item['category']][] = $item;
}

$pageTitle = 'Downloads';
$breadcrumbs = [['label' => 'Downloads']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Downloads</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:900px;">
    <?php if (empty($downloads)): ?>
      <p class="text-center" style="color:var(--text-light);">No files are available for download yet. Please check back soon.</p>
    <?php else: ?>
      <?php foreach ($byCategory as $category => $files): ?>
        <h2 style="margin-top:30px;"><?= e($category) ?></h2>
        <div class="downloads-list">
          <?php foreach ($files as $file): ?>
          <a href="<?= BASE_URL ?>/<?= e($file['file_path']) ?>" class="download-item" download>
            <span class="download-icon">📄</span>
            <span class="download-info">
              <strong><?= e($file['title']) ?></strong>
              <small><?= human_filesize((int)$file['file_size']) ?> · Added <?= format_date($file['uploaded_at']) ?></small>
            </span>
            <span class="download-arrow">⬇</span>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<style>
.downloads-list { display:flex; flex-direction:column; gap:12px; margin-top:16px; }
.download-item { display:flex; align-items:center; gap:16px; background:var(--white); border-radius:var(--radius); padding:18px 22px; box-shadow:var(--shadow); transition:var(--transition); }
.download-item:hover { transform:translateY(-2px); box-shadow:var(--shadow-lg); }
.download-item .download-icon { font-size:26px; }
.download-item .download-info { flex:1; display:flex; flex-direction:column; }
.download-item .download-info small { color:var(--text-light); }
.download-item .download-arrow { font-size:20px; color:var(--primary); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
