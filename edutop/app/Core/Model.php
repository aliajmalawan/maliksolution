<?php

namespace App\Core;

abstract class Model
{
    protected static string $table = '';

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::all('SELECT * FROM ' . static::$table . ' ORDER BY ' . $orderBy);
    }

    public static function deleteById(int $id): void
    {
        Database::query('DELETE FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }
}
