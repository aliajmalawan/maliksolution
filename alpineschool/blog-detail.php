<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = $pdo->prepare(
    'SELECT b.*, bc.name AS cat_name, bc.slug AS cat_slug, a.full_name AS author_name
     FROM blogs b
     LEFT JOIN blog_categories bc ON b.category_id = bc.id
     LEFT JOIN admins a ON b.author_id = a.id
     WHERE b.slug = ? AND b.is_published = 1'
);
$stmt->execute([$slug]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
}

$commentError = '';
$commentSuccess = false;

if ($item && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $commentError = 'Your session expired. Please try submitting again.';
    } elseif (trim((string)($_POST['website'] ?? '')) !== '') {
        // Honeypot filled — silently accept without saving.
        $commentSuccess = true;
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $comment = trim((string)($_POST['comment'] ?? ''));
        if ($name === '' || $comment === '') {
            $commentError = 'Please fill in your name and comment.';
        } elseif (mb_strlen($comment) > 2000) {
            $commentError = 'Comments are limited to 2000 characters.';
        } else {
            $pdo->prepare('INSERT INTO blog_comments (post_id, name, email, comment) VALUES (?, ?, ?, ?)')
                ->execute([(int)$item['id'], $name, $email, $comment]);
            notify($pdo, 'comment', 'New blog comment awaiting approval', $name . ' on "' . $item['title'] . '"', 'blog-comments.php');
            $commentSuccess = true;
        }
    }
}

$tags = [];
$related = [];
$comments = [];
if ($item) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $pdo->prepare('UPDATE blogs SET views = views + 1 WHERE id = ?')->execute([(int)$item['id']]);
        $item['views'] = (int)$item['views'] + 1;
    }

    $tagStmt = $pdo->prepare('SELECT bt.* FROM blog_tags bt INNER JOIN blog_post_tags bpt ON bpt.tag_id = bt.id WHERE bpt.post_id = ? ORDER BY bt.name');
    $tagStmt->execute([(int)$item['id']]);
    $tags = $tagStmt->fetchAll();

    // Related: shared tags (weight 2) or same category (weight 1), best first.
    $relStmt = $pdo->prepare(
        'SELECT b.*,
                ((SELECT COUNT(*) FROM blog_post_tags x
                  WHERE x.post_id = b.id AND x.tag_id IN (SELECT tag_id FROM blog_post_tags WHERE post_id = ?)) * 2
                 + IF(b.category_id IS NOT NULL AND b.category_id = ?, 1, 0)) AS relevance
         FROM blogs b
         WHERE b.id != ? AND b.is_published = 1
         HAVING relevance > 0
         ORDER BY relevance DESC, b.published_at DESC LIMIT 3'
    );
    $relStmt->execute([(int)$item['id'], (int)($item['category_id'] ?? 0), (int)$item['id']]);
    $related = $relStmt->fetchAll();
    if (!$related) {
        $fallback = $pdo->prepare('SELECT * FROM blogs WHERE id != ? AND is_published = 1 ORDER BY published_at DESC LIMIT 3');
        $fallback->execute([(int)$item['id']]);
        $related = $fallback->fetchAll();
    }

    $comStmt = $pdo->prepare('SELECT * FROM blog_comments WHERE post_id = ? AND is_approved = 1 ORDER BY created_at');
    $comStmt->execute([(int)$item['id']]);
    $comments = $comStmt->fetchAll();
}

$pageTitle = $item['title'] ?? 'Post Not Found';
$pageDescription = $item ? (($item['meta_description'] ?: $item['excerpt']) ?: '') : '';

