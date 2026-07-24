<?php

namespace App\Models;

use App\Core\Database;

class ActivityLog
{
    public static function record(?int $userId, string $action, string $module, string $description, string $ip): void
    {
        Database::query(
            'INSERT INTO activity_logs (user_id, action, module, description, ip, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$userId, $action, $module, $description, $ip]
        );
    }

    public static function recent(int $limit = 20): array
    {
        return Database::all(
            'SELECT al.*, u.name AS user_name FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC LIMIT ' . (int) $limit
        );
    }

    /** @return array{items: array, total: int, page: int, perPage: int} */
    public static function paginate(int $page, int $perPage, string $moduleFilter = ''): array
    {
        $where = '';
        $params = [];
        if ($moduleFilter !== '') {
            $where = 'WHERE al.module = ?';
            $params[] = $moduleFilter;
        }

        $total = (int) Database::one("SELECT COUNT(*) AS c FROM activity_logs al {$where}", $params)['c'];

        $offset = max(0, ($page - 1) * $perPage);
        $items = Database::all(
            "SELECT al.*, u.name AS user_name FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             {$where}
             ORDER BY al.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public static function distinctModules(): array
    {
        return array_column(Database::all('SELECT DISTINCT module FROM activity_logs ORDER BY module'), 'module');
    }
}
