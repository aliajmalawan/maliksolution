<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Blog Posts</h1>
    <?php if (can('blog.manage')): ?>
        <a href="<?= url('/admin/blog/posts/create') ?>" class="btn btn-primary btn-sm">Add Post</a>
    <?php endif; ?>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $statusFilter === '' ? 'active' : '' ?>" href="<?= url('/admin/blog/posts') ?>">All</a></li>
    <li class="nav-item"><a class="nav-link <?= $statusFilter === 'published' ? 'active' : '' ?>" href="<?= url('/admin/blog/posts?status=published') ?>">Published</a></li>
    <li class="nav-item"><a class="nav-link <?= $statusFilter === 'scheduled' ? 'active' : '' ?>" href="<?= url('/admin/blog/posts?status=scheduled') ?>">Scheduled</a></li>
    <li class="nav-item"><a class="nav-link <?= $statusFilter === 'draft' ? 'active' : '' ?>" href="<?= url('/admin/blog/posts?status=draft') ?>">Draft</a></li>
</ul>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?= e($post['title']) ?></td>
                        <td><?= e($post['author_name'] ?? 'Unknown') ?></td>
                        <td>
                            <span class="badge <?= ['published' => 'bg-success', 'scheduled' => 'bg-info', 'draft' => 'bg-secondary'][$post['status']] ?>">
                                <?= e($post['status']) ?>
                            </span>
                        </td>
                        <td><?= e($post['published_at'] ?? $post['scheduled_at'] ?? $post['created_at']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('/admin/blog/posts/' . $post['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <?php if ($post['status'] === 'published'): ?>
                                <a href="<?= url('/blog/' . $post['slug']) ?>" class="btn btn-sm btn-outline-dark" target="_blank">View</a>
                            <?php endif; ?>
                            <?php if (can('blog.manage')): ?>
                                <form method="POST" action="<?= url('/admin/blog/posts/' . $post['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this post?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($posts)): ?>
                    <tr><td colspan="5" class="text-muted text-center py-4">No posts yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
