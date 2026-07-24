<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function create(int $userId, string $title, string $message, string $type = 'info', ?array $data = null, ?string $url = null): int
    {
        return Database::insertGetId(
            'INSERT INTO notifications (user_id, title, message, type, data, url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$userId, $title, $message, $type, $data !== null ? json_encode($data) : null, $url]
        );
    }

    public static function markAsRead(int $id): void
    {
        Database::query('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?', [$id]);
    }

    public static function unreadForUser(int $userId): array
    {
        return Database::all(
            'SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC',
            [$userId]
        );
    }

    public static function allForUser(int $userId): array
    {
        return Database::all(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }
}
