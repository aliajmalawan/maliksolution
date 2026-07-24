<h1 class="h4 mb-4">Site Settings</h1>

<?php include __DIR__ . '/_tabs.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/settings') ?>">
            <?= csrf_field() ?>

            <h6 class="text-uppercase text-muted small fw-bold">Company Info</h6>
            <div class="mb-3">
                <label class="form-label">Site Name</label>
                <input type="text" name="site_name" class="form-control" value="<?= e($company['site_name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Tagline</label>
                <input type="text" name="tagline" class="form-control" value="<?= e($company['tagline'] ?? '') ?>">
            </div>
            <?= \App\Core\SectionForm::renderStandaloneMedia('logo', 'Logo', $company['logo'] ?? null, '300×100px') ?>
            <?= \App\Core\SectionForm::renderStandaloneMedia('favicon', 'Favicon', $company['favicon'] ?? null, '512×512px') ?>

            <hr>
            <h6 class="text-uppercase text-muted small fw-bold">Contact Details</h6>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e($contact['phone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="text" name="email" class="form-control" value="<?= e($contact['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= e($contact['address'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">WhatsApp Number</label>
                <input type="text" name="whatsapp" class="form-control" value="<?= e($contact['whatsapp'] ?? '') ?>">
            </div>

            <hr>
            <h6 class="text-uppercase text-muted small fw-bold">Social Links</h6>
            <div class="form-text mb-3">The footer always shows every link filled in below. "Show in header" controls the smaller icon row in the site header/topbar separately, so you can keep a link in the footer without cluttering the header.</div>
            <?php foreach (['facebook' => 'Facebook', 'twitter' => 'Twitter / X', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'youtube' => 'YouTube'] as $key => $label): ?>
                <div class="mb-3">
                    <label class="form-label"><?= e($label) ?></label>
                    <input type="text" name="<?= e($key) ?>" class="form-control" value="<?= e($social[$key] ?? '') ?>" placeholder="https://...">
                    <div class="form-check mt-1">
                        <input type="checkbox" class="form-check-input" id="<?= e($key) ?>_header" name="<?= e($key) ?>_header" value="1" <?= ($social[$key . '_header'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="<?= e($key) ?>_header">Show in header</label>
                    </div>
                </div>
            <?php endforeach; ?>

            <hr>
            <h6 class="text-uppercase text-muted small fw-bold">Footer</h6>
            <div class="mb-3">
                <label class="form-label">Copyright Text</label>
                <input type="text" name="copyright_text" class="form-control" value="<?= e($footer['copyright_text'] ?? '') ?>" placeholder="&copy; 2026 EduTop. All rights reserved.">
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>
