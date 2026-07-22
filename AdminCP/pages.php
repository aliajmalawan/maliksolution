<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

$page_defs = require __DIR__ . '/../includes/page-fields.php';

$page_key = trim((string)($_GET['page'] ?? 'home'));
if (!isset($page_defs[$page_key])) $page_key = 'home';
$def = $page_defs[$page_key];

/** Save an uploaded page image, return its site-relative path ('' on no file). */
function save_page_image(array $file, string $key): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed — please try again.');
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Images must be smaller than 4 MB.');
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'avif'], true)) {
        throw new RuntimeException('Images must be PNG, JPG, WEBP, or AVIF.');
    }
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('That file is not a valid image.');
    }
    $dir = __DIR__ . '/../assets/uploads/pages';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = $key . '-' . time() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException('Could not save the image file.');
    }
    return 'assets/uploads/pages/' . $name;
}

/** Delete a previously uploaded page image (only inside the uploads folder). */
function delete_page_image(string $path): void
{
    if (str_starts_with($path, 'assets/uploads/pages/')) {
        @unlink(__DIR__ . '/../' . $path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($def['sections'] as $fields) {
            foreach ($fields as $key => [$label, $type, $default]) {
                if ($type === 'image') {
                    if (!empty($_POST['reset_' . $key])) {
                        delete_page_image(get_content($conn, $page_key, $key));
                        set_content($conn, $page_key, $key, '');
                    } elseif (!empty($_POST['remove_' . $key])) {
                        // Permanently hide this image on the website
                        delete_page_image(get_content($conn, $page_key, $key));
                        set_content($conn, $page_key, $key, '__none__');
                    } elseif (isset($_FILES['img_' . $key])) {
                        $new_path = save_page_image($_FILES['img_' . $key], $page_key . '-' . $key);
                        if ($new_path !== '') {
                            delete_page_image(get_content($conn, $page_key, $key));
                            set_content($conn, $page_key, $key, $new_path);
                        }
                    }
                } elseif (array_key_exists($key, $_POST)) {
                    set_content($conn, $page_key, $key, trim((string)$_POST[$key]));
                }
            }
        }
        log_activity($conn, 'pages', 'Updated "' . $def['label'] . '" page content');
        $_SESSION['flash'] = ['type' => 'success', 'message' => '"' . $def['label'] . '" page content saved.'];
    } catch (RuntimeException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
    }
    header('Location: pages.php?page=' . urlencode($page_key));
    exit;
}

$page_title = 'Website Pages';
$active_nav = 'pages';
include 'header.php';

$page_urls = [
    'home'            => '../index.php',
    'about'           => '../about.php',
    'web-development' => '../web-development.php',
    'it-services'     => '../it-services.php',
    'mobile-app'      => '../mobile-app.php',
    'hosting'         => '../hosting.php',
    'projects'        => '../projects.php',
    'demonstration'   => '../demonstration.php',
    'contact'         => '../contact.php',
];
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-file-pen me-2 text-primary"></i>Website Pages</h1>
    <p>Edit the text shown on every public page — headings, paragraphs, stats, and hosting plans</p>
  </div>
  <a href="<?= htmlspecialchars($page_urls[$page_key]) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View "<?= htmlspecialchars($def['label']) ?>" Page
  </a>
</div>

<!-- Page tabs -->
<div class="cardx mb-3" style="height:auto;padding:.6rem">
  <div class="d-flex flex-wrap gap-1">
    <?php foreach ($page_defs as $key => $d): ?>
      <a href="pages.php?page=<?= urlencode($key) ?>"
         class="btn btn-sm <?= $key === $page_key ? 'btn-primary' : 'btn-outline-secondary' ?>">
        <i class="fa-solid <?= htmlspecialchars($d['icon']) ?> me-1"></i><?= htmlspecialchars($d['label']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<form method="post" enctype="multipart/form-data">
  <div class="row g-3">
    <div class="col-xl-9">
      <?php foreach ($def['sections'] as $section => $fields): ?>
        <div class="cardx mb-3" style="height:auto">
          <div class="section-hd"><h2><i class="fa-solid fa-layer-group me-2 text-primary"></i><?= htmlspecialchars($section) ?></h2></div>
          <div class="row g-3">
            <?php foreach ($fields as $key => $meta):
                [$label, $type, $default] = $meta;
                $rec_size = $meta[3] ?? '';
                $value = get_content($conn, $page_key, $key, $default);
                $wide  = $type === 'textarea'; ?>
              <div class="<?= $wide ? 'col-12' : 'col-md-6' ?>">
                <label class="form-label" for="pc-<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                <?php if ($type === 'select'):
                    $options = is_array($meta[3] ?? null) ? $meta[3] : []; ?>
                  <select class="form-select" id="pc-<?= $key ?>" name="<?= $key ?>">
                    <?php foreach ($options as $optVal => $optLabel): ?>
                      <option value="<?= htmlspecialchars((string)$optVal) ?>" <?= $value === (string)$optVal ? 'selected' : '' ?>>
                        <?= htmlspecialchars($optLabel) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif ($type === 'image'):
                    $is_hidden = $value === '__none__';
                    $is_custom = str_starts_with($value, 'assets/uploads/pages/'); ?>
                  <div class="d-flex align-items-start gap-3 mb-2">
                    <?php if ($is_hidden): ?>
                      <div style="width:110px;height:74px;border:1px dashed var(--line);border-radius:8px;display:grid;place-items:center;color:#8898b3">
                        <i class="fa-solid fa-eye-slash"></i>
                      </div>
                    <?php else: ?>
                      <img src="../<?= htmlspecialchars($value) ?>" alt="<?= htmlspecialchars($label) ?>"
                           style="width:110px;height:74px;object-fit:cover;background:#fff;border:1px solid var(--line);border-radius:8px;padding:2px">
                    <?php endif; ?>
                    <div class="text-muted small" style="line-height:1.5">
                      <?php if ($is_hidden): ?>
                        This picture is <span class="badge bg-danger-subtle text-danger">hidden</span> — it does not appear on the website.
                        <div class="form-check mt-1">
                          <input class="form-check-input" type="checkbox" name="reset_<?= $key ?>" value="1" id="reset-<?= $key ?>">
                          <label class="form-check-label" for="reset-<?= $key ?>">Show again (restore default image)</label>
                        </div>
                      <?php else: ?>
                        Current: <code><?= htmlspecialchars(basename($value)) ?></code>
                        <?= $is_custom ? '<span class="badge bg-primary-subtle text-primary ms-1">uploaded</span>' : '<span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">default</span>' ?>
                        <?php if ($is_custom): ?>
                          <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="reset_<?= $key ?>" value="1" id="reset-<?= $key ?>">
                            <label class="form-check-label" for="reset-<?= $key ?>">Remove &amp; use default image</label>
                          </div>
                        <?php endif; ?>
                        <div class="form-check mt-1">
                          <input class="form-check-input" type="checkbox" name="remove_<?= $key ?>" value="1" id="remove-<?= $key ?>">
                          <label class="form-check-label text-danger" for="remove-<?= $key ?>">
                            <i class="fa-solid fa-eye-slash me-1"></i>Remove picture permanently (hide on website)
                          </label>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if (!$is_hidden): ?>
                  <input type="file" class="form-control" id="pc-<?= $key ?>" name="img_<?= $key ?>" accept=".png,.jpg,.jpeg,.webp,.avif">
                  <div class="form-text">
                    PNG, JPG, WEBP, or AVIF — max 4 MB.
                    <?php if ($rec_size !== ''): ?>
                      <br><i class="fa-solid fa-ruler-combined me-1 text-primary"></i>
                      <strong>Recommended size: <?= htmlspecialchars($rec_size) ?></strong>
                    <?php endif; ?>
                  </div>
                  <?php endif; ?>
                <?php elseif ($type === 'textarea'): ?>
                  <textarea class="form-control" id="pc-<?= $key ?>" name="<?= $key ?>"
                            rows="<?= substr_count($default, "\n") > 2 ? 7 : 3 ?>"><?= htmlspecialchars($value) ?></textarea>
                <?php else: ?>
                  <input type="text" class="form-control" id="pc-<?= $key ?>" name="<?= $key ?>"
                         value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk me-1"></i>Save "<?= htmlspecialchars($def['label']) ?>" Content
      </button>
    </div>

    <div class="col-xl-3">
      <div class="cardx" style="height:auto">
        <div class="section-hd"><h2><i class="fa-solid fa-circle-info me-2 text-primary"></i>Tips</h2></div>
        <ul class="small text-muted mb-0" style="line-height:1.9;padding-left:1.1rem">
          <li>Changes go live on the website as soon as you save.</li>
          <li>Each page's <strong>Images</strong> section lets you upload new pictures; tick "Remove" to go back to the default image.</li>
          <li>Stats use the format <code>number | label</code> (e.g. <code>150+ | Projects</code>).</li>
          <li>Hosting plan features: one feature per line.</li>
          <li>Clearing a field brings back the original default text.</li>
          <li>Contact details, social links, logo &amp; footer are edited in <a href="settings.php">Site Settings</a>.</li>
        </ul>
      </div>
    </div>
  </div>
</form>

<?php include 'footer.php'; ?>
