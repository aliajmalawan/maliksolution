<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/partners.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM partners WHERE id = ?')->execute([$id]);
        flash_set('success', 'Partner removed.');
        redirect(BASE_URL . '/admin/partners.php');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $url = trim((string)($_POST['url'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if ($name === '') {
        flash_set('error', 'Partner name is required.');
        redirect(BASE_URL . '/admin/partners.php');
    }

    $logoPath = null;
    if (!empty($_FILES['logo']['name'])) {
        $uploaded = upload_image($_FILES['logo'], 'partners');
        if ($uploaded) {
            $logoPath = 'uploads/' . $uploaded;
        }
    }

    if ($id > 0) {
        if ($logoPath) {
            $pdo->prepare('UPDATE partners SET name=?, logo_path=?, url=?, sort_order=? WHERE id=?')
                ->execute([$name, $logoPath, $url, $sortOrder, $id]);
        } else {
            $pdo->prepare('UPDATE partners SET name=?, url=?, sort_order=? WHERE id=?')
                ->execute([$name, $url, $sortOrder, $id]);
        }
        flash_set('success', 'Partner updated.');
    } else {
        if (!$logoPath) {
            flash_set('error', 'Please upload a logo for the new partner.');
            redirect(BASE_URL . '/admin/partners.php');
        }
        $pdo->prepare('INSERT INTO partners (name, logo_path, url, sort_order) VALUES (?, ?, ?, ?)')
            ->execute([$name, $logoPath, $url, $sortOrder]);
        flash_set('success', 'Partner added.');
    }
    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'partner_save', 'Saved partner "' . $name . '"');
    redirect(BASE_URL . '/admin/partners.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM partners WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$partners = $pdo->query('SELECT * FROM partners ORDER BY sort_order, id')->fetchAll();

$pageTitle = 'Partners';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Partner' : 'Add Partner' ?></h2></div>
  <form method="post" action="partners.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <?php if ($editing && $editing['logo_path']): ?>
      <img src="<?= BASE_URL ?>/<?= e($editing['logo_path']) ?>" class="current-image" alt="Logo">
    <?php endif; ?>
    <div class="form-row">
      <div class="form-group">
        <label for="name">Partner / Affiliation Name *</label>
        <input type="text" id="name" name="name" required value="<?= e($editing['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="logo">Logo <?= $editing ? '(leave empty to keep current)' : '*' ?></label>
        <input type="file" id="logo" name="logo" accept="image/*" <?= $editing ? '' : 'required' ?>>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="url">Website (optional)</label>
        <input type="text" id="url" name="url" value="<?= e($editing['url'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?> Partner</button>
    <?php if ($editing): ?><a href="partners.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Partners (<?= count($partners) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($partners)): ?>
      <div class="empty-state">No partners yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Logo</th><th>Name</th><th>Website</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($partners as $partner): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/<?= e($partner['logo_path']) ?>" class="thumb"></td>
          <td><?= e($partner['name']) ?></td>
          <td><?php if ($partner['url']): ?><a href="<?= e($partner['url']) ?>" target="_blank" rel="noopener" style="color:var(--primary);">Visit ↗</a><?php else: ?>—<?php endif; ?></td>
          <td><?= (int)$partner['sort_order'] ?></td>
          <td class="actions-cell">
            <a href="partners.php?edit=<?= (int)$partner['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="partners.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$partner['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Remove this partner?">Delete</button>
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
