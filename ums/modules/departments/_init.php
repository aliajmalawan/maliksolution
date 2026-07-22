<?php
declare(strict_types=1);

/**
 * Departments module — shared init.
 * Root of the academic structure: Courses, Teachers, Students and
 * Timetable all reference a department. Owns the ums_departments table.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();

/** Ensure the departments table exists (idempotent). */
ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('departments') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NOT NULL DEFAULT "",
    head_name VARCHAR(150) NOT NULL DEFAULT "",
    email VARCHAR(190) NOT NULL DEFAULT "",
    phone VARCHAR(30) NOT NULL DEFAULT "",
    description TEXT,
    status ENUM("active","inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

/** Fetch one department by id, or null. */
function dept_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('departments') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Options list of active departments [id => name] — used by Courses/Teachers/Students. */
function dept_options(int $campus): array
{
    $out = [];
    $stmt = ums_db()->prepare('SELECT id, name FROM ' . tbl('departments') . ' WHERE campus_id = ? AND status = "active" ORDER BY name');
    $stmt->bind_param('i', $campus);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['name'];
    $stmt->close();
    return $out;
}

/** Module URL helper. */
function dept_url(string $path = ''): string
{
    return UMS_URL . '/modules/departments/' . $path;
}
