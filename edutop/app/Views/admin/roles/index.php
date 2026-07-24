<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Roles &amp; Permissions</h1>
    <a href="<?= url('/admin/roles/matrix') ?>" class="btn btn-outline-secondary btn-sm">View Permission Matrix</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Role</th>
                    <th>Users</th>
                    <th>Permissions Granted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td><?= e($role['name']) ?></td>
                        <td><?= (int) $role['user_count'] ?></td>
                        <td>
                            <?php if ($role['slug'] === 'super-admin'): ?>
                                <span class="badge bg-dark">All permissions</span>
                            <?php else: ?>
                                <?= (int) $role['permission_count'] ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('/admin/roles/' . $role['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
                                <?= $role['slug'] === 'super-admin' ? 'View' : 'Edit' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
