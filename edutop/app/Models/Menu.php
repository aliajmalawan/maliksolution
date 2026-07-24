<?php

namespace App\Models;

use App\Core\Database;

class Menu
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM menus WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM menus WHERE slug = ?', [$slug]);
    }

    public static function ensure(string $slug, string $name): int
    {
        $existing = self::findBySlug($slug);
        if ($existing) {
            return (int) $existing['id'];
        }
        return Database::insertGetId('INSERT INTO menus (slug, name, created_at) VALUES (?, ?, NOW())', [$slug, $name]);
    }
}
