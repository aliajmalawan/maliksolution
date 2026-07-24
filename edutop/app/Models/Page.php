<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Page extends Model
{
    protected static string $table = 'pages';

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM pages WHERE slug = ?', [$slug]);
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM pages WHERE slug = ? AND status = ?', [$slug, 'published']);
    }

    public static function findHome(): ?array
    {
        return Database::one('SELECT * FROM pages WHERE is_home = 1 AND status = ? LIMIT 1', ['published']);
    }

    public static function allOrdered(): array
    {
        return Database::all('SELECT * FROM pages ORDER BY position ASC, is_home DESC, title ASC');
    }

    /** @param int[] $orderedIds page ids in their new display order */
    public static function reorder(array $orderedIds): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE pages SET position = ? WHERE id = ?');

        $pdo->beginTransaction();
        try {
            foreach ($orderedIds as $position => $id) {
                $stmt->execute([$position, (int) $id]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM pages WHERE id = ?', [$id]);
    }

    public static function create(string $slug, string $title, string $status): int
    {
        return Database::insertGetId(
            'INSERT INTO pages (slug, title, status, created_at) VALUES (?, ?, ?, NOW())',
            [$slug, $title, $status]
        );
    }

    public static function update(int $id, string $slug, string $title, string $status): void
    {
        Database::query(
            'UPDATE pages SET slug = ?, title = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [$slug, $title, $status, $id]
        );
    }

    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $row = Database::one('SELECT id FROM pages WHERE slug = ? AND id != ?', [$slug, $excludeId]);
        } else {
            $row = Database::one('SELECT id FROM pages WHERE slug = ?', [$slug]);
        }
        return $row !== null;
    }

    public static function count(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM pages')['c'];
    }

    /** @return array<string,int> status => count */
    public static function statusCounts(): array
    {
        $rows = Database::all('SELECT status, COUNT(*) AS c FROM pages GROUP BY status');
        $counts = ['published' => 0, 'draft' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        return $counts;
    }
}
