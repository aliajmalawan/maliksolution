<?php $pageTitle = $content['heading'] ?? ($page['title'] ?? ''); ?>
<section class="edu-page-header text-center text-white">
    <div class="container position-relative">
        <nav class="edu-breadcrumb" aria-label="breadcrumb">
            <a href="<?= url('/') ?>"><i class="bi bi-house-door-fill me-1"></i>Home</a>
            <span class="edu-breadcrumb-sep"><i class="bi bi-chevron-right"></i></span>
            <span class="edu-breadcrumb-current"><?= e($pageTitle) ?></span>
        </nav>
        <h1 class="edu-page-header-title"><?= e($pageTitle) ?></h1>
        <?php if (!empty($content['subtext'])): ?>
            <p class="edu-page-header-sub mx-auto"><?= e($content['subtext']) ?></p>
        <?php endif; ?>
    </div>
    <div class="edu-wave" aria-hidden="true">
        <svg viewBox="0 0 1440 70" preserveAspectRatio="none"><path d="M0,40 C240,80 480,0 720,25 C960,50 1200,15 1440,45 L1440,70 L0,70 Z" fill="currentColor"></path></svg>
    </div>
</section>
