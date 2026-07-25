<?php
declare(strict_types=1);
// SEO helpers: canonical URLs, Open Graph, Twitter Cards, JSON-LD (schema.org), breadcrumbs.
// Pages may set these before including header.php:
//   $pageTitle, $pageDescription, $seoImage (relative path), $seoType ('website'|'article'),
//   $seoArticle = ['published' => ..., 'modified' => ..., 'author' => ..., 'section' => ...],
//   $breadcrumbs = [['label' => 'Blogs', 'url' => 'blogs.php'], ['label' => 'Post title']]

/** Absolute site root, e.g. https://example.com/AlpineSchool (no trailing slash). */
function seo_site_url(PDO $pdo): string
{
    $url = trim(get_setting($pdo, 'seo_site_url', ''));
    if ($url === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;
    }
    return rtrim($url, '/');
}

/** Turn a relative path/URL into an absolute one. */
function seo_abs_url(PDO $pdo, string $path): string
{
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return seo_site_url($pdo) . '/' . ltrim($path, '/');
}

/** Canonical URL for the current request (query string dropped except whitelisted keys). */
function seo_canonical(PDO $pdo): string
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    // Clean URLs: the .php extension is hidden (index.php is the site root).
    $script = $script === 'index.php' ? '' : preg_replace('/\.php$/', '', $script);
    $keep = ['slug', 'cat', 'tag', 'album', 'tab', 'pg', 'q'];
    $params = array_intersect_key($_GET, array_flip($keep));
    // Page 1 is the canonical of a paginated series.
    if (($params['pg'] ?? '') === '1') {
        unset($params['pg']);
    }
    $url = seo_site_url($pdo) . '/' . $script;
    if ($params) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/** Organization / School JSON-LD (emitted on every page). */
function seo_org_schema(PDO $pdo): array
{
    $type = get_setting($pdo, 'seo_org_type', 'School') ?: 'School';
    $siteUrl = seo_site_url($pdo);
    $logo = seo_abs_url($pdo, get_setting($pdo, 'logo_path'));

    $sameAs = array_values(array_filter([
        get_setting($pdo, 'facebook'),
        get_setting($pdo, 'instagram'),
        get_setting($pdo, 'tiktok'),
    ]));

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $type,
        '@id' => $siteUrl . '/#organization',
        'name' => get_setting($pdo, 'site_name'),
        'alternateName' => trim(get_setting($pdo, 'site_name') . ' — ' . get_setting($pdo, 'campus_name')),
        'url' => $siteUrl,
        'slogan' => get_setting($pdo, 'motto'),
        'description' => get_setting($pdo, 'seo_meta_description') ?: get_setting($pdo, 'tagline'),
    ];
    if ($logo) {
        $schema['logo'] = $logo;
        $schema['image'] = $logo;
    }
    $phone = get_setting($pdo, 'phone');
    if ($phone) {
        $schema['telephone'] = $phone;
    }
    $email = get_setting($pdo, 'email');
    if ($email) {
        $schema['email'] = $email;
    }
    $address = get_setting($pdo, 'address');
    if ($address) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $address,
            'addressLocality' => 'Haroonabad',
            'addressCountry' => 'PK',
        ];
    }
    $founded = get_setting($pdo, 'seo_founding_year');
    if ($founded) {
        $schema['foundingDate'] = $founded;
    }
    if ($sameAs) {
        $schema['sameAs'] = $sameAs;
    }
    return $schema;
}

/** WebSite schema with the site search action. */
function seo_website_schema(PDO $pdo): array
{
    $siteUrl = seo_site_url($pdo);
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => $siteUrl . '/#website',
        'url' => $siteUrl,
        'name' => get_setting($pdo, 'site_name'),
        'publisher' => ['@id' => $siteUrl . '/#organization'],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $siteUrl . '/search.php?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

/** BreadcrumbList JSON-LD from a breadcrumb trail. */
function seo_breadcrumb_schema(PDO $pdo, array $crumbs): array
{
    $items = [[
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => seo_site_url($pdo) . '/index.php',
    ]];
    $pos = 2;
    foreach ($crumbs as $crumb) {
        $entry = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $crumb['label'],
        ];
        if (!empty($crumb['url'])) {
            $entry['item'] = seo_abs_url($pdo, $crumb['url']);
        }
        $items[] = $entry;
    }
    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}

/** Article JSON-LD for blog/news posts. */
function seo_article_schema(PDO $pdo, string $title, string $description, string $image, array $article): array
{
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => mb_substr($title, 0, 110),
        'description' => $description,
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => seo_canonical($pdo)],
        'publisher' => ['@id' => seo_site_url($pdo) . '/#organization'],
    ];
    if ($image) {
        $schema['image'] = seo_abs_url($pdo, $image);
    }
    if (!empty($article['published'])) {
        $schema['datePublished'] = date('c', strtotime((string)$article['published']));
        $schema['dateModified'] = date('c', strtotime((string)($article['modified'] ?? $article['published'])));
    }
    if (!empty($article['author'])) {
        $schema['author'] = ['@type' => 'Person', 'name' => $article['author']];
    }
    if (!empty($article['section'])) {
        $schema['articleSection'] = $article['section'];
    }
    return $schema;
}

/** Render one <script type="application/ld+json"> block. */
function seo_jsonld(array $schema): string
{
    return '<script type="application/ld+json">'
        . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>';
}

/** Visible breadcrumb trail (used inside page banners). */
function seo_breadcrumb_html(array $crumbs): string
{
    $html = '<a href="index.php">Home</a>';
    foreach ($crumbs as $crumb) {
        $html .= ' / ';
        $html .= !empty($crumb['url'])
            ? '<a href="' . e($crumb['url']) . '">' . e($crumb['label']) . '</a>'
            : '<span>' . e($crumb['label']) . '</span>';
    }
    return $html;
}
