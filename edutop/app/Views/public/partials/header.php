<?php
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;

$company = Setting::group('company');
$contact = Setting::group('contact');
$social = Setting::group('social');
$primaryMenu = Menu::findBySlug('primary');
$navItems = $primaryMenu ? MenuItem::tree((int) $primaryMenu['id']) : [];

// Internal menu URLs are stored as bare paths (e.g. "/about") and need the
// app's base-path prefix; external URLs (absolute http(s)?/protocol-relative) don't.
$menuHref = static fn(string $url): string => preg_match('#^(https?:)?//#i', $url) ? $url : url($url);

$currentPath = rtrim(current_path(), '/') ?: '/';
$isActive = static fn(string $url): bool => !preg_match('#^(https?:)?//#i', $url)
    && (rtrim($url, '/') ?: '/') === $currentPath;

// Menu labels don't carry an icon in the database, so the icon-over-label nav
// style (main navbar) picks a Bootstrap Icon by matching common label words —
// first match wins, falling back to a plain dot for anything unrecognized.
$navIconMap = [
    'home' => 'bi-house-door-fill',
    'about' => 'bi-info-circle-fill',
    'admission' => 'bi-file-earmark-text-fill',
    'academic' => 'bi-mortarboard-fill',
    'leadership' => 'bi-award-fill',
    'student' => 'bi-people-fill',
    'research' => 'bi-flask-fill',
    'gallery' => 'bi-images',
    'media' => 'bi-camera-reels-fill',
    'news' => 'bi-newspaper',
    'blog' => 'bi-newspaper',
    'career' => 'bi-briefcase-fill',
    'contact' => 'bi-headset',
];
$iconFor = static function (string $label) use ($navIconMap): string {
    $needle = strtolower($label);
    foreach ($navIconMap as $key => $icon) {
        if (str_contains($needle, $key)) {
            return $icon;
        }
    }
    return 'bi-dot';
};

$topSocial = array_filter([
    'facebook' => $social['facebook'] ?? '',
    'twitter-x' => $social['twitter'] ?? '',
    'linkedin' => $social['linkedin'] ?? '',
    'instagram' => $social['instagram'] ?? '',
    'youtube' => $social['youtube'] ?? '',
]);

// The header/topbar icon row is independently toggleable per network (the
// footer, rendered separately, always shows every link filled in above).
$topSocialHeader = array_filter($topSocial, static function (string $key) use ($social): bool {
    $settingKey = ($key === 'twitter-x' ? 'twitter' : $key) . '_header';
    return ($social[$settingKey] ?? '1') === '1';
}, ARRAY_FILTER_USE_KEY);
// Brand-colored circular social icons (matches each network's own color).
$socialColors = [
    'facebook' => '#1877F2',
    'twitter-x' => '#000000',
    'linkedin' => '#0A66C2',
    'instagram' => '#E4405F',
    'youtube' => '#FF0000',
];

