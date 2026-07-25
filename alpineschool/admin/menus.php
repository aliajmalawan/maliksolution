<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

// ---------- POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/menus.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $menuId = (int)($_POST['menu_id'] ?? 0);
    $back = BASE_URL . '/admin/menus.php?menu=' . $menuId;

    if ($action === 'create_menu') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            flash_set('error', 'Menu name is required.');
            redirect(BASE_URL . '/admin/menus.php');
        }
        $slug = slugify($name);
        $base = $slug;
        $suffix = 1;
        $check = $pdo->prepare('SELECT id FROM menus WHERE slug = ?');
        $check->execute([$slug]);
        while ($check->fetch()) {
            $slug = $base . '-' . (++$suffix);
            $check->execute([$slug]);
        }
        $pdo->prepare('INSERT INTO menus (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'menu_create', 'Created menu "' . $name . '"');
        flash_set('success', 'Menu "' . $name . '" created.');
        redirect(BASE_URL . '/admin/menus.php?menu=' . (int)$pdo->lastInsertId());
    }

    if ($action === 'rename_menu') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '') {
            $pdo->prepare('UPDATE menus SET name = ? WHERE id = ?')->execute([$name, $menuId]);
            flash_set('success', 'Menu renamed.');
        }
        redirect($back);
    }

    if ($action === 'delete_menu') {
        $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ?');
        $stmt->execute([$menuId]);
        $menu = $stmt->fetch();
        if ($menu && $menu['slug'] === 'main') {
            flash_set('error', 'The Header Menu cannot be deleted — the site navigation depends on it.');
            redirect($back);
        }
        if ($menu) {
            $pdo->prepare('DELETE FROM menu_items WHERE menu_id = ?')->execute([$menuId]);
            $pdo->prepare('DELETE FROM menus WHERE id = ?')->execute([$menuId]);
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'menu_delete', 'Deleted menu "' . $menu['name'] . '"');
            flash_set('success', 'Menu deleted.');
        }
        redirect(BASE_URL . '/admin/menus.php');
    }

    if ($action === 'save_structure') {
        $order = array_values(array_map('intval', (array)($_POST['order'] ?? [])));
        $depths = array_values(array_map('intval', (array)($_POST['depth'] ?? [])));
        if ($order && count($order) === count($depths)) {
            $stmt = $pdo->prepare('UPDATE menu_items SET sort_order = ?, parent_id = ? WHERE id = ? AND menu_id = ?');
            $stack = []; // depth => last item id at that depth
            $prevDepth = 0;
            foreach ($order as $i => $id) {
                $depth = max(0, min($depths[$i], $i === 0 ? 0 : $prevDepth + 1));
                $parent = $depth > 0 ? ($stack[$depth - 1] ?? null) : null;
                $stmt->execute([$i + 1, $parent, $id, $menuId]);
                $stack[$depth] = $id;
                $prevDepth = $depth;
            }
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'menu_reorder', 'Rearranged menu structure');
            flash_set('success', 'Menu structure saved.');
        }
        redirect($back);
    }

    if ($action === 'delete_item') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
        flash_set('success', 'Menu item deleted (sub-items removed too).');
        redirect($back);
    }

    if ($action === 'save_item') {
        $id = (int)($_POST['id'] ?? 0);
        $label = trim((string)($_POST['label'] ?? ''));
        $url = trim((string)($_POST['url'] ?? '')) ?: '#';
        $icon = ''; // Icon feature removed — re-saving an item clears any legacy emoji.
        $newTab = isset($_POST['new_tab']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($label === '') {
            flash_set('error', 'Menu label is required.');
            redirect($back);
        }

        if ($id > 0) {
            $pdo->prepare('UPDATE menu_items SET label=?, url=?, icon=?, new_tab=?, is_active=? WHERE id=?')
                ->execute([$label, $url, $icon, $newTab, $isActive, $id]);
            flash_set('success', 'Menu item updated.');
        } else {
            $max = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) m FROM menu_items WHERE menu_id = ' . $menuId)->fetch()['m'];
            $pdo->prepare('INSERT INTO menu_items (menu_id, parent_id, label, url, icon, new_tab, sort_order, is_active) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)')
                ->execute([$menuId, $label, $url, $icon, $newTab, $max + 1, $isActive]);
            flash_set('success', 'Menu item added to the end — drag it into place.');
        }
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'menu_item_save', 'Saved menu item "' . $label . '"');
        redirect($back);
    }
}

