<h1 class="h4 mb-4">File Manager</h1>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= url('/admin/files') ?>">Root</a></li>
        <?php foreach ($breadcrumb as $crumb): ?>
            <li class="breadcrumb-item"><a href="<?= url('/admin/files?path=' . urlencode($crumb['path'])) ?>"><?= e($crumb['name']) ?></a></li>
        <?php endforeach; ?>
    </ol>
</nav>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <form method="POST" action="<?= url('/admin/files/upload') ?>" enctype="multipart/form-data" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="path" value="<?= e($currentPath) ?>">
            <input type="file" name="file" class="form-control" required>
            <button class="btn btn-primary text-nowrap">Upload</button>
        </form>
        <div class="form-text">Max 50MB. Executable/script file types are blocked.</div>
    </div>
    <div class="col-md-6">
        <form method="POST" action="<?= url('/admin/files/folder') ?>" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="path" value="<?= e($currentPath) ?>">
            <input type="text" name="name" class="form-control" placeholder="New folder name" required>
            <button class="btn btn-outline-primary text-nowrap">Create Folder</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Name</th><th>Size</th><th>Modified</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($folders as $folder): ?>
                    <tr>
                        <td><a href="<?= url('/admin/files?path=' . urlencode($folder['path'])) ?>">&#128193; <?= e($folder['name']) ?></a></td>
                        <td class="text-muted">&mdash;</td>
                        <td class="text-muted small"><?= date('Y-m-d H:i', $folder['mtime']) ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-rename-trigger data-path="<?= e($folder['path']) ?>" data-name="<?= e($folder['name']) ?>">Rename</button>
                            <form method="POST" action="<?= url('/admin/files/delete') ?>" class="d-inline" data-confirm="Delete this folder and everything inside it?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="path" value="<?= e($folder['path']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td>&#128196; <?= e($file['name']) ?></td>
                        <td class="text-muted small"><?= number_format($file['size'] / 1024, 1) ?> KB</td>
                        <td class="text-muted small"><?= date('Y-m-d H:i', $file['mtime']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('/admin/files/download?path=' . urlencode($file['path'])) ?>" class="btn btn-sm btn-outline-secondary">Download</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-rename-trigger data-path="<?= e($file['path']) ?>" data-name="<?= e($file['name']) ?>">Rename</button>
                            <form method="POST" action="<?= url('/admin/files/delete') ?>" class="d-inline" data-confirm="Delete this file?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="path" value="<?= e($file['path']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($folders) && empty($files)): ?>
                    <tr><td colspan="4" class="text-muted text-center py-4">This folder is empty.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="<?= url('/admin/files/rename') ?>" id="renameForm" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="path" id="renamePath">
    <input type="text" name="new_name" id="renameNewName">
</form>
