<h1 class="h4 mb-4">Blog Tags</h1>

<div class="row g-4">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($tags as $tag): ?>
                            <tr>
                                <td><?= e($tag['name']) ?></td>
                                <td><code><?= e($tag['slug']) ?></code></td>
                                <td class="text-end">
                                    <?php if (can('blog.manage')): ?>
                                        <form method="POST" action="<?= url('/admin/blog/tags/' . $tag['id'] . '/delete') ?>" data-confirm="Delete this tag?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tags)): ?><tr><td colspan="3" class="text-muted text-center py-3">No tags yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php if (can('blog.manage')): ?>
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Add Tag</h6>
                    <form method="POST" action="<?= url('/admin/blog/tags') ?>" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="text" name="name" class="form-control" placeholder="Tag name" required>
                        <button class="btn btn-primary text-nowrap">Add</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
