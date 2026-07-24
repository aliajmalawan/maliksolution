<section class="edu-section">
    <div class="container">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <span class="edu-eyebrow">Gallery</span>
                <h2 class="fw-bold"><?= e($content['heading']) ?></h2>
            </div>
        <?php endif; ?>
        <div class="row g-4 animate-stagger">
            <?php foreach ($content['items'] ?? [] as $item): ?>
                <?php $imgUrl = media_url($item['image'] ?? null); ?>
                <div class="col-md-4">
                    <div class="edu-card hover-lift overflow-hidden">
                        <?php if ($imgUrl): ?>
                            <div style="overflow:hidden; border-radius: var(--edu-radius) var(--edu-radius) 0 0;">
                                <img src="<?= e($imgUrl) ?>" alt="<?= e($item['title'] ?? '') ?>" class="w-100" style="aspect-ratio:4/3;object-fit:cover;transition:transform .5s ease;">
                            </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h5 class="fw-bold mb-0"><?= e($item['title'] ?? '') ?></h5>
                            <?php if (!empty($item['subtitle'])): ?><div class="small mb-2" style="color: var(--edu-primary);"><?= e($item['subtitle']) ?></div><?php endif; ?>
                            <p class="mb-0" style="color: var(--edu-text);"><?= e($item['description'] ?? '') ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
