<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$perPage = 12;
$pg = max(1, (int)($_GET['pg'] ?? 1));
$tab = ($_GET['tab'] ?? 'photos') === 'videos' ? 'videos' : 'photos';
$catSlug = trim((string)($_GET['cat'] ?? ''));

$categories = $pdo->query('SELECT * FROM gallery_categories ORDER BY name')->fetchAll();

$activeCat = null;
foreach ($categories as $cat) {
    if ($cat['slug'] === $catSlug) {
        $activeCat = $cat;
        break;
    }
}

// ---------- Album detail view ----------
$albumView = null;
if (isset($_GET['album'])) {
    $stmt = $pdo->prepare('SELECT ga.*, gc.name AS cat_name FROM gallery_albums ga LEFT JOIN gallery_categories gc ON ga.category_id = gc.id WHERE ga.slug = ?');
    $stmt->execute([(string)$_GET['album']]);
    $albumView = $stmt->fetch() ?: null;
}

if ($albumView) {
    $countStmt = $pdo->prepare('SELECT COUNT(*) c FROM gallery_images WHERE album_id = ?');
    $countStmt->execute([(int)$albumView['id']]);
    $total = (int)$countStmt->fetch()['c'];
    $pages = max(1, (int)ceil($total / $perPage));
    $pg = min($pg, $pages);
    $offset = ($pg - 1) * $perPage;
    $imgStmt = $pdo->prepare("SELECT * FROM gallery_images WHERE album_id = ? ORDER BY sort_order, id LIMIT $perPage OFFSET $offset");
    $imgStmt->execute([(int)$albumView['id']]);
    $images = $imgStmt->fetchAll();
    $pageTitle = $albumView['name'];
} elseif ($tab === 'videos') {
    $total = (int)$pdo->query('SELECT COUNT(*) c FROM videos')->fetch()['c'];
    $pages = max(1, (int)ceil($total / $perPage));
    $pg = min($pg, $pages);
    $offset = ($pg - 1) * $perPage;
    $videos = $pdo->query("SELECT * FROM videos ORDER BY sort_order, id LIMIT $perPage OFFSET $offset")->fetchAll();
    $pageTitle = 'Video Gallery';
} else {
    // Albums (filtered), then loose/all photos (filtered) paginated
    if ($activeCat) {
        $albumStmt = $pdo->prepare(
            'SELECT ga.*, COUNT(gi.id) AS photo_count,
                    (SELECT image_path FROM gallery_images WHERE album_id = ga.id ORDER BY sort_order, id LIMIT 1) AS cover
             FROM gallery_albums ga LEFT JOIN gallery_images gi ON gi.album_id = ga.id
             WHERE ga.category_id = ? GROUP BY ga.id ORDER BY ga.created_at DESC'
        );
        $albumStmt->execute([(int)$activeCat['id']]);
    } else {
        $albumStmt = $pdo->query(
            'SELECT ga.*, COUNT(gi.id) AS photo_count,
                    (SELECT image_path FROM gallery_images WHERE album_id = ga.id ORDER BY sort_order, id LIMIT 1) AS cover
             FROM gallery_albums ga LEFT JOIN gallery_images gi ON gi.album_id = ga.id
             GROUP BY ga.id ORDER BY ga.created_at DESC'
        );
    }
    $albums = $albumStmt->fetchAll();

    if ($activeCat) {
        $countStmt = $pdo->prepare('SELECT COUNT(*) c FROM gallery_images WHERE category_id = ?');
        $countStmt->execute([(int)$activeCat['id']]);
        $total = (int)$countStmt->fetch()['c'];
    } else {
        $total = (int)$pdo->query('SELECT COUNT(*) c FROM gallery_images')->fetch()['c'];
    }
    $pages = max(1, (int)ceil($total / $perPage));
    $pg = min($pg, $pages);
    $offset = ($pg - 1) * $perPage;
    if ($activeCat) {
        $imgStmt = $pdo->prepare("SELECT * FROM gallery_images WHERE category_id = ? ORDER BY sort_order, id DESC LIMIT $perPage OFFSET $offset");
        $imgStmt->execute([(int)$activeCat['id']]);
    } else {
        $imgStmt = $pdo->query("SELECT * FROM gallery_images ORDER BY sort_order, id DESC LIMIT $perPage OFFSET $offset");
    }
    $images = $imgStmt->fetchAll();
    $pageTitle = 'Gallery';
}

/** Pagination links preserving current filters. */
function gallery_page_url(int $page): string
{
    $params = $_GET;
    $params['pg'] = $page;
    return 'gallery.php?' . http_build_query($params);
}

