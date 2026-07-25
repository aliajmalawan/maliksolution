<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

/** Sync a post's tags from a comma-separated string, creating tags as needed. */
function sync_post_tags(PDO $pdo, int $postId, string $tagsInput): void
{
    $pdo->prepare('DELETE FROM blog_post_tags WHERE post_id = ?')->execute([$postId]);
    $names = array_unique(array_filter(array_map('trim', explode(',', $tagsInput))));
    if (!$names) {
        return;
    }
    $find = $pdo->prepare('SELECT id FROM blog_tags WHERE slug = ?');
    $create = $pdo->prepare('INSERT INTO blog_tags (name, slug) VALUES (?, ?)');
    $link = $pdo->prepare('INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)');
    foreach (array_slice($names, 0, 15) as $name) {
        $slug = slugify($name);
        if ($slug === '') {
            continue;
        }
        $find->execute([$slug]);
        $tag = $find->fetch();
        if ($tag) {
            $tagId = (int)$tag['id'];
        } else {
            $create->execute([mb_substr($name, 0, 100), $slug]);
            $tagId = (int)$pdo->lastInsertId();
        }
        $link->execute([$postId, $tagId]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/blogs.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_category') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '') {
            $pdo->prepare('INSERT INTO blog_categories (name, slug) VALUES (?, ?)')->execute([$name, slugify($name)]);
            flash_set('success', 'Category added.');
        }
        redirect(BASE_URL . '/admin/blogs.php');
    }

    if ($action === 'delete_category') {
        $pdo->prepare('DELETE FROM blog_categories WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        $pdo->exec('UPDATE blogs SET category_id = NULL WHERE category_id NOT IN (SELECT id FROM blog_categories)');
        flash_set('success', 'Category deleted.');
        redirect(BASE_URL . '/admin/blogs.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM blogs WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash_set('success', 'Blog post deleted.');
        redirect(BASE_URL . '/admin/blogs.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? ''));
    $metaDescription = mb_substr(trim((string)($_POST['meta_description'] ?? '')), 0, 300);
    $body = (string)($_POST['body'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
    $authorId = (int)($_POST['author_id'] ?? 0) ?: (int)$currentAdmin['id'];
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $tagsInput = (string)($_POST['tags'] ?? '');
    $slug = slugify($title);

    if ($title === '') {
        flash_set('error', 'Title is required.');
        redirect(BASE_URL . '/admin/blogs.php');
    }

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploaded = upload_image($_FILES['image'], 'blogs');
        if ($uploaded) {
            $imagePath = 'uploads/' . $uploaded;
        }
    }

    if ($id > 0) {
        if ($imagePath) {
            $pdo->prepare('UPDATE blogs SET title=?, slug=?, category_id=?, author_id=?, excerpt=?, meta_description=?, body=?, image_path=?, is_published=? WHERE id=?')
                ->execute([$title, $slug, $categoryId, $authorId, $excerpt, $metaDescription, $body, $imagePath, $isPublished, $id]);
        } else {
            $pdo->prepare('UPDATE blogs SET title=?, slug=?, category_id=?, author_id=?, excerpt=?, meta_description=?, body=?, is_published=? WHERE id=?')
                ->execute([$title, $slug, $categoryId, $authorId, $excerpt, $metaDescription, $body, $isPublished, $id]);
        }
        sync_post_tags($pdo, $id, $tagsInput);
        flash_set('success', 'Blog post updated.');
    } else {
        if (!$imagePath) {
            flash_set('error', 'Please choose a featured image for the blog post.');
            redirect(BASE_URL . '/admin/blogs.php');
        }
        $pdo->prepare('INSERT INTO blogs (title, slug, category_id, author_id, excerpt, meta_description, body, image_path, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$title, $slug, $categoryId, $authorId, $excerpt, $metaDescription, $body, $imagePath, $isPublished]);
        sync_post_tags($pdo, (int)$pdo->lastInsertId(), $tagsInput);
        flash_set('success', 'Blog post published.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'blog_save', 'Saved blog post "' . $title . '"');
    redirect(BASE_URL . '/admin/blogs.php');
}

$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();
$authors = $pdo->query('SELECT id, full_name FROM admins ORDER BY full_name')->fetchAll();

$editing = null;
$editingTags = '';
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM blogs WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
    if ($editing) {
        $stmt = $pdo->prepare('SELECT bt.name FROM blog_tags bt INNER JOIN blog_post_tags bpt ON bpt.tag_id = bt.id WHERE bpt.post_id = ?');
        $stmt->execute([(int)$editing['id']]);
        $editingTags = implode(', ', array_column($stmt->fetchAll(), 'name'));
    }
}

$blogsList = $pdo->query(
    'SELECT b.*, bc.name AS cat_name, a.full_name AS author_name,
            (SELECT COUNT(*) FROM blog_comments WHERE post_id = b.id AND is_approved = 0) AS pending_comments
     FROM blogs b
     LEFT JOIN blog_categories bc ON b.category_id = bc.id
     LEFT JOIN admins a ON b.author_id = a.id
     ORDER BY b.published_at DESC'
)->fetchAll();

$pageTitle = 'Blogs';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2>Blog Categories</h2></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
    <?php foreach ($categories as $cat): ?>
      <form method="post" action="blogs.php" style="display:flex;align-items:center;gap:6px;background:var(--surface-2);padding:6px 6px 6px 14px;border-radius:999px;">
        <span style="font-size:13px;font-weight:600;"><?= e($cat['name']) ?></span>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete_category">
        <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 10px;" data-confirm="Delete this category? Posts keep publishing but lose the category.">×</button>
      </form>
    <?php endforeach; ?>
  </div>
  <form method="post" action="blogs.php" style="display:flex;gap:10px;align-items:flex-end;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_category">
    <div class="form-group" style="margin-bottom:0;flex:1;max-width:300px;">
      <label for="cat_name">New Category</label>
      <input type="text" id="cat_name" name="name" placeholder="e.g. Exam Preparation" required>
    </div>
    <button type="submit" class="btn btn-outline">Add Category</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Blog Post' : 'Write New Post' ?></h2></div>
  <form method="post" action="blogs.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-group">
      <label for="title">Title *</label>
      <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">— None —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= (int)($editing['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="author_id">Author</label>
        <select id="author_id" name="author_id">
          <?php foreach ($authors as $author): ?>
            <option value="<?= (int)$author['id'] ?>" <?= (int)($editing['author_id'] ?? $currentAdmin['id']) === (int)$author['id'] ? 'selected' : '' ?>><?= e($author['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="tags">Tags (comma separated)</label>
      <input type="text" id="tags" name="tags" placeholder="exams, study tips, motivation" value="<?= e($editingTags) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <?php if ($editing): ?>
          <img src="<?= BASE_URL ?>/<?= e($editing['image_path']) ?>" class="current-image" alt="Featured image">
        <?php endif; ?>
        <label for="image">Featured Image <?= $editing ? '(leave empty to keep current)' : '*' ?></label>
        <input type="file" id="image" name="image" accept="image/*" <?= $editing ? '' : 'required' ?>>
      </div>
      <div class="form-group">
        <label for="excerpt">Excerpt (shown on listing cards)</label>
        <input type="text" id="excerpt" name="excerpt" value="<?= e($editing['excerpt'] ?? '') ?>">
        <label for="meta_description" style="margin-top:12px;">SEO Meta Description</label>
        <input type="text" id="meta_description" name="meta_description" maxlength="300" placeholder="Falls back to the excerpt when empty" value="<?= e($editing['meta_description'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="body">Full Post</label>
      <textarea id="body" name="body" style="min-height:220px;"><?= e($editing['body'] ?? '') ?></textarea>
      <p class="form-hint">Basic HTML tags like &lt;p&gt;, &lt;strong&gt;, &lt;h3&gt; are supported. Reading time is calculated automatically.</p>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_published" <?= (!$editing || $editing['is_published']) ? 'checked' : '' ?>> Published (visible on site)</label>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Post' : 'Publish Post' ?></button>
    <?php if ($editing): ?><a href="blogs.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h2>All Blog Posts (<?= count($blogsList) ?>)</h2>
    <a href="blog-comments.php" class="btn btn-outline btn-sm">💬 Moderate Comments</a>
  </div>
  <div class="table-wrap">
    <?php if (empty($blogsList)): ?>
      <div class="empty-state">No blog posts yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Author</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($blogsList as $item): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" class="thumb"></td>
          <td>
            <?= e($item['title']) ?>
            <?php if ($item['pending_comments'] > 0): ?><span class="badge badge-new"><?= (int)$item['pending_comments'] ?> pending comment<?= $item['pending_comments'] > 1 ? 's' : '' ?></span><?php endif; ?>
            <br><small style="color:var(--muted);"><?= e(reading_time($item['body'])) ?> · <?= format_date($item['published_at']) ?></small>
          </td>
          <td><?= e($item['cat_name'] ?? '—') ?></td>
          <td><?= e($item['author_name'] ?? '—') ?></td>
          <td><?= (int)$item['views'] ?></td>
          <td><span class="badge <?= $item['is_published'] ? 'badge-published' : 'badge-draft' ?>"><?= $item['is_published'] ? 'Published' : 'Draft' ?></span></td>
          <td class="actions-cell">
            <a href="blogs.php?edit=<?= (int)$item['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="blogs.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this post and its comments?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
