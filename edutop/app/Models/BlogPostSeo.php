<?php

namespace App\Models;

use App\Core\Database;

class BlogPostSeo
{
    public static function forPost(int $postId): ?array
    {
        return Database::one('SELECT * FROM blog_post_seo WHERE post_id = ?', [$postId]);
    }

    public static function upsert(int $postId, array $fields): void
    {
        $existing = self::forPost($postId);

        $columns = [
            'seo_title', 'meta_description', 'meta_keywords', 'canonical_url',
            'og_image', 'og_title', 'og_description', 'twitter_card', 'robots', 'schema_markup',
        ];

        // twitter_card/robots are NOT NULL in the schema — fall back to the
        // same defaults the column itself would use, not NULL, when neither
        // a submitted value nor an existing row provides one.
        $columnDefaults = ['twitter_card' => 'summary_large_image', 'robots' => 'index,follow'];

        $values = [];
        foreach ($columns as $column) {
            $values[$column] = $fields[$column] ?? ($existing[$column] ?? ($columnDefaults[$column] ?? null));
        }

        if ($existing) {
            $set = implode(', ', array_map(fn($c) => "{$c} = ?", $columns));
            Database::query(
                "UPDATE blog_post_seo SET {$set}, updated_at = NOW() WHERE post_id = ?",
                [...array_values($values), $postId]
            );
        } else {
            $cols = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            Database::query(
                "INSERT INTO blog_post_seo (post_id, {$cols}, created_at) VALUES (?, {$placeholders}, NOW())",
                [$postId, ...array_values($values)]
            );
        }
    }
}
