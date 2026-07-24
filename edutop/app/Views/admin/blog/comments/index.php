<h1 class="h4 mb-4">Comments <?php if ($pendingCount): ?><span class="badge bg-warning text-dark"><?= (int) $pendingCount ?> pending</span><?php endif; ?></h1>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= $statusFilter === 'pending' ? 'active' : '' ?>" href="<?= url('/admin/blog/comments?status=pending') ?>">Pending</a></li>
    <li class="nav-item"><a class="nav-link <?= $statusFilter === 'approved' ? 'active' : '' ?>" href="<?= url('/admin/blog/comments?status=approved') ?>">Approved</a></li>
    <li class="nav-item"><a class="nav-link <?= $statusFilter === 'spam' ? 'active' : '' ?>" href="<?= url('/admin/blog/comments?status=spam') ?>">Spam</a></li>
    <li class="nav-item"><a class="nav-link <?= $statusFilter === '' ? 'active' : '' ?>" href="<?= url('/admin/blog/comments?status=') ?>">All</a></li>
</ul>

<div class="card border-0 shadow-sm">
    <ul class="list-group list-group-flush">
        <?php foreach ($comments as $comment): ?>
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong><?= e($comment['author_name']) ?></strong>
                        <span class="text-muted small">&lt;<?= e($comment['author_email']) ?>&gt;</span>
                        &middot; on <a href="<?= url('/blog/' . $comment['post_slug']) ?>" target="_blank"><?= e($comment['post_title']) ?></a>
                    </div>
                    <span class="badge <?= ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'spam' => 'bg-danger'][$comment['status']] ?>"><?= e($comment['status']) ?></span>
                </div>
                <p class="mb-2 mt-2"><?= e($comment['body']) ?></p>
                <div class="text-muted small mb-2"><?= e($comment['created_at']) ?> &middot; IP <?= e($comment['ip']) ?></div>
                <div class="d-flex gap-2">
                    <?php if ($comment['status'] !== 'approved'): ?>
                        <form method="POST" action="<?= url('/admin/blog/comments/' . $comment['id'] . '/approve') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($comment['status'] !== 'spam'): ?>
                        <form method="POST" action="<?= url('/admin/blog/comments/' . $comment['id'] . '/spam') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-warning">Mark Spam</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('/admin/blog/comments/' . $comment['id'] . '/delete') ?>" data-confirm="Delete this comment?">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
        <?php if (empty($comments)): ?>
            <li class="list-group-item text-muted text-center py-4">No comments here.</li>
        <?php endif; ?>
    </ul>
</div>
