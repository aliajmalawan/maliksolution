<h1 class="h4 mb-4">Site Settings</h1>

<?php include __DIR__ . '/_tabs.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 640px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/settings/integrations') ?>">
            <?= csrf_field() ?>

            <h6 class="text-uppercase text-muted small fw-bold">Analytics</h6>
            <div class="mb-3">
                <label class="form-label">Google Analytics Measurement ID</label>
                <input type="text" name="google_analytics_id" class="form-control" value="<?= e($integrations['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
            </div>
            <div class="mb-3">
                <label class="form-label">Google Tag Manager ID</label>
                <input type="text" name="google_tag_manager_id" class="form-control" value="<?= e($integrations['google_tag_manager_id'] ?? '') ?>" placeholder="GTM-XXXXXXX">
            </div>
            <div class="mb-3">
                <label class="form-label">Facebook Pixel ID</label>
                <input type="text" name="facebook_pixel_id" class="form-control" value="<?= e($integrations['facebook_pixel_id'] ?? '') ?>">
            </div>

            <hr>
            <h6 class="text-uppercase text-muted small fw-bold">reCAPTCHA</h6>
            <p class="text-muted small">When both keys are set, reCAPTCHA v2 appears on the Contact form, Campus Visit modal, and blog comment form. Leave blank to disable.</p>
            <div class="mb-3">
                <label class="form-label">Site Key</label>
                <input type="text" name="recaptcha_site_key" class="form-control" value="<?= e($integrations['recaptcha_site_key'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Secret Key</label>
                <input type="text" name="recaptcha_secret_key" class="form-control" value="<?= e($integrations['recaptcha_secret_key'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary">Save Integration Settings</button>
        </form>
    </div>
</div>
