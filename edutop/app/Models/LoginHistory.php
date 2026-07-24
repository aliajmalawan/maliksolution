<?php

namespace App\Models;

use App\Core\Database;

class LoginHistory
{
    public static function record(?int $userId, string $email, string $ip, string $userAgent, string $status): void
    {
        Database::query(
            'INSERT INTO login_history (user_id, email, ip, user_agent, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$userId, $email, $ip, $userAgent, $status]
        );
    }

    public static function recent(int $limit = 20): array
    {
        return Database::all(
            'SELECT * FROM login_history ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
    }

    /** @return array{items: array, total: int, page: int, perPage: int} */
    public static function paginate(int $page, int $perPage, string $statusFilter = ''): array
    {
        $where = '';
        $params = [];
        if ($statusFilter !== '') {
            $where = 'WHERE status = ?';
            $params[] = $statusFilter;
        }

        $total = (int) Database::one("SELECT COUNT(*) AS c FROM login_history {$where}", $params)['c'];

        $offset = max(0, ($page - 1) * $perPage);
        $items = Database::all(
            "SELECT * FROM login_history {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }
}
