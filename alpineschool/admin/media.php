<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$uploadRoot = realpath(UPLOAD_DIR) ?: UPLOAD_DIR;

/** List folders that exist directly under uploads/. */
function media_folders(string $root): array
{
    $folders = [];
    foreach (scandir($root) ?: [] as $entry) {
        if ($entry[0] !== '.' && is_dir($root . DIRECTORY_SEPARATOR . $entry)) {
            $folders[] = $entry;
        }
    }
    sort($folders);
    return $folders;
}

function valid_folder(string $name): bool
{
    return (bool)preg_match('/^[a-z0-9][a-z0-9-]{0,40}$/', $name);
}

$folders = media_folders($uploadRoot);
$activeFolder = (string)($_GET['folder'] ?? ($folders[0] ?? 'gallery'));
if (!valid_folder($activeFolder) || !in_array($activeFolder, $folders, true)) {
    $activeFolder = $folders[0] ?? 'gallery';
}
$backUrl = BASE_URL . '/admin/media.php?folder=' . $activeFolder;

$imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect($backUrl);
    }

    $action = (string)($_POST['action'] ?? '');

    // ---------- Folder actions ----------
    if ($action === 'create_folder') {
        $name = slugify((string)($_POST['name'] ?? ''));
        if ($name === '' || !valid_folder($name)) {
            flash_set('error', 'Folder names may contain lowercase letters, numbers, and dashes.');
            redirect($backUrl);
        }
        $dir = $uploadRoot . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'media_folder', 'Created media folder "' . $name . '"');
            flash_set('success', 'Folder "' . $name . '" created.');
        }
        redirect(BASE_URL . '/admin/media.php?folder=' . $name);
    }

    if ($action === 'delete_folder') {
        $name = (string)($_POST['name'] ?? '');
        $dir = $uploadRoot . DIRECTORY_SEPARATOR . $name;
        if (valid_folder($name) && is_dir($dir)) {
            $entries = array_diff(scandir($dir) ?: [], ['.', '..']);
            if ($entries) {
                flash_set('error', 'Folder is not empty — delete its files first.');
                redirect(BASE_URL . '/admin/media.php?folder=' . $name);
            }
            rmdir($dir);
            flash_set('success', 'Empty folder deleted.');
        }
        redirect(BASE_URL . '/admin/media.php');
    }

    if ($action === 'save_settings') {
        $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $update->execute(['media_compress', isset($_POST['media_compress']) ? '1' : '0']);
        $update->execute(['media_webp', isset($_POST['media_webp']) ? '1' : '0']);
        $update->execute(['media_quality', (string)max(30, min(100, (int)($_POST['media_quality'] ?? 82)))]);
        $update->execute(['media_max_width', (string)max(320, min(4000, (int)($_POST['media_max_width'] ?? 1920)))]);
        flash_set('success', 'Media settings saved — they apply to every new upload site-wide.');
        redirect($backUrl);
    }

    if ($action === 'upload' && !empty($_FILES['file']['name'])) {
        $uploaded = upload_image($_FILES['file'], $activeFolder);
        if (!$uploaded) {
            $doc = upload_file($_FILES['file'], $activeFolder, document_mime_map());
            $uploaded = $doc['path'] ?? null;
        }
        if ($uploaded) {
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'media_upload', 'Uploaded ' . $uploaded);
            flash_set('success', 'Uploaded as ' . basename($uploaded) . '.');
        } else {
            flash_set('error', 'Upload failed — allowed: images (5 MB), PDF/DOC/XLS/ZIP (15 MB).');
        }
        redirect($backUrl);
    }

    // ---------- Single-file actions (file inside active folder) ----------
    $fileName = basename((string)($_POST['file'] ?? ''));
    $filePath = $uploadRoot . DIRECTORY_SEPARATOR . $activeFolder . DIRECTORY_SEPARATOR . $fileName;
    $relPath = 'uploads/' . $activeFolder . '/' . $fileName;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileUrl = $backUrl . '&file=' . urlencode($fileName);

    if ($fileName === '' || !is_file($filePath)) {
        flash_set('error', 'File not found.');
        redirect($backUrl);
    }

    if ($action === 'delete_file') {
        unlink($filePath);
        $pdo->prepare('DELETE FROM media WHERE file_path = ?')->execute([$relPath]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'media_delete', 'Deleted ' . $relPath);
        flash_set('success', 'File deleted. Content still referencing it will show a broken image.');
        redirect($backUrl);
    }

    if ($action === 'save_alt') {
        $alt = mb_substr(trim((string)($_POST['alt_text'] ?? '')), 0, 300);
        $pdo->prepare('INSERT INTO media (file_path, alt_text) VALUES (?, ?) ON DUPLICATE KEY UPDATE alt_text = VALUES(alt_text)')
            ->execute([$relPath, $alt]);
        flash_set('success', 'Alt text saved.');
        redirect($fileUrl);
    }

    if ($action === 'rename') {
        $newBase = slugify((string)($_POST['new_name'] ?? ''));
        if ($newBase === '') {
            flash_set('error', 'Enter a new name (letters, numbers, dashes).');
            redirect($fileUrl);
        }
        $dir = dirname($filePath);
        $newName = $newBase . '.' . $ext;
        $n = 1;
        while (file_exists($dir . '/' . $newName) && $newName !== $fileName) {
            $newName = $newBase . '-' . (++$n) . '.' . $ext;
        }
        if ($newName !== $fileName) {
            rename($filePath, $dir . '/' . $newName);
            $newRel = 'uploads/' . $activeFolder . '/' . $newName;
            $pdo->prepare('UPDATE media SET file_path = ? WHERE file_path = ?')->execute([$newRel, $relPath]);
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'media_rename', $relPath . ' → ' . $newRel);
            flash_set('success', 'Renamed to ' . $newName . '. Update any content that used the old file name.');
            redirect($backUrl . '&file=' . urlencode($newName));
        }
        redirect($fileUrl);
    }

    // Image edits below require GD + an image file.
    if (in_array($action, ['resize', 'crop', 'convert_webp', 'compress'], true)) {
        if (!in_array($ext, $imageExts, true) || $ext === 'gif') {
            flash_set('error', 'This operation supports JPG, PNG, and WebP images.');
            redirect($fileUrl);
        }
        $img = gd_load($filePath, $ext === 'jpeg' ? 'jpg' : $ext);
        if (!$img) {
            flash_set('error', 'Could not open the image.');
            redirect($fileUrl);
        }
        $quality = max(30, min(100, (int)get_setting($pdo, 'media_quality', '82')));

        if ($action === 'resize') {
            $newW = max(10, min(6000, (int)($_POST['width'] ?? 0)));
            $img2 = imagescale($img, $newW);
            if ($ext === 'png' || $ext === 'webp') {
                imagealphablending($img2, false);
                imagesavealpha($img2, true);
            }
            gd_save($img2, $filePath, $ext === 'jpeg' ? 'jpg' : $ext, $quality);
            imagedestroy($img2);
            flash_set('success', 'Image resized to ' . $newW . 'px wide.');
        } elseif ($action === 'crop') {
            $x = max(0, (int)($_POST['crop_x'] ?? 0));
            $y = max(0, (int)($_POST['crop_y'] ?? 0));
            $w = (int)($_POST['crop_w'] ?? 0);
            $h = (int)($_POST['crop_h'] ?? 0);
            $imgW = imagesx($img);
            $imgH = imagesy($img);
            $w = min($w, $imgW - $x);
            $h = min($h, $imgH - $y);
            if ($w >= 10 && $h >= 10) {
                $img2 = imagecrop($img, ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h]);
                if ($img2 !== false) {
                    if ($ext === 'png' || $ext === 'webp') {
                        imagealphablending($img2, false);
                        imagesavealpha($img2, true);
                    }
                    gd_save($img2, $filePath, $ext === 'jpeg' ? 'jpg' : $ext, $quality);
                    imagedestroy($img2);
                    flash_set('success', 'Image cropped to ' . $w . '×' . $h . 'px.');
                }
            } else {
                flash_set('error', 'Drag a selection on the image first (min 10×10px).');
            }
        } elseif ($action === 'convert_webp') {
            if ($ext === 'webp') {
                flash_set('error', 'Already a WebP file.');
            } else {
                $newPath = preg_replace('/\.[a-z]+$/i', '.webp', $filePath);
                imagealphablending($img, false);
                imagesavealpha($img, true);
                if (imagewebp($img, $newPath, $quality)) {
                    unlink($filePath);
                    $newRel = 'uploads/' . $activeFolder . '/' . basename($newPath);
                    $pdo->prepare('UPDATE media SET file_path = ? WHERE file_path = ?')->execute([$newRel, $relPath]);
                    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'media_webp', $relPath . ' → WebP');
                    flash_set('success', 'Converted to WebP. Update content that used the old file name.');
                    imagedestroy($img);
                    redirect($backUrl . '&file=' . urlencode(basename($newPath)));
                }
                flash_set('error', 'WebP conversion failed.');
            }
        } elseif ($action === 'compress') {
            $before = filesize($filePath);
            gd_save($img, $filePath, $ext === 'jpeg' ? 'jpg' : $ext, $quality);
            clearstatcache();
            $after = filesize($filePath);
            flash_set('success', 'Recompressed: ' . human_filesize((int)$before) . ' → ' . human_filesize((int)$after) . '.');
        }
        imagedestroy($img);
        redirect($fileUrl);
    }

    redirect($backUrl);
}

