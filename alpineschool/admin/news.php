<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/news.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$id]);
        flash_set('success', 'News article deleted.');
        redirect(BASE_URL . '/admin/news.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? ''));
    $body = (string)($_POST['body'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $slug = slugify($title);

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploaded = upload_image($_FILES['image'], 'news');
        if ($uploaded) {
            $imagePath = 'uploads/' . $uploaded;
        }
    }

    if ($id > 0) {
        if ($imagePath) {
            $stmt = $pdo->prepare('UPDATE news SET title=?, slug=?, excerpt=?, body=?, image_path=?, is_published=? WHERE id=?');
            $stmt->execute([$title, $slug, $excerpt, $body, $imagePath, $is_published, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE news SET title=?, slug=?, excerpt=?, body=?, is_published=? WHERE id=?');
            $stmt->execute([$title, $slug, $excerpt, $body, $is_published, $id]);
        }
        flash_set('success', 'News article updated.');
    } else {
        if (!$imagePath) {
            flash_set('error', 'Please choose an image for the news article.');
            redirect(BASE_URL . '/admin/news.php');
        }
        $stmt = $pdo->prepare('INSERT INTO news (title, slug, excerpt, body, image_path, is_published) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $slug, $excerpt, $body, $imagePath, $is_published]);
        flash_set('success', 'News article published.');
    }
    redirect(BASE_URL . '/admin/news.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$newsList = $pdo->query('SELECT * FROM news ORDER BY published_at DESC')->fetchAll();

$pageTitle = 'News';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Article' : 'Add News Article' ?></h2></div>
  <form method="post" action="news.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <?php if ($editing): ?>
      <img src="<?= BASE_URL ?>/<?= e($editing['image_path']) ?>" class="current-image" alt="Current article image">
    <?php endif; ?>
    <div class="form-group">
      <label for="image">Image <?= $editing ? '(leave empty to keep current)' : '*' ?></label>
      <input type="file" id="image" name="image" accept="image/*" <?= $editing ? '' : 'required' ?>>
    </div>
    <div class="form-group">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label for="excerpt">Excerpt (short summary)</label>
      <input type="text" id="excerpt" name="excerpt" value="<?= e($editing['excerpt'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label for="body">Full Article</label>
      <textarea id="body" name="body" style="min-height:200px;"><?= e($editing['body'] ?? '') ?></textarea>
      <p class="form-hint">Basic HTML tags like &lt;p&gt;, &lt;strong&gt;, &lt;br&gt; are supported.</p>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_published" <?= (!$editing || $editing['is_published']) ? 'checked' : '' ?>> Published (visible on site)</label>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Article' : 'Publish Article' ?></button>
    <?php if ($editing): ?><a href="news.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All News Articles</h2></div>
  <div class="table-wrap">
    <?php if (empty($newsList)): ?>
      <div class="empty-state">No news articles yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Image</th><th>Title</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($newsList as $item): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" class="thumb"></td>
          <td><?= e($item['title']) ?></td>
          <td><?= format_date($item['published_at']) ?></td>
          <td><span class="badge <?= $item['is_published'] ? 'badge-published' : 'badge-draft' ?>"><?= $item['is_published'] ? 'Published' : 'Draft' ?></span></td>
          <td class="actions-cell">
            <a href="news.php?edit=<?= (int)$item['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="news.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this article?">Delete</button>
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
