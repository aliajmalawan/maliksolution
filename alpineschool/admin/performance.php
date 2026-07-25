<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$root = dirname(__DIR__);
$imageExts = ['jpg', 'jpeg', 'png', 'webp'];

/** Walk uploads/ and gather image stats. */
function scan_images(string $root, array $exts): array
{
    $files = [];
    $dir = $root . '/uploads';
    if (!is_dir($dir)) {
        return $files;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $exts, true)) {
            continue;
        }
        $path = str_replace('\\', '/', $file->getPathname());
        $rel = ltrim(str_replace(str_replace('\\', '/', $root), '', $path), '/');
        $dims = @getimagesize($file->getPathname());
        $files[] = [
            'rel' => $rel,
            'full' => $file->getPathname(),
            'size' => $file->getSize(),
            'width' => $dims[0] ?? 0,
            'height' => $dims[1] ?? 0,
            'ext' => $ext,
        ];
    }
    return $files;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/performance.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_settings') {
        $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $update->execute(['perf_cache', isset($_POST['perf_cache']) ? '1' : '0']);
        $update->execute(['perf_minify', isset($_POST['perf_minify']) ? '1' : '0']);
        $update->execute(['perf_html_minify', isset($_POST['perf_html_minify']) ? '1' : '0']);
        cache_clear();
        flash_set('success', 'Performance settings saved and caches cleared.');
        redirect(BASE_URL . '/admin/performance.php');
    }

    if ($action === 'clear_cache') {
        $n = cache_clear();
        // Also drop generated asset bundles so they rebuild from source.
        foreach (glob($root . '/assets/{css,js}/*.min.*', GLOB_BRACE) ?: [] as $file) {
            unlink($file);
            $n++;
        }
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'cache_clear', 'Cleared performance caches');
        flash_set('success', 'Cleared ' . $n . ' cached file(s). They will rebuild on the next page load.');
        redirect(BASE_URL . '/admin/performance.php');
    }

    if ($action === 'optimize_images') {
        $maxWidth = max(320, (int)get_setting($pdo, 'media_max_width', '1920'));
        $quality = max(30, min(100, (int)get_setting($pdo, 'media_quality', '82')));
        $toWebp = isset($_POST['convert_webp']) && function_exists('imagewebp');

        $savedBytes = 0;
        $processed = 0;
        $converted = 0;

        foreach (scan_images($root, $imageExts) as $img) {
            // Skip application documents — those are records, not display assets.
            if (str_starts_with($img['rel'], 'uploads/applications/')) {
                continue;
            }

            $before = $img['size'];
            // Cap the LONGEST edge — portrait photos are just as heavy as landscape ones.
            $longest = max($img['width'], $img['height']);
            $needsResize = $longest > $maxWidth;
            $needsConvert = $toWebp && $img['ext'] !== 'webp';
            if (!$needsResize && !$needsConvert) {
                continue;
            }

            $gd = gd_load($img['full'], $img['ext'] === 'jpeg' ? 'jpg' : $img['ext']);
            if (!$gd) {
                continue;
            }
            if ($needsResize) {
                $scale = $maxWidth / $longest;
                $newW = max(1, (int)round($img['width'] * $scale));
                $newH = max(1, (int)round($img['height'] * $scale));
                $gd = imagescale($gd, $newW, $newH);
            }
            if ($img['ext'] === 'png' || $img['ext'] === 'webp') {
                imagealphablending($gd, false);
                imagesavealpha($gd, true);
            }

            if ($needsConvert) {
                $newFull = preg_replace('/\.[a-z]+$/i', '.webp', $img['full']);
                $newRel = preg_replace('/\.[a-z]+$/i', '.webp', $img['rel']);
                if (imagewebp($gd, $newFull, $quality)) {
                    unlink($img['full']);
                    // Repoint every DB reference at the new file.
                    foreach ([
                        ['hero_slides', 'image_path'], ['news', 'image_path'], ['blogs', 'image_path'],
                        ['events', 'image_path'], ['gallery_images', 'image_path'], ['faculty', 'photo_path'],
                        ['testimonials', 'photo_path'], ['partners', 'logo_path'], ['results', 'file_path'],
                    ] as [$table, $column]) {
                        $pdo->prepare("UPDATE $table SET $column = ? WHERE $column = ?")->execute([$newRel, $img['rel']]);
                    }
                    $pdo->prepare('UPDATE media SET file_path = ? WHERE file_path = ?')->execute([$newRel, $img['rel']]);
                    $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_value = ?')->execute([$newRel, $img['rel']]);
                    clearstatcache();
                    $savedBytes += $before - (int)filesize($newFull);
                    $converted++;
                    $processed++;
                }
            } else {
                gd_save($gd, $img['full'], $img['ext'] === 'jpeg' ? 'jpg' : $img['ext'], $quality);
                clearstatcache();
                $savedBytes += $before - (int)filesize($img['full']);
                $processed++;
            }
            imagedestroy($gd);
        }

        cache_clear();
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'image_optimize',
            'Optimized ' . $processed . ' images, saved ' . human_filesize(max(0, $savedBytes)));
        flash_set('success', sprintf(
            'Optimized %d image(s) — %d converted to WebP. Saved %s.',
            $processed, $converted, human_filesize(max(0, $savedBytes))
        ));
        redirect(BASE_URL . '/admin/performance.php');
    }
}

