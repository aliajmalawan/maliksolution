<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/seo.php';

header('Content-Type: application/xml; charset=utf-8');

$base = seo_site_url($pdo);
$urls = [];

/** @param string $loc relative file, e.g. "about.php" (listed extensionless — clean URLs) */
function sm_add(array &$urls, string $base, string $loc, ?string $lastmod, string $changefreq, string $priority): void
{
    $loc = ltrim($loc, '/');
    $loc = $loc === 'index.php' ? '' : preg_replace('/\.php(?=$|\?)/', '', $loc);
    $urls[] = [
        'loc' => $base . '/' . $loc,
        'lastmod' => $lastmod ? date('Y-m-d', strtotime($lastmod)) : null,
        'changefreq' => $changefreq,
        'priority' => $priority,
    ];
}

// Static / core pages
sm_add($urls, $base, 'index.php', null, 'daily', '1.0');
foreach ([
    'about.php' => '0.8', 'academics.php' => '0.8', 'admissions.php' => '0.9',
    'faculty.php' => '0.7', 'gallery.php' => '0.7', 'news.php' => '0.8',
    'admission-form.php' => '0.9', 'application-status.php' => '0.5',
    'blogs.php' => '0.8', 'events.php' => '0.7', 'contact.php' => '0.8',
    'faqs.php' => '0.6', 'videos.php' => '0.6', 'media.php' => '0.6', 'downloads.php' => '0.6',
    'results.php' => '0.6', 'career.php' => '0.6', 'search.php' => '0.3',
] as $file => $priority) {
    if (is_file(__DIR__ . '/' . $file)) {
        sm_add($urls, $base, $file, null, 'weekly', $priority);
    }
}

// DB-driven content pages (principal-message.php, campus-life.php, …)
foreach ($pdo->query('SELECT slug, updated_at FROM pages')->fetchAll() as $page) {
    $file = $page['slug'] . '.php';
    if (is_file(__DIR__ . '/' . $file)) {
        sm_add($urls, $base, $file, $page['updated_at'], 'monthly', '0.7');
    }
}

// News
foreach ($pdo->query('SELECT slug, published_at FROM news WHERE is_published = 1 ORDER BY published_at DESC')->fetchAll() as $row) {
    sm_add($urls, $base, 'news-detail.php?slug=' . urlencode($row['slug']), $row['published_at'], 'monthly', '0.6');
}

// Blog posts
foreach ($pdo->query('SELECT slug, published_at FROM blogs WHERE is_published = 1 ORDER BY published_at DESC')->fetchAll() as $row) {
    sm_add($urls, $base, 'blog-detail.php?slug=' . urlencode($row['slug']), $row['published_at'], 'monthly', '0.6');
}

// Blog categories & tags
foreach ($pdo->query('SELECT slug FROM blog_categories')->fetchAll() as $row) {
    sm_add($urls, $base, 'blogs.php?cat=' . urlencode($row['slug']), null, 'weekly', '0.5');
}
foreach ($pdo->query('SELECT slug FROM blog_tags')->fetchAll() as $row) {
    sm_add($urls, $base, 'blogs.php?tag=' . urlencode($row['slug']), null, 'weekly', '0.4');
}

// Gallery albums
foreach ($pdo->query('SELECT slug, created_at FROM gallery_albums')->fetchAll() as $row) {
    sm_add($urls, $base, 'gallery.php?album=' . urlencode($row['slug']), $row['created_at'], 'monthly', '0.5');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
  <url>
    <loc><?= e($url['loc']) ?></loc>
<?php if ($url['lastmod']): ?>
    <lastmod><?= e($url['lastmod']) ?></lastmod>
<?php endif; ?>
    <changefreq><?= e($url['changefreq']) ?></changefreq>
    <priority><?= e($url['priority']) ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
