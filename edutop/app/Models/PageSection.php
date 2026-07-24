<?php

namespace App\Models;

use App\Core\Database;

class PageSection
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM page_sections WHERE id = ?', [$id]);
    }

    public static function forPage(int $pageId): array
    {
        return Database::all('SELECT * FROM page_sections WHERE page_id = ? ORDER BY position ASC', [$pageId]);
    }

    public static function forPageEnabled(int $pageId): array
    {
        return Database::all(
            'SELECT * FROM page_sections WHERE page_id = ? AND is_enabled = 1 ORDER BY position ASC',
            [$pageId]
        );
    }

    public static function create(int $pageId, string $sectionType, array $content = []): int
    {
        $position = (int) (Database::one('SELECT COALESCE(MAX(position), -1) AS max_pos FROM page_sections WHERE page_id = ?', [$pageId])['max_pos']) + 1;

        return Database::insertGetId(
            'INSERT INTO page_sections (page_id, section_type, position, is_enabled, content, created_at) VALUES (?, ?, ?, 1, ?, NOW())',
            [$pageId, $sectionType, $position, json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]
        );
    }

    public static function updateContent(int $id, array $content): void
    {
        Database::query(
            'UPDATE page_sections SET content = ?, updated_at = NOW() WHERE id = ?',
            [json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $id]
        );
    }

    public static function toggle(int $id): void
    {
        Database::query('UPDATE page_sections SET is_enabled = NOT is_enabled, updated_at = NOW() WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM page_sections WHERE id = ?', [$id]);
    }

    /** @param int[] $orderedIds section ids in their new display order */
    public static function reorder(int $pageId, array $orderedIds): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE page_sections SET position = ? WHERE id = ? AND page_id = ?');

        $pdo->beginTransaction();
        try {
            foreach ($orderedIds as $position => $id) {
                $stmt->execute([$position, (int) $id, $pageId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function decodeContent(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
