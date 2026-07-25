<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

start_html_minify($pdo);

$site_name    = get_setting($pdo, 'site_name', 'The Alpine School');
$campus_name  = get_setting($pdo, 'campus_name', 'Haroonabad Campus');
$logo_path    = get_setting($pdo, 'logo_path', 'assets/images/logo.jpg');
$phone        = get_setting($pdo, 'phone');
$whatsapp     = get_setting($pdo, 'whatsapp');
$email        = get_setting($pdo, 'email');
$tagline      = get_setting($pdo, 'tagline');
$facebook     = get_setting($pdo, 'facebook');
$instagram    = get_setting($pdo, 'instagram');
$tiktok       = get_setting($pdo, 'tiktok');
$primary      = get_setting($pdo, 'primary_color', '#2E1B6B');
$primary_dark = get_setting($pdo, 'primary_dark', '#161233');
$secondary    = get_setting($pdo, 'secondary_color', '#8A8D93');
$accent       = get_setting($pdo, 'accent_color', '#7ED321');
$accent2      = get_setting($pdo, 'accent2_color', '#D4AF37');

// ---- Theme Builder values ----
$themeFonts = [
    'Poppins' => "'Poppins', sans-serif", 'Inter' => "'Inter', sans-serif", 'Roboto' => "'Roboto', sans-serif",
    'Open Sans' => "'Open Sans', sans-serif", 'Lato' => "'Lato', sans-serif", 'Montserrat' => "'Montserrat', sans-serif",
    'Nunito' => "'Nunito', sans-serif", 'Raleway' => "'Raleway', sans-serif",
    'Playfair Display' => "'Playfair Display', serif", 'Merriweather' => "'Merriweather', serif",
];
$fontHeading = get_setting($pdo, 'theme_font_heading', 'Poppins');
$fontBody = get_setting($pdo, 'theme_font_body', 'Inter');
$fontHeading = isset($themeFonts[$fontHeading]) ? $fontHeading : 'Poppins';
$fontBody = isset($themeFonts[$fontBody]) ? $fontBody : 'Inter';
$gfQuery = implode('&', array_map(
    fn($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@400;500;600;700;800',
    array_unique([$fontHeading, $fontBody])
));

$sectionPads = ['compact' => '56px', 'regular' => '80px', 'spacious' => '110px'];
$sectionPad = $sectionPads[get_setting($pdo, 'theme_section_spacing', 'regular')] ?? '80px';

$btnRadii = ['pill' => '999px', 'rounded' => '10px', 'square' => '3px'];
$btnRadius = $btnRadii[get_setting($pdo, 'theme_btn_style', 'pill')] ?? '999px';

$themeRadius = max(0, min(30, (int)get_setting($pdo, 'theme_radius', '14'))) . 'px';

$shadows = [
    'none' => ['none', 'none'],
    'soft' => ['0 10px 30px rgba(22, 18, 51, 0.08)', '0 20px 50px rgba(22, 18, 51, 0.16)'],
    'strong' => ['0 14px 40px rgba(22, 18, 51, 0.18)', '0 26px 70px rgba(22, 18, 51, 0.3)'],
];
[$shadow, $shadowLg] = $shadows[get_setting($pdo, 'theme_shadow', 'soft')] ?? $shadows['soft'];

$bodyClasses = [];
$headerStyle = get_setting($pdo, 'theme_header_style', 'light');
if (in_array($headerStyle, ['dark', 'primary'], true)) {
    $bodyClasses[] = 'header-' . $headerStyle;
}
$footerStyle = get_setting($pdo, 'theme_footer_style', 'dark');
if (in_array($footerStyle, ['primary', 'light'], true)) {
    $bodyClasses[] = 'footer-' . $footerStyle;
}
if (get_setting($pdo, 'theme_animations', '1') !== '1') {
    $bodyClasses[] = 'anim-off';
}
if (get_setting($pdo, 'theme_btn_uppercase', '0') === '1') {
    $bodyClasses[] = 'btn-uppercase';
}

$favicon_path = get_setting($pdo, 'favicon_path');
$seo_keywords = get_setting($pdo, 'seo_meta_keywords');
$seo_desc_global = get_setting($pdo, 'seo_meta_description');
$seo_robots = get_setting($pdo, 'seo_robots', 'index, follow');
$seo_ga = get_setting($pdo, 'seo_google_analytics');

$activeNotice = $pdo->query(
    "SELECT * FROM notices WHERE is_active = 1
     AND (starts_at IS NULL OR starts_at <= CURDATE())
     AND (ends_at IS NULL OR ends_at >= CURDATE())
     ORDER BY created_at DESC LIMIT 1"
)->fetch();

$current_page = basename($_SERVER['SCRIPT_NAME']);
track_page_view($pdo);

function nav_active(string $page, string $current): string
{
    return $page === $current ? ' class="active"' : '';
}

// ---- SEO: title, description, canonical, OG/Twitter, JSON-LD ----
require_once __DIR__ . '/seo.php';

$seoTitle = ($pageTitle ?? '') !== ''
    ? $pageTitle . ' | ' . $site_name . ' — ' . $campus_name
    : $site_name . ' — ' . $campus_name;
$seoDescription = trim((string)($pageDescription ?? '')) ?: ($seo_desc_global ?: $site_name . ' — ' . $campus_name . '. ' . get_setting($pdo, 'tagline'));
$seoDescription = mb_substr(trim(strip_tags($seoDescription)), 0, 300);
$canonicalUrl = seo_canonical($pdo);
$ogImagePath = ($seoImage ?? '') ?: (get_setting($pdo, 'seo_og_image') ?: $logo_path);
$ogImage = seo_abs_url($pdo, $ogImagePath);
$ogType = ($seoType ?? 'website') === 'article' ? 'article' : 'website';
$twitterHandle = trim(get_setting($pdo, 'seo_twitter_handle'));
$breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= e($seoTitle) ?></title>
<meta name="description" content="<?= e($seoDescription) ?>">
<?php if ($seo_keywords): ?><meta name="keywords" content="<?= e($seo_keywords) ?>"><?php endif; ?>
<meta name="robots" content="<?= e($seo_robots) ?>">
<link rel="canonical" href="<?= e($canonicalUrl) ?>">

<!-- Open Graph -->
<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:site_name" content="<?= e($site_name) ?>">
<meta property="og:title" content="<?= e($seoTitle) ?>">
<meta property="og:description" content="<?= e($seoDescription) ?>">
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta property="og:locale" content="en_US">
<?php if ($ogType === 'article' && !empty($seoArticle)): ?>
  <?php if (!empty($seoArticle['published'])): ?><meta property="article:published_time" content="<?= e(date('c', strtotime((string)$seoArticle['published']))) ?>"><?php endif; ?>
  <?php if (!empty($seoArticle['author'])): ?><meta property="article:author" content="<?= e($seoArticle['author']) ?>"><?php endif; ?>
  <?php if (!empty($seoArticle['section'])): ?><meta property="article:section" content="<?= e($seoArticle['section']) ?>"><?php endif; ?>
<?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seoTitle) ?>">
<meta name="twitter:description" content="<?= e($seoDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php if ($twitterHandle): ?>
<meta name="twitter:site" content="<?= e($twitterHandle) ?>">
<meta name="twitter:creator" content="<?= e($twitterHandle) ?>">
<?php endif; ?>

