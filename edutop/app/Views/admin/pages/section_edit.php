<h1 class="h4 mb-1">Edit Section</h1>
<p class="text-muted mb-4"><?= e($schema['label']) ?> &mdash; <?= e($page['title']) ?></p>

<?php $custom = $content['_custom_code'] ?? []; ?>

<?php if ($section['section_type'] === 'image_gallery'): ?>
    <?php
    $existingCategories = [];
    foreach ($content['items'] ?? [] as $item) {
        $cat = trim((string) ($item['category'] ?? ''));
        if ($cat !== '' && !isset($existingCategories[strtolower($cat)])) {
            $existingCategories[strtolower($cat)] = $cat;
        }
    }
    ?>
    <div class="card border-0 shadow-sm mb-4" style="max-width: 720px;">
        <div class="card-body">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">&uarr; Upload Photos</button>
            <div class="form-text mt-2">Upload several photos at once, all tagged with the same category.</div>
        </div>
    </div>

    <div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Photos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="bulkUploadCategorySelect">
                            <option value="">No Category</option>
                            <?php foreach ($existingCategories as $cat): ?>
                                <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="__new__">+ Create New Category&hellip;</option>
                        </select>
                        <input type="text" class="form-control mt-2 d-none" id="bulkUploadNewCategory" placeholder="New category name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Images (multiple allowed)</label>
                        <input type="file" class="form-control" id="bulkUploadFiles" accept="image/*" multiple>
                    </div>
                    <div class="small text-muted" id="bulkUploadStatus"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="bulkUploadSubmit" data-upload-url="<?= e(url('/admin/media/upload')) ?>" data-repeater-group="content[items]">&uarr; Upload</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Compact card renderer for the gallery photo grid — same underlying inputs
// (content[items][N][image] / [category]) as the generic repeater, just a
// small thumbnail-grid layout instead of the generic stacked-field editor.
// $index is a string so the shared <template> can use the '__INDEX__' token.
$renderGalleryCard = static function ($item, $index): string {
    $imageId = (int) ($item['image'] ?? 0);
    $imgUrl = $imageId > 0 ? media_url($imageId) : null;
    $category = e((string) ($item['category'] ?? ''));

    return '<div class="repeater-row edu-gallery-card">'
        . '<div class="position-relative">'
        . ($imgUrl
            ? '<img src="' . e($imgUrl) . '" class="rounded">'
            : '<div class="edu-gallery-card-empty rounded d-flex align-items-center justify-content-center text-muted small">No image</div>')
        . '<button type="button" class="btn btn-sm btn-danger rounded-circle edu-gallery-card-remove" data-repeater-remove title="Remove">&times;</button>'
        . '</div>'
        . '<input type="hidden" name="content[items][' . $index . '][image]" value="' . ($imageId ?: '') . '">'
        . '<input type="text" class="form-control form-control-sm mt-1" name="content[items][' . $index . '][category]" value="' . $category . '" placeholder="Category">'
        . '</div>';
};
?>

<div class="card border-0 shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/sections/' . $section['id']) ?>">
            <?= csrf_field() ?>
            <?php if ($section['section_type'] === 'image_gallery'): ?>
                <?= \App\Core\SectionForm::renderFields(['heading' => $schema['fields']['heading']], $content) ?>
                <label class="form-label fw-bold">Photos</label>
                <div class="repeater" data-repeater-group="content[items]">
                    <div class="repeater-rows d-flex flex-wrap gap-3">
                        <?php foreach (array_values($content['items'] ?? []) as $i => $item): ?>
                            <?= $renderGalleryCard($item, (string) $i) ?>
                        <?php endforeach; ?>
                    </div>
                    <template class="repeater-template"><?= $renderGalleryCard([], '__INDEX__') ?></template>
                </div>
            <?php else: ?>
                <?= \App\Core\SectionForm::renderFields($schema['fields'], $content) ?>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary mt-3">Save Section</button>
            <a href="<?= url('/admin/pages/' . $page['id'] . '/sections') ?>" class="btn btn-link">Back to Sections</a>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4" style="max-width: 720px;">
    <div class="card-body">
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#customCodePanel">
            &lt;/&gt; Custom Design (Code)
        </button>
        <div class="collapse <?= !empty($custom['enabled']) ? 'show' : '' ?> mt-3" id="customCodePanel">
            <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/sections/' . $section['id']) ?>">
                <?= csrf_field() ?>
                <p class="text-muted small">
                    The box below is pre-filled with this section's own current markup, so you can edit its classes,
                    ids and inline styles directly instead of starting from scratch. When enabled and saved, this
                    replaces the standard layout above. Scripts, iframes, and forms are stripped for security;
                    class/id/style attributes are allowed.
                </p>
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" class="form-check-input" id="customCodeEnabled" name="custom_code[enabled]" value="1" <?= !empty($custom['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="customCodeEnabled">Use custom code for this section</label>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0" for="customCodeHtml">Custom HTML</label>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-load-current-design data-target="#customCodeHtml">&#8635; Load Current Design</button>
                    </div>
                    <?php $customHtml = !empty($custom['html']) ? $custom['html'] : $renderedHtml; ?>
                    <textarea class="form-control font-monospace" id="customCodeHtml" name="custom_code[html]" rows="14" spellcheck="false"><?= e($customHtml) ?></textarea>
                    <template id="customCodeCurrentDesign"><?= e($renderedHtml) ?></template>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="customCodeCss">Custom CSS</label>
                    <textarea class="form-control font-monospace" id="customCodeCss" name="custom_code[css]" rows="8" spellcheck="false" placeholder=".my-class { color: #2D1B7B; }"><?= e($custom['css'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="custom_code_only" value="1" class="btn btn-dark">Save Custom Code</button>
            </form>
        </div>
    </div>
</div>