// ---------- Data for the view ----------
$files = [];
$dir = $uploadRoot . DIRECTORY_SEPARATOR . $activeFolder;
if (is_dir($dir)) {
    foreach (scandir($dir) as $entry) {
        $full = $dir . DIRECTORY_SEPARATOR . $entry;
        if ($entry[0] === '.' || !is_file($full)) {
            continue;
        }
        $files[] = [
            'name' => $entry,
            'size' => (int)filesize($full),
            'mtime' => (int)filemtime($full),
            'is_image' => in_array(strtolower(pathinfo($entry, PATHINFO_EXTENSION)), $imageExts, true),
        ];
    }
    usort($files, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
}

$detail = null;
if (isset($_GET['file'])) {
    $name = basename((string)$_GET['file']);
    $full = $dir . DIRECTORY_SEPARATOR . $name;
    if (is_file($full)) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $dims = in_array($ext, $imageExts, true) ? @getimagesize($full) : false;
        $rel = 'uploads/' . $activeFolder . '/' . $name;
        $stmt = $pdo->prepare('SELECT * FROM media WHERE file_path = ?');
        $stmt->execute([$rel]);
        $meta = $stmt->fetch() ?: [];
        $detail = [
            'name' => $name, 'rel' => $rel, 'ext' => $ext,
            'size' => (int)filesize($full),
            'width' => $dims[0] ?? 0, 'height' => $dims[1] ?? 0,
            'is_image' => (bool)$dims,
            'alt' => (string)($meta['alt_text'] ?? ''),
            'original' => (string)($meta['original_name'] ?? ''),
        ];
    }
}

