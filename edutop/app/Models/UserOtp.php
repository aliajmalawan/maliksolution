<?php

namespace App\Models;

use App\Core\Database;

class UserOtp
{
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 30;

    /** Generates and stores a hashed 6-digit OTP; returns the plaintext code to email. */
    public static function generate(int $userId, int $expiresInMinutes = 10): string
    {
        Database::query('DELETE FROM user_otps WHERE user_id = ?', [$userId]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Database::query(
            'INSERT INTO user_otps (user_id, otp_hash, expires_at, used, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 0, NOW())',
            [$userId, password_hash($code, PASSWORD_DEFAULT), $expiresInMinutes]
        );

        return $code;
    }

    /** False while the most recently issued code is still within its resend cooldown window. */
    public static function canResend(int $userId): bool
    {
        $row = Database::one(
            'SELECT created_at FROM user_otps WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [$userId]
        );

        if (!$row) {
            return true;
        }

        return strtotime($row['created_at']) <= time() - self::RESEND_COOLDOWN_SECONDS;
    }

    public static function verify(int $userId, string $code): bool
    {
        $row = Database::one(
            'SELECT * FROM user_otps WHERE user_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [$userId]
        );

        if (!$row || (int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (!password_verify($code, $row['otp_hash'])) {
            Database::query('UPDATE user_otps SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
            return false;
        }

        Database::query('UPDATE user_otps SET used = 1 WHERE id = ?', [$row['id']]);
        return true;
    }
}
