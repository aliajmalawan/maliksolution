<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class BlogCategory extends Model
{
    protected static string $table = 'blog_categories';

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM blog_categories WHERE slug = ?', [$slug]);
    }

    public static function create(string $name, string $slug): int
    {
        return Database::insertGetId(
            'INSERT INTO blog_categories (name, slug, created_at) VALUES (?, ?, NOW())',
            [$name, $slug]
        );
    }

    public static function slugExists(string $slug): bool
    {
        return Database::one('SELECT id FROM blog_categories WHERE slug = ?', [$slug]) !== null;
    }

    public static function forPost(int $postId): array
    {
        return Database::all(
            'SELECT c.* FROM blog_categories c
             INNER JOIN blog_post_categories pc ON pc.category_id = c.id
             WHERE pc.post_id = ? ORDER BY c.name',
            [$postId]
        );
    }
}
