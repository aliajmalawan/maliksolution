<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/page-sections.php';

// Pages with a custom-coded layout (working forms etc.): their sections are
// fixed by design, only their text (Settings tab) is edited here.
$customPages = ['admissions'];

/** Re-number a page's sections 1..n and return them in order. */
function page_sections_all(PDO $pdo, int $pageId): array
{
    $stmt = $pdo->prepare('SELECT * FROM page_sections WHERE page_id = ? ORDER BY sort_order, id');
    $stmt->execute([$pageId]);
    $rows = $stmt->fetchAll();
    $upd = $pdo->prepare('UPDATE page_sections SET sort_order = ? WHERE id = ?');
    foreach ($rows as $i => $row) {
        if ((int)$row['sort_order'] !== $i + 1) {
            $upd->execute([$i + 1, (int)$row['id']]);
            $rows[$i]['sort_order'] = $i + 1;
        }
    }
    return $rows;
}

// ---------- POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/pages.php');
    }

    $action = (string)($_POST['action'] ?? 'save_settings');
    $pageId = (int)($_POST['page_id'] ?? ($_POST['id'] ?? 0));
    $tab = in_array((string)($_POST['tab'] ?? ''), ['settings', 'seo', 'sections'], true) ? (string)$_POST['tab'] : 'settings';
    $back = BASE_URL . '/admin/pages.php?edit=' . $pageId . '&tab=' . $tab;

    if ($action === 'save_settings') {
        $title = trim((string)($_POST['title'] ?? ''));
        if (isset($_POST['body'])) {
            $pdo->prepare('UPDATE pages SET title=?, body=? WHERE id=?')
                ->execute([$title, (string)$_POST['body'], $pageId]);
        } else {
            $pdo->prepare('UPDATE pages SET title=? WHERE id=?')->execute([$title, $pageId]);
        }
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'page_edit', 'Edited page #' . $pageId . ' settings');
        flash_set('success', 'Page settings saved.');
        redirect($back);
    }

    if ($action === 'save_seo') {
        $pdo->prepare('UPDATE pages SET meta_description=? WHERE id=?')
            ->execute([trim((string)($_POST['meta_description'] ?? '')), $pageId]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'page_edit', 'Edited page #' . $pageId . ' SEO');
        flash_set('success', 'SEO settings saved.');
        redirect($back);
    }

    if ($action === 'section_add') {
        $type = (string)($_POST['section_key'] ?? '');
        $types = page_section_types();
        if (isset($types[$type])) {
            $max = 0;
            foreach (page_sections_all($pdo, $pageId) as $row) {
                $max = max($max, (int)$row['sort_order']);
            }
            $pdo->prepare('INSERT INTO page_sections (page_id, section_key, label, settings, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)')
                ->execute([$pageId, $type, $types[$type]['label'], json_encode(new stdClass()), $max + 1]);
            flash_set('success', $types[$type]['label'] . ' section added — now fill in its content.');
            redirect($back . '&secedit=' . (int)$pdo->lastInsertId() . '#section-editor');
        }
        redirect($back);
    }

    $secId = (int)($_POST['section_id'] ?? 0);
    $secStmt = $pdo->prepare('SELECT * FROM page_sections WHERE id = ? AND page_id = ?');
    $secStmt->execute([$secId, $pageId]);
    $section = $secStmt->fetch();

    if ($section && $action === 'section_toggle') {
        $pdo->prepare('UPDATE page_sections SET is_active = 1 - is_active WHERE id = ?')->execute([$secId]);
        flash_set('success', 'Section ' . ($section['is_active'] ? 'disabled' : 'enabled') . '.');
        redirect($back . '#sections-list');
    }

    if ($section && $action === 'section_delete') {
        $pdo->prepare('DELETE FROM page_sections WHERE id = ?')->execute([$secId]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'page_section_delete', 'Deleted section "' . $section['label'] . '" from page #' . $pageId);
        flash_set('success', 'Section deleted.');
        redirect($back . '#sections-list');
    }

    if ($section && $action === 'section_move') {
        $dir = ($_POST['dir'] ?? '') === 'up' ? -1 : 1;
        $rows = page_sections_all($pdo, $pageId);
        foreach ($rows as $i => $row) {
            if ((int)$row['id'] === $secId && isset($rows[$i + $dir])) {
                $other = $rows[$i + $dir];
                $upd = $pdo->prepare('UPDATE page_sections SET sort_order = ? WHERE id = ?');
                $upd->execute([(int)$other['sort_order'], $secId]);
                $upd->execute([(int)$row['sort_order'], (int)$other['id']]);
                break;
            }
        }
        redirect($back . '#sections-list');
    }

    if ($section && $action === 'section_save') {
        $S = json_decode((string)$section['settings'], true) ?: [];
        $S['heading'] = trim((string)($_POST['heading'] ?? ''));

        switch ($section['section_key']) {
            case 'content':
            case 'image-text':
            case 'story':
                $S['body'] = (string)($_POST['body'] ?? '');
                if (in_array($section['section_key'], ['image-text', 'story'], true)) {
                    if (!empty($_FILES['image']['name'])) {
                        $path = upload_image($_FILES['image'], 'pages');
                        if ($path) {
                            $S['image'] = 'uploads/' . $path;
                        } else {
                            flash_set('error', 'Image upload failed (JPG/PNG/WEBP, max 5 MB).');
                        }
                    }
                    if (isset($_POST['remove_image'])) {
                        $S['image'] = '';
                    }
                }
                if ($section['section_key'] === 'image-text') {
                    $S['side'] = ($_POST['side'] ?? 'left') === 'right' ? 'right' : 'left';
                    $S['btn_label'] = trim((string)($_POST['btn_label'] ?? ''));
                    $S['btn_link'] = trim((string)($_POST['btn_link'] ?? ''));
                }
                if ($section['section_key'] === 'story') {
                    $S['eyebrow'] = trim((string)($_POST['eyebrow'] ?? ''));
                    $S['badge_number'] = trim((string)($_POST['badge_number'] ?? ''));
                    $S['badge_text'] = trim((string)($_POST['badge_text'] ?? ''));
                    $S['checks'] = array_values(array_filter(array_map(
                        fn($c) => mb_substr(trim((string)$c), 0, 120),
                        (array)($_POST['checks'] ?? [])
                    ), fn($c) => $c !== ''));
                }
                break;
            case 'cards':
                $S['eyebrow'] = trim((string)($_POST['eyebrow'] ?? ''));
                $S['subheading'] = trim((string)($_POST['subheading'] ?? ''));
                $cards = [];
                foreach ((array)($_POST['card_title'] ?? []) as $i => $title) {
                    $title = trim((string)$title);
                    if ($title === '') {
                        continue;
                    }
                    $cards[] = [
                        'icon' => mb_substr(trim((string)($_POST['card_icon'][$i] ?? '')), 0, 8),
                        'title' => mb_substr($title, 0, 100),
                        'text' => mb_substr(trim((string)($_POST['card_text'][$i] ?? '')), 0, 300),
                    ];
                }
                $S['cards'] = $cards;
                break;
            case 'gallery':
                $S['subheading'] = trim((string)($_POST['subheading'] ?? ''));
                $S['count'] = max(4, min(12, (int)($_POST['count'] ?? 8)));
                break;
            case 'cta':
                $S['text'] = trim((string)($_POST['text'] ?? ''));
                $S['btn_label'] = trim((string)($_POST['btn_label'] ?? ''));
                $S['btn_link'] = trim((string)($_POST['btn_link'] ?? ''));
                break;
        }

        $label = trim((string)($_POST['label'] ?? '')) ?: $section['label'];
        $pdo->prepare('UPDATE page_sections SET label = ?, settings = ? WHERE id = ?')
            ->execute([mb_substr($label, 0, 100), json_encode($S, JSON_UNESCAPED_SLASHES), $secId]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'page_section_save', 'Saved section "' . $label . '" on page #' . $pageId);
        flash_set('success', 'Section saved.');
        redirect($back . '&secedit=' . $secId . '#section-editor');
    }

    redirect($back);
}

