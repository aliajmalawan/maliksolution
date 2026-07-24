<?php $items = array_values($content['items'] ?? []); ?>
<section class="edu-section edu-programs-section">
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

        <div class="edu-journey animate-stagger" style="--edu-journey-cols: <?= max(1, count($items)) ?>;">
            <?php foreach ($items as $i => $item): ?>
                <div class="edu-journey-step">
                    <div class="edu-journey-node">
                        <span class="edu-journey-number"><?= $i + 1 ?></span>
                    </div>
                    <div class="edu-journey-card">
                        <?php if (!empty($item['icon'])): ?>
                            <div class="edu-journey-icon"><?= e($item['icon']) ?></div>
                        <?php endif; ?>
                        <h5 class="fw-bold mb-1"><?= e($item['title'] ?? '') ?></h5>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="edu-journey-badge"><?= e($item['badge']) ?></span>
                        <?php endif; ?>
                        <p class="mb-0 small" style="color: var(--edu-text); line-height: 1.7;"><?= e($item['description'] ?? '') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
