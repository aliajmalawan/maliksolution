<div class="row g-2">
    <?php if (empty($items)): ?>
        <p class="text-muted">No media files yet. Upload some from the Media Library first.</p>
    <?php endif; ?>
    <?php foreach ($items as $item): ?>
        <?php $itemUrl = url('/public/uploads' . $item['path']); ?>
        <div class="col-4 col-md-3">
            <div class="border rounded p-1 text-center" style="cursor:pointer;" data-media-id="<?= (int) $item['id'] ?>" data-media-url="<?= e($itemUrl) ?>" title="<?= e($item['original_name']) ?>">
                <div class="d-flex align-items-center justify-content-center bg-light" style="height:80px;overflow:hidden;">
                    <?php if ($item['type'] === 'image'): ?>
                        <img src="<?= e($itemUrl) ?>" style="max-width:100%;max-height:100%;object-fit:cover;" alt="">
                    <?php else: ?>
                        <span class="text-muted text-uppercase small"><?= e($item['type']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-truncate small mt-1"><?= e($item['original_name']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