// ---------- Data ----------
$pages = $pdo->query('SELECT * FROM pages ORDER BY title')->fetchAll();
$sectionCounts = [];
foreach ($pdo->query('SELECT page_id, COUNT(*) c FROM page_sections GROUP BY page_id') as $row) {
    $sectionCounts[(int)$row['page_id']] = (int)$row['c'];
}

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($pages as $p) {
        if ((int)$p['id'] === (int)$_GET['edit']) {
            $editing = $p;
            break;
        }
    }
}
$tab = in_array((string)($_GET['tab'] ?? ''), ['settings', 'seo', 'sections'], true) ? (string)$_GET['tab'] : 'settings';
$isCustom = $editing && in_array($editing['slug'], $customPages, true);
$sections = $editing && !$isCustom ? page_sections_all($pdo, (int)$editing['id']) : [];
$hasBuilder = $editing && !$isCustom;

$secEditing = null;
if ($hasBuilder && isset($_GET['secedit'])) {
    foreach ($sections as $s) {
        if ((int)$s['id'] === (int)$_GET['secedit']) {
            $secEditing = $s;
            break;
        }
    }
}
$SE = $secEditing ? (json_decode((string)$secEditing['settings'], true) ?: []) : [];
$types = page_section_types();

// Fixed-design section guides for the custom pages (shown read-only in their Sections tab).
$customGuide = [
    'admissions' => [
        ['Top banner (purple) with the page title', 'Settings tab'],
        ['Intro text — from the Settings tab (shown as plain text)', 'Settings tab'],
        ['4 admission steps + "Apply Online" panel — session and open/closed from Settings', 'Admin → Settings'],
        ['Inquiry form and "Talk to Admissions" card — contact info from Settings', 'Admin → Settings'],
    ],
];

