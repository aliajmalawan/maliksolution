<?php

namespace App\Core;

/**
 * Hardened session bootstrap: httponly/secure/strict cookies, periodic ID
 * regeneration, sliding inactivity timeout, and an absolute lifetime.
 */
class Session
{
    private const REGENERATE_INTERVAL = 300; // seconds

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = Env::bool('APP_FORCE_HTTPS', false);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => Env::get('APP_BASE_PATH', '/') . '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_name('edutop_session');
        session_start();

        self::enforceTimeout();
        self::regeneratePeriodically();
    }

    private static function enforceTimeout(): void
    {
        $inactivityLimit = Env::int('SESSION_INACTIVITY_TIMEOUT_MINUTES', 20) * 60;
        $absoluteLimit = Env::int('SESSION_LIFETIME_MINUTES', 120) * 60;
        $now = time();

        if (isset($_SESSION['_last_activity']) && ($now - $_SESSION['_last_activity']) > $inactivityLimit) {
            self::destroy();
            self::start();
            $_SESSION['_flash_error'] = 'You have been logged out due to inactivity.';
            return;
        }

        if (isset($_SESSION['_started_at']) && ($now - $_SESSION['_started_at']) > $absoluteLimit) {
            self::destroy();
            self::start();
            $_SESSION['_flash_error'] = 'Your session has expired. Please log in again.';
            return;
        }

        if (!isset($_SESSION['_started_at'])) {
            $_SESSION['_started_at'] = $now;
        }

        $_SESSION['_last_activity'] = $now;
    }

    private static function regeneratePeriodically(): void
    {
        if (!isset($_SESSION['_regenerated_at'])) {
            $_SESSION['_regenerated_at'] = time();
            return;
        }

        if ((time() - $_SESSION['_regenerated_at']) > self::REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['_regenerated_at'] = time();
        }
    }

    /** Call after login / privilege change to prevent session fixation. */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_regenerated_at'] = time();
        $_SESSION['_started_at'] = time();
        $_SESSION['_last_activity'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key): mixed
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $value;
    }
}
