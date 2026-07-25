<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/seo.php';

$textFields = [
    'seo_meta_keywords', 'seo_meta_description', 'seo_google_analytics', 'seo_robots',
    'seo_site_url', 'seo_twitter_handle', 'seo_org_type', 'seo_founding_year',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/seo.php');
    }

    $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($textFields as $field) {
        $value = trim((string)($_POST[$field] ?? ''));
        if ($field === 'seo_site_url') {
            $value = rtrim($value, '/');
        }
        if ($field === 'seo_twitter_handle' && $value !== '' && $value[0] !== '@') {
            $value = '@' . $value;
        }
        $update->execute([$field, $value]);
    }

    if (!empty($_FILES['og_image']['name'])) {
        $path = upload_image($_FILES['og_image'], 'branding');
        if ($path) {
            $update->execute(['seo_og_image', 'uploads/' . $path]);
        } else {
            flash_set('error', 'Social share image upload failed (JPG/PNG/WEBP).');
        }
    }
    if (isset($_POST['remove_og_image'])) {
        $update->execute(['seo_og_image', '']);
    }

    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'seo_update', 'Updated SEO settings');
    flash_set('success', 'SEO settings saved.');
    redirect(BASE_URL . '/admin/seo.php');
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$s = fn(string $k, string $d = '') => $settings[$k] ?? $d;

$pages = $pdo->query('SELECT id, slug, title, meta_description FROM pages ORDER BY title')->fetchAll();

// Sitemap stats
$counts = [
    'News articles' => (int)$pdo->query('SELECT COUNT(*) c FROM news WHERE is_published = 1')->fetch()['c'],
    'Blog posts' => (int)$pdo->query('SELECT COUNT(*) c FROM blogs WHERE is_published = 1')->fetch()['c'],
    'Blog categories' => (int)$pdo->query('SELECT COUNT(*) c FROM blog_categories')->fetch()['c'],
    'Blog tags' => (int)$pdo->query('SELECT COUNT(*) c FROM blog_tags')->fetch()['c'],
    'Gallery albums' => (int)$pdo->query('SELECT COUNT(*) c FROM gallery_albums')->fetch()['c'],
    'Content pages' => count($pages),
];

$siteUrl = seo_site_url($pdo);

$pageTitle = 'SEO Manager';
require_once __DIR__ . '/includes/header.php';
?>

