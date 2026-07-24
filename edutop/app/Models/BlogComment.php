<?php

namespace App\Models;

use App\Core\Database;

class BlogComment
{
    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM blog_comments WHERE id = ?', [$id]);
    }

    public static function approvedForPost(int $postId): array
    {
        return Database::all(
            "SELECT * FROM blog_comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC",
            [$postId]
        );
    }

    public static function adminList(string $statusFilter = ''): array
    {
        $sql = 'SELECT c.*, p.title AS post_title, p.slug AS post_slug FROM blog_comments c
                INNER JOIN blog_posts p ON p.id = c.post_id WHERE 1=1';
        $params = [];
        if ($statusFilter !== '') {
            $sql .= ' AND c.status = ?';
            $params[] = $statusFilter;
        }
        $sql .= ' ORDER BY c.created_at DESC';
        return Database::all($sql, $params);
    }

    public static function create(int $postId, string $name, string $email, string $body, string $ip): int
    {
        return Database::insertGetId(
            "INSERT INTO blog_comments (post_id, author_name, author_email, body, status, ip, created_at)
             VALUES (?, ?, ?, ?, 'pending', ?, NOW())",
            [$postId, $name, $email, $body, $ip]
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::query('UPDATE blog_comments SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM blog_comments WHERE id = ?', [$id]);
    }

    public static function pendingCount(): int
    {
        return (int) Database::one("SELECT COUNT(*) AS c FROM blog_comments WHERE status = 'pending'")['c'];
    }
}
