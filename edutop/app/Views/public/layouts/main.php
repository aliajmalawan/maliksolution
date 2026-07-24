<?php
use App\Core\Nonce;
use App\Models\Setting;

$company = Setting::group('company');
$siteName = $company['site_name'] ?? 'EduTop';
$seo = $seo ?? [];
$theme = Setting::group('theme');
$integrations = Setting::group('integrations');
$nonce = Nonce::value();

$pageTitle = trim((string) ($seo['seo_title'] ?? $page['title'] ?? $siteName));
$fullTitle = $pageTitle === $siteName ? $siteName : ($pageTitle . ' | ' . $siteName);

$metaDescription = $seo['meta_description'] ?? '';
$ogDescription = $seo['og_description'] ?? $metaDescription;

$validSchema = null;
if (!empty($seo['schema_markup']) && json_decode($seo['schema_markup']) !== null) {
    $validSchema = str_replace('</script>', '<\/script>', $seo['schema_markup']);
}

$hexToRgb = static function (string $hex): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return '0,0,0';
    }
    return implode(',', [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))]);
};

// Server-computed darker (or, with a negative amount, lighter) shade for
// hover states and derived tones — avoids relying on the
// still-inconsistently-supported CSS color-mix() function.
$darken = static function (string $hex, float $amount = 0.15): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return '#000000';
    }
    $rgb = array_map(fn($c) => min(255, max(0, (int) round(hexdec($c) * (1 - $amount)))), str_split($hex, 2));
    return '#' . implode('', array_map(fn($c) => str_pad(dechex($c), 2, '0', STR_PAD_LEFT), $rgb));
};