$pageTitle = 'Pages';
require_once __DIR__ . '/includes/header.php';

$tabUrl = fn(string $t) => 'pages.php?edit=' . (int)($editing['id'] ?? 0) . '&tab=' . $t;
?>

<div class="card">
  <div class="card-header"><h2>Pages</h2></div>
  <p class="form-hint" style="margin-bottom:14px;">Every website page with its sections. Press <strong>Sections</strong> to add, edit, reorder or hide the blocks that build a page.</p>
  <table class="pages-table">
    <tr><th>Title</th><th>Slug</th><th>Status</th><th>Sections</th><th style="text-align:right;">Actions</th></tr>
    <?php foreach ($pages as $p): $isRowCustom = in_array($p['slug'], $customPages, true); ?>
    <tr<?= $editing && $editing['id'] == $p['id'] ? ' class="row-active"' : '' ?>>
      <td><strong><?= e($p['title']) ?></strong></td>
      <td><code>/<?= e($p['slug']) ?></code></td>
      <td><span class="badge badge-published">published</span></td>
      <td><?= $isRowCustom ? '<span title="Custom-designed page">custom</span>' : (int)($sectionCounts[(int)$p['id']] ?? 0) ?></td>
      <td style="text-align:right;white-space:nowrap;">
        <a href="pages.php?edit=<?= (int)$p['id'] ?>&tab=settings" class="btn btn-outline btn-sm">Edit</a>
        <a href="pages.php?edit=<?= (int)$p['id'] ?>&tab=sections" class="btn btn-outline btn-sm" style="color:var(--primary);border-color:var(--primary);">Sections</a>
        <a href="<?= BASE_URL ?>/<?= e($p['slug']) ?>.php" target="_blank" rel="noopener" class="btn btn-outline btn-sm">View</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php if ($editing): ?>
