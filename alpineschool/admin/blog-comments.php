<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/blog-comments.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'approve') {
        $pdo->prepare('UPDATE blog_comments SET is_approved = 1 WHERE id = ?')->execute([$id]);
        flash_set('success', 'Comment approved and now visible on the site.');
    } elseif ($action === 'unapprove') {
        $pdo->prepare('UPDATE blog_comments SET is_approved = 0 WHERE id = ?')->execute([$id]);
        flash_set('success', 'Comment hidden.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM blog_comments WHERE id = ?')->execute([$id]);
        flash_set('success', 'Comment deleted.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'comment_' . $action, 'Comment #' . $id);
    redirect(BASE_URL . '/admin/blog-comments.php' . (isset($_POST['filter']) ? '?filter=' . urlencode((string)$_POST['filter']) : ''));
}

$filter = (string)($_GET['filter'] ?? 'pending');
$where = $filter === 'approved' ? 'c.is_approved = 1' : ($filter === 'all' ? '1=1' : 'c.is_approved = 0');

$comments = $pdo->query(
    "SELECT c.*, b.title AS post_title, b.slug AS post_slug
     FROM blog_comments c INNER JOIN blogs b ON c.post_id = b.id
     WHERE $where ORDER BY c.created_at DESC LIMIT 200"
)->fetchAll();

$pendingCount = (int)$pdo->query('SELECT COUNT(*) c FROM blog_comments WHERE is_approved = 0')->fetch()['c'];

$pageTitle = 'Blog Comments';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Comments — <?= e(ucfirst($filter)) ?> (<?= count($comments) ?>)</h2>
    <div style="display:flex;gap:8px;">
      <a href="blog-comments.php?filter=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-outline' ?>">Pending<?= $pendingCount ? ' (' . $pendingCount . ')' : '' ?></a>
      <a href="blog-comments.php?filter=approved" class="btn btn-sm <?= $filter === 'approved' ? 'btn-primary' : 'btn-outline' ?>">Approved</a>
      <a href="blog-comments.php?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All</a>
    </div>
  </div>

  <?php if (empty($comments)): ?>
    <div class="empty-state"><?= $filter === 'pending' ? 'No comments waiting for approval. 🎉' : 'No comments here yet.' ?></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Comment</th><th>On Post</th><th>Status</th><th>When</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($comments as $comment): ?>
        <tr>
          <td style="max-width:340px;">
            <strong><?= e($comment['name']) ?></strong>
            <?php if ($comment['email']): ?><small style="color:var(--muted);"> · <?= e($comment['email']) ?></small><?php endif; ?>
            <br><?= e(mb_substr($comment['comment'], 0, 200)) ?><?= mb_strlen($comment['comment']) > 200 ? '…' : '' ?>
          </td>
          <td><a href="<?= BASE_URL ?>/blog-detail.php?slug=<?= e($comment['post_slug']) ?>" target="_blank" style="color:var(--primary);"><?= e(mb_substr($comment['post_title'], 0, 40)) ?> ↗</a></td>
          <td><span class="badge <?= $comment['is_approved'] ? 'badge-published' : 'badge-contacted' ?>"><?= $comment['is_approved'] ? 'Approved' : 'Pending' ?></span></td>
          <td style="white-space:nowrap;"><?= e(time_ago($comment['created_at'])) ?></td>
          <td class="actions-cell">
            <form method="post" action="blog-comments.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="filter" value="<?= e($filter) ?>">
              <input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
              <?php if ($comment['is_approved']): ?>
                <input type="hidden" name="action" value="unapprove">
                <button type="submit" class="btn btn-outline btn-sm">Hide</button>
              <?php else: ?>
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary btn-sm">✓ Approve</button>
              <?php endif; ?>
            </form>
            <form method="post" action="blog-comments.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="filter" value="<?= e($filter) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this comment?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
