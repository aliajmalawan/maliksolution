<?php
$items = array_values($content['items'] ?? []);
$isSlider = ($content['display'] ?? 'grid') === 'slider' && count($items) > 1;
$tsId = 'eduTslider' . substr(md5(json_encode(array_column($items, 'name'))), 0, 6);

$renderStars = static function (array $item): string {
    if (empty($item['rating'])) {
        return '';
    }
    $stars = max(0, min(5, (int) $item['rating']));
    $html = '<div class="edu-testimonial-stars mb-3">';
    for ($i = 0; $i < $stars; $i++) { $html .= '<i class="bi bi-star-fill"></i>'; }
    for ($i = $stars; $i < 5; $i++) { $html .= '<i class="bi bi-star"></i>'; }
    return $html . '</div>';
};
?>
<section class="edu-section">
    <div class="container">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <span class="edu-eyebrow">Testimonials</span>
                <h2 class="fw-bold mb-0"><?= e($content['heading']) ?></h2>
                <div class="edu-heading-divider mx-auto"></div>
            </div>
        <?php endif; ?>

        <?php
        $renderCard = static function (array $item) use ($renderStars): string {
            $photoUrl = media_url($item['photo'] ?? null);
            $html = '<div class="edu-card edu-testimonial-card p-4 d-flex flex-column h-100">'
                . '<div class="d-flex justify-content-between align-items-start mb-3">'
                . '<i class="bi bi-quote edu-testimonial-quote"></i>' . $renderStars($item) . '</div>'
                . '<p class="mb-4 flex-grow-1 edu-testimonial-text">' . e($item['quote'] ?? '') . '</p>'
                . '<div class="pt-3" style="border-top: 1px solid var(--edu-border);">'
                . '<div class="d-flex align-items-center gap-3">';
            if ($photoUrl) {
                $html .= '<div class="edu-avatar-ring flex-shrink-0"><img src="' . e($photoUrl) . '" alt="' . e($item['name'] ?? '') . '"></div>';
            } else {
                $html .= '<div class="edu-avatar-initial flex-shrink-0">' . e(mb_strtoupper(mb_substr(trim($item['name'] ?? '?'), 0, 1))) . '</div>';
            }
            $html .= '<div><div class="fw-bold" style="color: var(--edu-heading);">' . e($item['name'] ?? '') . '</div>'
                . '<div class="small" style="color: var(--edu-primary); font-weight: 500;">' . e($item['role'] ?? '') . '</div></div>'
                . '</div></div></div>';
            return $html;
        };

        $renderCarousel = static function (array $chunks, string $id, string $colClass) use ($renderCard): void {
            ?>
            <div id="<?= $id ?>" class="carousel slide edu-tslider-multi" data-bs-ride="carousel" data-bs-interval="5000">
                <div class="carousel-inner">
                    <?php foreach ($chunks as $ci => $chunk): ?>
                        <div class="carousel-item <?= $ci === 0 ? 'active' : '' ?>">
                            <div class="row g-4 justify-content-center">
                                <?php foreach ($chunk as $item): ?>
                                    <div class="<?= $colClass ?>"><?= $renderCard($item) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($chunks) > 1): ?>
                    <div class="edu-tslider-nav">
                        <button class="edu-tslider-btn" type="button" data-bs-target="#<?= $id ?>" data-bs-slide="prev" aria-label="Previous"><i class="bi bi-arrow-left"></i></button>
                        <div class="carousel-indicators edu-tslider-dots">
                            <?php foreach ($chunks as $ci => $chunk): ?>
                                <button type="button" data-bs-target="#<?= $id ?>" data-bs-slide-to="<?= $ci ?>" class="<?= $ci === 0 ? 'active' : '' ?>" aria-label="Slide <?= $ci + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <button class="edu-tslider-btn" type="button" data-bs-target="#<?= $id ?>" data-bs-slide="next" aria-label="Next"><i class="bi bi-arrow-right"></i></button>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        };
        ?>
        <?php if ($isSlider): ?>
            <div class="animate-on-scroll">
                <!-- Desktop/tablet: 3 cards per slide -->
                <div class="d-none d-md-block">
                    <?php $renderCarousel(array_chunk($items, 3), $tsId, 'col-md-4'); ?>
                </div>
                <!-- Phone: 1 card per slide -->
                <div class="d-md-none">
                    <?php $renderCarousel(array_chunk($items, 1), $tsId . 'm', 'col-12'); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4 justify-content-center animate-stagger">
                <?php foreach ($items as $item): ?>
                    <?php $photoUrl = media_url($item['photo'] ?? null); ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="edu-card edu-testimonial-card hover-lift p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <i class="bi bi-quote edu-testimonial-quote"></i>
                                <?= $renderStars($item) ?>
                            </div>
                            <p class="mb-4 flex-grow-1 edu-testimonial-text"><?= e($item['quote'] ?? '') ?></p>
                            <div class="pt-3" style="border-top: 1px solid var(--edu-border);">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($photoUrl): ?>
                                        <div class="edu-avatar-ring flex-shrink-0"><img src="<?= e($photoUrl) ?>" alt="<?= e($item['name'] ?? '') ?>"></div>
                                    <?php else: ?>
                                        <div class="edu-avatar-initial flex-shrink-0"><?= e(mb_strtoupper(mb_substr(trim($item['name'] ?? '?'), 0, 1))) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold" style="color: var(--edu-heading);"><?= e($item['name'] ?? '') ?></div>
                                        <div class="small" style="color: var(--edu-primary); font-weight: 500;"><?= e($item['role'] ?? '') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
