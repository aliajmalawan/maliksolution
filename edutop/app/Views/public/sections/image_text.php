<?php
$imgUrl = media_url($content['image'] ?? null);
$imageRight = ($content['image_position'] ?? 'right') === 'right';
?>
<section class="edu-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-<?= $imageRight ? '1' : '2' ?> animate-on-scroll <?= $imageRight ? 'reveal-left' : 'reveal-right' ?>">
                <?php if (!empty($content['heading'])): ?>
                    <h2 class="fw-bold mb-3"><?= e($content['heading']) ?></h2>
                <?php endif; ?>
                <?php /* Sanitized server-side via App\Core\Sanitizer::richText() on save — safe to output raw. */ ?>
                <div style="color: var(--edu-text);"><?= $content['text'] ?? '' ?></div>
                <?php if (!empty($content['button_text'])): ?>
                    <a href="<?= e(safe_url($content['button_url'] ?? '#')) ?>" class="btn btn-primary mt-3"><?= e($content['button_text']) ?></a>
                <?php endif; ?>
            </div>
            <?php if ($imgUrl): ?>
                <div class="col-lg-6 order-lg-<?= $imageRight ? '2' : '1' ?> animate-on-scroll <?= $imageRight ? 'reveal-right' : 'reveal-left' ?>" style="transition-delay:.15s;">
                    <img src="<?= e($imgUrl) ?>" alt="<?= e($content['heading'] ?? '') ?>" class="img-fluid rounded-4" style="box-shadow: var(--edu-shadow-lg);">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
