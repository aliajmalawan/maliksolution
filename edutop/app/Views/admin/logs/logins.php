<h1 class="h4 mb-4">Login History</h1>

<form method="GET" action="<?= url('/admin/logs/logins') ?>" class="d-flex gap-2 mb-3" style="max-width:300px;">
    <select name="status" class="form-select" data-auto-submit-select>
        <option value="">All</option>
        <option value="success" <?= $statusFilter === 'success' ? 'selected' : '' ?>>Success</option>
        <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>
</form>

<div class="card border-0 shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Email</th><th>IP</th><th>User Agent</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($logins as $entry): ?>
                    <tr>
                        <td><?= e($entry['email']) ?></td>
                        <td><?= e($entry['ip']) ?></td>
                        <td class="text-truncate small text-muted" style="max-width:250px;"><?= e($entry['user_agent']) ?></td>
                        <td><span class="badge <?= $entry['status'] === 'success' ? 'bg-success' : 'bg-danger' ?>"><?= e($entry['status']) ?></span></td>
                        <td><?= e($entry['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logins)): ?><tr><td colspan="5" class="text-muted text-center py-4">No login history found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php $qs = http_build_query(array_filter(['status' => $statusFilter, 'page' => $p])); ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= url('/admin/logs/logins?' . $qs) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
