<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/faqs.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM faqs WHERE id = ?')->execute([$id]);
        flash_set('success', 'FAQ deleted.');
        redirect(BASE_URL . '/admin/faqs.php');
    }

    $question = trim((string)($_POST['question'] ?? ''));
    $answer = trim((string)($_POST['answer'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($question === '' || $answer === '') {
        flash_set('error', 'Question and answer are both required.');
        redirect(BASE_URL . '/admin/faqs.php');
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE faqs SET question=?, answer=?, sort_order=?, is_active=? WHERE id=?')
            ->execute([$question, $answer, $sortOrder, $isActive, $id]);
        flash_set('success', 'FAQ updated.');
    } else {
        $pdo->prepare('INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, ?)')
            ->execute([$question, $answer, $sortOrder, $isActive]);
        flash_set('success', 'FAQ added.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'faq_save', 'Saved FAQ "' . mb_substr($question, 0, 60) . '"');
    redirect(BASE_URL . '/admin/faqs.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM faqs WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$faqs = $pdo->query('SELECT * FROM faqs ORDER BY sort_order, id')->fetchAll();

$pageTitle = 'FAQs';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit FAQ' : 'Add FAQ' ?></h2></div>
  <form method="post" action="faqs.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-group">
      <label for="question">Question *</label>
      <input type="text" id="question" name="question" required value="<?= e($editing['question'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label for="answer">Answer *</label>
      <textarea id="answer" name="answer" required><?= e($editing['answer'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
      </div>
      <div class="form-group">
        <label style="margin-top:28px;"><input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>> Active (shown on FAQs page)</label>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?> FAQ</button>
    <?php if ($editing): ?><a href="faqs.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All FAQs (<?= count($faqs) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($faqs)): ?>
      <div class="empty-state">No FAQs yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Question</th><th>Answer</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($faqs as $faq): ?>
        <tr>
          <td style="max-width:260px;"><?= e($faq['question']) ?></td>
          <td style="max-width:300px;"><?= e(mb_substr($faq['answer'], 0, 100)) ?><?= mb_strlen($faq['answer']) > 100 ? '…' : '' ?></td>
          <td><?= (int)$faq['sort_order'] ?></td>
          <td><span class="badge <?= $faq['is_active'] ? 'badge-published' : 'badge-draft' ?>"><?= $faq['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td class="actions-cell">
            <a href="faqs.php?edit=<?= (int)$faq['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="faqs.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$faq['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this FAQ?">Delete</button>
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
