<section class="edu-section">
    <div class="container">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <span class="edu-eyebrow">Trusted By</span>
                <h2 class="fw-bold"><?= e($content['heading']) ?></h2>
            </div>
        <?php endif; ?>
        <div class="row g-4 align-items-center justify-content-center animate-stagger">
            <?php foreach ($content['items'] ?? [] as $item): ?>
                <?php $logoUrl = media_url($item['logo'] ?? null); ?>
                <div class="col-6 col-md-2 text-center">
                    <?php if ($item['url'] ?? false): ?><a href="<?= e(safe_url($item['url'])) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none"><?php endif; ?>
                    <?php if ($logoUrl): ?>
                        <img src="<?= e($logoUrl) ?>" alt="<?= e($item['name'] ?? '') ?>" class="img-fluid edu-client-logo" style="max-height:60px;">
                    <?php else: ?>
                        <span class="fw-bold" style="color: var(--edu-text);"><?= e($item['name'] ?? '') ?></span>
                    <?php endif; ?>
                    <?php if ($item['url'] ?? false): ?></a><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
