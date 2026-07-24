<?php

namespace App\Models;

use App\Core\Database;

class MediaFolder
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM media_folders WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return Database::all('SELECT * FROM media_folders ORDER BY name ASC');
    }

    public static function childrenOf(?int $parentId): array
    {
        if ($parentId === null) {
            return Database::all('SELECT * FROM media_folders WHERE parent_id IS NULL ORDER BY name ASC');
        }
        return Database::all('SELECT * FROM media_folders WHERE parent_id = ? ORDER BY name ASC', [$parentId]);
    }

    public static function create(string $name, ?int $parentId = null): int
    {
        return Database::insertGetId(
            'INSERT INTO media_folders (parent_id, name, created_at) VALUES (?, ?, NOW())',
            [$parentId, $name]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM media_folders WHERE id = ?', [$id]);
    }
}
