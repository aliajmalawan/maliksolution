<h1 class="h4 mb-4">Site Settings</h1>

<?php include __DIR__ . '/_tabs.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/settings/theme') ?>">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Primary Color</label>
                    <input type="color" name="primary_color" class="form-control form-control-color" value="<?= e($theme['primary_color'] ?? '#1F7A4D') ?>">
                    <div class="form-text">Buttons, links, key accents.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Secondary Color</label>
                    <input type="color" name="secondary_color" class="form-control form-control-color" value="<?= e($theme['secondary_color'] ?? '#D4AF37') ?>">
                    <div class="form-text">Highlights, badges, hover accents.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Page Background</label>
                    <input type="color" name="background_color" class="form-control form-control-color" value="<?= e($theme['background_color'] ?? '#F4F6FA') ?>">
                    <div class="form-text">Behind everything.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Card Background</label>
                    <input type="color" name="card_color" class="form-control form-control-color" value="<?= e($theme['card_color'] ?? '#FFFFFF') ?>">
                    <div class="form-text">Cards, panels, boxes.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Heading Color</label>
                    <input type="color" name="heading_color" class="form-control form-control-color" value="<?= e($theme['heading_color'] ?? '#1A1A1A') ?>">
                    <div class="form-text">Titles, headings.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Body Text Color</label>
                    <input type="color" name="text_color" class="form-control form-control-color" value="<?= e($theme['text_color'] ?? '#6B7280') ?>">
                    <div class="form-text">Paragraphs, muted text.</div>
                </div>
            </div>
            <p class="text-muted small">Applied across the entire public site.</p>

            <button type="submit" class="btn btn-primary">Save Theme</button>
        </form>
    </div>
</div>
