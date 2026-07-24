<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Sanitizer;
use App\Models\ActivityLog;

/**
 * Sandboxed to storage/files/ — outside the public webroot (storage/.htaccess
 * already denies all direct access), separate from the Media Library. Files
 * are never directly web-accessible; downloads stream through this
 * authenticated, RBAC-gated controller. Every path is validated to stay
 * contained within the root before any filesystem operation.
 */
class FileManagerController extends Controller
{
    private const DENIED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'phar',
        'exe', 'sh', 'bat', 'cmd', 'com', 'msi', 'dll', 'cgi', 'pl',
        'htaccess', 'htpasswd', 'jsp', 'asp', 'aspx', 'vbs', 'ps1',
    ];

    private const MAX_BYTES = 50 * 1024 * 1024; // 50MB

    private function rootDir(): string
    {
        $root = dirname(__DIR__, 3) . '/storage/files';
        if (!is_dir($root)) {
            mkdir($root, 0755, true);
        }
        return realpath($root);
    }

    /** Resolves and validates a relative path against the sandbox root. Must already exist. */
    private function resolveExisting(string $relative): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        $root = $this->rootDir();

        if ($relative === '') {
            return $root;
        }

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '..' || $segment === '.' || $segment === '') {
                throw new \RuntimeException('Invalid path.');
            }
        }

        $real = realpath($root . '/' . $relative);
        if ($real === false || !str_starts_with($real, $root)) {
            throw new \RuntimeException('Invalid path.');
        }

        return $real;
    }

    private function relativePath(string $absolute): string
    {
        $root = $this->rootDir();
        $rel = substr($absolute, strlen($root));
        return ltrim(str_replace('\\', '/', $rel), '/');
    }

    public function index(Request $request): void
    {
        try {
            $currentDir = $this->resolveExisting((string) $request->input('path', ''));
        } catch (\RuntimeException $e) {
            $this->flashError('Invalid path.');
            $currentDir = $this->rootDir();
        }

        if (!is_dir($currentDir)) {
            $currentDir = $this->rootDir();
        }

        $entries = scandir($currentDir) ?: [];
        $folders = [];
        $files = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $currentDir . '/' . $entry;
            $item = [
                'name' => $entry,
                'path' => $this->relativePath($full),
                'mtime' => filemtime($full),
            ];
            if (is_dir($full)) {
                $folders[] = $item;
            } else {
                $item['size'] = filesize($full);
                $files[] = $item;
            }
        }

        usort($folders, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        $currentRelative = $this->relativePath($currentDir);
        $breadcrumb = [];
        if ($currentRelative !== '') {
            $parts = explode('/', $currentRelative);
            $accum = '';
            foreach ($parts as $part) {
                $accum = $accum === '' ? $part : $accum . '/' . $part;
                $breadcrumb[] = ['name' => $part, 'path' => $accum];
            }
        }

        $this->view('admin/files/index', [
            'folders' => $folders,
            'files' => $files,
            'currentPath' => $currentRelative,
            'breadcrumb' => $breadcrumb,
        ], 'admin/layouts/main');
    }

    public function upload(Request $request): void
    {
        try {
            $currentDir = $this->resolveExisting((string) $request->input('path', ''));
        } catch (\RuntimeException $e) {
            $this->flashError('Invalid path.');
            $this->redirect('/admin/files');
        }

        $file = $request->file('file');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->flashError('No file provided.');
            $this->redirect('/admin/files?path=' . urlencode($this->relativePath($currentDir)));
        }

        if ($file['size'] > self::MAX_BYTES) {
            $this->flashError('File exceeds the 50MB limit.');
            $this->redirect('/admin/files?path=' . urlencode($this->relativePath($currentDir)));
        }

        $safeName = Sanitizer::filename($file['name']);
        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

        if (in_array($extension, self::DENIED_EXTENSIONS, true)) {
            $this->flashError('That file type is not allowed.');
            $this->redirect('/admin/files?path=' . urlencode($this->relativePath($currentDir)));
        }

        $destination = $currentDir . '/' . $safeName;
        if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $destination)) {
            $this->flashError('Could not save the uploaded file.');
            $this->redirect('/admin/files?path=' . urlencode($this->relativePath($currentDir)));
        }

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'create', 'files', "Uploaded file '{$safeName}'", $request->ip());

        $this->flashSuccess('File uploaded.');
        $this->redirect('/admin/files?path=' . urlencode($this->relativePath($currentDir)));
    }

    public function createFolder(Request $request): void
    {
        try {
            $currentDir = $this->resolveExisting((string) $request->input('path', ''));
        } catch (\RuntimeException $e) {
            $this->flashError('Invalid path.');
            $this->redirect('/admin/files');
        }

        $rawName = trim((string) $request->input('name'));
        if ($rawName === '') {
            $this->flashError('Folder name is required.');
            $this->redirect('/admin/files?path=' . urlencode($this->relativePath($currentDir)));
        }
        $name = Sanitizer::filename($rawName);

        $target = $currentDir . '/' . $name;
        if (is_dir($target) || is_file($target)) {
            $this->flashError('An item with that name already exists.');
        } else {
            mkdir($target, 0755);
            $this->flashSuccess('Folder created.');
        }

        $this->redirect('/admin/files?path=' . urlencode($this->relativePath($currentDir)));
    }

    public function rename(Request $request): void
    {
        try {
            $target = $this->resolveExisting((string) $request->input('path', ''));
        } catch (\RuntimeException $e) {
            $this->flashError('Invalid path.');
            $this->redirect('/admin/files');
        }

        $newName = Sanitizer::filename((string) $request->input('new_name'));
        $parentDir = dirname($target);
        $parentRelative = $this->relativePath($parentDir);
        $newPath = $parentDir . '/' . $newName;

        if ($newName === '' || is_dir($newPath) || is_file($newPath)) {
            $this->flashError('Invalid or duplicate name.');
        } else {
            rename($target, $newPath);
            $actor = Auth::user();
            ActivityLog::record($actor['id'] ?? null, 'update', 'files', "Renamed to '{$newName}'", $request->ip());
            $this->flashSuccess('Renamed.');
        }

        $this->redirect('/admin/files?path=' . urlencode($parentRelative));
    }

    public function delete(Request $request): void
    {
        try {
            $target = $this->resolveExisting((string) $request->input('path', ''));
        } catch (\RuntimeException $e) {
            $this->flashError('Invalid path.');
            $this->redirect('/admin/files');
        }

        if ($target === $this->rootDir()) {
            $this->flashError('Cannot delete the root folder.');
            $this->redirect('/admin/files');
        }

        $parentRelative = $this->relativePath(dirname($target));

        if (is_dir($target)) {
            $this->deleteDirectoryRecursive($target);
        } else {
            unlink($target);
        }

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'files', 'Deleted ' . basename($target), $request->ip());

        $this->flashSuccess('Deleted.');
        $this->redirect('/admin/files?path=' . urlencode($parentRelative));
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function download(Request $request): void
    {
        try {
            $target = $this->resolveExisting((string) $request->input('path', ''));
        } catch (\RuntimeException $e) {
            Response::abort(404, 'File not found.');
        }

        if (!is_file($target)) {
            Response::abort(404, 'File not found.');
        }

        $filename = Sanitizer::filename(basename($target));
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($target) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($target));
        header('X-Content-Type-Options: nosniff');
        readfile($target);
        exit;
    }
}
