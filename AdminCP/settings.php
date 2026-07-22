<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

// Text settings, grouped into cards: group => [key => [label, icon, type, placeholder]]
$groups = [
    'General' => [
        'site_name' => ['Site Name', 'fa-signature',  'text', 'Malik Solution'],
        'tagline'   => ['Tagline',   'fa-quote-left', 'text', 'Web · Mobile · Software Solutions'],
    ],
    'Contact' => [
        'contact_email'   => ['Contact Email',  'fa-envelope',     'email',    'support@maliksolution.com'],
        'contact_phone'   => ['Contact Phone',  'fa-phone',        'text',     '+92 333 2150925'],
        'contact_address' => ['Office Address', 'fa-location-dot', 'textarea', 'Kehkashan Society, Malir Halt, Karachi, Pakistan'],
    ],
    'Social Links' => [
        'facebook_url'  => ['Facebook URL',  'fa-brands fa-facebook',  'url', 'https://facebook.com/...'],
        'instagram_url' => ['Instagram URL', 'fa-brands fa-instagram', 'url', 'https://instagram.com/...'],
        'youtube_url'   => ['YouTube URL',   'fa-brands fa-youtube',   'url', 'https://youtube.com/...'],
    ],
    'Footer' => [
        'footer_description' => ['Footer Description', 'fa-align-left', 'textarea', 'A professional software development company crafting high-performance websites, mobile apps, and IT solutions.'],
        'footer_copyright'   => ['Copyright Line',     'fa-copyright',  'text',     '© ' . date('Y') . ' Malik Solution Private Limited. All rights reserved.'],
        'privacy_url'        => ['Privacy Policy URL', 'fa-shield-halved', 'url',   'privacy.php or https://...'],
        'terms_url'          => ['Terms of Use URL',   'fa-file-contract', 'url',   'terms.php or https://...'],
    ],
];

/**
 * Save an uploaded branding image and return its site-relative path ('' on no file).
 * Throws RuntimeException with a user-facing message on invalid files.
 */
function save_branding_upload(array $file, string $prefix, array $allowed_ext): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(ucfirst($prefix) . ' upload failed — please try again.');
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException(ucfirst($prefix) . ' must be smaller than 2 MB.');
    }
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        throw new RuntimeException(ucfirst($prefix) . ' must be one of: ' . implode(', ', $allowed_ext) . '.');
    }
    if ($ext !== 'ico' && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException(ucfirst($prefix) . ' is not a valid image file.');
    }
    $dir = __DIR__ . '/../assets/uploads/branding';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = $prefix . '-' . time() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException('Could not save the ' . $prefix . ' file.');
    }
    return 'assets/uploads/branding/' . $name;
}

/** Delete a previously uploaded branding file (only inside the branding folder). */
function delete_branding_file(string $path): void
{
    if (str_starts_with($path, 'assets/uploads/branding/')) {
        @unlink(__DIR__ . '/../' . $path);
    }
}

/**
 * Generate a white monochrome variant of the logo for the dark navbar.
 * Returns the site-relative path, or '' if generation failed.
 */
function make_white_logo(string $logoRelPath): string
{
    $abs = __DIR__ . '/../' . $logoRelPath;
    $data = @file_get_contents($abs);
    if ($data === false) return '';
    $im = @imagecreatefromstring($data);
    if (!$im) return '';
    imagepalettetotruecolor($im);
    $w = imagesx($im); $h = imagesy($im);

    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($im, $x, $y);
            $a = ($rgba >> 24) & 0x7F;
            if ($a >= 120) continue;
            $r = ($rgba >> 16) & 0xFF; $g = ($rgba >> 8) & 0xFF; $b = $rgba & 0xFF;
            if ($r > 235 && $g > 235 && $b > 235) continue; // drop white bg/glow
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, 255, 255, 255, $a));
        }
    }
    $rel = 'assets/uploads/branding/logo-white-' . time() . '.png';
    if (!imagepng($out, __DIR__ . '/../' . $rel)) return '';
    return $rel;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ── Branding uploads ──
        foreach ([
            'logo_file'    => ['logo',    ['png', 'jpg', 'jpeg', 'webp'], 'logo_path'],
            'favicon_file' => ['favicon', ['ico', 'png', 'jpg', 'jpeg'],  'favicon_path'],
        ] as $input => [$prefix, $allowed, $setting]) {
            if (isset($_FILES[$input])) {
                $new_path = save_branding_upload($_FILES[$input], $prefix, $allowed);
                if ($new_path !== '') {
                    delete_branding_file(get_setting($conn, $setting));
                    set_setting($conn, $setting, $new_path);
                    // New logo → regenerate the white navbar variant too
                    if ($setting === 'logo_path') {
                        $white = make_white_logo($new_path);
                        if ($white !== '') {
                            delete_branding_file(get_setting($conn, 'logo_white_path'));
                            set_setting($conn, 'logo_white_path', $white);
                        }
                    }
                }
            }
        }

        // ── Text settings ──
        foreach ($groups as $fields) {
            foreach ($fields as $key => $meta) {
                set_setting($conn, $key, trim((string)($_POST[$key] ?? '')));
            }
        }

        log_activity($conn, 'settings', 'Updated site settings');
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Site settings saved.'];
    } catch (RuntimeException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
    }
    header('Location: settings.php');
    exit;
}

$page_title = 'Site Settings';
$active_nav = 'settings';
include 'header.php';