if ($item) {
    $seoImage = $item['image_path'];
    $seoType = 'article';
    $seoArticle = [
        'published' => $item['published_at'],
        'author' => $item['author_name'] ?? '',
        'section' => $item['cat_name'] ?? '',
    ];
    $breadcrumbs = [['label' => 'Blogs', 'url' => 'blogs.php']];
    if ($item['cat_name']) {
        $breadcrumbs[] = ['label' => $item['cat_name'], 'url' => 'blogs.php?cat=' . $item['cat_slug']];
    }
    $breadcrumbs[] = ['label' => $item['title']];
} else {
    $breadcrumbs = [['label' => 'Blogs', 'url' => 'blogs.php'], ['label' => 'Not Found']];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1><?= e($item['title'] ?? 'Post Not Found') ?></h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:820px;">
    <?php if ($item): ?>
      <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e(media_alt($pdo, $item['image_path'], $item['title'])) ?>" style="border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:24px;">

      <p style="font-size:13.5px;color:var(--text-light);margin-bottom:20px;">
        <?= $item['author_name'] ? '✍️ <strong>' . e($item['author_name']) . '</strong> · ' : '' ?>
        <?= format_date($item['published_at']) ?> ·
        <?= e(reading_time($item['body'])) ?> ·
        👁 <?= (int)$item['views'] ?> view<?= (int)$item['views'] === 1 ? '' : 's' ?>
        <?php if ($item['cat_name']): ?> · <a href="blogs.php?cat=<?= e($item['cat_slug']) ?>" style="color:var(--primary);font-weight:600;"><?= e($item['cat_name']) ?></a><?php endif; ?>
      </p>

      <div><?= $item['body'] ?></div>

      <?php if ($tags): ?>
      <div style="margin-top:26px;display:flex;gap:8px;flex-wrap:wrap;">
        <?php foreach ($tags as $tag): ?>
          <a href="blogs.php?tag=<?= e($tag['slug']) ?>" class="filter-link" style="padding:5px 14px;font-size:12.5px;">#<?= e($tag['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Comments -->
      <div style="margin-top:46px;">
        <h3>Comments (<?= count($comments) ?>)</h3>
        <?php if (empty($comments)): ?>
          <p style="color:var(--text-light);">No comments yet — be the first to share your thoughts.</p>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:14px;margin-top:16px;">
            <?php foreach ($comments as $comment): ?>
            <div style="background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px 22px;">
              <strong><?= e($comment['name']) ?></strong>
              <small style="color:var(--text-light);"> · <?= e(time_ago($comment['created_at'])) ?></small>
              <p style="margin:8px 0 0;"><?= nl2br(e($comment['comment'])) ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="form-card" style="margin-top:26px;padding:28px;">
          <h3 style="margin-bottom:6px;">Leave a Comment</h3>
          <p class="form-hint" style="margin-bottom:16px;">Comments are reviewed before appearing on the site.</p>
          <?php if ($commentSuccess): ?>
            <div role="status" class="alert alert-success">Thank you! Your comment has been submitted and is awaiting approval.</div>
          <?php elseif ($commentError): ?>
            <div role="alert" class="alert alert-error"><?= e($commentError) ?></div>
          <?php endif; ?>
          <form method="post" action="blog-detail.php?slug=<?= e($slug) ?>">
            <?= csrf_field() ?>
            <input type="text" name="website" value="" style="display:none;" tabindex="-1" autocomplete="off">
            <div class="form-row">
              <div class="form-group">
                <label for="c_name">Name *</label>
                <input type="text" id="c_name" name="name" required>
              </div>
              <div class="form-group">
                <label for="c_email">Email (not shown publicly)</label>
                <input type="email" id="c_email" name="email">
              </div>
            </div>
            <div class="form-group">
              <label for="c_comment">Comment *</label>
              <textarea id="c_comment" name="comment" required maxlength="2000" style="min-height:110px;"></textarea>
            </div>
            <button type="submit" class="btn btn-dark">Submit Comment</button>
          </form>
        </div>
      </div>

      <?php if (!empty($related)): ?>
      <div style="margin-top:46px;">
        <h3>Related Posts</h3>
        <div class="news-grid">
          <?php foreach ($related as $rel): ?>
          <div class="news-card">
            <img src="<?= BASE_URL ?>/<?= e($rel['image_path']) ?>" alt="<?= e($rel['title']) ?>" loading="lazy">
            <div class="news-card-body">
              <span class="news-date"><?= format_date($rel['published_at']) ?> · <?= e(reading_time($rel['body'])) ?></span>
              <h3><?= e($rel['title']) ?></h3>
              <a href="blog-detail.php?slug=<?= e($rel['slug']) ?>" class="read-more">Read More →</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <p>Sorry, this blog post could not be found.</p>
      <a href="blogs.php" class="btn btn-dark">Back to Blogs</a>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
