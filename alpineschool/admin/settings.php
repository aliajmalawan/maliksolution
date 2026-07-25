<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$fields = [
    'site_name', 'campus_name', 'tagline', 'motto',
    'phone', 'whatsapp', 'email', 'address',
    'facebook', 'instagram', 'tiktok', 'website', 'map_embed',
    'footer_text', 'stats_students', 'stats_faculty', 'stats_years', 'stats_results',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($fields as $field) {
            $value = trim((string)($_POST[$field] ?? ''));
            $update->execute([$field, $value]);
        }

        if (!empty($_FILES['logo']['name'])) {
            $path = upload_image($_FILES['logo'], 'branding');
            if ($path) {
                $update->execute(['logo_path', 'uploads/' . $path]);
            }
        }

        if (!empty($_FILES['favicon']['name'])) {
            $path = upload_image($_FILES['favicon'], 'branding');
            if ($path) {
                $update->execute(['favicon_path', 'uploads/' . $path]);
            }
        }

        flash_set('success', 'Settings updated successfully.');
        redirect(BASE_URL . '/admin/settings.php');
    }
}

$rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$settings = [];
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Site Settings';
require_once __DIR__ . '/includes/header.php';
?>

<form method="post" action="settings.php" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header"><h2>Branding</h2></div>
    <img src="<?= BASE_URL ?>/<?= e($settings['logo_path'] ?? '') ?>" class="current-image" alt="Current logo">
    <div class="form-row">
      <div class="form-group">
        <label for="logo">Upload New Logo</label>
        <input type="file" id="logo" name="logo" accept="image/*">
        <p class="form-hint">JPG, PNG or WEBP. Leave empty to keep the current logo.</p>
      </div>
      <div class="form-group">
        <?php if (!empty($settings['favicon_path'])): ?>
          <img src="<?= BASE_URL ?>/<?= e($settings['favicon_path']) ?>" style="width:32px;height:32px;border-radius:6px;border:1px solid var(--border);display:block;margin-bottom:8px;" alt="Favicon">
        <?php endif; ?>
        <label for="favicon">Upload Favicon</label>
        <input type="file" id="favicon" name="favicon" accept="image/*">
        <p class="form-hint">Square PNG recommended (32×32 or 64×64). Falls back to the logo if not set.</p>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="site_name">Site Name</label>
        <input type="text" id="site_name" name="site_name" value="<?= e($settings['site_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="campus_name">Campus Name</label>
        <input type="text" id="campus_name" name="campus_name" value="<?= e($settings['campus_name'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="tagline">Tagline</label>
        <input type="text" id="tagline" name="tagline" value="<?= e($settings['tagline'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="motto">Motto</label>
        <input type="text" id="motto" name="motto" value="<?= e($settings['motto'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Theme Colors</h2></div>
    <p class="form-hint">Colors, fonts, header/footer styles, buttons, spacing, and effects have moved to the <a href="theme.php" style="color:var(--primary);font-weight:600;">Theme Builder</a>.</p>
  </div>

  <div class="card">
    <div class="card-header"><h2>Contact Information</h2></div>
    <div class="form-row">
      <div class="form-group">
        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="<?= e($settings['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="whatsapp">WhatsApp (with country code, no +)</label>
        <input type="text" id="whatsapp" name="whatsapp" value="<?= e($settings['whatsapp'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($settings['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" value="<?= e($settings['website'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label for="address">Address</label>
      <input type="text" id="address" name="address" value="<?= e($settings['address'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label for="map_embed">Google Maps Embed Code (iframe)</label>
      <textarea id="map_embed" name="map_embed"><?= e($settings['map_embed'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Social Links</h2></div>
    <div class="form-row">
      <div class="form-group">
        <label for="facebook">Facebook URL</label>
        <input type="text" id="facebook" name="facebook" value="<?= e($settings['facebook'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="instagram">Instagram URL</label>
        <input type="text" id="instagram" name="instagram" value="<?= e($settings['instagram'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group" style="max-width:calc(50% - 8px);">
      <label for="tiktok">TikTok URL</label>
      <input type="text" id="tiktok" name="tiktok" value="<?= e($settings['tiktok'] ?? '') ?>">
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Homepage Stats</h2></div>
    <div class="form-row">
      <div class="form-group">
        <label for="stats_students">Students</label>
        <input type="text" id="stats_students" name="stats_students" value="<?= e($settings['stats_students'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="stats_faculty">Faculty Members</label>
        <input type="text" id="stats_faculty" name="stats_faculty" value="<?= e($settings['stats_faculty'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="stats_years">Years of Excellence</label>
        <input type="text" id="stats_years" name="stats_years" value="<?= e($settings['stats_years'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="stats_results">Result Rate (%)</label>
        <input type="text" id="stats_results" name="stats_results" value="<?= e($settings['stats_results'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>Footer</h2></div>
    <div class="form-group">
      <label for="footer_text">Footer Copyright Text</label>
      <input type="text" id="footer_text" name="footer_text" value="<?= e($settings['footer_text'] ?? '') ?>">
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Save Settings</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