if ($albumView) {
    $breadcrumbs = [['label' => 'Gallery', 'url' => 'gallery.php'], ['label' => $albumView['name']]];
    $pageDescription = $albumView['description'] ?: ('Photos from ' . $albumView['name'] . ' at ' . get_setting($pdo, 'site_name') . '.');
    if (!empty($images[0]['image_path'])) {
        $seoImage = $images[0]['image_path'];
    }
} elseif ($tab === 'videos') {
    $breadcrumbs = [['label' => 'Gallery', 'url' => 'gallery.php'], ['label' => 'Videos']];
} else {
    $breadcrumbs = [['label' => 'Gallery']];
    if ($activeCat) {
        $breadcrumbs = [['label' => 'Gallery', 'url' => 'gallery.php'], ['label' => $activeCat['name']]];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><?= e($albumView['name'] ?? ($tab === 'videos' ? 'Video Gallery' : 'Photo Gallery')) ?></h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">

    <?php if ($albumView): ?>
      <?php if ($albumView['description']): ?><p style="color:var(--text-light);max-width:640px;margin-bottom:8px;"><?= e($albumView['description']) ?></p><?php endif; ?>
      <p class="form-hint" style="margin-bottom:24px;"><a href="gallery.php" style="color:var(--primary);font-weight:600;">← All Albums</a> · <?= $total ?> photo<?= $total === 1 ? '' : 's' ?><?= $albumView['cat_name'] ? ' · ' . e($albumView['cat_name']) : '' ?></p>

      <?php if (empty($images)): ?>
        <p class="text-center" style="color:var(--text-light);">This album is empty.</p>
      <?php else: ?>
      <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
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
      <?php endif; ?>

    <?php elseif ($tab === 'videos'): ?>
      <div class="gallery-filters">
        <a href="gallery.php" class="filter-link">📷 Photos</a>
        <a href="gallery.php?tab=videos" class="filter-link active">🎬 Videos</a>
      </div>

      <?php if (empty($videos)): ?>
        <p class="text-center" style="color:var(--text-light);">No videos have been published yet.</p>
      <?php else: ?>
      <div class="gallery-grid">
        <?php foreach ($videos as $video): $vid = youtube_id($video['youtube_url']); ?>
          <?php if ($vid === '') continue; ?>
          <div class="gallery-item lb-item video-thumb" role="button" tabindex="0"
               aria-label="Play video: <?= e($video['title']) ?>"
               data-lb-type="video" data-lb-src="https://www.youtube.com/embed/<?= e($vid) ?>?autoplay=1"
               data-lb-caption="<?= e($video['title']) ?>">
            <img src="https://img.youtube.com/vi/<?= e($vid) ?>/hqdefault.jpg" alt="" loading="lazy" decoding="async">
            <span class="play-badge" aria-hidden="true">▶</span>
            <div class="caption"><?= e($video['title']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="gallery-filters">
        <a href="gallery.php" class="filter-link<?= !$activeCat ? ' active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
          <a href="gallery.php?cat=<?= e($cat['slug']) ?>" class="filter-link<?= $activeCat && $activeCat['id'] === $cat['id'] ? ' active' : '' ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
        <a href="gallery.php?tab=videos" class="filter-link">🎬 Videos</a>
      </div>

      <?php if (!empty($albums)): ?>
      <h2 style="font-size:22px;margin-bottom:18px;">Albums</h2>
      <div class="albums-grid">
        <?php foreach ($albums as $album): ?>
        <a href="gallery.php?album=<?= e($album['slug']) ?>" class="album-card">
          <?php if ($album['cover']): ?>
            <img src="<?= BASE_URL ?>/<?= e($album['cover']) ?>" alt="<?= e($album['name']) ?>" loading="lazy" decoding="async"<?= img_dimensions($album['cover']) ?>>
          <?php else: ?>
            <div class="album-cover-empty">📁</div>
          <?php endif; ?>
          <div class="album-card-body">
            <strong><?= e($album['name']) ?></strong>
            <small><?= (int)$album['photo_count'] ?> photo<?= (int)$album['photo_count'] === 1 ? '' : 's' ?></small>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <h2 style="font-size:22px;margin:36px 0 18px;"><?= $activeCat ? e($activeCat['name']) . ' Photos' : 'All Photos' ?></h2>
      <?php if (empty($images)): ?>
        <p class="text-center" style="color:var(--text-light);">No photos in this category yet.</p>
      <?php else: ?>
      <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
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
      <?php endif; ?>
    <?php endif; ?>

    <?php if (($pages ?? 1) > 1): ?>
    <div class="pagination">
      <?php if ($pg > 1): ?><a href="<?= e(gallery_page_url($pg - 1)) ?>" class="page-link">← Prev</a><?php endif; ?>
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= e(gallery_page_url($i)) ?>" class="page-link<?= $i === $pg ? ' active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($pg < $pages): ?><a href="<?= e(gallery_page_url($pg + 1)) ?>" class="page-link">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-labelledby="lbCaption">
  <button type="button" class="lb-close" id="lbClose" aria-label="Close (Escape)"><span aria-hidden="true">✕</span></button>
  <button type="button" class="lb-nav lb-prev" id="lbPrev" aria-label="Previous item (Left arrow)"><span aria-hidden="true">‹</span></button>
  <div class="lb-content" id="lbContent"></div>
  <button type="button" class="lb-nav lb-next" id="lbNext" aria-label="Next item (Right arrow)"><span aria-hidden="true">›</span></button>
  <p class="lb-caption" id="lbCaption" role="status"></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
