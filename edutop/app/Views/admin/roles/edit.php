<h1 class="h4 mb-4">Permissions &mdash; <?= e($role['name']) ?></h1>

<?php if ($isSuperAdmin): ?>
    <div class="alert alert-info">Super Admin always has full, unrestricted access. Permissions cannot be changed for this role.</div>
<?php endif; ?>

<?php $permissionIdBySlug = array_column($allPermissions, 'id', 'slug'); ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/roles/' . $role['id']) ?>">
            <?= csrf_field() ?>
            <div class="row">
                <?php foreach ($permissionModules as $module => $permissions): ?>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-uppercase text-muted small fw-bold"><?= e($module) ?></h6>
                        <?php foreach ($permissions as $slug => $label): ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="permissions[]"
                                    value="<?= (int) ($permissionIdBySlug[$slug] ?? 0) ?>"
                                    id="perm-<?= e($slug) ?>"
                                    <?= in_array($slug, $granted, true) ? 'checked' : '' ?>
                                    <?= $isSuperAdmin ? 'disabled' : '' ?>
                                >
                                <label class="form-check-label" for="perm-<?= e($slug) ?>"><?= e($label) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$isSuperAdmin): ?>
                <button type="submit" class="btn btn-primary">Save Permissions</button>
            <?php endif; ?>
            <a href="<?= url('/admin/roles') ?>" class="btn btn-link">Back</a>
        </form>
    </div>
</div>
