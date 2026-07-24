<h1 class="h4 mb-4">Activity Log</h1>

<form method="GET" action="<?= url('/admin/logs/activity') ?>" class="d-flex gap-2 mb-3" style="max-width:300px;">
    <select name="module" class="form-select" data-auto-submit-select>
        <option value="">All Modules</option>
        <?php foreach ($modules as $m): ?>
            <option value="<?= e($m) ?>" <?= $moduleFilter === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card border-0 shadow-sm mb-3">
    <ul class="list-group list-group-flush">
        <?php foreach ($logs as $entry): ?>
            <li class="list-group-item">
                <div><?= e($entry['description']) ?></div>
                <div class="text-muted small"><?= e($entry['user_name'] ?? 'System') ?> &middot; <span class="badge bg-light text-dark"><?= e($entry['module']) ?></span> &middot; <?= e($entry['created_at']) ?> &middot; <?= e($entry['ip']) ?></div>
            </li>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?><li class="list-group-item text-muted text-center py-4">No activity found.</li><?php endif; ?>
    </ul>
</div>

<?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php $qs = http_build_query(array_filter(['module' => $moduleFilter, 'page' => $p])); ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= url('/admin/logs/activity?' . $qs) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
