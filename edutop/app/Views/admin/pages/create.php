<h1 class="h4 mb-4">Add Page</h1>

<div class="card border-0 shadow-sm" style="max-width: 560px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/pages') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug (optional — derived from title if left blank)</label>
                <input type="text" name="slug" class="form-control" placeholder="e.g. about-us">
            </div>
            <button type="submit" class="btn btn-primary">Create Page</button>
            <a href="<?= url('/admin/pages') ?>" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
