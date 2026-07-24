<section class="edu-stats-section edu-section">
    <div class="container position-relative">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <span class="edu-eyebrow">Our Numbers</span>
                <h2 class="fw-bold mb-0"><?= e($content['heading']) ?></h2>
                <div class="edu-heading-divider mx-auto"></div>
            </div>
        <?php endif; ?>
        <div class="row g-4 text-center animate-stagger justify-content-center">
            <?php foreach ($content['items'] ?? [] as $item):
                $raw = (string) ($item['value'] ?? '');
                preg_match('/^([^\d]*)([\d,.]+)(.*)$/', $raw, $m);
                $hasNumber = !empty($m[2]);
                $prefix = $hasNumber ? trim($m[1]) : '';
                $number = $hasNumber ? (float) str_replace(',', '', $m[2]) : 0;
                $suffix = $hasNumber ? trim($m[3]) : '';
            ?>
                <div class="col-6 col-lg-3">
                    <div class="edu-stat-card h-100">
                        <div class="edu-stat-number">
                            <?php if ($hasNumber): ?>
                                <span data-count-to="<?= e((string) $number) ?>" data-count-prefix="<?= e($prefix) ?>" data-count-suffix="<?= e($suffix) ?>">0</span>
                            <?php else: ?>
                                <?= e($raw) ?>
                            <?php endif; ?>
                        </div>
                        <div class="edu-stat-label mt-2"><?= e($item['label'] ?? '') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
