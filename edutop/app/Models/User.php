<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function create(string $name, string $email, string $password, int $roleId, string $status = 'active'): int
    {
        return Database::insertGetId(
            'INSERT INTO users (name, email, password_hash, role_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleId, $status]
        );
    }

    public static function updateProfile(int $id, string $name, string $email, int $roleId, string $status): void
    {
        Database::query(
            'UPDATE users SET name = ?, email = ?, role_id = ?, status = ?, updated_at = NOW() WHERE id = ?',
            [$name, $email, $roleId, $status, $id]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::query(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    public static function touchLastLogin(int $id): void
    {
        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public static function withRole(int $id): ?array
    {
        return Database::one(
            'SELECT u.*, r.name AS role_name, r.slug AS role_slug
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?',
            [$id]
        );
    }

    public static function allWithRoles(): array
    {
        return Database::all(
            'SELECT u.*, r.name AS role_name FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             ORDER BY u.created_at DESC'
        );
    }

    public static function count(): int
    {
        return (int) Database::one('SELECT COUNT(*) AS c FROM users')['c'];
    }
}
