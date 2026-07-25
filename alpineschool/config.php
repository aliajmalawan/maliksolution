<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // PHP's default session headers send "no-store", which disables the browser's
    // back/forward cache. Take control and let pages revalidate instead.
    session_cache_limiter('');
    session_start();
    if (!headers_sent()) {
        header('Cache-Control: private, max-age=0, must-revalidate');
    }
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'alpine_school_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', '/AlpineSchool');
define('UPLOAD_DIR', __DIR__ . '/uploads');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please make sure the alpine_school_db database has been imported.');
}