$siteName = $company['site_name'] ?? 'EduTop';
$brandHtml = '<span style="color: #2D1B7B;">' . e($siteName) . '</span>';
$logoUrl = !empty($company['logo']) ? media_url($company['logo']) : null;
?>
<header class="edu-header sticky-top">
    <div class="edu-topbar-light">
        <div class="container d-flex align-items-center justify-content-between gap-3 py-2">
            <a class="edu-topbar-brand d-flex align-items-center gap-2 text-decoration-none" href="<?= url('/') ?>">
                <?php if ($logoUrl): ?>
                    <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="edu-topbar-logo">
                <?php endif; ?>
                <span>
                    <span class="edu-brand-name d-block"><?= $brandHtml ?></span>
                    <?php if (!empty($company['tagline'])): ?>
                        <span class="edu-topbar-tagline d-none d-sm-block"><?= e($company['tagline']) ?></span>
                    <?php endif; ?>
                </span>
            </a>

            <div class="d-none d-lg-flex align-items-center gap-2">
                <?php if (!empty($contact['phone'])): ?>
                    <a class="edu-topbar-info" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $contact['phone'])) ?>">
                        <span class="edu-topbar-info-icon"><i class="bi bi-telephone-fill"></i></span>
                        <span><small>Call Us</small><strong><?= e($contact['phone']) ?></strong></span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($contact['email'])): ?>
                    <a class="edu-topbar-info" href="mailto:<?= e($contact['email']) ?>">
                        <span class="edu-topbar-info-icon"><i class="bi bi-envelope-fill"></i></span>
                        <span><small>Email Us</small><strong><?= e($contact['email']) ?></strong></span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="d-none d-lg-flex align-items-center gap-2">
                    <?php foreach ($topSocialHeader as $icon => $link): ?>
                        <a href="<?= e(safe_url($link)) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($icon) ?>" class="edu-topbar-social" style="background: <?= e($socialColors[$icon] ?? '#333') ?>;"><i class="bi bi-<?= e($icon) ?>"></i></a>
                    <?php endforeach; ?>
                    <?php if (!empty($contact['whatsapp'])): ?>
                        <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $contact['whatsapp'])) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="edu-topbar-social" style="background: #25D366;"><i class="bi bi-whatsapp"></i></a>
                    <?php endif; ?>
                </div>
                <a href="<?= url('/admissions-form') ?>" class="d-none d-md-inline-flex edu-topbar-apply"><i class="bi bi-pencil-square me-1"></i>Apply Now</a>
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNavDrawer" aria-controls="mainNavDrawer" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="edu-navbar-icons d-none d-lg-block">
        <nav class="navbar navbar-expand-lg container justify-content-center py-0">
            <ul class="navbar-nav align-items-lg-center gap-lg-3">
                <?php foreach ($navItems as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link edu-nav-icon-link dropdown-toggle" href="<?= e($menuHref($item['url'])) ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi <?= e($iconFor($item['label'])) ?>"></i>
                                <span><?= e($item['label']) ?></span>
                            </a>
                            <ul class="dropdown-menu edu-dropdown">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><a class="dropdown-item" href="<?= e($menuHref($child['url'])) ?>" target="<?= e($child['target']) ?>"><?= e($child['label']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link edu-nav-icon-link <?= $isActive($item['url']) ? 'active' : '' ?>" href="<?= e($menuHref($item['url'])) ?>" target="<?= e($item['target']) ?>">
                                <i class="bi <?= e($iconFor($item['label'])) ?>"></i>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>

<div class="offcanvas offcanvas-end edu-nav-drawer" tabindex="-1" id="mainNavDrawer" aria-labelledby="mainNavDrawerLabel">
    <div class="offcanvas-header edu-nav-drawer-head">
        <span class="edu-brand-name fs-5" id="mainNavDrawerLabel"><?= $brandHtml ?></span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
            <?php foreach ($navItems as $item): ?>
                <?php if (!empty($item['children'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link edu-nav-link dropdown-toggle" href="<?= e($menuHref($item['url'])) ?>" role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><?= e($item['label']) ?></a>
                        <ul class="dropdown-menu edu-dropdown">
                            <?php foreach ($item['children'] as $child): ?>
                                <li><a class="dropdown-item" href="<?= e($menuHref($child['url'])) ?>" target="<?= e($child['target']) ?>"><?= e($child['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link edu-nav-link <?= $isActive($item['url']) ? 'active' : '' ?>" href="<?= e($menuHref($item['url'])) ?>" target="<?= e($item['target']) ?>"><?= e($item['label']) ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                <a href="<?= url('/admissions-form') ?>" class="btn btn-secondary px-3 py-2 fw-semibold w-100 w-lg-auto">
                    <i class="bi bi-mortarboard-fill me-1"></i>Apply Now
                </a>
            </li>
        </ul>

        <!-- Mobile drawer footer: contact + socials -->
        <div class="edu-drawer-extra d-lg-none mt-4 pt-4">
            <?php if (!empty($contact['phone'])): ?>
                <a class="edu-drawer-contact" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $contact['phone'])) ?>"><i class="bi bi-telephone-fill me-2"></i><?= e($contact['phone']) ?></a>
            <?php endif; ?>
            <?php if (!empty($contact['email'])): ?>
                <a class="edu-drawer-contact" href="mailto:<?= e($contact['email']) ?>"><i class="bi bi-envelope-fill me-2"></i><?= e($contact['email']) ?></a>
            <?php endif; ?>
            <?php if (!empty($contact['address'])): ?>
                <div class="edu-drawer-contact"><i class="bi bi-geo-alt-fill me-2"></i><?= e($contact['address']) ?></div>
            <?php endif; ?>
            <?php if (!empty($topSocial)): ?>
                <div class="d-flex gap-2 mt-3">
                    <?php foreach ($topSocial as $icon => $link): ?>
                        <a href="<?= e(safe_url($link)) ?>" class="edu-social-btn edu-social-btn-dark" target="_blank" rel="noopener noreferrer" aria-label="<?= e($icon) ?>"><i class="bi bi-<?= e($icon) ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
