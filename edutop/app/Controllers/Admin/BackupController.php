<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DatabaseBackup;
use App\Core\Request;
use App\Core\Response;
use App\Core\Sanitizer;
use App\Models\ActivityLog;

class BackupController extends Controller
{
    private function backupsDir(): string
    {
        return DatabaseBackup::backupsDir();
    }

    public function index(Request $request): void
    {
        $dir = $this->backupsDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $files = [];
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $dir . '/' . $entry;
            if (!is_file($full)) {
                continue;
            }
            $files[] = [
                'name' => $entry,
                'type' => str_ends_with($entry, '.zip') ? 'files' : 'database',
                'size' => filesize($full),
                'mtime' => filemtime($full),
            ];
        }
        usort($files, fn($a, $b) => $b['mtime'] <=> $a['mtime']);

        $this->view('admin/backups/index', [
            'files' => $files,
            'isSuperAdmin' => (Auth::user()['role_slug'] ?? '') === 'super-admin',
        ], 'admin/layouts/main');
    }

    public function createDatabase(Request $request): void
    {
        try {
            $filename = DatabaseBackup::dump();
        } catch (\Throwable $e) {
            $this->flashError('Backup failed: ' . $e->getMessage());
            $this->redirect('/admin/backups');
        }

        $this->logAndRedirect($request, 'create', "Created database backup '{$filename}'");
    }

    /**
     * The files backup bundles .env (DB/SMTP credentials) — restricted to super-admin
     * specifically, same as restore(), rather than just the backups.manage permission.
     */
    public function createFiles(Request $request): void
    {
        $actor = Auth::user();
        if (($actor['role_slug'] ?? '') !== 'super-admin') {
            Response::abort(403, 'Only Super Admin can create a files backup.');
        }

        if (!class_exists(\ZipArchive::class)) {
            $this->flashError('The PHP zip extension is not available on this server.');
            $this->redirect('/admin/backups');
        }

        $root = dirname(__DIR__, 3);
        $filename = 'files_backup_' . date('Y-m-d_His') . '.zip';
        $dir = $this->backupsDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $zipPath = $dir . '/' . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            $this->flashError('Could not create the backup archive.');
            $this->redirect('/admin/backups');
        }

        $includePaths = ['app', 'config', 'public', 'database', 'bootstrap.php', '.htaccess', '.env'];
        foreach ($includePaths as $relative) {
            $full = $root . '/' . $relative;
            if (is_file($full)) {
                $zip->addFile($full, $relative);
            } elseif (is_dir($full)) {
                $this->addDirectoryToZip($zip, $full, $relative);
            }
        }

        $zip->close();

        $this->logAndRedirect($request, 'create', "Created files backup '{$filename}'");
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $zipPathPrefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $localPath = $zipPathPrefix . '/' . substr($file->getPathname(), strlen($dir) + 1);
            $localPath = str_replace('\\', '/', $localPath);
            if ($file->isDir()) {
                $zip->addEmptyDir($localPath);
            } else {
                $zip->addFile($file->getPathname(), $localPath);
            }
        }
    }

    public function download(Request $request): void
    {
        $filename = $this->safeFilename((string) $request->input('file', ''));
        $path = $this->backupsDir() . '/' . $filename;

        if ($filename === '' || !is_file($path)) {
            Response::abort(404, 'Backup file not found.');
        }

        // Files backups (.zip) contain .env credentials — same super-admin gate as createFiles()/restore().
        if (str_ends_with($filename, '.zip') && (Auth::user()['role_slug'] ?? '') !== 'super-admin') {
            Response::abort(403, 'Only Super Admin can download a files backup.');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function delete(Request $request): void
    {
        $filename = $this->safeFilename((string) $request->input('file', ''));
        $path = $this->backupsDir() . '/' . $filename;

        if ($filename !== '' && is_file($path)) {
            unlink($path);
            $this->logAndRedirect($request, 'delete', "Deleted backup '{$filename}'");
            return;
        }

        $this->flashError('Backup file not found.');
        $this->redirect('/admin/backups');
    }

    /**
     * Restores a database backup. Locked to the super-admin role specifically
     * (not just the backups.manage permission) — the one action in this app
     * that can destroy data.
     */
    public function restore(Request $request): void
    {
        $actor = Auth::user();
        if (($actor['role_slug'] ?? '') !== 'super-admin') {
            Response::abort(403, 'Only Super Admin can restore a database backup.');
        }

        $filename = $this->safeFilename((string) $request->input('file', ''));
        $path = $this->backupsDir() . '/' . $filename;

        if ($filename === '' || !is_file($path) || !str_ends_with($filename, '.sql')) {
            $this->flashError('Select a valid .sql backup to restore.');
            $this->redirect('/admin/backups');
        }

        try {
            DatabaseBackup::restore($path);
        } catch (\Throwable $e) {
            $this->flashError('Restore failed: ' . $e->getMessage());
            $this->redirect('/admin/backups');
        }

        $this->logAndRedirect($request, 'update', "Restored database from backup '{$filename}'");
    }

    /** basename() only — refuses any path separator, blocking directory traversal via the filename param. */
    private function safeFilename(string $name): string
    {
        $name = basename($name);
        return preg_match('/^[A-Za-z0-9._-]+$/', $name) ? $name : '';
    }

    private function logAndRedirect(Request $request, string $action, string $description): void
    {
        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, $action, 'backups', $description, $request->ip());

        $this->flashSuccess('Done.');
        $this->redirect('/admin/backups');
    }
}
