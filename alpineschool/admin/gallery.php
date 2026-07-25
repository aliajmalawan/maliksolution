<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/gallery.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_category') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '') {
            $pdo->prepare('INSERT INTO gallery_categories (name, slug) VALUES (?, ?)')->execute([$name, slugify($name)]);
            flash_set('success', 'Category added.');
        }
        redirect(BASE_URL . '/admin/gallery.php');
    }

    if ($action === 'delete_category') {
        $pdo->prepare('DELETE FROM gallery_categories WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash_set('success', 'Category deleted. Its albums and photos remain, uncategorized.');
        redirect(BASE_URL . '/admin/gallery.php');
    }

    if ($action === 'save_album') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $description = trim((string)($_POST['description'] ?? ''));
        if ($name === '') {
            flash_set('error', 'Album name is required.');
            redirect(BASE_URL . '/admin/gallery.php');
        }
        if ($id > 0) {
            $pdo->prepare('UPDATE gallery_albums SET name=?, category_id=?, description=? WHERE id=?')
                ->execute([$name, $categoryId, $description, $id]);
            flash_set('success', 'Album updated.');
        } else {
            $slug = slugify($name);
            $base = $slug;
            $n = 1;
            $check = $pdo->prepare('SELECT id FROM gallery_albums WHERE slug = ?');
            $check->execute([$slug]);
            while ($check->fetch()) {
                $slug = $base . '-' . (++$n);
                $check->execute([$slug]);
            }
            $pdo->prepare('INSERT INTO gallery_albums (name, slug, category_id, description) VALUES (?, ?, ?, ?)')
                ->execute([$name, $slug, $categoryId, $description]);
            flash_set('success', 'Album created.');
        }
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'album_save', 'Saved gallery album "' . $name . '"');
        redirect(BASE_URL . '/admin/gallery.php');
    }

    if ($action === 'delete_album') {
        $pdo->prepare('DELETE FROM gallery_albums WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash_set('success', 'Album deleted. Its photos remain, unassigned.');
        redirect(BASE_URL . '/admin/gallery.php');
    }

    if ($action === 'delete_image') {
        $pdo->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash_set('success', 'Photo deleted.');
        redirect(BASE_URL . '/admin/gallery.php');
    }

    if ($action === 'update_image') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE gallery_images SET category_id=?, album_id=?, caption=? WHERE id=?')->execute([
            (int)($_POST['category_id'] ?? 0) ?: null,
            (int)($_POST['album_id'] ?? 0) ?: null,
            trim((string)($_POST['caption'] ?? '')),
            $id,
        ]);
        flash_set('success', 'Photo updated.');
        redirect(BASE_URL . '/admin/gallery.php');
    }

    if ($action === 'add_images') {
        $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
        $albumId = (int)($_POST['album_id'] ?? 0) ?: null;
        $caption = trim((string)($_POST['caption'] ?? ''));

        // Album's category wins when an album is chosen.
        if ($albumId) {
            $stmt = $pdo->prepare('SELECT category_id FROM gallery_albums WHERE id = ?');
            $stmt->execute([$albumId]);
            $albumCat = $stmt->fetch();
            if ($albumCat && $albumCat['category_id']) {
                $categoryId = (int)$albumCat['category_id'];
            }
        }

        $files = $_FILES['images'] ?? null;
        if (!$files || empty($files['name'][0])) {
            flash_set('error', 'Please choose at least one photo.');
            redirect(BASE_URL . '/admin/gallery.php');
        }

        $insert = $pdo->prepare('INSERT INTO gallery_images (category_id, album_id, image_path, caption) VALUES (?, ?, ?, ?)');
        $ok = 0;
        $failed = 0;
        foreach (array_keys($files['name']) as $i) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            $uploaded = upload_image($file, 'gallery');
            if ($uploaded) {
                $insert->execute([$categoryId, $albumId, 'uploads/' . $uploaded, $caption]);
                $ok++;
            } else {
                $failed++;
            }
        }
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'gallery_upload', 'Uploaded ' . $ok . ' gallery photo(s)');
        flash_set($failed ? 'error' : 'success', $ok . ' photo(s) uploaded.' . ($failed ? " $failed failed (JPG/PNG/WEBP/GIF, max 5MB)." : ''));
        redirect(BASE_URL . '/admin/gallery.php');
    }
}

$categories = $pdo->query('SELECT * FROM gallery_categories ORDER BY name')->fetchAll();
$albums = $pdo->query(
    'SELECT ga.*, gc.name AS cat_name, COUNT(gi.id) AS photo_count
     FROM gallery_albums ga
     LEFT JOIN gallery_categories gc ON ga.category_id = gc.id
     LEFT JOIN gallery_images gi ON gi.album_id = ga.id
     GROUP BY ga.id ORDER BY ga.created_at DESC'
)->fetchAll();
$images = $pdo->query(
    'SELECT gi.*, gc.name AS cat_name, ga.name AS album_name
     FROM gallery_images gi
     LEFT JOIN gallery_categories gc ON gi.category_id = gc.id
     LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
     ORDER BY gi.id DESC'
)->fetchAll();

