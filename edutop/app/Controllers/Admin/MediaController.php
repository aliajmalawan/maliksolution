<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\MediaCompressor;
use App\Core\MediaResizer;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Core\Uploader;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\MediaFolder;

class MediaController extends Controller
{
    public function index(Request $request): void
    {
        $folderId = $request->input('folder') !== null && $request->input('folder') !== '' ? (int) $request->input('folder') : null;
        $search = trim((string) $request->input('q', ''));

        $items = $search !== '' ? Media::search($search) : Media::inFolder($folderId);

        $this->view('admin/media/index', [
            'items' => $items,
            'folders' => MediaFolder::childrenOf($folderId),
            'allFolders' => MediaFolder::all(),
            'currentFolderId' => $folderId,
            'currentFolder' => $folderId ? MediaFolder::find($folderId) : null,
            'search' => $search,
        ], 'admin/layouts/main');
    }

    public function picker(Request $request): void
    {
        $folderId = $request->input('folder') !== null && $request->input('folder') !== '' ? (int) $request->input('folder') : null;
        $search = trim((string) $request->input('q', ''));

        // Hidden media stays out of the picker so it can't be newly placed
        // into pages/posts/settings, without deleting the file or breaking
        // anywhere it's already used.
        $items = $search !== '' ? Media::search($search, true) : Media::inFolder($folderId, '', true);

        $this->view('admin/media/picker_grid', [
            'items' => $items,
            'folders' => MediaFolder::childrenOf($folderId),
            'currentFolderId' => $folderId,
        ]);
    }

