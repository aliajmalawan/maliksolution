<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/hero-slides.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM hero_slides WHERE id = ?')->execute([$id]);
        flash_set('success', 'Slide deleted.');
        redirect(BASE_URL . '/admin/hero-slides.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $subtitle = trim((string)($_POST['subtitle'] ?? ''));
    $button_text = trim((string)($_POST['button_text'] ?? ''));
    $button_link = trim((string)($_POST['button_link'] ?? ''));
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploaded = upload_image($_FILES['image'], 'hero');
        if ($uploaded) {
            $imagePath = 'uploads/' . $uploaded;
        }
    }

    if ($id > 0) {
        if ($imagePath) {
            $stmt = $pdo->prepare('UPDATE hero_slides SET image_path=?, title=?, subtitle=?, button_text=?, button_link=?, sort_order=?, is_active=? WHERE id=?');
            $stmt->execute([$imagePath, $title, $subtitle, $button_text, $button_link, $sort_order, $is_active, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE hero_slides SET title=?, subtitle=?, button_text=?, button_link=?, sort_order=?, is_active=? WHERE id=?');
            $stmt->execute([$title, $subtitle, $button_text, $button_link, $sort_order, $is_active, $id]);
        }
        flash_set('success', 'Slide updated.');
    } else {
        if (!$imagePath) {
            flash_set('error', 'Please choose an image for the new slide.');
            redirect(BASE_URL . '/admin/hero-slides.php');
        }
        $stmt = $pdo->prepare('INSERT INTO hero_slides (image_path, title, subtitle, button_text, button_link, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$imagePath, $title, $subtitle, $button_text, $button_link, $sort_order, $is_active]);
        flash_set('success', 'Slide added.');
    }
    redirect(BASE_URL . '/admin/hero-slides.php');
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM hero_slides WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$slides = $pdo->query('SELECT * FROM hero_slides ORDER BY sort_order')->fetchAll();

function slide_image_info(string $imagePath): string
{
    $file = dirname(__DIR__) . '/' . $imagePath;
    if (!is_file($file)) {
        return 'File missing';
    }
    $parts = [];
    $dims = @getimagesize($file);
    if ($dims) {
        $parts[] = $dims[0] . ' × ' . $dims[1] . ' px';
    }
    $bytes = filesize($file);
    if ($bytes !== false) {
        $parts[] = human_filesize((int)$bytes);
    }
    return $parts ? implode(' · ', $parts) : 'Unknown';
}

$pageTitle = 'Hero Slider';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $editing ? 'Edit Slide' : 'Add New Slide' ?></h2></div>
  <form method="post" action="hero-slides.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
    <?php if ($editing): ?>
      <img src="<?= BASE_URL ?>/<?= e($editing['image_path']) ?>" class="current-image" alt="Current slide image">
      <p class="form-hint" style="margin-top:-6px;margin-bottom:14px;"><?= e(slide_image_info($editing['image_path'])) ?></p>
    <?php endif; ?>
    <div class="form-group">
      <label for="image">Slide Image <?= $editing ? '(leave empty to keep current)' : '*' ?></label>
      <input type="file" id="image" name="image" accept="image/*" <?= $editing ? '' : 'required' ?>>
      <p class="form-hint">Recommended upload size: <strong>1920 × 800 px</strong>, landscape. Keep the main subject centered — the left side has a dark gradient for the title text.</p>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="subtitle">Subtitle</label>
        <input type="text" id="subtitle" name="subtitle" value="<?= e($editing['subtitle'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="button_text">Button Text</label>
        <input type="text" id="button_text" name="button_text" value="<?= e($editing['button_text'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="button_link">Button Link</label>
        <input type="text" id="button_link" name="button_link" value="<?= e($editing['button_link'] ?? 'admissions.php') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="sort_order">Sort Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>> Active (shown on homepage)</label>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Slide' : 'Add Slide' ?></button>
    <?php if ($editing): ?><a href="hero-slides.php" class="btn btn-outline">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>Current Slides</h2></div>
  <div class="table-wrap">
    <?php if (empty($slides)): ?>
      <div class="empty-state">No hero slides yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Image</th><th>Title</th><th>Image Size</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($slides as $slide): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/<?= e($slide['image_path']) ?>" class="thumb"></td>
          <td><?= e($slide['title']) ?></td>
          <td style="white-space:nowrap;"><?= e(slide_image_info($slide['image_path'])) ?></td>
          <td><?= (int)$slide['sort_order'] ?></td>
          <td><span class="badge <?= $slide['is_active'] ? 'badge-published' : 'badge-draft' ?>"><?= $slide['is_active'] ? 'Active' : 'Hidden' ?></span></td>
          <td class="actions-cell">
            <a href="hero-slides.php?edit=<?= (int)$slide['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="hero-slides.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$slide['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this slide?">Delete</button>
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
