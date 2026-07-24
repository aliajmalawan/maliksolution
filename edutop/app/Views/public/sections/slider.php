<?php
$slides = array_values(array_filter($content['slides'] ?? [], fn($s) => !empty($s['heading']) || media_url($s['image'] ?? null)));
if (empty($slides)) {
    return;
}
$autoplay = isset($content['autoplay_ms']) && (int) $content['autoplay_ms'] > 0 ? (int) $content['autoplay_ms'] : 6000;
$carouselId = 'eduSlider' . substr(md5(json_encode(array_column($slides, 'heading'))), 0, 6);
$demoAttrs = static fn(string $url): string => $url === '#demo-modal' ? 'data-bs-toggle="modal" data-bs-target="#demoModal"' : '';
$hrefFor = static fn(string $url): string => $url === '#demo-modal' ? '#' : (preg_match('#^(https?:)?//#i', $url) ? $url : url($url));
?>
<section class="edu-slider">
    <div id="<?= $carouselId ?>" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="<?= $autoplay ?>" data-bs-pause="false">
        <?php if (count($slides) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($slides as $i => $slide): ?>
                    <button type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="carousel-inner">
            <?php foreach ($slides as $i => $slide): ?>
                <?php $imgUrl = media_url($slide['image'] ?? null); ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <div class="edu-slide" style="<?= $imgUrl ? 'background-image: url(' . e($imgUrl) . ');' : '' ?>">
                        <div class="edu-slide-overlay"></div>
                        <div class="container position-relative">
                            <div class="edu-slide-content text-center text-lg-start">
                                <?php if (!empty($slide['eyebrow'])): ?>
                                    <span class="edu-slide-eyebrow"><i class="bi bi-stars me-1"></i><?= e($slide['eyebrow']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($slide['heading'])): ?>
                                    <h1 class="edu-slide-heading"><?= e($slide['heading']) ?></h1>
                                <?php endif; ?>
                                <?php if (!empty($slide['subheading'])): ?>
                                    <p class="edu-slide-sub"><?= e($slide['subheading']) ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-3 flex-wrap justify-content-center justify-content-lg-start">
                                    <?php if (!empty($slide['button_text'])): ?>
                                        <a href="<?= e($hrefFor($slide['button_url'] ?? '#')) ?>" class="btn btn-secondary btn-lg" <?= $demoAttrs($slide['button_url'] ?? '') ?>><?= e($slide['button_text']) ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($slide['button2_text'])): ?>
                                        <a href="<?= e($hrefFor($slide['button2_url'] ?? '#')) ?>" class="btn btn-outline-light btn-lg" <?= $demoAttrs($slide['button2_url'] ?? '') ?>><?= e($slide['button2_text']) ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($slides) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="prev">
                <span class="edu-slider-arrow"><i class="bi bi-chevron-left"></i></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#<?= $carouselId ?>" data-bs-slide="next">
                <span class="edu-slider-arrow"><i class="bi bi-chevron-right"></i></span>
                <span class="visually-hidden">Next</span>
            </button>
        <?php endif; ?>
    </div>
    <div class="edu-wave" aria-hidden="true">
        <svg viewBox="0 0 1440 70" preserveAspectRatio="none"><path d="M0,40 C240,80 480,0 720,25 C960,50 1200,15 1440,45 L1440,70 L0,70 Z" fill="currentColor"></path></svg>
    </div>
</section>
