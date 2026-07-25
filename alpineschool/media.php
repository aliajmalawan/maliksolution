<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

// Hub tile counts
$newsCount  = (int)$pdo->query('SELECT COUNT(*) c FROM news WHERE is_published = 1')->fetch()['c'];
$photoCount = (int)$pdo->query('SELECT COUNT(*) c FROM gallery_images')->fetch()['c'];
$videoCount = (int)$pdo->query('SELECT COUNT(*) c FROM videos')->fetch()['c'];
$blogCount  = (int)$pdo->query('SELECT COUNT(*) c FROM blogs WHERE is_published = 1')->fetch()['c'];

// Latest content per section
$news   = $pdo->query('SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3')->fetchAll();
$photos = $pdo->query('SELECT * FROM gallery_images ORDER BY id DESC LIMIT 8')->fetchAll();
$videos = $pdo->query('SELECT * FROM videos ORDER BY sort_order, id LIMIT 3')->fetchAll();
$blogs  = $pdo->query(
    'SELECT b.*, bc.name AS cat_name, bc.slug AS cat_slug
     FROM blogs b LEFT JOIN blog_categories bc ON b.category_id = bc.id
     WHERE b.is_published = 1 ORDER BY b.published_at DESC LIMIT 3'
)->fetchAll();

$pageTitle = 'Media Centre';
$pageDescription = 'News, photos, videos and stories from ' . get_setting($pdo, 'site_name') . ' — ' . get_setting($pdo, 'campus_name') . '.';
$breadcrumbs = [['label' => 'Media']];
require_once __DIR__ . '/includes/header.php';

$tiles = [
    ['url' => 'news.php',               'icon' => '📰', 'title' => 'News & Announcements', 'count' => $newsCount,  'unit' => 'article'],
    ['url' => 'gallery.php',            'icon' => '🖼️', 'title' => 'Photo Gallery',         'count' => $photoCount, 'unit' => 'photo'],
    ['url' => 'gallery.php?tab=videos', 'icon' => '🎬', 'title' => 'Videos',                'count' => $videoCount, 'unit' => 'video'],
    ['url' => 'blogs.php',              'icon' => '✍️', 'title' => 'Blog',                  'count' => $blogCount,  'unit' => 'post'],
];
?>

<div class="page-banner">
  <div class="container">
    <h1>Media Centre</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<!-- Hub tiles -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Media Centre</span>
      <h2>Explore Our Media</h2>
      <p>The latest happenings at our campus — announcements, photo albums, videos and stories in one place.</p>
    </div>
    <div class="media-tiles">
      <?php foreach ($tiles as $tile): ?>
      <a href="<?= e($tile['url']) ?>" class="media-tile">
        <span class="media-tile-icon" aria-hidden="true"><?= $tile['icon'] ?></span>
        <strong><?= e($tile['title']) ?></strong>
        <small><?= (int)$tile['count'] ?> <?= e($tile['unit']) ?><?= (int)$tile['count'] === 1 ? '' : 's' ?></small>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!empty($news)): ?>
<!-- Latest news -->
<section class="section" style="background:var(--bg);">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Newsroom</span>
      <h2>Latest News</h2>
    </div>
    <div class="news-grid">
      <?php foreach ($news as $item): ?>
      <div class="news-card">
        <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e(media_alt($pdo, $item['image_path'], $item['title'])) ?>" loading="lazy">
        <div class="news-card-body">
          <span class="news-date"><?= format_date($item['published_at']) ?></span>
          <h3><?= e($item['title']) ?></h3>
          <p><?= e($item['excerpt']) ?></p>
          <a href="news-detail.php?slug=<?= e($item['slug']) ?>" class="read-more">Read More →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="media-view-all"><a href="news.php" class="btn btn-dark btn-sm">View All News</a></p>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($photos)): ?>
