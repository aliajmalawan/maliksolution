<?php

/**
 * Shared bootstrap: autoloader, .env, and global helpers. Included by the
 * public front controller and by CLI scripts (migrate.php, seed.php).
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require __DIR__ . '/app/Core/helpers.php';

App\Core\Env::load(__DIR__ . '/.env');

date_default_timezone_set('UTC');
