<?php

namespace App\Models;

use App\Core\Database;

class Lead
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM leads WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insertGetId(
            'INSERT INTO leads (type, name, email, phone, school_name, message, status, ip, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, \'new\', ?, ?, NOW())',
            [
                $data['type'], $data['name'], $data['email'], $data['phone'] ?? null,
                $data['school_name'] ?? null, $data['message'] ?? null, $data['ip'], $data['user_agent'],
            ]
        );
    }

    public static function adminList(string $typeFilter = '', string $statusFilter = '', string $search = ''): array
    {
        $sql = 'SELECT * FROM leads WHERE 1=1';
        $params = [];
        if ($typeFilter !== '') {
            $sql .= ' AND type = ?';
            $params[] = $typeFilter;
        }
        if ($statusFilter !== '') {
            $sql .= ' AND status = ?';
            $params[] = $statusFilter;
        }
        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $sql .= ' ORDER BY created_at DESC';
        return Database::all($sql, $params);
    }

    /** @return array<string,int> status => count, honoring an optional type filter */
    public static function statusCounts(string $typeFilter = ''): array
    {
        $sql = 'SELECT status, COUNT(*) AS c FROM leads';
        $params = [];
        if ($typeFilter !== '') {
            $sql .= ' WHERE type = ?';
            $params[] = $typeFilter;
        }
        $sql .= ' GROUP BY status';
        $rows = Database::all($sql, $params);
        $counts = ['new' => 0, 'contacted' => 0, 'closed' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        return $counts;
    }

    public static function recentByType(string $type, int $limit = 5): array
    {
        return Database::all(
            'SELECT * FROM leads WHERE type = ? ORDER BY created_at DESC LIMIT ' . (int) $limit,
            [$type]
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE leads SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM leads WHERE id = ?', [$id]);
    }

    public static function count(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM leads')['c'];
    }

    public static function countByType(string $type): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM leads WHERE type = ?', [$type])['c'];
    }

    /** @return array<string,int> type => count */
    public static function typeCounts(): array
    {
        $rows = Database::all('SELECT type, COUNT(*) AS c FROM leads GROUP BY type');
        $counts = ['contact' => 0, 'demo' => 0, 'admission' => 0];
        foreach ($rows as $row) {
            $counts[$row['type']] = (int) $row['c'];
        }
        return $counts;
    }

    /** @return array<string,int> "Y-m" => count, zero-filled for the trailing N months */
    public static function monthlyCounts(int $months = 6): array
    {
        $rows = Database::all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c FROM leads
             WHERE created_at >= (NOW() - INTERVAL ? MONTH) GROUP BY ym",
            [$months]
        );
        $byMonth = array_column($rows, 'c', 'ym');

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $result[$ym] = (int) ($byMonth[$ym] ?? 0);
        }
        return $result;
    }
}