// ---------- Data ----------
$menus = $pdo->query('SELECT * FROM menus ORDER BY id')->fetchAll();
$activeMenuId = (int)($_GET['menu'] ?? ($menus[0]['id'] ?? 0));
$activeMenu = null;
foreach ($menus as $menu) {
    if ((int)$menu['id'] === $activeMenuId) {
        $activeMenu = $menu;
        break;
    }
}
if (!$activeMenu && $menus) {
    $activeMenu = $menus[0];
    $activeMenuId = (int)$activeMenu['id'];
}

$stmt = $pdo->prepare('SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order, id');
$stmt->execute([$activeMenuId]);
$items = $stmt->fetchAll();

$byParent = [];
foreach ($items as $item) {
    $byParent[(int)($item['parent_id'] ?? 0)][] = $item;
}

/** Flatten the tree depth-first into rows with depth. */
function menu_flatten(array $byParent, int $parent = 0, int $depth = 0): array
{
    $rows = [];
    foreach ($byParent[$parent] ?? [] as $item) {
        $rows[] = ['item' => $item, 'depth' => $depth];
        $rows = array_merge($rows, menu_flatten($byParent, (int)$item['id'], $depth + 1));
    }
    return $rows;
}
$flat = menu_flatten($byParent);

// ---------- Site pages for the link picker ----------
// Curated labels for known pages; any other top-level page is auto-discovered
// with a prettified name, so new pages show up here without code changes.
$pageNames = [
    'index.php' => 'Home', 'about.php' => 'About Us', 'academics.php' => 'Academics',
    'achievements.php' => 'Achievements', 'admissions.php' => 'Admissions',
    'admission-form.php' => 'Admission Form', 'application-status.php' => 'Application Status',
    'blogs.php' => 'Blog', 'campus-life.php' => 'Campus Life', 'career.php' => 'Careers',
    'contact.php' => 'Contact Us', 'director-message.php' => "Director's Message",
    'downloads.php' => 'Downloads', 'events.php' => 'Events', 'facilities.php' => 'Facilities',
    'faculty.php' => 'Faculty', 'faqs.php' => 'FAQs', 'fee-structure.php' => 'Fee Structure',
    'gallery.php' => 'Photo Gallery', 'media.php' => 'Media Centre', 'news.php' => 'News',
    'principal-message.php' => "Principal's Message", 'privacy-policy.php' => 'Privacy Policy',
    'programs.php' => 'Programs', 'results.php' => 'Results', 'search.php' => 'Search',
    'terms.php' => 'Terms & Conditions', 'videos.php' => 'Videos',
];
// Not linkable from a menu: internals, feeds, and detail pages that need a slug.
$pageExclude = [
    'config.php', '404.php', 'form-submit.php', 'newsletter-subscribe.php',
    'application-print.php', 'robots.txt.php', 'sitemap.xml.php',
    'blog-detail.php', 'news-detail.php',
];
$sitePages = [];
foreach ($pageNames as $file => $name) {
    if (is_file(dirname(__DIR__) . '/' . $file)) {
        $sitePages[$file] = $name;
    }
}
foreach (glob(dirname(__DIR__) . '/*.php') ?: [] as $f) {
    $file = basename($f);
    if (!isset($pageNames[$file]) && !in_array($file, $pageExclude, true)) {
        $sitePages[$file] = ucwords(str_replace('-', ' ', basename($file, '.php')));
    }
}
asort($sitePages, SORT_NATURAL | SORT_FLAG_CASE);

$editing = null;
if (isset($_GET['edit'])) {
    foreach ($items as $item) {
        if ((int)$item['id'] === (int)$_GET['edit']) {
            $editing = $item;
            break;
        }
    }
}