$settings = [];
foreach ($pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'media_%'")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Media Manager';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Folders</h2>
    <form method="post" action="media.php?folder=<?= e($activeFolder) ?>" style="display:flex;gap:8px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_folder">
      <input type="text" name="name" placeholder="new-folder-name" required style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--surface-2);color:var(--text);font-family:inherit;">
      <button type="submit" class="btn btn-primary btn-sm">📁 New Folder</button>
    </form>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php foreach ($folders as $folder): ?>
      <a href="media.php?folder=<?= e($folder) ?>" class="btn btn-sm <?= $folder === $activeFolder ? 'btn-primary' : 'btn-outline' ?>">📁 <?= e($folder) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($detail): ?>
<div class="card">
  <div class="card-header">
    <h2>📄 <?= e($detail['name']) ?></h2>
    <a href="media.php?folder=<?= e($activeFolder) ?>" class="btn btn-outline btn-sm">← Back to Files</a>
  </div>
  <div class="grid-2" style="align-items:start;">
    <div>
      <?php if ($detail['is_image']): ?>
        <div id="cropStage" style="position:relative;display:inline-block;max-width:100%;">
          <img id="cropImage" src="<?= BASE_URL ?>/<?= e($detail['rel']) ?>?v=<?= time() ?>" alt="" style="max-width:100%;border-radius:10px;display:block;user-select:none;-webkit-user-drag:none;">
          <div id="cropBox" style="position:absolute;border:2px dashed #fff;background:rgba(79,70,229,.25);display:none;pointer-events:none;"></div>
        </div>
        <p class="form-hint" style="margin-top:8px;">
          <?= (int)$detail['width'] ?> × <?= (int)$detail['height'] ?> px · <?= human_filesize($detail['size']) ?> · <?= strtoupper($detail['ext']) ?>
          <?= $detail['original'] ? ' · uploaded as "' . e($detail['original']) . '"' : '' ?>
        </p>
      <?php else: ?>
        <div class="empty-state">📄 <?= e($detail['name']) ?> (<?= human_filesize($detail['size']) ?>) — <a href="<?= BASE_URL ?>/<?= e($detail['rel']) ?>" target="_blank" style="color:var(--primary);">open ↗</a></div>
      <?php endif; ?>
    </div>
    <div>
      <form method="post" action="media.php?folder=<?= e($activeFolder) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_alt">
        <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
        <div class="form-group">
          <label for="alt_text">Alt Text (for SEO &amp; accessibility)</label>
          <input type="text" id="alt_text" name="alt_text" maxlength="300" placeholder="e.g. Students receiving awards at the annual ceremony" value="<?= e($detail['alt']) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save Alt Text</button>
      </form>

      <form method="post" action="media.php?folder=<?= e($activeFolder) ?>" style="margin-top:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="rename">
        <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
        <div class="form-group">
          <label for="new_name">SEO-Friendly Name</label>
          <div style="display:flex;gap:8px;">
            <input type="text" id="new_name" name="new_name" placeholder="sports-day-2026" value="<?= e(pathinfo($detail['name'], PATHINFO_FILENAME)) ?>">
            <button type="submit" class="btn btn-outline btn-sm">Rename</button>
          </div>
          <p class="form-hint">Lowercase words separated by dashes. ⚠ Content already using this file keeps the old name — re-select the image there after renaming.</p>
        </div>
      </form>

      <?php if ($detail['is_image'] && $detail['ext'] !== 'gif'): ?>
      <form method="post" action="media.php?folder=<?= e($activeFolder) ?>" style="margin-top:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="resize">
        <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
        <div class="form-group">
          <label for="width">Resize (new width in px — height follows automatically)</label>
          <div style="display:flex;gap:8px;">
            <input type="number" id="width" name="width" min="10" max="6000" value="<?= (int)$detail['width'] ?>">
            <button type="submit" class="btn btn-outline btn-sm" data-confirm="Resize this image? This overwrites the file.">Resize</button>
          </div>
        </div>
      </form>

      <form method="post" action="media.php?folder=<?= e($activeFolder) ?>" id="cropForm" style="margin-top:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="crop">
        <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
        <input type="hidden" name="crop_x" id="crop_x" value="0">
        <input type="hidden" name="crop_y" id="crop_y" value="0">
        <input type="hidden" name="crop_w" id="crop_w" value="0">
        <input type="hidden" name="crop_h" id="crop_h" value="0">
        <div class="form-group">
          <label>Crop</label>
          <p class="form-hint">Drag a selection directly on the image preview, then press Crop. <span id="cropReadout" style="font-weight:600;"></span></p>
          <button type="submit" class="btn btn-outline btn-sm" data-confirm="Crop this image to the selected area? This overwrites the file.">✂ Crop to Selection</button>
        </div>
      </form>

      <div style="display:flex;gap:8px;margin-top:18px;flex-wrap:wrap;">
        <?php if ($detail['ext'] !== 'webp'): ?>
        <form method="post" action="media.php?folder=<?= e($activeFolder) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="convert_webp">
          <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
          <button type="submit" class="btn btn-outline btn-sm" data-confirm="Convert to WebP? The original file is replaced.">⚡ Convert to WebP</button>
        </form>
        <?php endif; ?>
        <form method="post" action="media.php?folder=<?= e($activeFolder) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="compress">
          <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
          <button type="submit" class="btn btn-outline btn-sm" data-confirm="Recompress this image at quality <?= (int)($settings['media_quality'] ?? 82) ?>?">🗜 Compress</button>
        </form>
        <form method="post" action="media.php?folder=<?= e($activeFolder) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_file">
          <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
          <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this file permanently?">Delete</button>
        </form>
      </div>
      <?php else: ?>
      <div style="display:flex;gap:8px;margin-top:18px;">
        <form method="post" action="media.php?folder=<?= e($activeFolder) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_file">
          <input type="hidden" name="file" value="<?= e($detail['name']) ?>">
          <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this file permanently?">Delete</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($detail['is_image']): ?>
