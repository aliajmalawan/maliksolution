<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$fontOptions = ['Poppins', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Nunito', 'Raleway', 'Playfair Display', 'Merriweather'];
$colorFields = ['primary_color', 'primary_dark', 'secondary_color', 'accent_color', 'accent2_color'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/theme.php');
    }

    $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

    // Colors — only accept valid hex.
    foreach ($colorFields as $field) {
        $value = trim((string)($_POST[$field] ?? ''));
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) {
            $update->execute([$field, $value]);
        }
    }

    // Enumerated options — whitelist every value.
    $choices = [
        'theme_font_heading' => $fontOptions,
        'theme_font_body' => $fontOptions,
        'theme_header_style' => ['light', 'dark', 'primary'],
        'theme_footer_style' => ['dark', 'primary', 'light'],
        'theme_btn_style' => ['pill', 'rounded', 'square'],
        'theme_section_spacing' => ['compact', 'regular', 'spacious'],
        'theme_shadow' => ['none', 'soft', 'strong'],
    ];
    foreach ($choices as $key => $allowed) {
        $value = (string)($_POST[$key] ?? '');
        if (in_array($value, $allowed, true)) {
            $update->execute([$key, $value]);
        }
    }

    $update->execute(['theme_radius', (string)max(0, min(30, (int)($_POST['theme_radius'] ?? 14)))]);
    $update->execute(['theme_animations', isset($_POST['theme_animations']) ? '1' : '0']);
    $update->execute(['theme_btn_uppercase', isset($_POST['theme_btn_uppercase']) ? '1' : '0']);

    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'theme_update', 'Updated theme builder settings');
    flash_set('success', 'Theme saved — the website now uses your new design.');
    redirect(BASE_URL . '/admin/theme.php');
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$s = fn(string $key, string $default = '') => $settings[$key] ?? $default;

$pageTitle = 'Theme Builder';
require_once __DIR__ . '/includes/header.php';
?>

<form method="post" action="theme.php">
  <?= csrf_field() ?>

  <div class="grid-2" style="align-items:start;">
    <div>
      <div class="card">
        <div class="card-header"><h2>🎨 Colors</h2></div>
        <?php
        $colorLabels = [
            'primary_color' => 'Primary Color',
            'primary_dark' => 'Secondary / Dark (header & footer)',
            'secondary_color' => 'Muted Gray',
            'accent_color' => 'Accent (buttons & highlights)',
            'accent2_color' => 'Accent 2 (gold details)',
        ];
        ?>
        <?php foreach ($colorLabels as $field => $label): ?>
        <div class="form-group">
          <label for="<?= $field ?>"><?= $label ?></label>
          <div class="color-input-row">
            <input type="color" id="<?= $field ?>" name="<?= $field ?>" value="<?= e($s($field, '#000000')) ?>">
            <input type="text" value="<?= e($s($field)) ?>" oninput="document.getElementById('<?= $field ?>').value=this.value" style="width:auto;flex:1;">
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-header"><h2>🔤 Fonts</h2></div>
        <div class="form-row">
          <div class="form-group">
            <label for="theme_font_heading">Heading Font</label>
            <select id="theme_font_heading" name="theme_font_heading">
              <?php foreach ($fontOptions as $font): ?>
                <option value="<?= e($font) ?>" <?= $s('theme_font_heading', 'Poppins') === $font ? 'selected' : '' ?>><?= e($font) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="theme_font_body">Body Font</label>
            <select id="theme_font_body" name="theme_font_body">
              <?php foreach ($fontOptions as $font): ?>
                <option value="<?= e($font) ?>" <?= $s('theme_font_body', 'Inter') === $font ? 'selected' : '' ?>><?= e($font) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <p class="form-hint">Fonts load automatically from Google Fonts — no code needed.</p>
      </div>

      <div class="card">
        <div class="card-header"><h2>🔘 Buttons</h2></div>
        <div class="form-group">
          <label>Button Shape</label>
          <?php foreach (['pill' => 'Pill (fully rounded)', 'rounded' => 'Rounded corners', 'square' => 'Square'] as $value => $label): ?>
            <label style="font-weight:400;display:block;margin-bottom:6px;">
              <input type="radio" name="theme_btn_style" value="<?= $value ?>" <?= $s('theme_btn_style', 'pill') === $value ? 'checked' : '' ?>> <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="form-group">
          <label style="font-weight:400;"><input type="checkbox" name="theme_btn_uppercase" <?= $s('theme_btn_uppercase', '0') === '1' ? 'checked' : '' ?>> UPPERCASE button text</label>
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-header"><h2>🖥️ Header &amp; Footer</h2></div>
        <div class="form-group">
          <label>Header Style</label>
          <?php foreach (['light' => 'Light (white bar)', 'dark' => 'Dark (deep navy)', 'primary' => 'Primary color'] as $value => $label): ?>
            <label style="font-weight:400;display:block;margin-bottom:6px;">
              <input type="radio" name="theme_header_style" value="<?= $value ?>" <?= $s('theme_header_style', 'light') === $value ? 'checked' : '' ?>> <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="form-group">
          <label>Footer Style</label>
          <?php foreach (['dark' => 'Dark (deep navy)', 'primary' => 'Primary color', 'light' => 'Light gray'] as $value => $label): ?>
            <label style="font-weight:400;display:block;margin-bottom:6px;">
              <input type="radio" name="theme_footer_style" value="<?= $value ?>" <?= $s('theme_footer_style', 'dark') === $value ? 'checked' : '' ?>> <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>📐 Layout &amp; Effects</h2></div>
        <div class="form-group">
          <label>Section Spacing</label>
          <?php foreach (['compact' => 'Compact (56px)', 'regular' => 'Regular (80px)', 'spacious' => 'Spacious (110px)'] as $value => $label): ?>
            <label style="font-weight:400;display:block;margin-bottom:6px;">
              <input type="radio" name="theme_section_spacing" value="<?= $value ?>" <?= $s('theme_section_spacing', 'regular') === $value ? 'checked' : '' ?>> <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="form-group">
          <label for="theme_radius">Corner Radius (cards &amp; images): <span id="radiusVal"><?= (int)$s('theme_radius', '14') ?></span>px</label>
          <input type="range" id="theme_radius" name="theme_radius" min="0" max="30" step="1" value="<?= (int)$s('theme_radius', '14') ?>" oninput="document.getElementById('radiusVal').textContent=this.value">
        </div>
        <div class="form-group">
          <label>Card Shadows</label>
          <?php foreach (['none' => 'None (flat)', 'soft' => 'Soft', 'strong' => 'Strong'] as $value => $label): ?>
            <label style="font-weight:400;display:block;margin-bottom:6px;">
              <input type="radio" name="theme_shadow" value="<?= $value ?>" <?= $s('theme_shadow', 'soft') === $value ? 'checked' : '' ?>> <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="form-group">
          <label style="font-weight:400;"><input type="checkbox" name="theme_animations" <?= $s('theme_animations', '1') === '1' ? 'checked' : '' ?>> Enable animations (hover lifts &amp; smooth transitions)</label>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>Preview</h2></div>
        <p class="form-hint">Save, then open the website — every change applies instantly, no coding required.</p>
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-outline">Open Website ↗</a>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">💾 Save Theme</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
