<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class BlogPost extends Model
{
    protected static string $table = 'blog_posts';

    public static function findBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM blog_posts WHERE slug = ?', [$slug]);
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::one('SELECT * FROM blog_posts WHERE slug = ? AND status = ?', [$slug, 'published']);
    }

    public static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            return Database::one('SELECT id FROM blog_posts WHERE slug = ? AND id != ?', [$slug, $excludeId]) !== null;
        }
        return Database::one('SELECT id FROM blog_posts WHERE slug = ?', [$slug]) !== null;
    }

    /** Lazily flips any due scheduled posts to published — no cron needed. */
    public static function publishDueScheduled(): void
    {
        Database::query(
            "UPDATE blog_posts SET status = 'published', published_at = NOW()
             WHERE status = 'scheduled' AND scheduled_at <= NOW()"
        );
    }

    public static function create(array $data): int
    {
        return Database::insertGetId(
            'INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, author_id, status, scheduled_at, published_at, comments_enabled, is_featured, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $data['title'], $data['slug'], $data['excerpt'], $data['content'],
                $data['featured_image'], $data['author_id'], $data['status'],
                $data['scheduled_at'], $data['published_at'], $data['comments_enabled'], $data['is_featured'] ?? 0,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?,
             status = ?, scheduled_at = ?, published_at = ?, comments_enabled = ?, is_featured = ?, updated_at = NOW() WHERE id = ?',
            [
                $data['title'], $data['slug'], $data['excerpt'], $data['content'], $data['featured_image'],
                $data['status'], $data['scheduled_at'], $data['published_at'], $data['comments_enabled'], $data['is_featured'] ?? 0, $id,
            ]
        );
    }

    public static function setCategories(int $postId, array $categoryIds): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO blog_post_categories (post_id, category_id) VALUES (?, ?)');

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM blog_post_categories WHERE post_id = ?')->execute([$postId]);
            foreach ($categoryIds as $categoryId) {
                $stmt->execute([$postId, (int) $categoryId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function setTags(int $postId, array $tagIds): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)');

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM blog_post_tags WHERE post_id = ?')->execute([$postId]);
            foreach ($tagIds as $tagId) {
                $stmt->execute([$postId, (int) $tagId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function adminList(string $statusFilter = ''): array
    {
        $sql = 'SELECT p.*, u.name AS author_name FROM blog_posts p LEFT JOIN users u ON u.id = p.author_id WHERE 1=1';
        $params = [];
        if ($statusFilter !== '') {
            $sql .= ' AND p.status = ?';
            $params[] = $statusFilter;
        }
        $sql .= ' ORDER BY p.created_at DESC';
        return Database::all($sql, $params);
    }

    /**
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public static function paginate(int $page, int $perPage, array $filters = []): array
    {
        $where = ['p.status = ?'];
        $params = ['published'];
        $joins = '';

        if (!empty($filters['category'])) {
            $joins .= ' INNER JOIN blog_post_categories fc ON fc.post_id = p.id
                        INNER JOIN blog_categories fcat ON fcat.id = fc.category_id AND fcat.slug = ?';
            $params[] = $filters['category'];
        }

        if (!empty($filters['tag'])) {
            $joins .= ' INNER JOIN blog_post_tags ft ON ft.post_id = p.id
                        INNER JOIN blog_tags ftag ON ftag.id = ft.tag_id AND ftag.slug = ?';
            $params[] = $filters['tag'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::one(
            "SELECT COUNT(DISTINCT p.id) AS c FROM blog_posts p {$joins} WHERE {$whereSql}",
            $params
        )['c'];

        $offset = max(0, ($page - 1) * $perPage);
        $items = Database::all(
            "SELECT DISTINCT p.* FROM blog_posts p {$joins} WHERE {$whereSql}
             ORDER BY p.is_featured DESC, p.published_at DESC, p.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public static function relatedPosts(int $postId, int $limit = 3): array
    {
        return Database::all(
            "SELECT DISTINCT p.* FROM blog_posts p
             INNER JOIN blog_post_categories pc ON pc.post_id = p.id
             WHERE p.status = 'published' AND p.id != ?
             AND pc.category_id IN (SELECT category_id FROM blog_post_categories WHERE post_id = ?)
             ORDER BY p.published_at DESC LIMIT {$limit}",
            [$postId, $postId]
        );
    }

    public static function count(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM blog_posts')['c'];
    }

    /** @return array<string,int> status => count */
    public static function statusCounts(): array
    {
        $rows = Database::all('SELECT status, COUNT(*) AS c FROM blog_posts GROUP BY status');
        $counts = ['draft' => 0, 'scheduled' => 0, 'published' => 0];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        return $counts;
    }
}
