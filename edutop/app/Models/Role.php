<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Role extends Model
{
    protected static string $table = 'roles';

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM roles WHERE slug = ?', [$slug]);
    }

    public static function permissionSlugs(int $roleId): array
    {
        $rows = Database::all(
            'SELECT p.slug FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?',
            [$roleId]
        );
        return array_column($rows, 'slug');
    }

    public static function setPermissions(int $roleId, array $permissionIds): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
            foreach (array_unique(array_map('intval', $permissionIds)) as $permissionId) {
                $stmt->execute([$roleId, $permissionId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function userCount(int $roleId): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM users WHERE role_id = ?', [$roleId])['c'];
    }
}
