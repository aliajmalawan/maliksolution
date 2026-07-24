<?php

namespace App\Models;

use App\Core\Database;
use App\Core\SectionForm;
use App\Core\Uploader;

class Media
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM media WHERE id = ?', [$id]);
    }

    public static function inFolder(?int $folderId, string $search = '', bool $excludeHidden = false): array
    {
        $sql = 'SELECT * FROM media WHERE 1=1';
        $params = [];

        if ($folderId === null) {
            $sql .= ' AND folder_id IS NULL';
        } else {
            $sql .= ' AND folder_id = ?';
            $params[] = $folderId;
        }

        if ($search !== '') {
            $sql .= ' AND original_name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        if ($excludeHidden) {
            $sql .= ' AND is_hidden = 0';
        }

        $sql .= ' ORDER BY created_at DESC';

        return Database::all($sql, $params);
    }

    public static function search(string $term, bool $excludeHidden = false): array
    {
        $sql = 'SELECT * FROM media WHERE original_name LIKE ?';
        $params = ['%' . $term . '%'];

        if ($excludeHidden) {
            $sql .= ' AND is_hidden = 0';
        }

        $sql .= ' ORDER BY created_at DESC LIMIT 100';

        return Database::all($sql, $params);
    }

    public static function create(array $data): int
    {
        return Database::insertGetId(
            'INSERT INTO media (folder_id, filename, original_name, path, mime, size, type, alt_text, uploaded_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['folder_id'] ?? null,
                $data['filename'],
                $data['original_name'],
                $data['path'],
                $data['mime'],
                $data['size'],
                $data['type'],
                $data['alt_text'] ?? null,
                $data['uploaded_by'] ?? null,
            ]
        );
    }

    public static function updatePath(int $id, string $path, string $filename, int $size): void
    {
        Database::query(
            'UPDATE media SET path = ?, filename = ?, size = ? WHERE id = ?',
            [$path, $filename, $size, $id]
        );
    }

    public static function updateAltText(int $id, string $altText): void
    {
        Database::query('UPDATE media SET alt_text = ? WHERE id = ?', [$altText, $id]);
    }

    public static function updateSize(int $id, int $size): void
    {
        Database::query('UPDATE media SET size = ? WHERE id = ?', [$size, $id]);
    }

    public static function toggleHidden(int $id): void
    {
        Database::query('UPDATE media SET is_hidden = NOT is_hidden WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM media WHERE id = ?', [$id]);
    }

    /** Deletes both the DB record and the physical file — the one place both should ever happen together. */
    public static function deleteFileAndRecord(int $id): void
    {
        $media = self::find($id);
        if (!$media) {
            return;
        }

        $absolutePath = Uploader::uploadsRoot() . $media['path'];
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        self::delete($id);
    }

    /**
     * Scans every place a media ID can be referenced (page sections' JSON
     * content — including inside repeaters — blog post featured images, SEO
     * OG images, and settings like the site logo/favicon) and reports
     * whether it's still in use anywhere. Used to decide whether it's safe
     * to auto-delete an image being replaced.
     */
    public static function isUsedElsewhere(int $mediaId): bool
    {
        $sectionTypes = require dirname(__DIR__, 2) . '/config/section_types.php';

        foreach (Database::all('SELECT section_type, content FROM page_sections') as $section) {
            $schema = $sectionTypes[$section['section_type']]['fields'] ?? null;
            if (!$schema) {
                continue;
            }
            $content = PageSection::decodeContent($section['content']);
            if (in_array($mediaId, SectionForm::collectMediaIds($schema, $content), true)) {
                return true;
            }
        }

        if (Database::one('SELECT id FROM blog_posts WHERE featured_image = ?', [$mediaId])) {
            return true;
        }

        if (Database::one('SELECT id FROM seo_meta WHERE og_image = ?', [(string) $mediaId])) {
            return true;
        }

        if (Database::one('SELECT id FROM blog_post_seo WHERE og_image = ?', [(string) $mediaId])) {
            return true;
        }

        if (Database::one("SELECT id FROM settings WHERE type = 'media' AND value = ?", [(string) $mediaId])) {
            return true;
        }

        return false;
    }

    public static function count(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM media')['c'];
    }

    public static function typeFromMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            $mime === 'application/pdf' => 'pdf',
            default => 'document',
        };
    }
}
