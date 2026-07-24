<?php

namespace App\Core;

use App\Models\ActivityLog;
use App\Models\LoginAttempt;
use App\Models\LoginHistory;
use App\Models\RememberToken;
use App\Models\Role;
use App\Models\User;

class Auth
{
    private const REMEMBER_COOKIE = 'edutop_remember';

    /** Verifies credentials with lockout throttling. Returns the user row or null. */
    public static function attempt(string $email, string $password, string $ip, string $userAgent): ?array
    {
        if (LoginAttempt::isLockedOut($email, $ip)) {
            throw new \RuntimeException('Too many failed login attempts. Please try again later.');
        }

        $user = User::findByEmail($email);

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            LoginAttempt::record($email, $ip);
            LoginHistory::record($user['id'] ?? null, $email, $ip, $userAgent, 'failed');
            return null;
        }

        LoginAttempt::clear($email);
        return $user;
    }

    public static function login(array $user, string $ip, string $userAgent): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);

        User::touchLastLogin((int) $user['id']);
        LoginHistory::record((int) $user['id'], $user['email'], $ip, $userAgent, 'success');
        ActivityLog::record((int) $user['id'], 'login', 'auth', 'User logged in', $ip);
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user) {
            ActivityLog::record((int) $user['id'], 'logout', 'auth', 'User logged out', (new Request())->ip());
        }

        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            RememberToken::revoke($_COOKIE[self::REMEMBER_COOKIE]);
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, Request::basePath() . '/');
        }

        Session::destroy();
    }

    public static function issueRememberCookie(int $userId): void
    {
        $value = RememberToken::issue($userId);
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => Request::basePath() . '/',
            'secure' => Env::bool('APP_FORCE_HTTPS', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function attemptRememberLogin(string $ip, string $userAgent): bool
    {
        if (self::check() || !isset($_COOKIE[self::REMEMBER_COOKIE])) {
            return false;
        }

        $userId = RememberToken::verify($_COOKIE[self::REMEMBER_COOKIE]);
        if (!$userId) {
            return false;
        }

        $user = User::withRole($userId);
        if (!$user || $user['status'] !== 'active') {
            return false;
        }

        // Rotate the token on every use.
        RememberToken::revoke($_COOKIE[self::REMEMBER_COOKIE]);
        self::login($user, $ip, $userAgent);
        self::issueRememberCookie($userId);

        return true;
    }

    public static function check(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function user(): ?array
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }
        return User::withRole((int) $userId);
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        if ($user['role_slug'] === 'super-admin') {
            return true;
        }

        static $permissionCache = [];
        $roleId = (int) $user['role_id'];
        if (!isset($permissionCache[$roleId])) {
            $permissionCache[$roleId] = Role::permissionSlugs($roleId);
        }

        return in_array($permission, $permissionCache[$roleId], true);
    }
}