$editingAlbum = null;
if (isset($_GET['album'])) {
    foreach ($albums as $album) {
        if ((int)$album['id'] === (int)$_GET['album']) {
            $editingAlbum = $album;
            break;
        }
    }
}

$pageTitle = 'Gallery';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid-2" style="align-items:start;">
  <div class="card">
    <div class="card-header"><h2>Categories</h2></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
      <?php foreach ($categories as $cat): ?>
        <form method="post" action="gallery.php" style="display:flex;align-items:center;gap:6px;background:var(--surface-2);padding:6px 6px 6px 14px;border-radius:999px;">
          <span style="font-size:13px;font-weight:600;"><?= e($cat['name']) ?></span>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_category">
          <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm" style="padding:4px 10px;" data-confirm="Delete this category?">×</button>
        </form>
      <?php endforeach; ?>
    </div>
    <form method="post" action="gallery.php" style="display:flex;gap:10px;align-items:flex-end;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_category">
      <div class="form-group" style="margin-bottom:0;flex:1;max-width:300px;">
        <label for="name">New Category Name</label>
        <input type="text" id="name" name="name" placeholder="e.g. Science Fair" required>
      </div>
      <button type="submit" class="btn btn-outline">Add Category</button>
    </form>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><?= $editingAlbum ? 'Edit Album' : 'Create Album' ?></h2>
      <?php if ($editingAlbum): ?><a href="gallery.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
    </div>
    <form method="post" action="gallery.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_album">
      <input type="hidden" name="id" value="<?= e((string)($editingAlbum['id'] ?? '')) ?>">
      <div class="form-row">
        <div class="form-group">
          <label for="album_name">Album Name *</label>
          <input type="text" id="album_name" name="name" required placeholder="e.g. Sports Day 2026" value="<?= e($editingAlbum['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="album_category">Category</label>
          <select id="album_category" name="category_id">
            <option value="">— None —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ((int)($editingAlbum['category_id'] ?? 0)) === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="album_description">Description</label>
        <input type="text" id="album_description" name="description" value="<?= e($editingAlbum['description'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primary"><?= $editingAlbum ? 'Update Album' : 'Create Album' ?></button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2>Albums (<?= count($albums) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($albums)): ?>
      <div class="empty-state">No albums yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Album</th><th>Category</th><th>Photos</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($albums as $album): ?>
        <tr>
          <td><strong><?= e($album['name']) ?></strong><?= $album['description'] ? '<br><span class="form-hint" style="display:inline;">' . e($album['description']) . '</span>' : '' ?></td>
          <td><?= e($album['cat_name'] ?? '—') ?></td>
          <td><?= (int)$album['photo_count'] ?></td>
          <td><?= format_date($album['created_at']) ?></td>
          <td class="actions-cell">
            <a href="gallery.php?album=<?= (int)$album['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="gallery.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_album">
              <input type="hidden" name="id" value="<?= (int)$album['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this album? Photos stay but leave the album.">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2>Upload Photos</h2></div>
  <form method="post" action="gallery.php" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_images">
    <div class="form-row">
      <div class="form-group">
        <label for="images">Photos * (you can select multiple)</label>
        <input type="file" id="images" name="images[]" accept="image/*" multiple required>
      </div>
      <div class="form-group">
        <label for="album_id">Album</label>
        <select id="album_id" name="album_id">
          <option value="">— No album (loose photos) —</option>
          <?php foreach ($albums as $album): ?>
            <option value="<?= (int)$album['id'] ?>"><?= e($album['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="form-hint">Choosing an album also applies the album's category.</p>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="category_id">Category (for loose photos)</label>
        <select id="category_id" name="category_id">
          <option value="">Uncategorized</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="caption">Caption (applied to all selected photos)</label>
        <input type="text" id="caption" name="caption" placeholder="e.g. Students at the science fair">
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Upload Photos</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>All Photos (<?= count($images) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($images)): ?>
      <div class="empty-state">No photos uploaded yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Photo</th><th>Caption</th><th>Album</th><th>Category</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($images as $img): ?>
        <tr>
          <td><img src="<?= BASE_URL ?>/<?= e($img['image_path']) ?>" class="thumb"></td>
          <td colspan="3">
            <form method="post" action="gallery.php" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update_image">
              <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
              <input type="text" name="caption" value="<?= e($img['caption']) ?>" placeholder="Caption" style="flex:2;min-width:160px;padding:7px 10px;border:1.5px solid var(--border);border-radius:7px;background:var(--surface-2);color:var(--text);font-family:inherit;font-size:12.5px;">
              <select name="album_id" style="flex:1;min-width:120px;">
                <option value="">No album</option>
                <?php foreach ($albums as $album): ?>
                  <option value="<?= (int)$album['id'] ?>" <?= (int)$img['album_id'] === (int)$album['id'] ? 'selected' : '' ?>><?= e($album['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="category_id" style="flex:1;min-width:120px;">
                <option value="">Uncategorized</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>" <?= (int)$img['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-outline btn-sm">Save</button>
            </form>
          </td>
          <td>
            <form method="post" action="gallery.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_image">
              <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this photo?">Delete</button>
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
