<?php $embedUrl = trim($content['embed_url'] ?? ''); ?>
<?php if ($embedUrl !== '' && preg_match('#^https://#i', $embedUrl)): ?>
<section class="edu-section">
    <div class="container animate-on-scroll">
        <?php if (!empty($content['heading'])): ?>
            <h2 class="text-center fw-bold mb-4"><?= e($content['heading']) ?></h2>
        <?php endif; ?>
        <div class="ratio ratio-16x9 edu-map-frame rounded-4 overflow-hidden" style="box-shadow: var(--edu-shadow-md);">
            <iframe src="<?= e($embedUrl) ?>" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
    </div>
</section>
<?php endif; ?>
