<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Users</h1>
    <?php if (can('users.manage')): ?>
        <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary btn-sm">Add User</a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge bg-secondary"><?= e($u['role_name']) ?></span></td>
                        <td><span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= e($u['status']) ?></span></td>
                        <td><?= e($u['last_login_at'] ?? 'Never') ?></td>
                        <td class="text-end">
                            <a href="<?= url('/admin/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <?php if (can('users.manage')): ?>
                                <form method="POST" action="<?= url('/admin/users/' . $u['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this user?">
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
