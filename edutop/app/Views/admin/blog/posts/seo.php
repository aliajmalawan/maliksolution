<h1 class="h4 mb-4">SEO &mdash; <?= e($post['title']) ?></h1>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="<?= url('/admin/blog/posts/' . $post['id'] . '/edit') ?>">Content</a></li>
    <li class="nav-item"><a class="nav-link active" href="<?= url('/admin/blog/posts/' . $post['id'] . '/seo') ?>">SEO</a></li>
</ul>

<div class="card border-0 shadow-sm" style="max-width: 720px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/blog/posts/' . $post['id'] . '/seo') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">SEO Title</label>
                <input type="text" name="seo_title" class="form-control" value="<?= e($seo['seo_title'] ?? '') ?>" placeholder="<?= e($post['title']) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Meta Description</label>
                <textarea name="meta_description" class="form-control" rows="2"><?= e($seo['meta_description'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Meta Keywords</label>
                <input type="text" name="meta_keywords" class="form-control" value="<?= e($seo['meta_keywords'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Canonical URL</label>
                <input type="text" name="canonical_url" class="form-control" value="<?= e($seo['canonical_url'] ?? '') ?>">
            </div>

            <hr>
            <h6 class="text-uppercase text-muted small fw-bold">Open Graph / Social</h6>
            <div class="mb-3">
                <label class="form-label">OG Title</label>
                <input type="text" name="og_title" class="form-control" value="<?= e($seo['og_title'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">OG Description</label>
                <textarea name="og_description" class="form-control" rows="2"><?= e($seo['og_description'] ?? '') ?></textarea>
            </div>
            <?= \App\Core\SectionForm::renderStandaloneMedia('og_image', 'OG Image', $seo['og_image'] ?? null, '1200×630px') ?>

            <div class="mb-3">
                <label class="form-label">Twitter Card</label>
                <select name="twitter_card" class="form-select">
                    <option value="summary_large_image" <?= ($seo['twitter_card'] ?? '') === 'summary_large_image' ? 'selected' : '' ?>>Summary Large Image</option>
                    <option value="summary" <?= ($seo['twitter_card'] ?? '') === 'summary' ? 'selected' : '' ?>>Summary</option>
                </select>
            </div>

            <hr>
            <div class="mb-3">
                <label class="form-label">Robots</label>
                <select name="robots" class="form-select">
                    <option value="index,follow" <?= ($seo['robots'] ?? 'index,follow') === 'index,follow' ? 'selected' : '' ?>>index, follow</option>
                    <option value="noindex,follow" <?= ($seo['robots'] ?? '') === 'noindex,follow' ? 'selected' : '' ?>>noindex, follow</option>
                    <option value="index,nofollow" <?= ($seo['robots'] ?? '') === 'index,nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                    <option value="noindex,nofollow" <?= ($seo['robots'] ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Schema Markup (JSON-LD, optional)</label>
                <textarea name="schema_markup" class="form-control font-monospace" rows="4"><?= e($seo['schema_markup'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Save SEO Settings</button>
        </form>
    </div>
</div>
