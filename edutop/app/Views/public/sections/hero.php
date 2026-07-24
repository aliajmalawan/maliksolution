<?php $imageUrl = media_url($content['image'] ?? null); ?>
<section class="edu-hero">
    <div class="container position-relative">
        <div class="row align-items-center g-4 g-lg-5">
            <div class="col-lg-<?= $imageUrl ? '6' : '12' ?> <?= $imageUrl ? '' : 'text-center' ?> animate-on-scroll">
                <span class="edu-eyebrow"><i class="bi bi-stars me-1"></i>Quality Education Since Day One</span>
                <?php if (!empty($content['heading'])): ?>
                    <h1 class="display-4 fw-bold mb-3" style="letter-spacing:-0.03em;"><?= e($content['heading']) ?></h1>
                <?php endif; ?>
                <?php if (!empty($content['subheading'])): ?>
                    <p class="lead mb-4" style="color: var(--edu-text); max-width: 42rem; <?= $imageUrl ? '' : 'margin-left:auto;margin-right:auto;' ?>"><?= e($content['subheading']) ?></p>
                <?php endif; ?>
                <?php if (!empty($content['button_text'])): ?>
                    <?php $isDemo = ($content['button_url'] ?? '') === '#demo-modal'; ?>
                    <a href="<?= $isDemo ? '#' : e($content['button_url'] ?? '#') ?>" class="btn btn-primary btn-lg" <?= $isDemo ? 'data-bs-toggle="modal" data-bs-target="#demoModal"' : '' ?>><?= e($content['button_text']) ?></a>
                <?php endif; ?>
            </div>
            <?php if ($imageUrl): ?>
                <div class="col-lg-6 animate-on-scroll" style="transition-delay:.15s;">
                    <div class="edu-hero-media">
                        <img src="<?= e($imageUrl) ?>" alt="<?= e($content['heading'] ?? '') ?>" class="img-fluid rounded-4">
                        <div class="edu-hero-badge edu-hero-badge-top">
                            <span class="edu-hero-badge-icon">🎓</span>
                            <span><strong>Pre-School to Intermediate</strong><br><small>All classes under one roof</small></span>
                        </div>
                        <div class="edu-hero-badge edu-hero-badge-bottom">
                            <span class="edu-hero-badge-icon">⭐</span>
                            <span><strong>Trusted by 2,500+ students</strong><br><small>across Quaidabad, Khushab</small></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
