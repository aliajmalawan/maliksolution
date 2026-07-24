<section class="edu-section">
    <div class="container">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <span class="edu-eyebrow">FAQ</span>
                <h2 class="fw-bold"><?= e($content['heading']) ?></h2>
            </div>
        <?php endif; ?>
        <div class="row justify-content-center animate-on-scroll">
            <div class="col-lg-8">
                <div class="accordion edu-faq-accordion" id="faqAccordion">
                    <?php foreach ($content['items'] ?? [] as $i => $item): ?>
                        <?php $id = 'faq' . $i; ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>">
                                    <?= e($item['question'] ?? '') ?>
                                </button>
                            </h2>
                            <div id="<?= $id ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body" style="color: var(--edu-text);"><?= e($item['answer'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
