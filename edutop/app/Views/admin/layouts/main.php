<?php
$adminCompany = \App\Models\Setting::group('company');
$adminSiteName = $adminCompany['site_name'] ?? 'EduTop';
// Favicon falls back to the site logo when no dedicated favicon is set.
$adminFavicon = !empty($adminCompany['favicon']) ? media_url($adminCompany['favicon'])
    : (!empty($adminCompany['logo']) ? media_url($adminCompany['logo']) : null);
$adminLogo = !empty($adminCompany['logo']) ? media_url($adminCompany['logo']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminSiteName) ?> Admin</title>
    <?php if ($adminFavicon): ?><link rel="icon" href="<?= e($adminFavicon) ?>"><?php endif; ?>
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php $adminCssPath = dirname(__DIR__, 4) . '/public/assets/admin/css/custom.css'; ?>
    <link href="<?= asset('admin/css/custom.css') ?>?v=<?= is_file($adminCssPath) ? filemtime($adminCssPath) : '1' ?>" rel="stylesheet">
    <style>
        body { background: #f1f5f9; }
        /* AlpineSchool-style layout: sidebar and topbar stay fixed while only
           the page body scrolls. The sidebar scrolls its own nav if it's taller
           than the viewport. */
        .sidebar { background: #0f172a; flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar .nav-link { color: #cbd5e1; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); border-radius: .375rem; }
        .sidebar-brand { color: #fff; }
        .sidebar-brand-logo { max-height: 34px; width: auto; border-radius: 6px; background: #fff; padding: 2px; }
        .admin-topbar { position: sticky; top: 0; z-index: 100; }
    </style>
</head>
<body>
<?php $user = current_user(); ?>
<div class="d-flex">
    <nav class="sidebar p-3" style="width: 240px;">
        <div class="sidebar-brand d-flex align-items-center gap-2 fs-5 fw-bold mb-4 px-2">
            <?php if ($adminLogo): ?>
                <img src="<?= e($adminLogo) ?>" alt="<?= e($adminSiteName) ?>" class="sidebar-brand-logo">
            <?php endif; ?>
            <span><?= e($adminSiteName) ?> Admin</span>
        </div>
        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link" href="<?= url('/admin/dashboard') ?>">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('/admin/analytics') ?>">Analytics</a></li>
            <?php if (can('users.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/users') ?>">Users</a></li>
            <?php endif; ?>
            <?php if (can('roles.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/roles') ?>">Roles &amp; Permissions</a></li>
            <?php endif; ?>
            <?php if (can('pages.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/pages') ?>">Pages</a></li>
            <?php endif; ?>
            <?php if (can('media.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/media') ?>">Media Library</a></li>
            <?php endif; ?>
            <?php if (can('pages.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/menus/primary/edit') ?>">Menus</a></li>
            <?php endif; ?>
            <?php if (can('blog.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/blog/posts') ?>">Blog Posts</a></li>
                <li class="nav-item"><a class="nav-link small" href="<?= url('/admin/blog/categories') ?>">&nbsp;&nbsp;Categories</a></li>
                <li class="nav-item"><a class="nav-link small" href="<?= url('/admin/blog/tags') ?>">&nbsp;&nbsp;Tags</a></li>
                <li class="nav-item"><a class="nav-link small" href="<?= url('/admin/blog/comments') ?>">&nbsp;&nbsp;Comments</a></li>
            <?php endif; ?>
            <?php if (can('leads.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/leads') ?>">Leads</a></li>
            <?php endif; ?>
            <?php if (can('files.manage')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/files') ?>">File Manager</a></li>
            <?php endif; ?>
            <?php if (can('backups.manage')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/backups') ?>">Backups</a></li>
            <?php endif; ?>
            <?php if (can('logs.view')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/logs/activity') ?>">Activity Log</a></li>
                <li class="nav-item"><a class="nav-link small" href="<?= url('/admin/logs/logins') ?>">&nbsp;&nbsp;Login History</a></li>
            <?php endif; ?>
            <?php if (can('settings.manage')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('/admin/settings') ?>">Settings</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="<?= url('/admin/profile') ?>">My Profile</a></li>
        </ul>
    </nav>
    <div class="flex-fill" style="min-width: 0;">
        <nav class="navbar navbar-light bg-white border-bottom px-4 admin-topbar">
            <span class="navbar-text">Signed in as <strong><?= e($user['name'] ?? '') ?></strong> (<?= e($user['role_name'] ?? '') ?>)</span>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>View Site
                </a>
                <form method="POST" action="<?= url('/admin/logout') ?>" class="mb-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Logout</button>
                </form>
            </div>
        </nav>
        <main class="p-4">
            <?php if ($success = flash('_flash_success')): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error = flash('_flash_error')): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>

<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Media</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="px-3 pt-3">
                <label class="btn btn-sm btn-primary mb-0">
                    Upload New Image
                    <input type="file" id="mediaPickerUploadInput" class="d-none" accept="image/*,application/pdf,video/*" multiple data-upload-url="<?= e(url('/admin/media/upload')) ?>">
                </label>
                <span id="mediaPickerUploadStatus" class="text-muted small ms-2"></span>
            </div>
            <div class="modal-body" id="mediaPickerBody">
                <div class="text-muted">Loading&hellip;</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<?php $adminJsPath = dirname(__DIR__, 4) . '/public/assets/admin/js/admin.js'; ?>
<script src="<?= asset('admin/js/admin.js') ?>?v=<?= is_file($adminJsPath) ? filemtime($adminJsPath) : '1' ?>"></script>
</body>
</html>