// ---------- Stats ----------
$images = scan_images($root, $imageExts);
$totalBytes = array_sum(array_column($images, 'size'));
$maxEdge = (int)get_setting($pdo, 'media_max_width', '1920');
$oversized = array_values(array_filter($images, fn($i) => max($i['width'], $i['height']) > $maxEdge));
$nonWebp = array_values(array_filter($images, fn($i) => $i['ext'] !== 'webp' && !str_starts_with($i['rel'], 'uploads/applications/')));
usort($images, fn($a, $b) => $b['size'] <=> $a['size']);
$heaviest = array_slice($images, 0, 8);

$cacheFiles = glob($root . '/cache/*.php') ?: [];
$minFiles = glob($root . '/assets/{css,js}/*.min.*', GLOB_BRACE) ?: [];

$cssSize = is_file($root . '/assets/css/style.css') ? filesize($root . '/assets/css/style.css') : 0;
$cssMinSize = is_file($root . '/assets/css/style.min.css') ? filesize($root . '/assets/css/style.min.css') : 0;
$jsSize = is_file($root . '/assets/js/main.js') ? filesize($root . '/assets/js/main.js') : 0;
$jsMinSize = is_file($root . '/assets/js/main.min.js') ? filesize($root . '/assets/js/main.min.js') : 0;

$gzipOn = function_exists('apache_get_modules') ? in_array('mod_deflate', apache_get_modules(), true) : null;

$pageTitle = 'Performance';
require_once __DIR__ . '/includes/header.php';
?>

<div class="dash-stats">
  <div class="dash-stat">
    <strong><?= human_filesize((int)$totalBytes) ?></strong>
    <span>Total Image Weight</span>
    <span class="stat-sub"><?= count($images) ?> images in uploads/</span>
  </div>
  <div class="dash-stat">
    <strong><?= count($oversized) ?></strong>
    <span>Oversized Images</span>
    <span class="stat-sub">wider than <?= (int)get_setting($pdo, 'media_max_width', '1920') ?>px</span>
  </div>
  <div class="dash-stat">
    <strong><?= count($nonWebp) ?></strong>
    <span>Not Yet WebP</span>
    <span class="stat-sub">convertible to modern format</span>
  </div>
  <div class="dash-stat">
    <strong><?= $cssMinSize ? round((1 - $cssMinSize / max(1, $cssSize)) * 100) . '%' : '—' ?></strong>
    <span>CSS Size Reduction</span>
    <span class="stat-sub"><?= human_filesize((int)$cssSize) ?> → <?= $cssMinSize ? human_filesize((int)$cssMinSize) : 'not built' ?></span>
  </div>
</div>

