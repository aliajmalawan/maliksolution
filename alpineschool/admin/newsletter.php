<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (isset($_GET['export'])) {
    $subscribers = $pdo->query('SELECT email, created_at FROM newsletter_subscribers ORDER BY created_at')->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="newsletter-subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Subscribed At']);
    foreach ($subscribers as $sub) {
        fputcsv($out, [$sub['email'], $sub['created_at']]);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/newsletter.php');
    }
    if (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash_set('success', 'Subscriber removed.');
    }
    redirect(BASE_URL . '/admin/newsletter.php');
}

$subscribers = $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Newsletter';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Newsletter Subscribers (<?= count($subscribers) ?>)</h2>
    <a href="newsletter.php?export=1" class="btn btn-primary btn-sm">⬇ Export CSV</a>
  </div>
  <div class="table-wrap">
    <?php if (empty($subscribers)): ?>
      <div class="empty-state">No subscribers yet. The subscribe form is in the website footer.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Email</th><th>Subscribed</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($subscribers as $sub): ?>
        <tr>
          <td><?= e($sub['email']) ?></td>
          <td><?= e(time_ago($sub['created_at'])) ?></td>
          <td>
            <form method="post" action="newsletter.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Remove this subscriber?">Remove</button>
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