$pageTitle = 'Menu Builder';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Menus</h2>
    <form method="post" action="menus.php" style="display:flex;gap:8px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_menu">
      <input type="text" name="name" placeholder="New menu name…" required style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--surface-2);color:var(--text);font-family:inherit;">
      <button type="submit" class="btn btn-primary btn-sm">➕ Create Menu</button>
    </form>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php foreach ($menus as $menu): ?>
      <a href="menus.php?menu=<?= (int)$menu['id'] ?>" class="btn btn-sm <?= (int)$menu['id'] === $activeMenuId ? 'btn-primary' : 'btn-outline' ?>">
        <?= e($menu['name']) ?> <span style="opacity:.65;">(<?= e($menu['slug']) ?>)</span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="form-hint" style="margin-top:12px;">The <strong>main</strong> menu is the website header navigation. <strong>footer-quick</strong> and <strong>footer-explore</strong> are the footer link columns. Any other menus you create can be used later.</p>
</div>

<?php if ($activeMenu): ?>
<div class="grid-2" style="grid-template-columns:7fr 5fr;align-items:start;">
  <div class="card">
    <div class="card-header">
      <h2>Structure: <?= e($activeMenu['name']) ?></h2>
      <div style="display:flex;gap:8px;">
        <form method="post" action="menus.php" style="display:flex;gap:6px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="rename_menu">
          <input type="hidden" name="menu_id" value="<?= $activeMenuId ?>">
          <input type="text" name="name" value="<?= e($activeMenu['name']) ?>" style="padding:6px 10px;border:1.5px solid var(--border);border-radius:8px;background:var(--surface-2);color:var(--text);font-family:inherit;width:140px;">
          <button type="submit" class="btn btn-outline btn-sm">Rename</button>
        </form>
        <?php if ($activeMenu['slug'] !== 'main'): ?>
        <form method="post" action="menus.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_menu">
          <input type="hidden" name="menu_id" value="<?= $activeMenuId ?>">
          <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this menu and ALL its items?">Delete Menu</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($flat)): ?>
      <div class="empty-state">This menu is empty. Add your first item on the right.</div>
    <?php else: ?>
    <p class="form-hint" style="margin-bottom:12px;">Drag ⠿ to reorder · use ⇤ ⇥ to change nesting level (unlimited depth) · then <strong>Save Structure</strong>.</p>
    <form method="post" action="menus.php" id="menuStructureForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_structure">
      <input type="hidden" name="menu_id" value="<?= $activeMenuId ?>">
      <div id="menuTree">
        <?php foreach ($flat as $row): $item = $row['item']; ?>
        <div class="hb-row menu-row<?= $item['is_active'] ? '' : ' hb-hidden' ?>" draggable="true" data-id="<?= (int)$item['id'] ?>" data-depth="<?= (int)$row['depth'] ?>" style="margin-left:<?= (int)$row['depth'] * 26 ?>px;">
          <span class="hb-grip" title="Drag to reorder">⠿</span>
          <span class="hb-label">
            <?= e($item['label']) ?>
            <small style="color:var(--muted);font-weight:400;"> <?= e($item['url']) ?><?= $item['new_tab'] ? ' ↗' : '' ?></small>
          </span>
          <button type="button" class="btn btn-outline btn-sm menu-outdent" title="Move up a level">⇤</button>
          <button type="button" class="btn btn-outline btn-sm menu-indent" title="Make sub-item">⇥</button>
          <a href="menus.php?menu=<?= $activeMenuId ?>&edit=<?= (int)$item['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
          <button type="button" class="btn btn-danger btn-sm menu-row-delete" data-id="<?= (int)$item['id'] ?>" title="Delete this item" aria-label="Delete <?= e($item['label']) ?>">✕</button>
          <input type="hidden" name="order[]" value="<?= (int)$item['id'] ?>">
          <input type="hidden" name="depth[]" value="<?= (int)$row['depth'] ?>">
        </div>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:14px;">💾 Save Structure</button>
    </form>
    <form method="post" action="menus.php" id="menuRowDeleteForm" hidden>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_item">
      <input type="hidden" name="menu_id" value="<?= $activeMenuId ?>">
      <input type="hidden" name="id" id="menuRowDeleteId" value="">
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><?= $editing ? 'Edit Item' : 'Add Item' ?></h2>
      <?php if ($editing): ?><a href="menus.php?menu=<?= $activeMenuId ?>" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
    </div>
    <form method="post" action="menus.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_item">
      <input type="hidden" name="menu_id" value="<?= $activeMenuId ?>">
      <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '')) ?>">
      <div class="form-group">
        <label for="label">Label *</label>
        <input type="text" id="label" name="label" required value="<?= e($editing['label'] ?? '') ?>">
      </div>
      <?php
        $editUrl = (string)($editing['url'] ?? '');
        $isCustomUrl = $editUrl !== '' && $editUrl !== '#' && !isset($sitePages[$editUrl]);
      ?>
      <div class="form-group">
        <label for="pageSelect">Link to Page</label>
        <select id="pageSelect">
          <option value="">— Select a page —</option>
          <?php foreach ($sitePages as $file => $name): ?>
          <option value="<?= e($file) ?>"<?= $editUrl === $file ? ' selected' : '' ?>><?= e($name) ?></option>
          <?php endforeach; ?>
          <option value="#"<?= $editUrl === '#' ? ' selected' : '' ?>>Dropdown parent (no link)</option>
          <option value="custom"<?= $isCustomUrl ? ' selected' : '' ?>>Custom URL…</option>
        </select>
        <p class="form-hint">Pick a page and the link fills in automatically. Choose "Custom URL…" for external links.</p>
      </div>
      <div class="form-group" id="urlGroup"<?= $isCustomUrl ? '' : ' hidden' ?>>
        <label for="url">Custom URL</label>
        <input type="text" id="url" name="url" placeholder="https://…" value="<?= e($editUrl) ?>">
      </div>
      <div class="form-group">
        <label style="font-weight:400;"><input type="checkbox" name="new_tab" <?= !empty($editing['new_tab']) ? 'checked' : '' ?>> Open in new tab ↗</label>
        <label style="font-weight:400;"><input type="checkbox" name="is_active" <?= (!$editing || $editing['is_active']) ? 'checked' : '' ?>> Active (visible)</label>
      </div>
      <button type="submit" class="btn btn-primary"><?= $editing ? 'Update Item' : 'Add Item' ?></button>
      <?php if ($editing): ?>
        <button type="submit" form="menuItemDelete" class="btn btn-danger" data-confirm="Delete this item and all its sub-items?">Delete</button>
      <?php endif; ?>
    </form>
    <?php if ($editing): ?>
    <form method="post" action="menus.php" id="menuItemDelete">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_item">
      <input type="hidden" name="menu_id" value="<?= $activeMenuId ?>">
      <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Per-row delete in the structure list.
  document.querySelectorAll('.menu-row-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Delete this menu item? Its sub-items will be removed too.')) return;
      document.getElementById('menuRowDeleteId').value = btn.getAttribute('data-id');
      document.getElementById('menuRowDeleteForm').submit();
    });
  });

  var pageSelect = document.getElementById('pageSelect');
  var urlGroup = document.getElementById('urlGroup');
  var urlInput = document.getElementById('url');
  var labelInput = document.getElementById('label');
  if (!pageSelect || !urlGroup || !urlInput) return;

  pageSelect.addEventListener('change', function () {
    if (pageSelect.value === 'custom') {
      urlGroup.hidden = false;
      urlInput.value = '';
      urlInput.focus();
      return;
    }
    urlGroup.hidden = true;
    urlInput.value = pageSelect.value;
    // Auto-fill the label with the page name when it's still empty.
    if (pageSelect.value && pageSelect.value !== '#' && labelInput && labelInput.value.trim() === '') {
      labelInput.value = pageSelect.options[pageSelect.selectedIndex].text;
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