<div class="card">
  <div class="card-header">
    <h2><?= e($editing['title']) ?></h2>
    <a href="<?= BASE_URL ?>/<?= e($editing['slug']) ?>.php" target="_blank" rel="noopener" class="btn btn-outline btn-sm">View Page ↗</a>
  </div>

  <div class="page-tabs">
    <a href="<?= $tabUrl('settings') ?>" class="<?= $tab === 'settings' ? 'active' : '' ?>">Settings</a>
    <a href="<?= $tabUrl('seo') ?>" class="<?= $tab === 'seo' ? 'active' : '' ?>">SEO</a>
    <a href="<?= $tabUrl('sections') ?>" class="<?= $tab === 'sections' ? 'active' : '' ?>">Sections<?= $hasBuilder ? ' (' . count($sections) . ')' : '' ?></a>
  </div>

  <?php if ($tab === 'settings'): ?>
    <?php $showBody = $isCustom || empty($sections); ?>
    <form method="post" action="pages.php" id="pageForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_settings">
      <input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>">
      <input type="hidden" name="tab" value="settings">
      <div class="form-group">
        <label for="title">Page Title (shown in the purple banner)</label>
        <input type="text" id="title" name="title" value="<?= e($editing['title']) ?>">
      </div>
      <?php if ($showBody): ?>
      <div class="form-group">
        <label>Main Content</label>
        <div class="editor-toolbar">
          <button type="button" data-cmd="bold"><b>B</b></button>
          <button type="button" data-cmd="italic"><i>I</i></button>
          <button type="button" data-cmd="underline"><u>U</u></button>
          <button type="button" data-cmd="insertUnorderedList">• List</button>
          <button type="button" data-cmd="formatBlock" data-value="H3">H3</button>
          <button type="button" data-cmd="formatBlock" data-value="P">Paragraph</button>
          <button type="button" data-cmd="createLink">Link</button>
        </div>
        <div id="editor" contenteditable="true" class="rich-editor"><?= $editing['body'] ?? '' ?></div>
        <textarea name="body" id="bodyInput" style="display:none;"></textarea>
      </div>
      <?php else: ?>
      <p class="form-hint" style="margin-bottom:14px;">✨ This page's content is built from <strong>sections</strong> — edit the text in the <a href="<?= $tabUrl('sections') ?>" style="color:var(--primary);">Sections tab</a>.</p>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>

  <?php elseif ($tab === 'seo'): ?>
    <form method="post" action="pages.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_seo">
      <input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>">
      <input type="hidden" name="tab" value="seo">
      <div class="form-group">
        <label for="meta_description">Meta Description (shown in Google results)</label>
        <input type="text" id="meta_description" name="meta_description" maxlength="300" value="<?= e($editing['meta_description'] ?? '') ?>">
        <p class="form-hint">One or two sentences, up to ~160 characters is ideal.</p>
      </div>
      <div class="form-group">
        <label>Page URL</label>
        <input type="text" value="<?= e(BASE_URL) ?>/<?= e($editing['slug']) ?>.php" readonly style="opacity:.7;">
      </div>
      <button type="submit" class="btn btn-primary">Save SEO</button>
    </form>

  <?php elseif ($tab === 'sections' && !$hasBuilder): ?>
    <p class="form-hint" style="margin-bottom:12px;">This page has a <strong>custom design</strong> — its sections are fixed. Here is what it contains, top to bottom, and where each part is edited:</p>
    <ol class="page-sections-list">
      <?php foreach ($customGuide[$editing['slug']] ?? [] as [$label, $where]): ?>
      <li><span><?= e($label) ?></span> <span class="tag-where"><?= e($where) ?></span></li>
      <?php endforeach; ?>
    </ol>

  <?php elseif ($tab === 'sections'): ?>
    <div id="sections-list"></div>
    <?php if (empty($sections)): ?>
      <div class="empty-state">This page has no sections yet — add your first one below. (Until then, the old "Main Content" text from the Settings tab is shown.)</div>
    <?php else: ?>
    <div class="sec-list">
      <?php foreach ($sections as $i => $s): ?>
      <div class="sec-row<?= $s['is_active'] ? '' : ' sec-row-off' ?><?= $secEditing && $secEditing['id'] === $s['id'] ? ' sec-row-editing' : '' ?>">
        <div class="sec-row-main">
          <strong><?= e($s['label']) ?></strong>
          <small><?= e($types[$s['section_key']]['label'] ?? $s['section_key']) ?> · Position <?= (int)$s['sort_order'] ?></small>
        </div>
        <?php if (!$s['is_active']): ?><span class="badge badge-draft">Disabled</span><?php endif; ?>
        <form method="post" action="pages.php"><?= csrf_field() ?><input type="hidden" name="action" value="section_move"><input type="hidden" name="dir" value="up"><input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>"><input type="hidden" name="tab" value="sections"><input type="hidden" name="section_id" value="<?= (int)$s['id'] ?>"><button class="btn btn-outline btn-sm" <?= $i === 0 ? 'disabled' : '' ?> title="Move up">↑</button></form>
        <form method="post" action="pages.php"><?= csrf_field() ?><input type="hidden" name="action" value="section_move"><input type="hidden" name="dir" value="down"><input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>"><input type="hidden" name="tab" value="sections"><input type="hidden" name="section_id" value="<?= (int)$s['id'] ?>"><button class="btn btn-outline btn-sm" <?= $i === count($sections) - 1 ? 'disabled' : '' ?> title="Move down">↓</button></form>
        <a href="<?= $tabUrl('sections') ?>&secedit=<?= (int)$s['id'] ?>#section-editor" class="btn btn-outline btn-sm">Edit</a>
        <form method="post" action="pages.php"><?= csrf_field() ?><input type="hidden" name="action" value="section_toggle"><input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>"><input type="hidden" name="tab" value="sections"><input type="hidden" name="section_id" value="<?= (int)$s['id'] ?>"><button class="btn btn-outline btn-sm"><?= $s['is_active'] ? 'Disable' : 'Enable' ?></button></form>
        <form method="post" action="pages.php"><?= csrf_field() ?><input type="hidden" name="action" value="section_delete"><input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>"><input type="hidden" name="tab" value="sections"><input type="hidden" name="section_id" value="<?= (int)$s['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete this section?">Delete</button></form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="pages.php" style="display:flex;gap:10px;align-items:center;margin-top:16px;flex-wrap:wrap;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="section_add">
      <input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>">
      <input type="hidden" name="tab" value="sections">
      <select name="section_key" style="padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--surface-2);color:var(--text);font-family:inherit;">
        <?php foreach ($types as $key => $t): ?>
        <option value="<?= e($key) ?>"><?= e($t['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">➕ Add Section</button>
    </form>

    <?php if ($secEditing): $tHint = $types[$secEditing['section_key']]['hint'] ?? ''; ?>
    <div class="card-header" id="section-editor" style="margin-top:22px;scroll-margin-top:20px;"><h2>✏️ Edit Section: <?= e($secEditing['label']) ?></h2></div>
    <?php if ($tHint): ?><p class="form-hint" style="margin-bottom:12px;"><?= e($tHint) ?></p><?php endif; ?>
    <form method="post" action="pages.php" id="pageForm" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="section_save">
      <input type="hidden" name="page_id" value="<?= (int)$editing['id'] ?>">
      <input type="hidden" name="tab" value="sections">
      <input type="hidden" name="section_id" value="<?= (int)$secEditing['id'] ?>">

      <div class="form-row">
        <div class="form-group">
          <label for="label">Section Name (only shown here in admin)</label>
          <input type="text" id="label" name="label" value="<?= e($secEditing['label']) ?>">
        </div>
        <div class="form-group">
          <label for="heading">Heading (shown on the page<?= $secEditing['section_key'] === 'stats' ? ' — not used for stats' : '' ?>)</label>
          <input type="text" id="heading" name="heading" value="<?= e($SE['heading'] ?? '') ?>">
        </div>
      </div>

      <?php if (in_array($secEditing['section_key'], ['content', 'image-text', 'story'], true)): ?>
      <div class="form-group">
        <label>Text</label>
        <div class="editor-toolbar">
          <button type="button" data-cmd="bold"><b>B</b></button>
          <button type="button" data-cmd="italic"><i>I</i></button>
          <button type="button" data-cmd="underline"><u>U</u></button>
          <button type="button" data-cmd="insertUnorderedList">• List</button>
          <button type="button" data-cmd="formatBlock" data-value="H3">H3</button>
          <button type="button" data-cmd="formatBlock" data-value="P">Paragraph</button>
          <button type="button" data-cmd="createLink">Link</button>
        </div>
        <div id="editor" contenteditable="true" class="rich-editor"><?= $SE['body'] ?? '' ?></div>
        <textarea name="body" id="bodyInput" style="display:none;"></textarea>
      </div>
      <?php endif; ?>

      <?php if (in_array($secEditing['section_key'], ['image-text', 'story'], true)): ?>
      <div class="form-row">
        <div class="form-group">
          <?php if (!empty($SE['image'])): ?>
            <img src="<?= BASE_URL ?>/<?= e($SE['image']) ?>" class="current-image" alt="Section image">
            <label style="font-weight:400;"><input type="checkbox" name="remove_image"> Remove this picture</label>
          <?php endif; ?>
          <label for="image">Picture <?= empty($SE['image']) ? '' : '(choose a file to replace the current one)' ?></label>
          <input type="file" id="image" name="image" accept="image/*">
          <p class="form-hint">JPG/PNG/WEBP, max 5 MB. Recommended size: <strong>1200 × 900 px</strong> (4:3 landscape).</p>
        </div>
        <?php if ($secEditing['section_key'] === 'image-text'): ?>
        <div class="form-group">
          <label for="side">Picture Position</label>
          <select id="side" name="side">
            <option value="left" <?= ($SE['side'] ?? 'left') === 'left' ? 'selected' : '' ?>>Left of the text</option>
            <option value="right" <?= ($SE['side'] ?? '') === 'right' ? 'selected' : '' ?>>Right of the text</option>
          </select>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($secEditing['section_key'] === 'image-text'): ?>
      <div class="form-row">
        <div class="form-group">
          <label for="btn_label">Button Text (optional)</label>
          <input type="text" id="btn_label" name="btn_label" placeholder="e.g. Read More" value="<?= e($SE['btn_label'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="btn_link">Button Link</label>
          <input type="text" id="btn_link" name="btn_link" placeholder="e.g. about.php" value="<?= e($SE['btn_link'] ?? '') ?>">
        </div>
      </div>
      <?php endif; ?>

      <?php if ($secEditing['section_key'] === 'story'): ?>
      <div class="form-row">
        <div class="form-group">
          <label for="eyebrow">Eyebrow (small gold label above the heading)</label>
          <input type="text" id="eyebrow" name="eyebrow" value="<?= e($SE['eyebrow'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="badge_number">Badge Number (on the photo, e.g. "8+")</label>
          <input type="text" id="badge_number" name="badge_number" maxlength="10" value="<?= e($SE['badge_number'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="badge_text">Badge Text (e.g. "Years of Excellence")</label>
        <input type="text" id="badge_text" name="badge_text" maxlength="40" value="<?= e($SE['badge_text'] ?? '') ?>">
        <p class="form-hint">Leave the badge number empty to hide the badge completely.</p>
      </div>
      <div class="form-group">
        <label>Green Check-list (up to 4 lines, leave empty to skip)</label>
        <?php $checks = (array)($SE['checks'] ?? []); ?>
        <?php for ($i = 0; $i < 4; $i++): ?>
        <input type="text" name="checks[]" value="<?= e((string)($checks[$i] ?? '')) ?>" placeholder="e.g. Experienced &amp; caring faculty" maxlength="120" style="margin-bottom:8px;">
        <?php endfor; ?>
      </div>
      <?php endif; ?>

      <?php if ($secEditing['section_key'] === 'cards'): ?>
      <div class="form-row">
        <div class="form-group">
          <label for="eyebrow">Eyebrow (small gold label above the heading)</label>
          <input type="text" id="eyebrow" name="eyebrow" value="<?= e($SE['eyebrow'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="subheading">Sub-heading (small text under the heading)</label>
          <input type="text" id="subheading" name="subheading" value="<?= e($SE['subheading'] ?? '') ?>">
        </div>
      </div>
      <p class="form-hint" style="margin-bottom:10px;">Up to 6 cards — leave a title empty to skip that row.</p>
      <?php $editCards = is_array($SE['cards'] ?? null) ? $SE['cards'] : []; ?>
      <?php for ($i = 0; $i < 6; $i++): $card = $editCards[$i] ?? ['icon' => '', 'title' => '', 'text' => '']; ?>
      <div class="hb-card-row">
        <input type="text" name="card_icon[]" value="<?= e($card['icon'] ?? '') ?>" placeholder="🎓" maxlength="8" title="Icon (emoji)" style="width:64px;text-align:center;">
        <input type="text" name="card_title[]" value="<?= e($card['title'] ?? '') ?>" placeholder="Card title" maxlength="100" style="flex:1;min-width:140px;">
        <input type="text" name="card_text[]" value="<?= e($card['text'] ?? '') ?>" placeholder="Short description" maxlength="300" style="flex:2;min-width:180px;">
      </div>
      <?php endfor; ?>
      <?php endif; ?>

      <?php if ($secEditing['section_key'] === 'gallery'): ?>
      <div class="form-row">
        <div class="form-group">
          <label for="subheading">Sub-heading</label>
          <input type="text" id="subheading" name="subheading" value="<?= e($SE['subheading'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="count">How many photos (4–12)</label>
          <input type="number" id="count" name="count" min="4" max="12" value="<?= (int)($SE['count'] ?? 8) ?>">
          <p class="form-hint">Photos come automatically from Admin → Gallery (newest first).</p>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($secEditing['section_key'] === 'cta'): ?>
      <div class="form-group">
        <label for="text">Text under the heading</label>
        <input type="text" id="text" name="text" value="<?= e($SE['text'] ?? '') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="btn_label">Button Text</label>
          <input type="text" id="btn_label" name="btn_label" placeholder="Apply for Admission" value="<?= e($SE['btn_label'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="btn_link">Button Link</label>
          <input type="text" id="btn_link" name="btn_link" placeholder="admissions.php" value="<?= e($SE['btn_link'] ?? '') ?>">
        </div>
      </div>
      <?php endif; ?>

      <?php if ($secEditing['section_key'] === 'stats'): ?>
      <p class="form-hint" style="margin-bottom:14px;">The four numbers (Students, Faculty, Years, Result Rate) are edited in <strong>Admin → Settings</strong> — this section just displays them.</p>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary">Save Section</button>
      <a href="<?= $tabUrl('sections') ?>#sections-list" class="btn btn-outline">Close</a>
    </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h2>🖼 Picture Size Guide (whole website)</h2></div>
  <p class="form-hint" style="margin-bottom:12px;">Which picture is uploaded where, and the best size. Bigger is fine — pictures are cropped and optimized automatically.</p>
  <table class="pages-img-table">
    <tr><th>Picture</th><th>Where to upload</th><th>Recommended size</th></tr>
    <tr><td>School logo</td><td>Admin → Settings → General</td><td><strong>200 × 200 px</strong> (square)</td></tr>
    <tr><td>Hero slider (homepage top)</td><td>Admin → Hero Slider</td><td><strong>1920 × 800 px</strong> (wide)</td></tr>
    <tr><td>Section background images</td><td>Admin → Homepage Builder</td><td><strong>1920 × 1080 px</strong> (wide)</td></tr>
    <tr><td>About section photo (homepage)</td><td>Admin → Homepage Builder → About</td><td><strong>1200 × 900 px</strong> (4:3)</td></tr>
    <tr><td>Principal's photo</td><td>Admin → Homepage Builder → Principal's Message</td><td><strong>800 × 1000 px</strong> (portrait)</td></tr>
    <tr><td>"Image + Text" page sections</td><td>Admin → Pages → Sections</td><td><strong>1200 × 900 px</strong> (4:3)</td></tr>
    <tr><td>Gallery photos</td><td>Admin → Gallery</td><td><strong>1600 × 1200 px</strong> or bigger</td></tr>
    <tr><td>News / Blog cover images</td><td>Admin → News / Blogs</td><td><strong>1200 × 675 px</strong> (16:9)</td></tr>
    <tr><td>Faculty photos</td><td>Admin → Faculty</td><td><strong>600 × 600 px</strong> (square)</td></tr>
    <tr><td>Popup announcement image</td><td>Admin → Settings → Popup</td><td><strong>800 × 600 px</strong></td></tr>
  </table>
</div>

<style>
.pages-table { width:100%; border-collapse:collapse; font-size:14px; }
.pages-table th, .pages-table td { text-align:left; padding:10px 12px; border-bottom:1px solid var(--border); }
.pages-table th { font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
.pages-table tr.row-active td { background:rgba(46,27,107,.05); }
.pages-table .btn { margin-left:4px; }
.page-tabs { display:flex; gap:4px; border-bottom:2px solid var(--border); margin-bottom:20px; }
.page-tabs a { padding:10px 18px; font-weight:600; color:var(--muted); border-radius:8px 8px 0 0; border-bottom:2px solid transparent; margin-bottom:-2px; }
.page-tabs a.active { color:var(--primary); border-bottom-color:var(--primary); background:rgba(46,27,107,.05); }
.sec-list { display:flex; flex-direction:column; gap:8px; }
.sec-row { display:flex; align-items:center; gap:8px; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px; background:var(--surface-2); }
.sec-row form { display:inline; margin:0; }
.sec-row-off { opacity:.6; }
.sec-row-editing { border-color:var(--primary); }
.sec-row-main { flex:1; min-width:0; }
.sec-row-main strong { display:block; }
.sec-row-main small { color:var(--muted); font-size:12px; }
.page-sections-list { margin:0; padding-left:22px; display:flex; flex-direction:column; gap:8px; }
.page-sections-list li { line-height:1.5; }
.tag-where { display:inline-block; font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px; background:#eef0f4; color:#5b6270; margin-left:6px; white-space:nowrap; }
.pages-img-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.pages-img-table th, .pages-img-table td { text-align:left; padding:9px 12px; border-bottom:1px solid var(--border); }
.pages-img-table th { font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
@media (max-width:760px) {
  .pages-table, .pages-img-table { display:block; overflow-x:auto; }
  .sec-row { flex-wrap:wrap; }
}
.editor-toolbar { display:flex; gap:6px; margin-bottom:8px; flex-wrap:wrap; }
.editor-toolbar button { padding:8px 12px; border:1.5px solid var(--border); background:var(--white); border-radius:8px; cursor:pointer; font-size:13px; }
.editor-toolbar button:hover { border-color:var(--primary); color:var(--primary); }
.rich-editor { min-height:220px; border:1.5px solid var(--border); border-radius:9px; padding:16px; background:var(--white); }
.rich-editor:focus { outline:none; border-color:var(--primary); }
.rich-editor h3 { margin-top:0; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var editor = document.getElementById('editor');
  var form = document.getElementById('pageForm');

  document.querySelectorAll('.editor-toolbar button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var cmd = btn.getAttribute('data-cmd');
      var value = btn.getAttribute('data-value') || '';
      if (!editor) return;
      editor.focus();
      if (cmd === 'createLink') {
        var url = prompt('Enter URL:', 'https://');
        if (url) document.execCommand(cmd, false, url);
      } else {
        document.execCommand(cmd, false, value);
      }
    });
  });

  if (form && editor) {
    form.addEventListener('submit', function () {
      var input = document.getElementById('bodyInput');
      if (input) input.value = editor.innerHTML;
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
