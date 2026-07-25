<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$catSlug = trim((string)($_GET['cat'] ?? ''));
$tagSlug = trim((string)($_GET['tag'] ?? ''));

$categories = $pdo->query(
    'SELECT bc.*, COUNT(b.id) AS post_count FROM blog_categories bc
     LEFT JOIN blogs b ON b.category_id = bc.id AND b.is_published = 1
     GROUP BY bc.id ORDER BY bc.name'
)->fetchAll();

$params = [];
$where = 'b.is_published = 1';
$activeLabel = '';
if ($catSlug !== '') {
    $where .= ' AND bc.slug = ?';
    $params[] = $catSlug;
    foreach ($categories as $cat) {
        if ($cat['slug'] === $catSlug) {
            $activeLabel = $cat['name'];
        }
    }
}
if ($tagSlug !== '') {
    $where .= ' AND EXISTS (SELECT 1 FROM blog_post_tags bpt INNER JOIN blog_tags bt ON bt.id = bpt.tag_id WHERE bpt.post_id = b.id AND bt.slug = ?)';
    $params[] = $tagSlug;
    $activeLabel = '#' . $tagSlug;
}

$stmt = $pdo->prepare(
    "SELECT b.*, bc.name AS cat_name, bc.slug AS cat_slug, a.full_name AS author_name
     FROM blogs b
     LEFT JOIN blog_categories bc ON b.category_id = bc.id
     LEFT JOIN admins a ON b.author_id = a.id
     WHERE $where ORDER BY b.published_at DESC"
);
$stmt->execute($params);
$blogs = $stmt->fetchAll();

$pageTitle = 'Blogs' . ($activeLabel ? ' — ' . $activeLabel : '');
$breadcrumbs = [['label' => 'Blogs']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Blog<?= $activeLabel ? ': ' . e($activeLabel) : 's' ?></h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="gallery-filters">
      <a href="blogs.php" class="filter-link<?= $catSlug === '' && $tagSlug === '' ? ' active' : '' ?>">All Posts</a>
      <?php foreach ($categories as $cat): ?>
        <a href="blogs.php?cat=<?= e($cat['slug']) ?>" class="filter-link<?= $catSlug === $cat['slug'] ? ' active' : '' ?>"><?= e($cat['name']) ?> (<?= (int)$cat['post_count'] ?>)</a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($blogs)): ?>
      <p class="text-center" style="color:var(--text-light);">No blog posts <?= $activeLabel ? 'in this section yet' : 'have been published yet' ?>.</p>
    <?php else: ?>
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
          <p style="font-size:12.5px;color:var(--text-light);margin-bottom:10px;">
            <?= $item['author_name'] ? '✍️ ' . e($item['author_name']) . ' · ' : '' ?><?= e(reading_time($item['body'])) ?> · 👁 <?= (int)$item['views'] ?>
          </p>
          <a href="blog-detail.php?slug=<?= e($item['slug']) ?>" class="read-more">Read More →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