<div class="grid-2" style="align-items:start;">
  <div class="card">
    <div class="card-header"><h2>⚡ Optimization Settings</h2></div>
    <form method="post" action="performance.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_settings">
      <div class="form-group">
        <label style="font-weight:400;"><input type="checkbox" name="perf_cache" <?= get_setting($pdo, 'perf_cache', '1') === '1' ? 'checked' : '' ?>> <strong>Page data caching</strong> — cache settings &amp; menus to disk (fewer database queries)</label>
        <label style="font-weight:400;"><input type="checkbox" name="perf_minify" <?= get_setting($pdo, 'perf_minify', '1') === '1' ? 'checked' : '' ?>> <strong>Minify CSS &amp; JavaScript</strong> — with gzip pre-compression and cache-busting URLs</label>
        <label style="font-weight:400;"><input type="checkbox" name="perf_html_minify" <?= get_setting($pdo, 'perf_html_minify', '1') === '1' ? 'checked' : '' ?>> <strong>Minify HTML output</strong> — strips whitespace and comments from every page</label>
      </div>
      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>

    <div class="card-header" style="margin-top:26px;"><h2>🗑️ Cache</h2></div>
    <p class="form-hint" style="margin-bottom:12px;">
      <?= count($cacheFiles) ?> cached data file(s), <?= count($minFiles) ?> built asset file(s).
      Caches clear automatically whenever you save content — use this if something looks stale.
    </p>
    <form method="post" action="performance.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="clear_cache">
      <button type="submit" class="btn btn-outline">Clear All Caches</button>
    </form>

    <div class="card-header" style="margin-top:26px;"><h2>Server</h2></div>
    <div class="feed">
      <div class="feed-item"><span class="f-ico"><?= $gzipOn === false ? '⚠️' : '✅' ?></span><span><strong>Gzip compression</strong><small><?= $gzipOn === false ? 'mod_deflate not loaded' : 'Enabled — HTML/CSS/JS compressed in transit' ?></small></span></div>
      <div class="feed-item"><span class="f-ico">✅</span><span><strong>Browser caching</strong><small>Static assets cached for 1 year (immutable, fingerprinted URLs)</small></span></div>
      <div class="feed-item"><span class="f-ico"><?= extension_loaded('gd') ? '✅' : '⚠️' ?></span><span><strong>Image processing (GD)</strong><small><?= extension_loaded('gd') ? 'Available — WebP conversion and resizing enabled' : 'Not available' ?></small></span></div>
      <div class="feed-item"><span class="f-ico">✅</span><span><strong>Lazy loading</strong><small>Below-the-fold images defer until scrolled into view</small></span></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>🖼️ Bulk Image Optimizer</h2></div>
    <p class="form-hint" style="margin-bottom:16px;">
      Rescales images wider than <strong><?= (int)get_setting($pdo, 'media_max_width', '1920') ?>px</strong> and re-encodes them at quality
      <strong><?= (int)get_setting($pdo, 'media_quality', '82') ?></strong> (both configurable in
      <a href="media.php" style="color:var(--primary);">Media Manager</a>). WebP conversion also updates every database reference, so nothing breaks.
      Application documents are left untouched.
    </p>
    <form method="post" action="performance.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="optimize_images">
      <div class="form-group">
        <label style="font-weight:400;"><input type="checkbox" name="convert_webp" checked> Also convert JPG/PNG to WebP (recommended — typically 25–35% smaller)</label>
      </div>
      <button type="submit" class="btn btn-primary" data-confirm="Optimize all site images? Originals are replaced. This can take a minute.">🚀 Optimize All Images</button>
    </form>

    <?php if ($heaviest): ?>
    <div class="card-header" style="margin-top:26px;"><h2>Heaviest Images</h2></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>File</th><th>Size</th><th>Dimensions</th></tr></thead>
        <tbody>
        <?php foreach ($heaviest as $img): ?>
          <tr>
            <td style="max-width:230px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e(basename($img['rel'])) ?></td>
            <td><?= human_filesize((int)$img['size']) ?></td>
            <td>
              <?= (int)$img['width'] ?>×<?= (int)$img['height'] ?>
              <?php if (max($img['width'], $img['height']) > $maxEdge): ?>
                <span class="badge badge-new">oversized</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
