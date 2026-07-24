<h1 class="h4 mb-4"><?= $user ? 'Edit User' : 'Add User' ?></h1>

<div class="card border-0 shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="<?= $user ? url('/admin/users/' . $user['id']) : url('/admin/users') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= (($user['role_id'] ?? null) == $role['id']) ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($user): ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label"><?= $user ? 'New Password (leave blank to keep current)' : 'Password' ?></label>
                <input type="password" name="password" class="form-control" <?= $user ? 'minlength="10"' : '' ?> <?= $user ? '' : 'required' ?>>
                <?php if ($user): ?><div class="form-text">Minimum 10 characters.</div><?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary"><?= $user ? 'Save Changes' : 'Create User' ?></button>
            <a href="<?= url('/admin/users') ?>" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
