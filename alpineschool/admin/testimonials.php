<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/testimonials.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
        flash_set('success', 'Testimonial deleted.');
        redirect(BASE_URL . '/admin/testimonials.php');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $role = trim((string)($_POST['role'] ?? ''));
    $quote = trim((string)($_POST['quote'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $quote === '') {
        flash_set('error', 'Name and quote are required.');
        redirect(BASE_URL . '/admin/testimonials.php');
    }

    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
        $uploaded = upload_image($_FILES['photo'], 'testimonials');
        if ($uploaded) {
            $photoPath = 'uploads/' . $uploaded;
        }
    }

    if ($id > 0) {
        if ($photoPath) {
            $pdo->prepare('UPDATE testimonials SET name=?, role=?, quote=?, photo_path=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$name, $role, $quote, $photoPath, $sortOrder, $isActive, $id]);
        } else {
            $pdo->prepare('UPDATE testimonials SET name=?, role=?, quote=?, sort_order=?, is_active=? WHERE id=?')
                ->execute([$name, $role, $quote, $sortOrder, $isActive, $id]);
        }
        flash_set('success', 'Testimonial updated.');
    } else {
        $pdo->prepare('INSERT INTO testimonials (name, role, quote, photo_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$name, $role, $quote, $photoPath, $sortOrder, $isActive]);
        flash_set('success', 'Testimonial added.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'testimonial_save', 'Saved testimonial from "' . $name . '"');
    redirect(BASE_URL . '/admin/testimonials.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY sort_order, id')->fetchAll();

$pageTitle = 'Testimonials';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Testimonial' : 'Add Testimonial' ?></h2></div>
  <form method="post" action="testimonials.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <div class="form-row">
      <div class="form-group">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" required value="<?= e($editing['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="role">Role / Relation</label>
        <input type="text" id="role" name="role" placeholder="e.g. Parent of Grade 5 student" value="<?= e($editing['role'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="quote">Quote *</label>
      <textarea id="quote" name="quote" required><?= e($editing['quote'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <?php if ($editing && $editing['photo_path']): ?>
          <img src="<?= BASE_URL ?>/<?= e($editing['photo_path']) ?>" class="current-image" alt="Photo">
        <?php endif; ?>
        <label for="photo">Photo (optional)</label>
        <input type="file" id="photo" name="photo" accept="image/*">
      </div>
      <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
        <label style="margin-top:12px;"><input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>> Active (shown on homepage)</label>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?> Testimonial</button>
    <?php if ($editing): ?><a href="testimonials.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Testimonials (<?= count($testimonials) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($testimonials)): ?>
      <div class="empty-state">No testimonials yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Photo</th><th>Name</th><th>Role</th><th>Quote</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($testimonials as $t): ?>
        <tr>
          <td><?php if ($t['photo_path']): ?><img src="<?= BASE_URL ?>/<?= e($t['photo_path']) ?>" class="thumb"><?php else: ?>—<?php endif; ?></td>
          <td><?= e($t['name']) ?></td>
          <td><?= e($t['role']) ?></td>
          <td style="max-width:280px;"><?= e(mb_substr($t['quote'], 0, 90)) ?><?= mb_strlen($t['quote']) > 90 ? '…' : '' ?></td>
          <td><span class="badge <?= $t['is_active'] ? 'badge-published' : 'badge-draft' ?>"><?= $t['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td class="actions-cell">
            <a href="testimonials.php?edit=<?= (int)$t['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="testimonials.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this testimonial?">Delete</button>
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
