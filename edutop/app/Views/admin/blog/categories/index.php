<h1 class="h4 mb-4">Blog Categories</h1>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= e($cat['name']) ?></td>
                                <td><code><?= e($cat['slug']) ?></code></td>
                                <td class="text-end">
                                    <?php if (can('blog.manage')): ?>
                                        <form method="POST" action="<?= url('/admin/blog/categories/' . $cat['id'] . '/delete') ?>" data-confirm="Delete this category?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?><tr><td colspan="3" class="text-muted text-center py-3">No categories yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php if (can('blog.manage')): ?>
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Add Category</h6>
                    <form method="POST" action="<?= url('/admin/blog/categories') ?>" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="text" name="name" class="form-control" placeholder="Category name" required>
                        <button class="btn btn-primary text-nowrap">Add</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
