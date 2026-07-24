<h1 class="h4 mb-4">Site Settings</h1>

<?php include __DIR__ . '/_tabs.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 640px;">
    <div class="card-body">
        <p class="text-muted small">These values override <code>.env</code> when set. Leave blank to keep using <code>.env</code> (or the currently saved value, for the password).</p>
        <form method="POST" action="<?= url('/admin/settings/email') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">SMTP Host</label>
                <input type="text" name="smtp_host" class="form-control" value="<?= e($email['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="text" name="smtp_port" class="form-control" value="<?= e($email['smtp_port'] ?? '') ?>" placeholder="587">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Encryption</label>
                    <select name="smtp_encryption" class="form-select">
                        <option value="tls" <?= ($email['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= ($email['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">SMTP Username</label>
                <input type="text" name="smtp_username" class="form-control" value="<?= e($email['smtp_username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">SMTP Password</label>
                <input type="password" name="smtp_password" class="form-control" placeholder="Leave blank to keep current password" autocomplete="new-password">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">From Address</label>
                    <input type="text" name="mail_from_address" class="form-control" value="<?= e($email['mail_from_address'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">From Name</label>
                    <input type="text" name="mail_from_name" class="form-control" value="<?= e($email['mail_from_name'] ?? '') ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Email Settings</button>
        </form>
    </div>
</div>
