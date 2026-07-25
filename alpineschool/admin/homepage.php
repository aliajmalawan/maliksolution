<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/homepage.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_order') {
        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            $stmt = $pdo->prepare('UPDATE homepage_sections SET sort_order = ? WHERE id = ?');
            foreach (array_values($order) as $position => $id) {
                $stmt->execute([$position + 1, (int)$id]);
            }
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'homepage_reorder', 'Reordered homepage sections');
            flash_set('success', 'Section order saved.');
        }
        redirect(BASE_URL . '/admin/homepage.php');
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE homepage_sections SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'homepage_toggle', 'Toggled homepage section #' . $id);
        flash_set('success', 'Section visibility updated.');
        redirect(BASE_URL . '/admin/homepage.php');
    }

    if ($action === 'save_settings') {
        $key = (string)($_POST['section_key'] ?? '');
        $stmt = $pdo->prepare('SELECT * FROM homepage_sections WHERE section_key = ?');
        $stmt->execute([$key]);
        $section = $stmt->fetch();
        if (!$section) {
            flash_set('error', 'Section not found.');
            redirect(BASE_URL . '/admin/homepage.php');
        }

        $S = json_decode((string)$section['settings'], true) ?: [];

        $S['eyebrow'] = trim((string)($_POST['eyebrow'] ?? ''));
        $S['heading'] = trim((string)($_POST['heading'] ?? ''));
        $S['subheading'] = trim((string)($_POST['subheading'] ?? ''));

        $bgType = (string)($_POST['bg_type'] ?? 'default');
        $S['bg_type'] = in_array($bgType, ['default', 'color', 'image', 'video'], true) ? $bgType : 'default';
        $S['bg_color'] = trim((string)($_POST['bg_color'] ?? ''));
        $S['text_color'] = trim((string)($_POST['text_color'] ?? ''));
        $S['overlay'] = max(0, min(90, (int)($_POST['overlay'] ?? 0)));

        // Only allow safe hex/empty colors.
        foreach (['bg_color', 'text_color'] as $c) {
            if ($S[$c] !== '' && !preg_match('/^#[0-9a-fA-F]{3,8}$/', $S[$c])) {
                $S[$c] = '';
            }
        }

        if (!empty($_FILES['bg_image']['name'])) {
            $path = upload_image($_FILES['bg_image'], 'homepage');
            if ($path) {
                $S['bg_image'] = 'uploads/' . $path;
            } else {
                flash_set('error', 'Background image upload failed (JPG/PNG/WEBP, max 5 MB).');
            }
        }
        if (!empty($_FILES['bg_video']['name'])) {
            $path = upload_video($_FILES['bg_video'], 'homepage');
            if ($path) {
                $S['bg_video'] = 'uploads/' . $path;
            } else {
                flash_set('error', 'Video upload failed (MP4/WebM, max 50 MB).');
            }
        }
        if (!empty($_FILES['side_image']['name'])) {
            $path = upload_image($_FILES['side_image'], 'homepage');
            if ($path) {
                $S['side_image'] = 'uploads/' . $path;
            }
        }
        if (isset($_POST['remove_bg_image'])) {
            $S['bg_image'] = '';
        }
        if (isset($_POST['remove_bg_video'])) {
            $S['bg_video'] = '';
        }
        if (isset($_POST['remove_side_image'])) {
            $S['side_image'] = '';
        }

        // Repeatable content cards (Why Us / Programs / Quick Links).
        if (isset($_POST['card_title']) && is_array($_POST['card_title'])) {
            $cards = [];
            foreach ($_POST['card_title'] as $i => $title) {
                $title = trim((string)$title);
                if ($title === '') {
                    continue; // blank rows are skipped
                }
                $cards[] = [
                    'icon' => mb_substr(trim((string)($_POST['card_icon'][$i] ?? '')), 0, 8),
                    'title' => mb_substr($title, 0, 100),
                    'text' => mb_substr(trim((string)($_POST['card_text'][$i] ?? '')), 0, 300),
                    'link' => mb_substr(trim((string)($_POST['card_link'][$i] ?? '')), 0, 255),
                ];
            }
            // Empty list = "use the built-in defaults".
            $S['cards'] = $cards;
            if (!$cards) {
                unset($S['cards']);
            }
        }

        $pdo->prepare('UPDATE homepage_sections SET settings = ? WHERE id = ?')
            ->execute([json_encode($S, JSON_UNESCAPED_SLASHES), (int)$section['id']]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'homepage_edit', 'Edited homepage section "' . $section['label'] . '"');
        flash_set('success', '"' . $section['label'] . '" settings saved.');
        redirect(BASE_URL . '/admin/homepage.php?edit=' . urlencode($key));
    }
}

