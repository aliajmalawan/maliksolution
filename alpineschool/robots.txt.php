<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/seo.php';

header('Content-Type: text/plain; charset=utf-8');

$base = seo_site_url($pdo);
$robots = get_setting($pdo, 'seo_robots', 'index, follow');
$blockAll = str_contains($robots, 'noindex');

echo "User-agent: *\n";
if ($blockAll) {
    // Site is set to "noindex" in the SEO Manager — keep crawlers out entirely.
    echo "Disallow: /\n";
} else {
    echo "Disallow: /admin/\n";
    echo "Disallow: /includes/\n";
    echo "Disallow: /database/\n";
    echo "Disallow: /uploads/downloads/\n";
    echo "Disallow: /search.php\n";
    echo "Allow: /\n";
}
echo "\n";
echo "Sitemap: {$base}/sitemap.xml\n";
