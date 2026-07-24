<?php

namespace App\Models;

use App\Core\Database;

class MenuItem
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM menu_items WHERE id = ?', [$id]);
    }

    public static function forMenu(int $menuId): array
    {
        return Database::all('SELECT * FROM menu_items WHERE menu_id = ? ORDER BY position ASC', [$menuId]);
    }

    /** Flat list grouped into a one-level tree: top-level items with a 'children' key. */
    public static function tree(int $menuId): array
    {
        $items = self::forMenu($menuId);
        $byParent = [];
        foreach ($items as $item) {
            $byParent[$item['parent_id'] ?? 0][] = $item;
        }

        $topLevel = $byParent[0] ?? [];
        foreach ($topLevel as &$item) {
            $item['children'] = $byParent[$item['id']] ?? [];
        }

        return $topLevel;
    }

    public static function create(int $menuId, ?int $parentId, string $label, string $url, string $target = '_self'): int
    {
        $position = (int) (Database::one('SELECT COALESCE(MAX(position), -1) AS max_pos FROM menu_items WHERE menu_id = ?', [$menuId])['max_pos']) + 1;

        return Database::insertGetId(
            'INSERT INTO menu_items (menu_id, parent_id, label, url, position, target, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$menuId, $parentId, $label, $url, $position, $target]
        );
    }

    public static function update(int $id, ?int $parentId, string $label, string $url, string $target = '_self'): void
    {
        Database::query(
            'UPDATE menu_items SET parent_id = ?, label = ?, url = ?, target = ? WHERE id = ?',
            [$parentId, $label, $url, $target, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM menu_items WHERE id = ?', [$id]);
    }

    /** @param int[] $orderedIds */
    public static function reorder(int $menuId, array $orderedIds): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE menu_items SET position = ? WHERE id = ? AND menu_id = ?');

        $pdo->beginTransaction();
        try {
            foreach ($orderedIds as $position => $id) {
                $stmt->execute([$position, (int) $id, $menuId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
