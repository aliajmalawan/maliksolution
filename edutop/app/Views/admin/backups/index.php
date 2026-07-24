<h1 class="h4 mb-4">Backups</h1>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <form method="POST" action="<?= url('/admin/backups/database') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">Run Database Backup</button>
        </form>
    </div>
    <?php if ($isSuperAdmin): ?>
        <div class="col-md-6">
            <form method="POST" action="<?= url('/admin/backups/files') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-primary">Run Website Files Backup</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if (!$isSuperAdmin): ?>
    <div class="alert alert-info">Restoring a database backup, and creating or downloading a files backup (it contains <code>.env</code> credentials), is limited to Super Admin.</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>File</th><th>Type</th><th>Size</th><th>Created</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?= e($file['name']) ?></td>
                        <td><span class="badge <?= $file['type'] === 'database' ? 'bg-info' : 'bg-secondary' ?>"><?= e($file['type']) ?></span></td>
                        <td><?= number_format($file['size'] / 1024 / 1024, 2) ?> MB</td>
                        <td><?= date('Y-m-d H:i', $file['mtime']) ?></td>
                        <td class="text-end">
                            <?php if ($isSuperAdmin || $file['type'] === 'database'): ?>
                                <a href="<?= url('/admin/backups/download?file=' . urlencode($file['name'])) ?>" class="btn btn-sm btn-outline-secondary">Download</a>
                            <?php endif; ?>
                            <?php if ($isSuperAdmin && $file['type'] === 'database'): ?>
                                <form method="POST" action="<?= url('/admin/backups/restore') ?>" class="d-inline" data-confirm="Restore this backup? This will OVERWRITE all current data in the live database. This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="file" value="<?= e($file['name']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Restore</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="<?= url('/admin/backups/delete') ?>" class="d-inline" data-confirm="Delete this backup?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="file" value="<?= e($file['name']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($files)): ?>
                    <tr><td colspan="5" class="text-muted text-center py-4">No backups yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
