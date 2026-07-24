<section class="edu-section">
    <div class="container">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <span class="edu-eyebrow">Pricing</span>
                <h2 class="fw-bold"><?= e($content['heading']) ?></h2>
            </div>
        <?php endif; ?>
        <div class="row g-4 justify-content-center animate-stagger">
            <?php foreach ($content['items'] ?? [] as $item): ?>
                <?php $featured = !empty($item['is_featured']); ?>
                <div class="col-md-4">
                    <div class="edu-card <?= $featured ? 'hover-lift' : 'hover-lift' ?> h-100" style="<?= $featured ? 'border: 2px solid var(--edu-primary); box-shadow: var(--edu-shadow-md);' : '' ?>">
                        <div class="p-4 text-center d-flex flex-column h-100">
                            <?php if ($featured): ?><span class="badge align-self-center mb-2" style="background: var(--edu-secondary); color: var(--edu-heading);">Most Popular</span><?php endif; ?>
                            <h5 class="fw-bold"><?= e($item['plan_name'] ?? '') ?></h5>
                            <div class="display-6 fw-bold my-3"><?= e($item['price'] ?? '') ?><small class="fs-6" style="color: var(--edu-text);"><?= e($item['period'] ?? '') ?></small></div>
                            <ul class="list-unstyled mb-4 flex-grow-1 text-start" style="color: var(--edu-text);">
                                <?php foreach (array_filter(array_map('trim', explode("\n", $item['features'] ?? ''))) as $feature): ?>
                                    <li class="mb-2">&check; <?= e($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (!empty($item['button_text'])): ?>
                                <a href="<?= e(safe_url($item['button_url'] ?? '#')) ?>" class="btn <?= $featured ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($item['button_text']) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
