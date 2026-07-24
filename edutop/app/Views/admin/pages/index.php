<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Pages</h1>
    <?php if (can('pages.manage')): ?>
        <a href="<?= url('/admin/pages/create') ?>" class="btn btn-primary btn-sm">Add Page</a>
    <?php endif; ?>
</div>

<?php $canManage = can('pages.manage'); ?>
<?php if ($canManage): ?>
    <p class="text-muted small mb-2"><i class="bi bi-grip-vertical"></i> Drag rows by the handle to change page order.</p>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <?php if ($canManage): ?><th style="width:36px;"></th><?php endif; ?>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Sections</th>
                    <th></th>
                </tr>
            </thead>
            <tbody <?= $canManage ? 'data-sortable-pages data-reorder-url="' . e(url('/admin/pages/reorder')) . '"' : '' ?>>
                <?php foreach ($pages as $p): ?>
                    <tr data-page-id="<?= (int) $p['id'] ?>">
                        <?php if ($canManage): ?>
                            <td class="text-muted drag-handle" style="cursor:grab;" title="Drag to reorder">&#8942;&#8942;</td>
                        <?php endif; ?>
                        <td>
                            <?= e($p['title']) ?>
                            <?php if ($p['is_home']): ?><span class="badge bg-info ms-1">Home</span><?php endif; ?>
                        </td>
                        <td><code>/<?= e($p['slug']) ?></code></td>
                        <td><span class="badge <?= $p['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>"><?= e($p['status']) ?></span></td>
                        <td><?= (int) $p['section_count'] ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= url('/admin/pages/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <a href="<?= url('/admin/pages/' . $p['id'] . '/sections') ?>" class="btn btn-sm btn-outline-primary">Sections</a>
                            <?php if ($p['status'] === 'published'): ?>
                                <a href="<?= url('/' . $p['slug']) ?>" class="btn btn-sm btn-outline-dark" target="_blank">View</a>
                            <?php endif; ?>
                            <?php if ($canManage && empty($p['is_home'])): ?>
                                <form method="POST" action="<?= url('/admin/pages/' . $p['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete page '<?= e($p['title']) ?>' and all its sections? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
