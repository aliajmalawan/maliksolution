<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Media Library<?= $currentFolder ? ' — ' . e($currentFolder['name']) : '' ?></h1>
    <form method="GET" action="<?= url('/admin/media') ?>" class="d-flex gap-2">
        <?php if ($currentFolderId): ?><input type="hidden" name="folder" value="<?= (int) $currentFolderId ?>"><?php endif; ?>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search files..." value="<?= e($search) ?>">
        <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold small text-uppercase text-muted">Folders</h6>
                <ul class="list-unstyled mb-3">
                    <li><a href="<?= url('/admin/media') ?>" class="<?= !$currentFolderId ? 'fw-bold' : '' ?>">All Files</a></li>
                    <?php foreach ($allFolders as $folder): ?>
                        <li class="d-flex justify-content-between align-items-center">
                            <a href="<?= url('/admin/media?folder=' . $folder['id']) ?>" class="<?= $currentFolderId == $folder['id'] ? 'fw-bold' : '' ?>"><?= e($folder['name']) ?></a>
                            <?php if (can('media.manage')): ?>
                                <form method="POST" action="<?= url('/admin/media/folders/' . $folder['id'] . '/delete') ?>" data-confirm="Delete this folder? Files inside will move to All Files.">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">&times;</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (can('media.manage')): ?>
                    <form method="POST" action="<?= url('/admin/media/folders') ?>" class="d-flex gap-1">
                        <?= csrf_field() ?>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="New folder">
                        <button class="btn btn-sm btn-outline-primary">+</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php if (can('media.manage')): ?>
            <form id="mediaUploadForm" method="POST" action="<?= url('/admin/media/upload') ?>" enctype="multipart/form-data" class="mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="folder_id" value="<?= $currentFolderId ? (int) $currentFolderId : '' ?>">
                <div id="mediaDropzone" class="border border-2 border-dashed rounded-3 text-center py-5 text-muted" style="cursor:pointer;">
                    Drag &amp; drop files here, or click to choose files.<br>
                    <small>Images, PDFs, documents, and video — max 25MB each.</small>
                </div>
                <input type="file" id="mediaFileInput" name="files[]" multiple class="d-none">
            </form>
        <?php endif; ?>

        <div class="row g-3">
            <?php if (empty($items)): ?>
                <p class="text-muted">No files here yet.</p>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <?php $itemUrl = url('/public/uploads' . $item['path']); ?>
                <div class="col-6 col-md-4 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="position-relative d-flex align-items-center justify-content-center bg-light" style="height:120px;overflow:hidden;">
                            <?php if ($item['is_hidden']): ?>
                                <span class="badge bg-secondary position-absolute top-0 start-0 m-1">Hidden</span>
                            <?php endif; ?>
                            <?php if ($item['type'] === 'image'): ?>
                                <img src="<?= e($itemUrl) ?>" style="max-width:100%;max-height:100%;object-fit:cover;" alt="<?= e($item['original_name']) ?>">
                            <?php else: ?>
                                <span class="text-muted text-uppercase small fw-bold"><?= e($item['type']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-2">
                            <div class="text-truncate small mb-2" title="<?= e($item['original_name']) ?>"><?= e($item['original_name']) ?></div>
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-url="<?= e($itemUrl) ?>">Copy URL</button>
                                <?php if (can('media.manage')): ?>
                                    <form method="POST" action="<?= url('/admin/media/' . $item['id'] . '/replace') ?>" enctype="multipart/form-data" class="d-inline">
                                        <?= csrf_field() ?>
                                        <label class="btn btn-sm btn-outline-secondary mb-0">
                                            Replace
                                            <input type="file" class="d-none" name="file" data-auto-submit>
                                        </label>
                                    </form>
                                    <form method="POST" action="<?= url('/admin/media/' . $item['id'] . '/toggle-hidden') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><?= $item['is_hidden'] ? 'Show' : 'Hide' ?></button>
                                    </form>
                                    <form method="POST" action="<?= url('/admin/media/' . $item['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this file?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php if ($item['type'] === 'image' && can('media.manage')): ?>
                                <form method="POST" action="<?= url('/admin/media/' . $item['id'] . '/resize') ?>" class="d-flex align-items-center flex-wrap gap-1" data-confirm="Resize this image to the exact size given? This replaces the original file.">
                                    <?= csrf_field() ?>
                                    <input type="number" name="width" class="form-control form-control-sm" placeholder="W" min="16" max="4000" style="width:56px;" required>
                                    <span class="small text-muted">&times;</span>
                                    <input type="number" name="height" class="form-control form-control-sm" placeholder="H" min="16" max="4000" style="width:56px;" required>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Resize</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
