<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Permission Matrix</h1>
    <a href="<?= url('/admin/roles') ?>" class="btn btn-link btn-sm">Back to Roles</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Permission</th>
                    <?php foreach ($roles as $role): ?>
                        <th class="text-center"><?= e($role['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissionModules as $module => $permissions): ?>
                    <tr class="table-light">
                        <td colspan="<?= count($roles) + 1 ?>" class="fw-bold text-uppercase small"><?= e($module) ?></td>
                    </tr>
                    <?php foreach ($permissions as $slug => $label): ?>
                        <tr>
                            <td><?= e($label) ?> <code class="text-muted small"><?= e($slug) ?></code></td>
                            <?php foreach ($roles as $role): ?>
                                <?php $grants = $grantsByRole[$role['id']]; ?>
                                <td class="text-center">
                                    <?php if ($grants === null || in_array($slug, $grants, true)): ?>
                                        <span class="text-success">&check;</span>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
