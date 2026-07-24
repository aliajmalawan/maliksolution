<?php
$lightboxId = 'lightbox' . substr(md5(json_encode($content['items'] ?? [])), 0, 6);
$galleryId = 'gal' . substr(md5(json_encode($content['items'] ?? []) . 'g'), 0, 6);

// Categories are just whatever the admin typed on each photo — typing a new
// name "creates" it, reusing an existing name adds to it. Deduped
// case-insensitively so "Sports" and "sports" don't become two filters.
$categories = [];
foreach ($content['items'] ?? [] as $item) {
    $cat = trim((string) ($item['category'] ?? ''));
    if ($cat !== '' && !isset($categories[strtolower($cat)])) {
        $categories[strtolower($cat)] = $cat;
    }
}
$categories = array_values($categories);
?>
<section class="edu-section">
    <div class="container">
        <?php if (!empty($content['heading'])): ?>
            <div class="text-center mb-5 animate-on-scroll">
                <h2 class="fw-bold"><?= e($content['heading']) ?></h2>
            </div>
        <?php endif; ?>

        <?php if (!empty($categories)): ?>
            <div class="edu-gallery-filters d-flex flex-wrap justify-content-center gap-2 mb-4" data-gallery-filters="<?= e($galleryId) ?>">
                <button type="button" class="edu-gallery-filter-btn active" data-filter="all">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="edu-gallery-filter-btn" data-filter="<?= e(strtolower($cat)) ?>"><?= e($cat) ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="edu-masonry-grid animate-stagger" data-gallery-grid="<?= e($galleryId) ?>">
            <?php foreach ($content['items'] ?? [] as $item): ?>
                <?php
                $imgUrl = media_url($item['image'] ?? null);
                $cat = trim((string) ($item['category'] ?? ''));
                ?>
                <?php if ($imgUrl): ?>
                    <div class="edu-gallery-item" data-category="<?= e(strtolower($cat)) ?>">
                        <button type="button" class="edu-photo-tile hover-lift border-0 p-0 w-100" data-bs-toggle="modal" data-bs-target="#<?= e($lightboxId) ?>" data-lightbox-src="<?= e($imgUrl) ?>" aria-label="View larger image">
                            <img src="<?= e($imgUrl) ?>" alt="" loading="lazy">
                        </button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade edu-lightbox-modal" id="<?= e($lightboxId) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <button type="button" class="btn-close btn-close-white edu-lightbox-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <button type="button" class="edu-lightbox-nav edu-lightbox-prev" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                <img src="" alt="" class="edu-lightbox-img">
                <button type="button" class="edu-lightbox-nav edu-lightbox-next" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
</section>
