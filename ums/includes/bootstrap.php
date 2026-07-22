<?php
declare(strict_types=1);

/**
 * UMS bootstrap — the single entry point every UMS page requires.
 * Loads config → database → helpers → auth, starts the namespaced
 * session, and runs the idempotent foundation migration.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/migrate.php';

// Namespaced session (does not clash with website CMS / legacy portals)
if (session_status() === PHP_SESSION_NONE) {
    session_name(env('SESSION_NAME', 'UMSSESSID'));
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
        'path'     => '/',
    ]);
    session_start();
}

ums_migrate();

// Restore a persistent "remember me" session if one is present
ums_remember_login();
