<h1 class="h4 mb-4">My Profile</h1>

<div class="card border-0 shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/profile') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
            </div>
            <hr>
            <p class="text-muted small">Leave the password fields blank to keep your current password.</p>
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <div class="input-group">
                    <input type="password" name="current_password" id="currentPasswordInput" class="form-control">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="#currentPasswordInput" aria-label="Show password"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" name="new_password" id="newPasswordInput" class="form-control" minlength="10">
                    <button type="button" class="btn btn-outline-secondary" data-toggle-password="#newPasswordInput" aria-label="Show password"><i class="bi bi-eye"></i></button>
                </div>
                <div class="form-text">Minimum 10 characters.</div>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
