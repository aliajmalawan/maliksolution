<h1 class="h4 mb-4">Site Settings</h1>

<?php include __DIR__ . '/_tabs.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/settings/system') ?>">
            <?= csrf_field() ?>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="maintenanceMode" name="maintenance_mode" value="1" <?= ($system['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="maintenanceMode">Maintenance Mode</label>
                <div class="form-text">Shows the "Coming Soon" page to visitors (HTTP 503) while logged-in admins keep seeing the real site. Edit the <a href="<?= url('/admin/pages') ?>">Coming Soon page</a> to customize the message.</div>
            </div>

            <hr>
            <div class="mb-3">
                <label class="form-label">Custom API Keys / Notes</label>
                <textarea name="custom_api_notes" class="form-control font-monospace" rows="6" placeholder="key=value&#10;another_key=value"><?= e($system['custom_api_notes'] ?? '') ?></textarea>
                <div class="form-text">Freeform storage for any API keys or credentials you want to keep on hand — not wired to a specific integration.</div>
            </div>

            <button type="submit" class="btn btn-primary">Save System Settings</button>
        </form>
    </div>
</div>
