<?php
declare(strict_types=1);

/**
 * Database connection (mysqli, utf8mb4, exceptions on).
 * Shares the existing `maliksolution` database — all UMS tables use the
 * UMS_PREFIX ('ums_') so the website CMS tables are never touched.
 *
 * Usage:  $db = ums_db();          — shared connection
 *         tbl('students')          — returns 'ums_students'
 */

require_once __DIR__ . '/app.php';

/** Prefixed table name helper — always use this, never hardcode ums_. */
function tbl(string $name): string
{
    return UMS_PREFIX . $name;
}

/** Shared database connection (lazy, one per request). */
function ums_db(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli(
                env('DB_HOST', 'localhost'),
                env('DB_USER', 'root'),
                env('DB_PASS', ''),
                env('DB_NAME', 'maliksolution')
            );
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            if (UMS_ENV !== 'production') {
                die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
            }
            die('Service temporarily unavailable. Please try again shortly.');
        }
    }
    return $conn;
}
