<h1 class="h4 mb-4">Sections &mdash; <?= e($page['title']) ?></h1>

<?php include __DIR__ . '/_tabs.php'; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="list-group" data-sortable-sections data-reorder-url="<?= url('/admin/pages/' . $page['id'] . '/sections/reorder') ?>">
            <?php if (empty($sections)): ?>
                <p class="text-muted mb-0">No sections yet. Add one below to get started.</p>
            <?php endif; ?>
            <?php foreach ($sections as $section): ?>
                <div class="list-group-item d-flex align-items-center gap-3" data-section-id="<?= (int) $section['id'] ?>">
                    <span class="drag-handle text-muted" style="cursor:grab;" title="Drag to reorder">&#9776;</span>
                    <div class="flex-grow-1">
                        <div class="fw-bold"><?= e($section['type_label']) ?></div>
                        <div class="text-muted small">Position <?= (int) $section['position'] + 1 ?></div>
                    </div>
                    <?php if (!$section['is_enabled']): ?><span class="badge bg-secondary">Disabled</span><?php endif; ?>
                    <a href="<?= url('/admin/pages/' . $page['id'] . '/sections/' . $section['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/sections/' . $section['id'] . '/toggle') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><?= $section['is_enabled'] ? 'Disable' : 'Enable' ?></button>
                    </form>
                    <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/sections/' . $section['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this section?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width: 480px;">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Add Section</h6>
        <form method="POST" action="<?= url('/admin/pages/' . $page['id'] . '/sections') ?>" class="d-flex gap-2">
            <?= csrf_field() ?>
            <select name="section_type" class="form-select">
                <?php foreach ($sectionTypes as $type => $def): ?>
                    <option value="<?= e($type) ?>"><?= e($def['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary text-nowrap">Add Section</button>
        </form>
    </div>
</div>
