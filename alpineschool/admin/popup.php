<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $update->execute(['popup_enabled', isset($_POST['popup_enabled']) ? '1' : '0']);
        $update->execute(['popup_title', trim((string)($_POST['popup_title'] ?? ''))]);
        $update->execute(['popup_text', trim((string)($_POST['popup_text'] ?? ''))]);
        $update->execute(['popup_link', trim((string)($_POST['popup_link'] ?? ''))]);

        if (!empty($_FILES['popup_image']['name'])) {
            $path = upload_image($_FILES['popup_image'], 'popup');
            if ($path) {
                $update->execute(['popup_image', 'uploads/' . $path]);
            }
        }
        if (isset($_POST['remove_image'])) {
            $update->execute(['popup_image', '']);
        }

        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'popup_update', 'Updated website popup');
        flash_set('success', 'Popup settings saved.');
        redirect(BASE_URL . '/admin/popup.php');
    }
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Popup Manager';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Website Popup</h2>
    <span class="badge <?= ($settings['popup_enabled'] ?? '0') === '1' ? 'badge-published' : 'badge-draft' ?>">
      <?= ($settings['popup_enabled'] ?? '0') === '1' ? 'Enabled' : 'Disabled' ?>
    </span>
  </div>
  <form method="post" action="popup.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-group">
      <label><input type="checkbox" name="popup_enabled" <?= ($settings['popup_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Show popup on the website</label>
      <p class="form-hint">Visitors see it once per browser session, ~1 second after the page loads.</p>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="popup_title">Title</label>
        <input type="text" id="popup_title" name="popup_title" placeholder="e.g. Admissions Open 2026!" value="<?= e($settings['popup_title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="popup_link">Button Link (optional)</label>
        <input type="text" id="popup_link" name="popup_link" placeholder="admissions.php" value="<?= e($settings['popup_link'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="popup_text">Text</label>
      <textarea id="popup_text" name="popup_text" style="min-height:80px;"><?= e($settings['popup_text'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <?php if (!empty($settings['popup_image'])): ?>
        <img src="<?= BASE_URL ?>/<?= e($settings['popup_image']) ?>" class="current-image" alt="Popup image">
        <label style="font-weight:400;"><input type="checkbox" name="remove_image"> Remove current image</label>
      <?php endif; ?>
      <label for="popup_image">Popup Image (optional)</label>
      <input type="file" id="popup_image" name="popup_image" accept="image/*">
    </div>
    <button type="submit" class="btn btn-primary">Save Popup</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
