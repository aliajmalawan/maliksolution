<section class="edu-section">
    <div class="container">
        <div class="row justify-content-center animate-on-scroll">
            <div class="col-lg-8">
                <?php if (!empty($content['heading'])): ?>
                    <h2 class="fw-bold mb-4"><?= e($content['heading']) ?></h2>
                <?php endif; ?>
                <?php /* Sanitized server-side via App\Core\Sanitizer::richText() on save — safe to output raw. */ ?>
                <div class="rich-text-content" style="color: var(--edu-text);"><?= $content['content'] ?? '' ?></div>
            </div>
        </div>
    </div>
</section>