<script>
(function () {
  var img = document.getElementById('cropImage');
  var box = document.getElementById('cropBox');
  var stage = document.getElementById('cropStage');
  var natural = { w: <?= (int)$detail['width'] ?>, h: <?= (int)$detail['height'] ?> };
  var start = null;

  function coords(e) {
    var r = img.getBoundingClientRect();
    return {
      x: Math.max(0, Math.min(e.clientX - r.left, r.width)),
      y: Math.max(0, Math.min(e.clientY - r.top, r.height)),
      rw: r.width, rh: r.height
    };
  }

  stage.addEventListener('mousedown', function (e) {
    e.preventDefault();
    start = coords(e);
    box.style.display = 'block';
  });
  document.addEventListener('mousemove', function (e) {
    if (!start) return;
    var c = coords(e);
    var x = Math.min(start.x, c.x), y = Math.min(start.y, c.y);
    var w = Math.abs(c.x - start.x), h = Math.abs(c.y - start.y);
    box.style.left = x + 'px'; box.style.top = y + 'px';
    box.style.width = w + 'px'; box.style.height = h + 'px';
    var scaleX = natural.w / c.rw, scaleY = natural.h / c.rh;
    document.getElementById('crop_x').value = Math.round(x * scaleX);
    document.getElementById('crop_y').value = Math.round(y * scaleY);
    document.getElementById('crop_w').value = Math.round(w * scaleX);
    document.getElementById('crop_h').value = Math.round(h * scaleY);
    document.getElementById('cropReadout').textContent =
      'Selected: ' + Math.round(w * scaleX) + ' × ' + Math.round(h * scaleY) + ' px';
  });
  document.addEventListener('mouseup', function () { start = null; });
})();
</script>
<?php endif; ?>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h2>uploads/<?= e($activeFolder) ?> (<?= count($files) ?> files)</h2>
    <div style="display:flex;gap:8px;align-items:center;">
      <form method="post" action="media.php?folder=<?= e($activeFolder) ?>" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <input type="file" name="file" required style="font-size:12.5px;">
        <button type="submit" class="btn btn-primary btn-sm">Upload</button>
      </form>
      <?php if (empty($files)): ?>
      <form method="post" action="media.php?folder=<?= e($activeFolder) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete_folder">
        <input type="hidden" name="name" value="<?= e($activeFolder) ?>">
        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this empty folder?">Delete Folder</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($files)): ?>
    <div class="empty-state">This folder is empty. Upload a file above.</div>
  <?php else: ?>
  <div class="media-grid">
    <?php foreach ($files as $file): ?>
      <a class="media-item" href="media.php?folder=<?= e($activeFolder) ?>&file=<?= urlencode($file['name']) ?>">
        <?php if ($file['is_image']): ?>
          <img src="<?= BASE_URL ?>/uploads/<?= e($activeFolder . '/' . $file['name']) ?>" alt="<?= e($file['name']) ?>" loading="lazy">
        <?php else: ?>
          <span class="media-doc">📄</span>
        <?php endif; ?>
        <span class="media-meta">
          <small title="<?= e($file['name']) ?>"><?= e(mb_strlen($file['name']) > 24 ? mb_substr($file['name'], 0, 21) . '…' : $file['name']) ?></small>
          <small><?= human_filesize($file['size']) ?> · <?= date('d M Y', $file['mtime']) ?></small>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header"><h2>⚙️ Upload Pipeline Settings</h2></div>
  <form method="post" action="media.php?folder=<?= e($activeFolder) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">
    <div class="form-row">
      <div class="form-group">
        <label style="font-weight:400;"><input type="checkbox" name="media_compress" <?= ($settings['media_compress'] ?? '1') === '1' ? 'checked' : '' ?>> Compress images on upload</label>
        <label style="font-weight:400;"><input type="checkbox" name="media_webp" <?= ($settings['media_webp'] ?? '1') === '1' ? 'checked' : '' ?>> Convert uploads to WebP automatically</label>
      </div>
      <div class="form-group">
        <label for="media_quality">Quality (30–100)</label>
        <input type="number" id="media_quality" name="media_quality" min="30" max="100" value="<?= (int)($settings['media_quality'] ?? 82) ?>">
      </div>
    </div>
    <div class="form-group" style="max-width:calc(50% - 8px);">
      <label for="media_max_width">Max Width on Upload (px)</label>
      <input type="number" id="media_max_width" name="media_max_width" min="320" max="4000" value="<?= (int)($settings['media_max_width'] ?? 1920) ?>">
      <p class="form-hint">Larger images are scaled down automatically. Applies to every upload across the whole admin (hero, news, gallery, …).</p>
    </div>
    <button type="submit" class="btn btn-primary">Save Settings</button>
  </form>
</div>

<style>
.media-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:14px; }
.media-item { border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--surface-2); display:block; transition:.15s; }
.media-item:hover { border-color:var(--primary); }
.media-item img { width:100%; height:110px; object-fit:cover; display:block; }
.media-item .media-doc { display:flex; align-items:center; justify-content:center; height:110px; font-size:38px; }
.media-meta { padding:8px 10px; display:flex; flex-direction:column; gap:3px; }
.media-meta small { color:var(--text-light); font-size:11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