$colors = [
    'primary' => $theme['primary_color'] ?? '#2D1B7B',
    'secondary' => $theme['secondary_color'] ?? '#FFC107',
    'background' => $theme['background_color'] ?? '#FAFBFE',
    'card' => $theme['card_color'] ?? '#FFFFFF',
    'heading' => $theme['heading_color'] ?? '#1F2937',
    'text' => $theme['text_color'] ?? '#6B7280',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle) ?></title>
    <?php if ($metaDescription !== ''): ?><meta name="description" content="<?= e($metaDescription) ?>"><?php endif; ?>
    <?php if (!empty($seo['meta_keywords'])): ?><meta name="keywords" content="<?= e($seo['meta_keywords']) ?>"><?php endif; ?>
    <meta name="robots" content="<?= e($seo['robots'] ?? 'index,follow') ?>">
    <?php if (!empty($seo['canonical_url'])): ?><link rel="canonical" href="<?= e($seo['canonical_url']) ?>"><?php endif; ?>
    <?php $faviconUrl = !empty($company['favicon']) ? media_url($company['favicon']) : null; ?>
    <?php if ($faviconUrl): ?><link rel="icon" href="<?= e($faviconUrl) ?>"><?php endif; ?>

    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($seo['og_title'] ?? $pageTitle) ?>">
    <?php if ($ogDescription !== ''): ?><meta property="og:description" content="<?= e($ogDescription) ?>"><?php endif; ?>
    <?php if (!empty($seo['og_image'])): ?><meta property="og:image" content="<?= e(url($seo['og_image'])) ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?= e($seo['twitter_card'] ?? 'summary_large_image') ?>">

    <?php if ($validSchema): ?>
        <script type="application/ld+json"><?= $validSchema ?></script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('public/css/custom.css') ?>?v=<?= is_file($cssPath = dirname(__DIR__, 4) . '/public/assets/public/css/custom.css') ? filemtime($cssPath) : '1' ?>" rel="stylesheet">

    <style>
        :root {
            --edu-primary: <?= e($colors['primary']) ?>;
            --edu-primary-rgb: <?= $hexToRgb($colors['primary']) ?>;
            --edu-secondary: <?= e($colors['secondary']) ?>;
            --edu-secondary-rgb: <?= $hexToRgb($colors['secondary']) ?>;
            --edu-bg: <?= e($colors['background']) ?>;
            --edu-card: <?= e($colors['card']) ?>;
            --edu-heading: <?= e($colors['heading']) ?>;
            --edu-text: <?= e($colors['text']) ?>;
            --edu-primary-dark: <?= e($darken($colors['primary'], 0.2)) ?>;
            --edu-secondary-dark: <?= e($darken($colors['secondary'], 0.05)) ?>;
            --edu-accent: <?= e($darken($colors['primary'], -0.35)) ?>;
            --edu-surface: #F5F7FB;
            --edu-footer-bg: <?= e($darken($colors['primary'], 0.35)) ?>;
            --edu-border-c: #E5E7EB;
            --edu-text-light: #9CA3AF;
            --edu-success: #16A34A;
            --edu-warning: #F59E0B;
            --edu-danger: #DC2626;
            --edu-info: #2563EB;
            --bs-primary: var(--edu-primary);
            --bs-secondary: var(--edu-secondary);
            --bs-body-bg: var(--edu-bg);
            --bs-body-color: var(--edu-text);
        }
        body { background-color: var(--edu-bg); color: var(--edu-text); }
        h1, h2, h3, h4, h5, h6 { color: var(--edu-heading); }
        .card { background-color: var(--edu-card); }
        a { color: var(--edu-primary); }
        a:hover { color: var(--edu-secondary); }
        .btn-primary { background-color: var(--edu-primary) !important; border-color: var(--edu-primary) !important; }
        .btn-primary:hover { background-color: var(--edu-primary-dark) !important; border-color: var(--edu-primary-dark) !important; }
        .btn-outline-primary { color: var(--edu-primary) !important; border-color: var(--edu-primary) !important; }
        .btn-outline-primary:hover { background-color: var(--edu-primary) !important; color: #fff !important; }
        .btn-secondary { background-color: var(--edu-secondary) !important; border-color: var(--edu-secondary) !important; color: var(--edu-primary) !important; }
        .btn-secondary:hover { background-color: var(--edu-secondary-dark) !important; border-color: var(--edu-secondary-dark) !important; color: var(--edu-primary) !important; }
        .text-primary { color: var(--edu-primary) !important; }
        .bg-primary { background-color: var(--edu-primary) !important; }
        .page-item.active .page-link { background-color: var(--edu-primary); border-color: var(--edu-primary); }
    </style>

    <?php if (!empty($integrations['google_tag_manager_id'])): ?>
        <script nonce="<?= e($nonce) ?>">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($integrations['google_tag_manager_id']) ?>');</script>
    <?php endif; ?>

    <?php if (!empty($integrations['google_analytics_id'])): ?>
        <script src="https://www.googletagmanager.com/gtag/js?id=<?= e($integrations['google_analytics_id']) ?>" nonce="<?= e($nonce) ?>"></script>
        <script nonce="<?= e($nonce) ?>">window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '<?= e($integrations['google_analytics_id']) ?>');</script>
    <?php endif; ?>

    <?php if (!empty($integrations['facebook_pixel_id'])): ?>
        <script nonce="<?= e($nonce) ?>">!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', '<?= e($integrations['facebook_pixel_id']) ?>');fbq('track', 'PageView');</script>
    <?php endif; ?>

    <?php if (\App\Core\Recaptcha::isConfigured()): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body>
<?php if (!empty($integrations['google_tag_manager_id'])): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($integrations['google_tag_manager_id']) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>

<?php include __DIR__ . '/../partials/header.php'; ?>

<?php if ($success = flash('_flash_success')): ?>
    <div class="container mt-3"><div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div></div>
<?php endif; ?>
<?php if ($error = flash('_flash_error')): ?>
    <div class="container mt-3"><div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div></div>
<?php endif; ?>

<main><?= $content ?></main>

<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/demo_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $siteJsPath = dirname(__DIR__, 4) . '/public/assets/public/js/site.js'; ?>
<script src="<?= asset('public/js/site.js') ?>?v=<?= is_file($siteJsPath) ? filemtime($siteJsPath) : '1' ?>"></script>
</body>
</html>
