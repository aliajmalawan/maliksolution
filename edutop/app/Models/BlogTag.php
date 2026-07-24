<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class BlogTag extends Model
{
    protected static string $table = 'blog_tags';

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM blog_tags WHERE slug = ?', [$slug]);
    }

    public static function create(string $name, string $slug): int
    {
        return Database::insertGetId(
            'INSERT INTO blog_tags (name, slug, created_at) VALUES (?, ?, NOW())',
            [$name, $slug]
        );
    }

    public static function slugExists(string $slug): bool
    {
        return Database::one('SELECT id FROM blog_tags WHERE slug = ?', [$slug]) !== null;
    }

    public static function forPost(int $postId): array
    {
        return Database::all(
            'SELECT t.* FROM blog_tags t
             INNER JOIN blog_post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = ? ORDER BY t.name',
            [$postId]
        );
    }
}
