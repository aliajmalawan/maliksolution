<?php

namespace App\Models;

use App\Core\Database;

/**
 * Selector/validator remember-me tokens (the standard secure pattern):
 * the cookie carries a lookup "selector" plus a secret "validator". Only a
 * hash of the validator is stored, so a stolen DB row can't forge a cookie.
 */
class RememberToken
{
    public static function issue(int $userId, int $days = 30): string
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(33));

        Database::query(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, created_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), NOW())',
            [$userId, $selector, hash('sha256', $validator), $days]
        );

        return $selector . ':' . $validator;
    }

    public static function verify(string $cookieValue): ?int
    {
        if (!str_contains($cookieValue, ':')) {
            return null;
        }

        [$selector, $validator] = explode(':', $cookieValue, 2);

        $row = Database::one(
            'SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()',
            [$selector]
        );

        if (!$row || !hash_equals($row['validator_hash'], hash('sha256', $validator))) {
            return null;
        }

        return (int) $row['user_id'];
    }

    public static function revoke(string $cookieValue): void
    {
        if (!str_contains($cookieValue, ':')) {
            return;
        }
        [$selector] = explode(':', $cookieValue, 2);
        Database::query('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
    }

    public static function revokeAllForUser(int $userId): void
    {
        Database::query('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }
}
