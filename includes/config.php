<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "maliksolution";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("❌ Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

//echo "✅ Database Connected Successfully";

/**
 * Add a column to a table only if it doesn't already exist.
 * Works on all MySQL versions (no IF NOT EXISTS on ALTER needed).
 */
function ensure_column(mysqli $conn, string $table, string $column, string $definition): void {
    try {
        $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($result && mysqli_num_rows($result) === 0) {
            mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    } catch (Exception $e) {
        // Table missing or corrupt — skip silently
    }
}

/**
 * Read one site setting (settings table), with an in-request cache.
 * Returns $default when the table or key doesn't exist yet.
 */
function get_setting(mysqli $conn, string $name, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $res = mysqli_query($conn, "SELECT name, value FROM settings");
            while ($res && ($r = mysqli_fetch_assoc($res))) {
                $cache[$r['name']] = (string)$r['value'];
            }
        } catch (Exception $e) { /* settings table not created yet */ }
    }
    return ($cache[$name] ?? '') !== '' ? $cache[$name] : $default;
}

/** Write one site setting (creates the settings table on first use). */
function set_setting(mysqli $conn, string $name, string $value): void {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
        name VARCHAR(100) PRIMARY KEY,
        value TEXT
    )");
    $stmt = mysqli_prepare($conn, "REPLACE INTO settings (name, value) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ss', $name, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Read one editable page-content value (page_content table), with an
 * in-request cache. Returns $default when the table/key doesn't exist
 * or the stored value is empty — so pages always render sensible text.
 */
function get_content(mysqli $conn, string $page, string $name, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $res = mysqli_query($conn, "SELECT page, name, value FROM page_content");
            while ($res && ($r = mysqli_fetch_assoc($res))) {
                $cache[$r['page'] . '.' . $r['name']] = (string)$r['value'];
            }
        } catch (Exception $e) { /* page_content table not created yet */ }
    }
    $key = $page . '.' . $name;
    return ($cache[$key] ?? '') !== '' ? $cache[$key] : $default;
}

/** Write one page-content value (creates the table on first use). */
function set_content(mysqli $conn, string $page, string $name, string $value): void {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS page_content (
        page VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        value TEXT,
        PRIMARY KEY (page, name)
    )");
    $stmt = mysqli_prepare($conn, "REPLACE INTO page_content (page, name, value) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sss', $page, $name, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/** Record an admin action in the activity log (creates the table on first use). */
function log_activity(mysqli $conn, string $action, string $details = ''): void {
    try {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_email VARCHAR(255) DEFAULT '',
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip VARCHAR(45) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $email = (string)($_SESSION['admin_email'] ?? '');
        $ip    = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $stmt  = mysqli_prepare($conn, "INSERT INTO activity_logs (admin_email, action, details, ip) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $email, $action, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (Exception $e) { /* logging must never break the page */ }
}

/**
 * Drop a table if InnoDB reports it as corrupt ("doesn't exist in engine").
 * The next CREATE TABLE IF NOT EXISTS call will then recreate it cleanly.
 */
function repair_if_corrupt(mysqli $conn, string $table): void {
    try {
        mysqli_query($conn, "SELECT 1 FROM `$table` LIMIT 0");
    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, "doesn't exist in engine") || str_contains($msg, "doesn't exist")) {
            @mysqli_query($conn, "DROP TABLE IF EXISTS `$table`");
        }
    }
}

?>