<!-- Schema.org / JSON-LD -->
<?= seo_jsonld(seo_org_schema($pdo)) ?>
<?= seo_jsonld(seo_website_schema($pdo)) ?>
<?php if ($breadcrumbs): ?>
<?= seo_jsonld(seo_breadcrumb_schema($pdo, $breadcrumbs)) ?>
<?php endif; ?>
<?php if ($ogType === 'article' && !empty($seoArticle)): ?>
<?= seo_jsonld(seo_article_schema($pdo, (string)($pageTitle ?? ''), $seoDescription, $ogImagePath, $seoArticle)) ?>
<?php endif; ?>

<link rel="icon" href="<?= BASE_URL ?>/<?= e($favicon_path ?: $logo_path) ?>">
<?php if ($seo_ga): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($seo_ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($seo_ga) ?>');</script>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php $gfUrl = 'https://fonts.googleapis.com/css2?' . e($gfQuery) . '&display=swap'; ?>
<link rel="preload" as="style" href="<?= $gfUrl ?>">
<link rel="stylesheet" href="<?= $gfUrl ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?= $gfUrl ?>"></noscript>
<link rel="stylesheet" href="<?= e(asset_url($pdo, 'assets/css/style.css')) ?>">
<?php
// Preload the LCP image (first hero slide) so it starts downloading immediately.
$lcpImage = '';
if (($current_page ?? '') === 'index.php') {
    $firstSlide = cache_remember('hero_first', function () use ($pdo) {
        return $pdo->query('SELECT image_path FROM hero_slides WHERE is_active = 1 ORDER BY sort_order LIMIT 1')->fetch() ?: [];
    });
    $lcpImage = (string)($firstSlide['image_path'] ?? '');
}
?>
<?php if ($lcpImage): ?>
<link rel="preload" as="image" href="<?= BASE_URL ?>/<?= e($lcpImage) ?>" fetchpriority="high">
<?php endif; ?>
<style>
:root{
  --primary: <?= e($primary) ?>;
  --primary-dark: <?= e($primary_dark) ?>;
  --secondary: <?= e($secondary) ?>;
  --accent: <?= e($accent) ?>;
  --accent2: <?= e($accent2) ?>;
  --font-heading: <?= $themeFonts[$fontHeading] ?>;
  --font-body: <?= $themeFonts[$fontBody] ?>;
  --section-pad: <?= $sectionPad ?>;
  --btn-radius: <?= $btnRadius ?>;
  --radius: <?= $themeRadius ?>;
  --shadow: <?= $shadow ?>;
  --shadow-lg: <?= $shadowLg ?>;
}
</style>
</head>
<body<?= $bodyClasses ? ' class="' . e(implode(' ', $bodyClasses)) . '"' : '' ?>>

<a href="#main-content" class="skip-link">Skip to main content</a>

<?php if ($activeNotice): ?>
<div class="notice-bar" role="region" aria-label="Site announcement">
  <div class="container">
    <span aria-hidden="true">📢</span> <strong><?= e($activeNotice['title']) ?></strong><?php if ($activeNotice['body']): ?> — <?= e(mb_substr($activeNotice['body'], 0, 140)) ?><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php
// Header navigation — managed in Admin → Menu Builder ("main" menu, unlimited nesting).
$menuChildren = cached_menu($pdo, 'main');

/** Inline SVG icon for header/drawer UI (sized via CSS, colored via currentColor). */
function edu_icon(string $name): string
{
    $stroke = [
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
        'pencil' => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>',
        'map-pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    ];
    $fill = [
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'tiktok' => '<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>',
        'whatsapp' => '<path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.23-.64.08-.3-.15-1.26-.47-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.44-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.06 2.87 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.11.57-.08 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.28-.2-.57-.35m-5.42 7.4h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88a9.82 9.82 0 0 1 9.88 9.89c0 5.45-4.43 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.44h.01c6.55 0 11.89-5.33 11.89-11.89 0-3.18-1.24-6.16-3.48-8.41"/>',
    ];
    if (isset($stroke[$name])) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $stroke[$name] . '</svg>';
    }
    return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . ($fill[$name] ?? '') . '</svg>';
}

/** One nav item: label with optional icon + new-tab attrs (used inside dropdowns/drawer). */
function nav_item_html(array $item, string $current): string
{
    $isCurrent = basename($item['url']) === $current;
    $target = $item['new_tab'] ? ' target="_blank" rel="noopener"' : '';
    $newTabNote = $item['new_tab'] ? '<span class="sr-only"> (opens in a new tab)</span>' : '';
    $current_attr = $isCurrent ? ' aria-current="page"' : '';
    return '<a href="' . e($item['url']) . '"' . nav_active(basename($item['url']), $current) . $current_attr . $target . '>'
        . e($item['label']) . $newTabNote . '</a>';
}

/** True if this item or any descendant matches the current page. */
function nav_branch_active(array $item, array $children, string $current): bool
{
    if (basename($item['url']) === $current) {
        return true;
    }
    foreach ($children[(int)$item['id']] ?? [] as $kid) {
        if (nav_branch_active($kid, $children, $current)) {
            return true;
        }
    }
    return false;
}

/** Recursive dropdown menu body (levels ≥ 2 become fly-out submenus). */
function nav_render_submenu(array $items, array $children, string $current, string $labelledBy = ''): void
{
    echo '<div class="nav-drop-menu"' . ($labelledBy ? ' aria-labelledby="' . e($labelledBy) . '"' : '') . '>';
    foreach ($items as $item) {
        $kids = $children[(int)$item['id']] ?? [];
        if (empty($kids)) {
            echo nav_item_html($item, $current);
        } else {
            echo '<div class="nav-sub">';
            echo nav_item_html($item, $current);
            echo '<span class="sub-caret" aria-hidden="true">›</span>';
            nav_render_submenu($kids, $children, $current);
            echo '</div>';
        }
    }
    echo '</div>';
}

$applyLink = BASE_URL . '/admission-form.php';
?>
<header class="edu-header" id="siteHeader">
  <!-- Tier 1: light topbar (brand + contact + social + CTA) -->
  <div class="edu-topbar">
    <div class="container edu-topbar-inner">
      <a href="<?= BASE_URL ?>/index.php" class="edu-brand" <?= $current_page === 'index.php' ? 'aria-current="page"' : '' ?>>
        <img src="<?= BASE_URL ?>/<?= e($logo_path) ?>" alt="" width="50" height="50">
        <span class="edu-brand-text">
          <strong><?= e($site_name) ?></strong>
          <small><?= e($tagline ?: $campus_name) ?></small>
        </span>
      </a>

      <div class="edu-topbar-info-group">
        <?php if ($phone): ?>
        <a href="tel:<?= e($phone) ?>" class="edu-topbar-info">
          <span class="edu-topbar-info-icon"><?= edu_icon('phone') ?></span>
          <span><small>Call Us</small><strong><?= e($phone) ?></strong></span>
        </a>
        <?php endif; ?>
        <?php if ($email): ?>
        <a href="mailto:<?= e($email) ?>" class="edu-topbar-info">
          <span class="edu-topbar-info-icon"><?= edu_icon('mail') ?></span>
          <span><small>Email Us</small><strong><?= e($email) ?></strong></span>
        </a>
        <?php endif; ?>
      </div>

      <div class="edu-topbar-actions">
        <nav class="edu-topbar-social" aria-label="Social media">
          <?php if ($facebook): ?><a href="<?= e($facebook) ?>" target="_blank" rel="noopener" class="edu-soc-fb" aria-label="Facebook"><?= edu_icon('facebook') ?></a><?php endif; ?>
          <?php if ($instagram): ?><a href="<?= e($instagram) ?>" target="_blank" rel="noopener" class="edu-soc-ig" aria-label="Instagram"><?= edu_icon('instagram') ?></a><?php endif; ?>
          <?php if ($tiktok): ?><a href="<?= e($tiktok) ?>" target="_blank" rel="noopener" class="edu-soc-tt" aria-label="TikTok"><?= edu_icon('tiktok') ?></a><?php endif; ?>
          <?php if ($whatsapp): ?><a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="edu-soc-wa" aria-label="WhatsApp"><?= edu_icon('whatsapp') ?></a><?php endif; ?>
        </nav>
        <a href="<?= $applyLink ?>" class="edu-apply-btn"><?= edu_icon('pencil') ?> Apply Now</a>
        <button class="edu-nav-toggle" id="navToggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileDrawer">
          <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
        </button>
      </div>
    </div>
  </div>

  <!-- Tier 2: dark icon navbar (desktop) -->
  <nav class="edu-navbar" aria-label="Main navigation">
    <div class="container edu-navbar-inner">
      <?php foreach ($menuChildren[0] ?? [] as $navItem): $navKids = $menuChildren[(int)$navItem['id']] ?? []; ?>
        <?php if (empty($navKids)): ?>
          <a href="<?= e($navItem['url']) ?>" class="edu-nav-link<?= basename($navItem['url']) === $current_page ? ' active' : '' ?>"<?= $navItem['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?><?= basename($navItem['url']) === $current_page ? ' aria-current="page"' : '' ?>>
            <span class="edu-nav-label"><?= e($navItem['label']) ?></span>
          </a>
        <?php else: ?>
          <?php $dropId = 'navdrop-' . (int)$navItem['id']; ?>
          <div class="edu-nav-dropdown">
            <button type="button" id="<?= $dropId ?>"
                    class="edu-nav-link edu-nav-drop-btn<?= nav_branch_active($navItem, $menuChildren, $current_page) ? ' active' : '' ?>"
                    aria-expanded="false" aria-haspopup="true">
              <span class="edu-nav-label"><?= e($navItem['label']) ?> <span class="caret" aria-hidden="true">▾</span></span>
            </button>
            <?php nav_render_submenu($navKids, $menuChildren, $current_page, $dropId); ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </nav>
</header>

<!-- Mobile slide-in drawer -->
<div class="edu-drawer-overlay" id="drawerOverlay" hidden></div>
<aside class="edu-drawer" id="mobileDrawer" aria-label="Mobile navigation" aria-hidden="true">
  <div class="edu-drawer-head">
    <strong><?= e($site_name) ?></strong>
    <button type="button" class="edu-drawer-close" id="drawerClose" aria-label="Close navigation menu"><?= edu_icon('close') ?></button>
  </div>
  <nav class="edu-drawer-nav" aria-label="Mobile main navigation">
    <?php foreach ($menuChildren[0] ?? [] as $navItem): $navKids = $menuChildren[(int)$navItem['id']] ?? []; ?>
      <?php if (empty($navKids)): ?>
        <?= nav_item_html($navItem, $current_page) ?>
      <?php else: ?>
        <?php $dId = 'drawer-' . (int)$navItem['id']; ?>
        <div class="edu-drawer-group">
          <button type="button" class="edu-drawer-toggle<?= nav_branch_active($navItem, $menuChildren, $current_page) ? ' active' : '' ?>" aria-expanded="false" aria-controls="<?= $dId ?>">
            <?= e($navItem['label']) ?>
            <span class="caret" aria-hidden="true">▾</span>
          </button>
          <div class="edu-drawer-sub" id="<?= $dId ?>">
            <?php nav_render_submenu($navKids, $menuChildren, $current_page); ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/search.php"><?= edu_icon('search') ?> Search</a>
    <a href="<?= $applyLink ?>" class="edu-drawer-apply">Apply Now</a>
  </nav>
  <div class="edu-drawer-contact">
    <?php if ($phone): ?><a href="tel:<?= e($phone) ?>"><?= edu_icon('phone') ?> <?= e($phone) ?></a><?php endif; ?>
    <?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= edu_icon('mail') ?> <?= e($email) ?></a><?php endif; ?>
    <div class="edu-drawer-social">
      <?php if ($facebook): ?><a href="<?= e($facebook) ?>" target="_blank" rel="noopener" class="edu-soc-fb" aria-label="Facebook"><?= edu_icon('facebook') ?></a><?php endif; ?>
      <?php if ($instagram): ?><a href="<?= e($instagram) ?>" target="_blank" rel="noopener" class="edu-soc-ig" aria-label="Instagram"><?= edu_icon('instagram') ?></a><?php endif; ?>
      <?php if ($tiktok): ?><a href="<?= e($tiktok) ?>" target="_blank" rel="noopener" class="edu-soc-tt" aria-label="TikTok"><?= edu_icon('tiktok') ?></a><?php endif; ?>
      <?php if ($whatsapp): ?><a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="edu-soc-wa" aria-label="WhatsApp"><?= edu_icon('whatsapp') ?></a><?php endif; ?>
    </div>
  </div>
</aside>

<main id="main-content" tabindex="-1">
