<?php

namespace App\Core;

/**
 * Validated file upload handler. MIME-sniffs the actual file content (not the
 * client-supplied header), re-validates images via getimagesize(), enforces
 * size/extension whitelists, and always writes randomized filenames so user
 * input never reaches the filesystem path.
 */
class Uploader
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'video/mp4' => 'mp4',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    private const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const MAX_BYTES = 25 * 1024 * 1024; // 25MB

    /**
     * @param array $file A single entry from $_FILES
     * @return array{path:string, mime:string, size:int, original:string}
     * @throws \RuntimeException on validation failure
     */
    public static function store(array $file, string $subDir = 'media'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed (error code ' . ($file['error'] ?? 'unknown') . ').');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Invalid upload.');
        }

        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('File exceeds the maximum allowed size.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('File type not allowed.');
        }

        if (in_array($mime, self::IMAGE_TYPES, true)) {
            $info = @getimagesize($file['tmp_name']);
            if ($info === false) {
                throw new \RuntimeException('Uploaded file is not a valid image.');
            }
        }

        $extension = self::ALLOWED[$mime];
        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;

        $subDir = trim(preg_replace('/[^a-z0-9\/_-]/i', '', $subDir), '/');
        $targetDir = self::uploadsRoot() . '/' . date('Y/m') . ($subDir ? '/' . $subDir : '');

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Could not create upload directory.');
        }

        $targetPath = $targetDir . '/' . $randomName;

        // Realpath-based containment check: the resolved target directory must
        // stay inside uploads/, preventing any directory-traversal escape.
        $realUploadsRoot = realpath(self::uploadsRoot());
        $realTargetDir = realpath($targetDir);
        if ($realUploadsRoot === false || $realTargetDir === false || !str_starts_with($realTargetDir, $realUploadsRoot)) {
            throw new \RuntimeException('Invalid upload destination.');
        }

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Could not save uploaded file.');
        }

        $absolutePath = realpath($targetPath);
        // Normalize to forward slashes: realpath() returns OS-native separators
        // (backslashes on Windows), but this path is stored and used as a URL.
        $relativePath = str_replace('\\', '/', str_replace($realUploadsRoot, '', $absolutePath));

        return [
            'path' => $relativePath,
            'absolute_path' => $absolutePath,
            'mime' => $mime,
            'size' => $file['size'],
            'original' => Sanitizer::filename($file['name']),
        ];
    }

    public static function uploadsRoot(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads';
    }
}
