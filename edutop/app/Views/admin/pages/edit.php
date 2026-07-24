<h1 class="h4 mb-4">Edit Page &mdash; <?= e($page['title']) ?></h1>

<?php include __DIR__ . '/_tabs.php'; ?>

<div class="card border-0 shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/pages/' . $page['id']) ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($page['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <div class="input-group">
                    <span class="input-group-text">/</span>
                    <input type="text" name="slug" class="form-control" value="<?= e($page['slug']) ?>" required <?= $page['is_home'] ? 'readonly' : '' ?>>
                </div>
                <?php if ($page['is_home']): ?><div class="form-text">The homepage's slug is fixed.</div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
