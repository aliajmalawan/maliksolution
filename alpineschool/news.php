<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$news = $pdo->query('SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC')->fetchAll();

$pageTitle = 'News';
$breadcrumbs = [['label' => 'News']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>News &amp; Announcements</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($news)): ?>
      <p class="text-center" style="color:var(--text-light);">No news articles have been published yet.</p>
    <?php else: ?>
    <div class="news-grid">
      <?php foreach ($news as $item): ?>
      <div class="news-card">
        <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
        <div class="news-card-body">
          <span class="news-date"><?= format_date($item['published_at']) ?></span>
          <h3><?= e($item['title']) ?></h3>
          <p><?= e($item['excerpt']) ?></p>
          <a href="news-detail.php?slug=<?= e($item['slug']) ?>" class="read-more">Read More →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
