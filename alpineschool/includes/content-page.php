<?php
declare(strict_types=1);
// Generic renderer for a simple `pages`-table page.
// Expects $contentSlug (and optionally $bannerCrumb) to be set by the including file.

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/page-sections.php';

$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ?');
$stmt->execute([$contentSlug]);
$contentPage = $stmt->fetch();

if (!$contentPage) {
    http_response_code(404);
}

$pageSections = $contentPage ? page_sections_for($pdo, (int)$contentPage['id']) : [];

$pageTitle = $contentPage['title'] ?? 'Page Not Found';
$pageDescription = $contentPage['meta_description'] ?? '';
$breadcrumbs = [['label' => $bannerCrumb ?? ($contentPage['title'] ?? 'Page')]];
require_once __DIR__ . '/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><?= e($contentPage['title'] ?? 'Page Not Found') ?></h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<?php if ($pageSections): ?>
  <?php // Section-built page (managed in Admin → Pages → Sections); backgrounds alternate.
  foreach ($pageSections as $i => $sec) {
      render_page_section($pdo, $sec, $i % 2 === 1);
  } ?>
<?php else: ?>
<section class="section">
  <div class="container" style="max-width:820px;">
    <?php if ($contentPage): ?>
      <?= $contentPage['body'] ?>
    <?php else: ?>
      <p>Sorry, this page could not be found.</p>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-dark">Back to Home</a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
