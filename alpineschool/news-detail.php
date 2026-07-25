<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = $pdo->prepare('SELECT * FROM news WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
}

$related = $pdo->prepare('SELECT * FROM news WHERE slug != ? AND is_published = 1 ORDER BY published_at DESC LIMIT 3');
$related->execute([$slug]);
$relatedNews = $related->fetchAll();

$pageTitle = $item['title'] ?? 'News Not Found';
$pageDescription = $item['excerpt'] ?? '';

if ($item) {
    $seoImage = $item['image_path'];
    $seoType = 'article';
    $seoArticle = ['published' => $item['published_at'], 'section' => 'News'];
    $breadcrumbs = [['label' => 'News', 'url' => 'news.php'], ['label' => $item['title']]];
} else {
    $breadcrumbs = [['label' => 'News', 'url' => 'news.php'], ['label' => 'Not Found']];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><?= e($item['title'] ?? 'Article Not Found') ?></h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:820px;">
    <?php if ($item): ?>
      <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" style="border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:30px;">
      <span class="news-date"><?= format_date($item['published_at']) ?></span>
      <div style="margin-top:14px;"><?= $item['body'] ?></div>
    <?php else: ?>
      <p>Sorry, this news article could not be found.</p>
      <a href="news.php" class="btn btn-dark">Back to News</a>
    <?php endif; ?>

    <?php if (!empty($relatedNews)): ?>
    <div class="mt-40">
      <h3>More News</h3>
      <div class="news-grid">
        <?php foreach ($relatedNews as $rel): ?>
        <div class="news-card">
          <img src="<?= BASE_URL ?>/<?= e($rel['image_path']) ?>" alt="<?= e($rel['title']) ?>" loading="lazy">
          <div class="news-card-body">
            <span class="news-date"><?= format_date($rel['published_at']) ?></span>
            <h3><?= e($rel['title']) ?></h3>
            <a href="news-detail.php?slug=<?= e($rel['slug']) ?>" class="read-more">Read More →</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
