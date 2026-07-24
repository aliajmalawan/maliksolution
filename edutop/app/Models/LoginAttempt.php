<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Env;

class LoginAttempt
{
    public static function record(string $email, string $ip): void
    {
        Database::query(
            'INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, NOW())',
            [$email, $ip]
        );
    }

    public static function isLockedOut(string $email, string $ip): bool
    {
        $maxAttempts = Env::int('LOGIN_MAX_ATTEMPTS', 5);
        $lockoutMinutes = Env::int('LOGIN_LOCKOUT_MINUTES', 15);

        $row = Database::one(
            'SELECT COUNT(*) AS c FROM login_attempts
             WHERE (email = ? OR ip = ?) AND attempted_at > (NOW() - INTERVAL ? MINUTE)',
            [$email, $ip, $lockoutMinutes]
        );

        return ((int) $row['c']) >= $maxAttempts;
    }

    /** Only clears this email's own history — must not affect other accounts' attempts recorded from the same IP. */
    public static function clear(string $email): void
    {
        Database::query('DELETE FROM login_attempts WHERE email = ?', [$email]);
    }
}