    public function upload(Request $request): void
    {
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        // A field that's entirely absent from the request (e.g. the picker-modal
        // upload, which has no folder context) must be treated the same as an
        // empty string — both mean "no folder" — not coerced to (int) null = 0,
        // which would violate media.folder_id's foreign key constraint.
        $folderIdRaw = $request->input('folder_id');
        $folderId = ($folderIdRaw !== null && $folderIdRaw !== '') ? (int) $folderIdRaw : null;
        $files = $request->file('files');

        $created = [];
        $errors = [];

        if ($files && is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $single = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];

                try {
                    $created[] = $this->storeUpload($single, $folderId);
                } catch (\RuntimeException $e) {
                    $errors[] = $single['name'] . ': ' . $e->getMessage();
                }
            }
        }

        $actor = Auth::user();
        if ($created) {
            ActivityLog::record($actor['id'] ?? null, 'create', 'media', count($created) . ' file(s) uploaded', $request->ip());
        }

        if ($isAjax) {
            $this->json(['ok' => empty($errors), 'created' => $created, 'errors' => $errors]);
        }

        if ($errors) {
            $this->flashError(implode(' | ', $errors));
        } else {
            $this->flashSuccess(count($created) . ' file(s) uploaded.');
        }
        $this->redirect('/admin/media' . ($folderId ? '?folder=' . $folderId : ''));
    }

    private function storeUpload(array $file, ?int $folderId): array
    {
        $stored = Uploader::store($file, 'media');

        if (str_starts_with($stored['mime'], 'image/')) {
            MediaCompressor::compress($stored['absolute_path'], $stored['mime']);
        }

        $actor = Auth::user();
        $mediaId = Media::create([
            'folder_id' => $folderId,
            'filename' => basename($stored['path']),
            'original_name' => $stored['original'],
            'path' => $stored['path'],
            'mime' => $stored['mime'],
            'size' => filesize($stored['absolute_path']) ?: $stored['size'],
            'type' => Media::typeFromMime($stored['mime']),
            'uploaded_by' => $actor['id'] ?? null,
        ]);

        return ['id' => $mediaId, 'url' => media_url($mediaId), 'name' => $stored['original']];
    }

    public function replace(Request $request): void
    {
        $id = (int) $request->param('id');
        $media = Media::find($id);
        if (!$media) {
            $this->flashError('Media item not found.');
            $this->redirect('/admin/media');
        }

        $file = $request->file('file');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->flashError('No replacement file provided.');
            $this->redirect('/admin/media');
        }

        try {
            $stored = Uploader::store($file, 'media');
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/admin/media');
        }

        if (str_starts_with($stored['mime'], 'image/')) {
            MediaCompressor::compress($stored['absolute_path'], $stored['mime']);
        }

        $oldAbsolutePath = Uploader::uploadsRoot() . $media['path'];
        if (is_file($oldAbsolutePath)) {
            @unlink($oldAbsolutePath);
        }

        Media::updatePath($id, $stored['path'], basename($stored['path']), filesize($stored['absolute_path']) ?: $stored['size']);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'media', "Replaced media #{$id}", $request->ip());

        $this->flashSuccess('File replaced.');
        $this->redirect('/admin/media');
    }

    public function resize(Request $request): void
    {
        $id = (int) $request->param('id');
        $media = Media::find($id);
        if (!$media) {
            $this->flashError('Media item not found.');
            $this->redirect('/admin/media');
        }

        if ($media['type'] !== 'image') {
            $this->flashError('Only images can be resized.');
            $this->redirect('/admin/media');
        }

        $width = (int) $request->input('width');
        $height = (int) $request->input('height');

        if ($width < 1 || $width > 4000 || $height < 1 || $height > 4000) {
            $this->flashError('Width and height must be between 1 and 4000px.');
            $this->redirect('/admin/media');
        }

        $absolutePath = Uploader::uploadsRoot() . $media['path'];

        try {
            MediaResizer::resizeToExact($absolutePath, $media['mime'], $width, $height);
        } catch (\RuntimeException $e) {
            $this->flashError('Resize failed: ' . $e->getMessage());
            $this->redirect('/admin/media');
        }

        Media::updateSize($id, filesize($absolutePath) ?: $media['size']);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'media', "Resized media #{$id} to {$width}x{$height}", $request->ip());

        $this->flashSuccess("Image resized to {$width}×{$height}px.");
        $this->redirect('/admin/media');
    }

    public function toggleHidden(Request $request): void
    {
        $id = (int) $request->param('id');
        $media = Media::find($id);
        if (!$media) {
            $this->flashError('Media item not found.');
            $this->redirect('/admin/media');
        }

        Media::toggleHidden($id);

        $actor = Auth::user();
        $newState = $media['is_hidden'] ? 'visible' : 'hidden';
        ActivityLog::record($actor['id'] ?? null, 'update', 'media', "Marked media #{$id} as {$newState}", $request->ip());

        $this->flashSuccess($newState === 'hidden' ? 'Image hidden from the picker.' : 'Image is visible in the picker again.');
        $this->redirect('/admin/media');
    }

    public function delete(Request $request): void
    {
        $id = (int) $request->param('id');
        $media = Media::find($id);
        if (!$media) {
            $this->flashError('Media item not found.');
            $this->redirect('/admin/media');
        }

        Media::deleteFileAndRecord($id);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'media', "Deleted media #{$id} ({$media['original_name']})", $request->ip());

        $this->flashSuccess('File deleted.');
        $this->redirect('/admin/media');
    }

    public function createFolder(Request $request): void
    {
        $name = Sanitizer::text((string) $request->input('name'));
        // Same null-vs-empty-string pitfall as upload()'s folder_id: the "New
        // folder" form on the library root has no parent_id field at all, so
        // this must treat "absent" the same as "empty" (both mean top-level).
        $parentIdRaw = $request->input('parent_id');
        $parentId = ($parentIdRaw !== null && $parentIdRaw !== '') ? (int) $parentIdRaw : null;

        if ($name === '') {
            $this->flashError('Folder name is required.');
            $this->redirect('/admin/media' . ($parentId ? '?folder=' . $parentId : ''));
        }

        MediaFolder::create($name, $parentId);

        $this->flashSuccess('Folder created.');
        $this->redirect('/admin/media' . ($parentId ? '?folder=' . $parentId : ''));
    }

    public function deleteFolder(Request $request): void
    {
        $id = (int) $request->param('id');
        MediaFolder::delete($id); // ON DELETE SET NULL on media.folder_id, ON DELETE CASCADE on child folders

        $this->flashSuccess('Folder deleted.');
        $this->redirect('/admin/media');
    }
}
