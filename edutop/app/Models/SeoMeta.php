<?php

namespace App\Models;

use App\Core\Database;

class SeoMeta
{
    public static function forPage(int $pageId): ?array
    {
        return Database::one('SELECT * FROM seo_meta WHERE page_id = ?', [$pageId]);
    }

    public static function upsert(int $pageId, array $fields): void
    {
        $existing = self::forPage($pageId);

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
                "UPDATE seo_meta SET {$set}, updated_at = NOW() WHERE page_id = ?",
                [...array_values($values), $pageId]
            );
        } else {
            $cols = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            Database::query(
                "INSERT INTO seo_meta (page_id, {$cols}, created_at) VALUES (?, {$placeholders}, NOW())",
                [$pageId, ...array_values($values)]
            );
        }
    }
}
