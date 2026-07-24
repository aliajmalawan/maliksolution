<h1 class="h4 mb-4">Menu &mdash; <?= e($menu['name']) ?></h1>

<ul class="nav nav-pills mb-4">
    <li class="nav-item"><a class="nav-link <?= $menu['slug'] === 'primary' ? 'active' : '' ?>" href="<?= url('/admin/menus/primary/edit') ?>">Primary (Header)</a></li>
    <li class="nav-item"><a class="nav-link <?= $menu['slug'] === 'footer' ? 'active' : '' ?>" href="<?= url('/admin/menus/footer/edit') ?>">Footer</a></li>
</ul>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">Items (drag to reorder)</h6>
                <div class="list-group" data-sortable-menu data-reorder-url="<?= url('/admin/menus/' . $menu['slug'] . '/reorder') ?>">
                    <?php foreach ($items as $item): ?>
                        <div class="list-group-item d-flex align-items-center gap-3" data-item-id="<?= (int) $item['id'] ?>">
                            <span class="drag-handle text-muted" style="cursor:grab;">&#9776;</span>
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?= e($item['label']) ?><?= $item['parent_id'] ? ' <span class="text-muted small">(sub-item)</span>' : '' ?></div>
                                <div class="text-muted small"><?= e($item['url']) ?><?= $item['target'] === '_blank' ? ' &middot; new tab' : '' ?></div>
                            </div>
                            <a href="<?= url('/admin/menus/items/' . $item['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="<?= url('/admin/menus/items/' . $item['id'] . '/delete') ?>" data-confirm="Remove this menu item?">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                        <p class="text-muted mb-0">No items yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">Add Item</h6>
                <form method="POST" action="<?= url('/admin/menus/' . $menu['slug'] . '/items') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent (optional, for a dropdown sub-item)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— Top level —</option>
                            <?php foreach ($items as $item): ?>
                                <?php if (!$item['parent_id']): ?>
                                    <option value="<?= (int) $item['id'] ?>"><?= e($item['label']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link Type</label>
                        <select name="link_type" class="form-select" id="menuLinkType">
                            <option value="page">Internal Page</option>
                            <option value="custom">Custom URL</option>
                        </select>
                    </div>
                    <div class="mb-3" id="menuPageSelect">
                        <label class="form-label">Page</label>
                        <select name="page_id" class="form-select">
                            <option value="special:blog">Blog (/blog)</option>
                            <?php foreach ($pages as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= e($p['title']) ?> (/<?= e($p['slug']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="menuCustomUrl">
                        <label class="form-label">Custom URL</label>
                        <input type="text" name="custom_url" class="form-control" placeholder="https://example.com or /path">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="menuNewTab" name="new_tab" value="1">
                        <label class="form-check-label" for="menuNewTab">Open in new tab</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </form>
            </div>
        </div>
    </div>
</div>