$defaults = [
    'site_name'          => 'Malik Solution',
    'tagline'            => 'Web · Mobile · Software Solutions',
    'contact_email'      => 'support@maliksolution.com',
    'contact_address'    => 'Kehkashan Society, Malir Halt, Karachi, Pakistan',
    'footer_description' => 'A professional software development company crafting high-performance websites, mobile apps, and IT solutions for businesses worldwide.',
    'footer_copyright'   => '© ' . date('Y') . ' Malik Solution Private Limited. All rights reserved.',
];

$logo_path    = get_setting($conn, 'logo_path', 'maliksolution.png');
$favicon_path = get_setting($conn, 'favicon_path', '');

$group_icons = [
    'General'      => 'fa-sliders',
    'Contact'      => 'fa-address-book',
    'Social Links' => 'fa-share-nodes',
    'Footer'       => 'fa-shoe-prints',
];
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-gear me-2 text-primary"></i>Site Settings</h1>
    <p>Branding, contact details, social links, and footer content for the public website</p>
  </div>
</div>

<form method="post" enctype="multipart/form-data">
<div class="row g-3">
  <div class="col-xl-8">

    <!-- ── Branding ── -->
    <div class="cardx mb-3" style="height:auto">
      <div class="section-hd"><h2><i class="fa-solid fa-palette me-2 text-primary"></i>Branding</h2></div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label"><i class="fa-solid fa-image me-1 text-primary"></i>Logo</label>
          <div class="d-flex align-items-center gap-3 mb-2">
            <img src="../<?= htmlspecialchars($logo_path) ?>" alt="Current logo"
                 style="width:56px;height:56px;object-fit:contain;background:#fff;border:1px solid var(--line);border-radius:10px;padding:4px">
            <div class="text-muted small">
              Current: <code><?= htmlspecialchars(basename($logo_path)) ?></code><br>
              Shown in the site header, AdminCP sidebar, and login page.
            </div>
          </div>
          <input type="file" class="form-control" name="logo_file" accept=".png,.jpg,.jpeg,.webp">
          <div class="form-text">PNG, JPG, or WEBP — max 2 MB. Square works best.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label"><i class="fa-solid fa-star me-1 text-primary"></i>Favicon</label>
          <div class="d-flex align-items-center gap-3 mb-2">
            <?php if ($favicon_path !== ''): ?>
              <img src="../<?= htmlspecialchars($favicon_path) ?>" alt="Current favicon"
                   style="width:32px;height:32px;object-fit:contain;background:#fff;border:1px solid var(--line);border-radius:8px;padding:2px">
              <div class="text-muted small">Current: <code><?= htmlspecialchars(basename($favicon_path)) ?></code></div>
            <?php else: ?>
              <div class="text-muted small">No favicon uploaded yet — browsers show a default icon.</div>
            <?php endif; ?>
          </div>
          <input type="file" class="form-control" name="favicon_file" accept=".ico,.png,.jpg,.jpeg">
          <div class="form-text">ICO or PNG — max 2 MB. 32×32 or 64×64 recommended.</div>
        </div>
      </div>
    </div>

    <!-- ── Text setting groups ── -->
    <?php foreach ($groups as $group => $fields): ?>
      <div class="cardx mb-3" style="height:auto">
        <div class="section-hd"><h2><i class="fa-solid <?= $group_icons[$group] ?? 'fa-gear' ?> me-2 text-primary"></i><?= htmlspecialchars($group) ?></h2></div>
        <div class="row g-3">
          <?php foreach ($fields as $key => [$label, $icon, $type, $placeholder]):
              $value = get_setting($conn, $key, $defaults[$key] ?? ''); ?>
            <div class="<?= $type === 'textarea' ? 'col-12' : 'col-md-6' ?>">
              <label class="form-label" for="set-<?= $key ?>">
                <i class="fa-solid <?= htmlspecialchars($icon) ?> me-1 text-primary"></i><?= htmlspecialchars($label) ?>
              </label>
              <?php if ($type === 'textarea'): ?>
                <textarea class="form-control" id="set-<?= $key ?>" name="<?= $key ?>" rows="2"
                          placeholder="<?= htmlspecialchars($placeholder) ?>"><?= htmlspecialchars($value) ?></textarea>
              <?php else: ?>
                <input type="<?= $type ?>" class="form-control" id="set-<?= $key ?>" name="<?= $key ?>"
                       value="<?= htmlspecialchars($value) ?>" placeholder="<?= htmlspecialchars($placeholder) ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">
      <i class="fa-solid fa-floppy-disk me-1"></i>Save Settings
    </button>
  </div>

  <div class="col-xl-4">
    <div class="cardx" style="height:auto">
      <div class="section-hd"><h2><i class="fa-solid fa-circle-info me-2 text-primary"></i>Where these appear</h2></div>
      <ul class="small text-muted mb-0" style="line-height:2">
        <li><strong class="text-navy">Logo</strong> — site navbar, AdminCP sidebar, admin login page</li>
        <li><strong class="text-navy">Favicon</strong> — browser tab icon on every page</li>
        <li><strong class="text-navy">Contact email, phone &amp; address</strong> — site footer and Contact page</li>
        <li><strong class="text-navy">Social links</strong> — site footer icons</li>
        <li><strong class="text-navy">Footer description &amp; copyright</strong> — site footer</li>
        <li><strong class="text-navy">Privacy / Terms URLs</strong> — footer bottom links</li>
        <li>Leave a field empty to fall back to the built-in default.</li>
      </ul>
    </div>
  </div>
</div>
</form>

<?php include 'footer.php'; ?>
