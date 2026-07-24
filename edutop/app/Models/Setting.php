<?php

namespace App\Models;

use App\Core\Database;

class Setting
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $row = Database::one('SELECT value FROM settings WHERE `key` = ?', [$key]);
        return $row['value'] ?? $default;
    }

    public static function set(string $key, string $value, string $group = 'general', string $type = 'text'): void
    {
        Database::query(
            'INSERT INTO settings (`key`, value, `type`, `group`) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), `type` = VALUES(`type`), `group` = VALUES(`group`)',
            [$key, $value, $type, $group]
        );
    }

    public static function group(string $group): array
    {
        $rows = Database::all('SELECT `key`, value FROM settings WHERE `group` = ?', [$group]);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }
}