<form method="post" action="seo.php" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="grid-2" style="align-items:start;">
    <div>
      <div class="card">
        <div class="card-header"><h2>🔎 Search Engine Basics</h2></div>
        <div class="form-group">
          <label for="seo_site_url">Site URL (used for canonical links, sitemap, social shares)</label>
          <input type="text" id="seo_site_url" name="seo_site_url" placeholder="https://www.thealpineschoolhn.com" value="<?= e($s('seo_site_url')) ?>">
          <p class="form-hint">No trailing slash. Set this to your live domain before going public — every canonical/OG URL is built from it.</p>
        </div>
        <div class="form-group">
          <label for="seo_meta_description">Default Meta Description</label>
          <textarea id="seo_meta_description" name="seo_meta_description" style="min-height:70px;"><?= e($s('seo_meta_description')) ?></textarea>
          <p class="form-hint">Used when a page has no description of its own. Aim for 150–160 characters.</p>
        </div>
        <div class="form-group">
          <label for="seo_meta_keywords">Meta Keywords</label>
          <input type="text" id="seo_meta_keywords" name="seo_meta_keywords" placeholder="school, haroonabad, admissions, …" value="<?= e($s('seo_meta_keywords')) ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="seo_google_analytics">Google Analytics ID</label>
            <input type="text" id="seo_google_analytics" name="seo_google_analytics" placeholder="G-XXXXXXXXXX" value="<?= e($s('seo_google_analytics')) ?>">
          </div>
          <div class="form-group">
            <label for="seo_robots">Robots Directive</label>
            <select id="seo_robots" name="seo_robots">
              <?php foreach (['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $s('seo_robots', 'index, follow') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
            <p class="form-hint">Choosing "noindex" also makes robots.txt block all crawlers.</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><h2>📣 Social Sharing (Open Graph &amp; Twitter)</h2></div>
        <div class="form-group">
          <?php if ($s('seo_og_image')): ?>
            <img src="<?= BASE_URL ?>/<?= e($s('seo_og_image')) ?>" class="current-image" style="width:180px;height:auto;" alt="Share image">
            <label style="font-weight:400;"><input type="checkbox" name="remove_og_image"> Remove share image</label>
          <?php endif; ?>
          <label for="og_image">Default Share Image</label>
          <input type="file" id="og_image" name="og_image" accept="image/*">
          <p class="form-hint">Shown when a page is shared on Facebook, WhatsApp, LinkedIn, or X. Ideal size 1200×630px. Blog and news posts use their own featured image automatically; the logo is the fallback.</p>
        </div>
        <div class="form-group">
          <label for="seo_twitter_handle">Twitter / X Handle</label>
          <input type="text" id="seo_twitter_handle" name="seo_twitter_handle" placeholder="@thealpineschool" value="<?= e($s('seo_twitter_handle')) ?>">
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-header"><h2>🏫 Schema.org / Rich Results</h2></div>
        <p class="form-hint" style="margin-bottom:16px;">Structured data (JSON-LD) is added to every page automatically — Organization, WebSite with search, breadcrumbs, and Article data on posts. These fields fill it in.</p>
        <div class="form-row">
          <div class="form-group">
            <label for="seo_org_type">Organization Type</label>
            <select id="seo_org_type" name="seo_org_type">
              <?php foreach (['School', 'EducationalOrganization', 'HighSchool', 'ElementarySchool', 'Organization'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $s('seo_org_type', 'School') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="seo_founding_year">Founding Year</label>
            <input type="text" id="seo_founding_year" name="seo_founding_year" placeholder="2018" value="<?= e($s('seo_founding_year')) ?>">
          </div>
        </div>
        <p class="form-hint">Name, logo, phone, email, address, and social profiles come from <a href="settings.php" style="color:var(--primary);">Site Settings</a>.</p>
      </div>

      <div class="card">
        <div class="card-header"><h2>🗺️ Sitemap &amp; robots.txt</h2></div>
        <p class="form-hint" style="margin-bottom:14px;">Both are generated live from your content — no need to regenerate anything after publishing.</p>
        <div class="feed" style="margin-bottom:16px;">
          <?php foreach ($counts as $label => $count): ?>
          <div class="feed-item"><span class="f-ico">•</span><span><strong><?= $count ?></strong> <?= e($label) ?> in the sitemap</span></div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <a href="<?= e($siteUrl) ?>/sitemap.xml" target="_blank" class="btn btn-outline btn-sm">View sitemap.xml ↗</a>
          <a href="<?= e($siteUrl) ?>/robots.txt" target="_blank" class="btn btn-outline btn-sm">View robots.txt ↗</a>
        </div>
        <p class="form-hint" style="margin-top:12px;">Submit <code><?= e($siteUrl) ?>/sitemap.xml</code> to Google Search Console once your live domain is set above.</p>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">💾 Save SEO Settings</button>
</form>

<div class="card" style="margin-top:22px;">
  <div class="card-header"><h2>Per-Page Meta Descriptions</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Page</th><th>Meta Description</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pages as $page): ?>
        <tr>
          <td><?= e($page['title']) ?> <span class="form-hint" style="display:inline;">/<?= e($page['slug']) ?></span></td>
          <td style="max-width:380px;"><?= $page['meta_description'] ? e($page['meta_description']) : '<span class="form-hint" style="display:inline;">— uses the global default —</span>' ?></td>
          <td><a href="pages.php?edit=<?= (int)$page['id'] ?>" class="btn btn-outline btn-sm">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="form-hint" style="margin-top:12px;">Blog posts have their own SEO description field in the <a href="blogs.php" style="color:var(--primary);">blog editor</a>; news posts use their excerpt.</p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