$sections = $pdo->query('SELECT * FROM homepage_sections ORDER BY sort_order, id')->fetchAll();

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($sections as $section) {
        if ($section['section_key'] === $_GET['edit']) {
            $editing = $section;
            break;
        }
    }
}
$ES = $editing ? (json_decode((string)$editing['settings'], true) ?: []) : [];

$pageTitle = 'Homepage Builder';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid-2" style="grid-template-columns:5fr 7fr;align-items:start;">
  <div class="card">
    <div class="card-header">
      <h2>Sections</h2>
      <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-outline btn-sm">Preview Site ↗</a>
    </div>
    <p class="form-hint" style="margin-bottom:14px;">Drag sections to reorder, then press <strong>Save Order</strong>. Use the eye button to show/hide a section.</p>

    <form method="post" action="homepage.php" id="hbOrderForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_order">
      <div id="hbList">
        <?php foreach ($sections as $section): ?>
        <div class="hb-row<?= $section['is_active'] ? '' : ' hb-hidden' ?><?= $editing && $editing['id'] === $section['id'] ? ' hb-editing' : '' ?>" draggable="true" data-id="<?= (int)$section['id'] ?>">
          <span class="hb-grip" title="Drag to reorder">⠿</span>
          <span class="hb-label"><?= e($section['label']) ?></span>
          <span class="badge <?= $section['is_active'] ? 'badge-published' : 'badge-draft' ?>"><?= $section['is_active'] ? 'Visible' : 'Hidden' ?></span>
          <a href="homepage.php?edit=<?= e($section['section_key']) ?>" class="btn btn-outline btn-sm">Edit</a>
          <button type="button" class="btn btn-outline btn-sm hb-toggle" data-id="<?= (int)$section['id'] ?>" title="Show / hide"><?= $section['is_active'] ? '👁' : '🚫' ?></button>
          <input type="hidden" name="order[]" value="<?= (int)$section['id'] ?>">
        </div>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:14px;">💾 Save Order</button>
    </form>

    <form method="post" action="homepage.php" id="hbToggleForm" style="display:none;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle">
      <input type="hidden" name="id" id="hbToggleId" value="">
    </form>
  </div>

  <div class="card">
    <?php if (!$editing): ?>
      <div class="card-header"><h2>Section Settings</h2></div>
      <div class="empty-state">Select a section on the left and press <strong>Edit</strong> to customise its heading, colors, background image or video.</div>
    <?php else: ?>
      <div class="card-header">
        <h2>Edit: <?= e($editing['label']) ?></h2>
        <a href="homepage.php" class="btn btn-outline btn-sm">Close</a>
      </div>
      <form method="post" action="homepage.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_settings">
        <input type="hidden" name="section_key" value="<?= e($editing['section_key']) ?>">

        <?php if (!in_array($editing['section_key'], ['hero', 'stats'], true)): ?>
        <div class="form-row">
          <div class="form-group">
            <label for="eyebrow">Eyebrow (small label)</label>
            <input type="text" id="eyebrow" name="eyebrow" placeholder="default" value="<?= e($ES['eyebrow'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="heading">Heading</label>
            <input type="text" id="heading" name="heading" placeholder="default" value="<?= e($ES['heading'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="subheading">Sub-heading</label>
          <input type="text" id="subheading" name="subheading" placeholder="default" value="<?= e($ES['subheading'] ?? '') ?>">
          <p class="form-hint">Leave any field empty to keep the built-in default text.</p>
        </div>
        <?php elseif ($editing['section_key'] === 'hero'): ?>
        <div class="form-group">
          <label for="eyebrow">Eyebrow (small label above slide titles)</label>
          <input type="text" id="eyebrow" name="eyebrow" placeholder="Welcome to <?= e(get_setting($pdo, 'campus_name')) ?>" value="<?= e($ES['eyebrow'] ?? '') ?>">
          <p class="form-hint">Slides themselves are managed on the <a href="hero-slides.php" style="color:var(--primary);">Hero Slider</a> page.</p>
        </div>
        <?php endif; ?>

        <?php if ($editing['section_key'] !== 'hero'): ?>
        <div class="card-header" style="margin-top:8px;"><h2>Background</h2></div>
        <div class="form-row">
          <div class="form-group">
            <label for="bg_type">Background Type</label>
            <select id="bg_type" name="bg_type">
              <?php foreach (['default' => 'Default (theme)', 'color' => 'Solid color', 'image' => 'Image', 'video' => 'Video'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($ES['bg_type'] ?? 'default') === $value ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($editing['section_key'] === 'stats'): ?><p class="form-hint">The stats bar supports solid color only.</p><?php endif; ?>
          </div>
          <div class="form-group">
            <label for="overlay">Dark Overlay (image/video): <?= (int)($ES['overlay'] ?? 0) ?>%</label>
            <input type="range" id="overlay" name="overlay" min="0" max="90" step="5" value="<?= (int)($ES['overlay'] ?? 0) ?>" oninput="this.closest('.form-group').querySelector('label').textContent = 'Dark Overlay (image/video): ' + this.value + '%'">
            <p class="form-hint">Darkens the background so text stays readable.</p>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="bg_color">Background Color</label>
            <div class="color-input-row">
              <input type="color" id="bg_color" name="bg_color" value="<?= e(($ES['bg_color'] ?? '') ?: '#f4f6fb') ?>">
              <input type="text" value="<?= e($ES['bg_color'] ?? '') ?>" oninput="document.getElementById('bg_color').value=this.value" placeholder="#f4f6fb" style="width:auto;flex:1;">
            </div>
          </div>
          <div class="form-group">
            <label for="text_color">Text Color (optional)</label>
            <div class="color-input-row">
              <input type="color" id="text_color_pick" value="<?= e(($ES['text_color'] ?? '') ?: '#1f2430') ?>" oninput="document.getElementById('text_color').value=this.value">
              <input type="text" id="text_color" name="text_color" value="<?= e($ES['text_color'] ?? '') ?>" placeholder="empty = default" style="width:auto;flex:1;">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <?php if (!empty($ES['bg_image'])): ?>
              <img src="<?= BASE_URL ?>/<?= e($ES['bg_image']) ?>" class="current-image" alt="Background">
              <label style="font-weight:400;"><input type="checkbox" name="remove_bg_image"> Remove background image</label>
            <?php endif; ?>
            <label for="bg_image">Background Image</label>
            <input type="file" id="bg_image" name="bg_image" accept="image/*">
            <p class="form-hint">JPG/PNG/WEBP, max 5 MB. Recommended size: <strong>1920×1080 px</strong> (landscape) — it spans the full section width, so wide images look best.</p>
          </div>
          <div class="form-group">
            <?php if (!empty($ES['bg_video'])): ?>
              <video src="<?= BASE_URL ?>/<?= e($ES['bg_video']) ?>" style="width:150px;border-radius:10px;margin-bottom:8px;" muted loop autoplay playsinline></video>
              <label style="font-weight:400;"><input type="checkbox" name="remove_bg_video"> Remove background video</label>
            <?php endif; ?>
            <label for="bg_video">Background Video</label>
            <input type="file" id="bg_video" name="bg_video" accept="video/mp4,video/webm">
            <p class="form-hint">MP4/WebM, max 50 MB. Recommended: <strong>1920×1080 px</strong> (Full HD, landscape). Plays muted on loop.</p>
          </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($editing['section_key'], ['about', 'principal'], true)): ?>
        <div class="form-group">
          <?php if (!empty($ES['side_image'])): ?>
            <img src="<?= BASE_URL ?>/<?= e($ES['side_image']) ?>" class="current-image" alt="Side image">
            <label style="font-weight:400;"><input type="checkbox" name="remove_side_image"> Remove photo</label>
          <?php endif; ?>
          <label for="side_image"><?= $editing['section_key'] === 'principal' ? "Principal's Photo" : 'Section Photo (left side)' ?></label>
          <input type="file" id="side_image" name="side_image" accept="image/*">
          <?php if ($editing['section_key'] === 'principal'): ?>
            <p class="form-hint">JPG/PNG/WEBP, max 5 MB. Recommended size: <strong>800×1000 px</strong> (4:5 portrait) — a head-and-shoulders photo works best.</p>
            <p class="form-hint">The message text itself is edited in <a href="pages.php" style="color:var(--primary);">Pages → Message from the Principal</a>.</p>
          <?php else: ?>
            <p class="form-hint">JPG/PNG/WEBP, max 5 MB. Recommended size: <strong>1200×900 px</strong> (4:3 landscape) — shown beside the About text with the gradient frame.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php
        // Repeatable content cards for the card-based sections.
        $cardSections = [
            'why-us' => ['label' => 'Feature Cards', 'hasLink' => false, 'hint' => 'Up to 6 cards. Leave a title empty to skip that row; leave all empty to use the built-in defaults.'],
            'programs' => ['label' => 'Program Cards', 'hasLink' => true, 'hint' => 'Up to 6 programs with an optional "Learn More" link each.'],
            'quick-links' => ['label' => 'Quick Link Cards', 'hasLink' => true, 'hint' => 'The shortcut cards shown right under the hero. Up to 6.'],
        ];
        ?>
        <?php if (isset($cardSections[$editing['section_key']])): $cfg = $cardSections[$editing['section_key']]; ?>
        <div class="card-header" style="margin-top:8px;"><h2>🃏 <?= e($cfg['label']) ?></h2></div>
        <p class="form-hint" style="margin-bottom:12px;"><?= e($cfg['hint']) ?></p>
        <?php $editCards = $ES['cards'] ?? []; ?>
        <?php for ($i = 0; $i < 6; $i++): $card = $editCards[$i] ?? ['icon' => '', 'title' => '', 'text' => '', 'link' => '']; ?>
        <div class="hb-card-row">
          <input type="text" name="card_icon[]" value="<?= e($card['icon'] ?? '') ?>" placeholder="🎓" maxlength="8" title="Icon (emoji)" style="width:64px;text-align:center;">
          <input type="text" name="card_title[]" value="<?= e($card['title'] ?? '') ?>" placeholder="Card title" maxlength="100" style="flex:1;min-width:140px;">
          <input type="text" name="card_text[]" value="<?= e($card['text'] ?? '') ?>" placeholder="Short description" maxlength="300" style="flex:2;min-width:180px;">
          <?php if ($cfg['hasLink']): ?>
            <input type="text" name="card_link[]" value="<?= e($card['link'] ?? '') ?>" placeholder="Link (e.g. programs.php)" maxlength="255" style="flex:1;min-width:130px;">
          <?php else: ?>
            <input type="hidden" name="card_link[]" value="">
          <?php endif; ?>
        </div>
        <?php endfor; ?>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">Save Section</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
