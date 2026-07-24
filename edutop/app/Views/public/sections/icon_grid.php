<?php
$items = $content['items'] ?? [];
// 2-across rows read best for this list layout; 3-across when items divide by 3.
$colClass = count($items) % 3 === 0 && count($items) > 0 ? 'col-md-6 col-lg-4' : 'col-md-6';
?>
<section class="edu-section">
    <div class="container">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <?php if (!empty($content['eyebrow'])): ?>
                    <span class="edu-eyebrow"><?= e($content['eyebrow']) ?></span>
                <?php endif; ?>
                <h2 class="fw-bold"><?= e($content['heading']) ?></h2>
                <?php if (!empty($content['subheading'])): ?>
                    <p class="mx-auto" style="color: var(--edu-text); max-width: 40rem;"><?= e($content['subheading']) ?></p>
                <?php endif; ?>
                <div class="edu-heading-divider mx-auto"></div>
            </div>
        <?php endif; ?>
        <div class="row g-4 gx-lg-5 gy-lg-5 justify-content-center animate-stagger">
            <?php foreach ($items as $item): ?>
                <div class="<?= $colClass ?>">
                    <div class="edu-feature-item d-flex gap-3">
                        <?php if (!empty($item['icon'])): ?>
                            <div class="edu-feature-icon flex-shrink-0"><?= e($item['icon']) ?></div>
                        <?php endif; ?>
                        <div>
                            <h5 class="fw-bold mb-2"><?= e($item['title'] ?? '') ?></h5>
                            <p class="mb-0" style="color: var(--edu-text); line-height: 1.75;"><?= e($item['description'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
