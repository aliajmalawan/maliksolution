<?php $isPageLink = $matchedPageId !== null; ?>

<h1 class="h4 mb-4">Edit Menu Item &mdash; <?= e($menu['name']) ?></h1>

<div class="card border-0 shadow-sm" style="max-width: 480px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/menus/items/' . $item['id']) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Label</label>
                <input type="text" name="label" class="form-control" value="<?= e($item['label']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Parent (optional, for a dropdown sub-item)</label>
                <select name="parent_id" class="form-select">
                    <option value="">— Top level —</option>
                    <?php foreach ($items as $option): ?>
                        <?php if (!$option['parent_id'] && (int) $option['id'] !== (int) $item['id']): ?>
                            <option value="<?= (int) $option['id'] ?>" <?= (int) $item['parent_id'] === (int) $option['id'] ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Link Type</label>
                <select name="link_type" class="form-select" id="menuLinkType">
                    <option value="page" <?= $isPageLink ? 'selected' : '' ?>>Internal Page</option>
                    <option value="custom" <?= !$isPageLink ? 'selected' : '' ?>>Custom URL</option>
                </select>
            </div>
            <div class="mb-3 <?= $isPageLink ? '' : 'd-none' ?>" id="menuPageSelect">
                <label class="form-label">Page</label>
                <select name="page_id" class="form-select">
                    <option value="special:blog" <?= $matchedPageId === 'special:blog' ? 'selected' : '' ?>>Blog (/blog)</option>
                    <?php foreach ($pages as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $matchedPageId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?> (/<?= e($p['slug']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3 <?= $isPageLink ? 'd-none' : '' ?>" id="menuCustomUrl">
                <label class="form-label">Custom URL</label>
                <input type="text" name="custom_url" class="form-control" value="<?= $isPageLink ? '' : e($item['url']) ?>" placeholder="https://example.com or /path">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="menuNewTab" name="new_tab" value="1" <?= $item['target'] === '_blank' ? 'checked' : '' ?>>
                <label class="form-check-label" for="menuNewTab">Open in new tab</label>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= url('/admin/menus/' . $menu['slug'] . '/edit') ?>" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
