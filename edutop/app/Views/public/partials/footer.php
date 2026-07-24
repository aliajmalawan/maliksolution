<?php
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;

$company = Setting::group('company');
$contact = Setting::group('contact');
$social = Setting::group('social');
$footerSettings = Setting::group('footer');

$footerMenu = Menu::findBySlug('footer');
$footerItems = $footerMenu ? MenuItem::tree((int) $footerMenu['id']) : [];
$menuHref = static fn(string $url): string => preg_match('#^(https?:)?//#i', $url) ? $url : url($url);

$socialIcons = array_filter([
    'facebook' => $social['facebook'] ?? '',
    'twitter-x' => $social['twitter'] ?? '',
    'linkedin' => $social['linkedin'] ?? '',
    'instagram' => $social['instagram'] ?? '',
    'youtube' => $social['youtube'] ?? '',
]);
$footerLogoUrl = !empty($company['logo']) ? media_url($company['logo']) : null;
?>
<footer class="edu-footer">
    <div class="container pt-5 pb-4">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?php if ($footerLogoUrl): ?>
                        <img src="<?= e($footerLogoUrl) ?>" alt="<?= e($company['site_name'] ?? 'EduTop') ?>" style="max-height:44px;width:auto;">
                    <?php endif; ?>
                    <?php
                    $footerName = $company['site_name'] ?? 'EduTop';
                    // Same two-tone brand as the header; "Edu" stays white for
                    // contrast on the dark footer, "Top" in the gold accent.
                    $footerBrand = preg_match('/^(edu)(top)(.*)$/i', $footerName, $fm)
                        ? '<span class="text-white">' . e($fm[1]) . '</span><span style="color: var(--edu-secondary);">' . e($fm[2]) . '</span>' . ($fm[3] !== '' ? '<span class="text-white">' . e($fm[3]) . '</span>' : '')
                        : '<span class="text-white">' . e($footerName) . '</span>';
                    ?>
                    <span class="fs-5 fw-bold"><?= $footerBrand ?></span>
                </div>
                <p class="edu-footer-muted small mb-4"><?= e($company['tagline'] ?? '') ?></p>
                <?php if (!empty($socialIcons)): ?>
                    <div class="d-flex gap-2">
                        <?php foreach ($socialIcons as $icon => $link): ?>
                            <a href="<?= e(safe_url($link)) ?>" class="edu-social-btn" target="_blank" rel="noopener noreferrer" aria-label="<?= e($icon) ?>"><i class="bi bi-<?= e($icon) ?>"></i></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <h6 class="edu-footer-heading">Quick Links</h6>
                <ul class="list-unstyled">
                    <?php foreach ($footerItems as $item): ?>
                        <li class="mb-2"><a href="<?= e($menuHref($item['url'])) ?>" class="edu-footer-link"><i class="bi bi-chevron-right small me-1"></i><?= e($item['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-12 col-sm-6 col-lg-5">
                <h6 class="edu-footer-heading">Get In Touch</h6>
                <ul class="list-unstyled edu-footer-muted small">
                    <?php if (!empty($contact['address'])): ?>
                        <li class="mb-3 d-flex gap-2"><i class="bi bi-geo-alt-fill mt-1" style="color: var(--edu-secondary);"></i><span><?= e($contact['address']) ?></span></li>
                    <?php endif; ?>
                    <?php if (!empty($contact['phone'])): ?>
                        <li class="mb-3 d-flex gap-2"><i class="bi bi-telephone-fill mt-1" style="color: var(--edu-secondary);"></i><span><?= e($contact['phone']) ?></span></li>
                    <?php endif; ?>
                    <?php if (!empty($contact['email'])): ?>
                        <li class="mb-3 d-flex gap-2"><i class="bi bi-envelope-fill mt-1" style="color: var(--edu-secondary);"></i><span><?= e($contact['email']) ?></span></li>
                    <?php endif; ?>
                    <?php if (!empty($contact['whatsapp'])): ?>
                        <li class="mb-3 d-flex gap-2"><i class="bi bi-whatsapp mt-1" style="color: var(--edu-secondary);"></i><span><?= e($contact['whatsapp']) ?></span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="edu-footer-bottom py-3">
        <div class="container text-center small edu-footer-muted">
            <?= e($footerSettings['copyright_text'] ?? ('© ' . date('Y') . ' ' . ($company['site_name'] ?? 'EduTop') . '. All rights reserved.')) ?>
        </div>
    </div>
</footer>