<!-- Photo highlights -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Gallery</span>
      <h2>Photo Highlights</h2>
    </div>
    <div class="gallery-grid">
      <?php foreach ($photos as $img): ?>
      <?php $imgAlt = media_alt($pdo, $img['image_path'], (string)$img['caption']); ?>
      <div class="gallery-item lb-item" role="button" tabindex="0"
           aria-label="View larger: <?= e($imgAlt ?: 'photo') ?>"
           data-lb-type="image" data-lb-src="<?= BASE_URL ?>/<?= e($img['image_path']) ?>"
           data-lb-caption="<?= e($img['caption']) ?>" data-lb-alt="<?= e($imgAlt) ?>">
        <img src="<?= BASE_URL ?>/<?= e($img['image_path']) ?>" alt="<?= e($imgAlt) ?>" loading="lazy" decoding="async">
        <?php if ($img['caption']): ?><div class="caption"><?= e($img['caption']) ?></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="media-view-all"><a href="gallery.php" class="btn btn-dark btn-sm">Browse Full Gallery</a></p>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($videos)): ?>
<!-- Videos -->
<section class="section" style="background:var(--bg);">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Watch</span>
      <h2>Latest Videos</h2>
    </div>
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
    <p class="media-view-all"><a href="videos.php" class="btn btn-dark btn-sm">View All Videos</a></p>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($blogs)): ?>
<!-- Blog -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Stories</span>
      <h2>From the Blog</h2>
    </div>
    <div class="news-grid">
      <?php foreach ($blogs as $item): ?>
      <div class="news-card">
        <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e(media_alt($pdo, $item['image_path'], $item['title'])) ?>" loading="lazy">
        <div class="news-card-body">
          <span class="news-date">
            <?= format_date($item['published_at']) ?>
            <?php if ($item['cat_name']): ?> · <a href="blogs.php?cat=<?= e($item['cat_slug']) ?>" style="color:var(--primary);font-weight:600;"><?= e($item['cat_name']) ?></a><?php endif; ?>
          </span>
          <h3><?= e($item['title']) ?></h3>
          <p><?= e($item['excerpt']) ?></p>
          <a href="blog-detail.php?slug=<?= e($item['slug']) ?>" class="read-more">Read More →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="media-view-all"><a href="blogs.php" class="btn btn-dark btn-sm">View All Posts</a></p>
  </div>
</section>
<?php endif; ?>

<?php if (empty($news) && empty($photos) && empty($videos) && empty($blogs)): ?>
<section class="section">
  <div class="container">
    <p class="text-center" style="color:var(--text-light);">No media has been published yet. Check back soon!</p>
  </div>
</section>
<?php endif; ?>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-labelledby="lbCaption">
  <button type="button" class="lb-close" id="lbClose" aria-label="Close (Escape)"><span aria-hidden="true">✕</span></button>
  <button type="button" class="lb-nav lb-prev" id="lbPrev" aria-label="Previous item (Left arrow)"><span aria-hidden="true">‹</span></button>
  <div class="lb-content" id="lbContent"></div>
  <button type="button" class="lb-nav lb-next" id="lbNext" aria-label="Next item (Right arrow)"><span aria-hidden="true">›</span></button>
  <p class="lb-caption" id="lbCaption" role="status"></p>
</div>

<style>
.media-tiles { display:grid; grid-template-columns:repeat(4, 1fr); gap:24px; }
.media-tile {
  display:flex; flex-direction:column; align-items:center; gap:6px; text-align:center;
  background:var(--white); border:1px solid var(--border); border-radius:var(--radius);
  box-shadow:var(--shadow); padding:30px 20px; transition:var(--transition);
}
.media-tile:hover { transform:translateY(-4px); box-shadow:var(--shadow-lg); border-color:var(--primary); }
.media-tile-icon { font-size:34px; line-height:1; margin-bottom:6px; }
.media-tile strong { font-family:var(--font-heading); font-size:16px; color:var(--primary-dark); }
.media-tile small { color:var(--text-light); font-size:13px; }
.media-view-all { text-align:center; margin-top:36px; }
.videos-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:24px; }
.video-card { background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
.video-card h3 { font-size:16px; margin:14px 18px 16px; }
.video-embed { position:relative; padding-top:56.25%; }
.video-embed iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }
@media (max-width: 992px) {
  .media-tiles { grid-template-columns:repeat(2, 1fr); }
  .videos-grid { grid-template-columns:repeat(2, 1fr); }
}
@media (max-width: 640px) {
  .media-tiles { grid-template-columns:1fr; }
  .videos-grid { grid-template-columns:1fr; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
