<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/videos.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);
        flash_set('success', 'Video removed.');
        redirect(BASE_URL . '/admin/videos.php');
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $url = trim((string)($_POST['youtube_url'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if ($title === '' || youtube_id($url) === '') {
        flash_set('error', 'Please provide a title and a valid YouTube link.');
        redirect(BASE_URL . '/admin/videos.php');
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE videos SET title=?, youtube_url=?, sort_order=? WHERE id=?')
            ->execute([$title, $url, $sortOrder, $id]);
        flash_set('success', 'Video updated.');
    } else {
        $pdo->prepare('INSERT INTO videos (title, youtube_url, sort_order) VALUES (?, ?, ?)')
            ->execute([$title, $url, $sortOrder]);
        flash_set('success', 'Video added.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'video_save', 'Saved video "' . $title . '"');
    redirect(BASE_URL . '/admin/videos.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$videos = $pdo->query('SELECT * FROM videos ORDER BY sort_order, id')->fetchAll();

$pageTitle = 'Videos';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Video' : 'Add Video' ?></h2></div>
  <form method="post" action="videos.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-row">
      <div class="form-group">
        <label for="title">Video Title *</label>
        <input type="text" id="title" name="title" required value="<?= e($editing['title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="youtube_url">YouTube Link *</label>
        <input type="text" id="youtube_url" name="youtube_url" required placeholder="https://www.youtube.com/watch?v=…" value="<?= e($editing['youtube_url'] ?? '') ?>">
        <p class="form-hint">Full watch URL, share link (youtu.be/…), or Shorts link.</p>
      </div>
    </div>
    <div class="form-group" style="max-width:220px;">
      <label for="sort_order">Sort Order</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?> Video</button>
    <?php if ($editing): ?><a href="videos.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Videos (<?= count($videos) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($videos)): ?>
      <div class="empty-state">No videos yet. Add a YouTube link above.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Preview</th><th>Title</th><th>Link</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($videos as $video): $vid = youtube_id($video['youtube_url']); ?>
        <tr>
          <td><?php if ($vid): ?><img src="https://img.youtube.com/vi/<?= e($vid) ?>/default.jpg" class="thumb" style="width:64px;"><?php endif; ?></td>
          <td><?= e($video['title']) ?></td>
          <td><a href="<?= e($video['youtube_url']) ?>" target="_blank" rel="noopener" style="color:var(--primary);">Watch ↗</a></td>
          <td><?= (int)$video['sort_order'] ?></td>
          <td class="actions-cell">
            <a href="videos.php?edit=<?= (int)$video['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="videos.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$video['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Remove this video?">Delete</button>
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
