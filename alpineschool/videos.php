<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$videos = $pdo->query('SELECT * FROM videos ORDER BY sort_order, id')->fetchAll();

$pageTitle = 'Videos';
$breadcrumbs = [['label' => 'Videos']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Videos</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if (empty($videos)): ?>
      <p class="text-center" style="color:var(--text-light);">No videos have been published yet. Check back soon!</p>
    <?php else: ?>
      <div class="videos-grid">
        <?php foreach ($videos as $video): $vid = youtube_id($video['youtube_url']); ?>
          <?php if ($vid === '') continue; ?>
          <div class="video-card">
            <div class="video-embed">
              <iframe src="https://www.youtube.com/embed/<?= e($vid) ?>" title="<?= e($video['title']) ?>" loading="lazy" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
            <h3><?= e($video['title']) ?></h3>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.videos-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; }
.video-card { background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
.video-card h3 { font-size:16px; margin:14px 18px 16px; }
.video-embed { position:relative; padding-top:56.25%; }
.video-embed iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }
@media (max-width: 992px) { .videos-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 640px) { .videos-grid { grid-template-columns:1fr; } }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
