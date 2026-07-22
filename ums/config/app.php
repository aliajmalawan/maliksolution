<?php
declare(strict_types=1);

/**
 * Application constants — derived from the environment, never hardcoded.
 */

require_once __DIR__ . '/env.php';

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Karachi'));

define('UMS_NAME', env('APP_NAME', 'Malik UMS'));
define('UMS_ENV', env('APP_ENV', 'production'));
define('UMS_URL', rtrim(env('APP_URL', '/ums'), '/'));
define('UMS_VERSION', '1.0.0-phase1');
define('UMS_PREFIX', env('DB_PREFIX', 'ums_'));

// Production hides PHP errors from visitors; local shows them
if (UMS_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
