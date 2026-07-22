<?php
declare(strict_types=1);

/**
 * Courses module — shared init.
 * Semester-based courses with credit hours and prerequisites, each
 * belonging to a department. Owns the ums_courses table and links to
 * ums_departments (created by the Departments module).
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_once __DIR__ . '/../departments/_init.php'; // gives dept_options()/dept_find()
require_login(['super_admin', 'admin']);

$user = ums_user();

/** Ensure the courses table exists (idempotent). */
ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('courses') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    department_id INT NOT NULL DEFAULT 0,
    code VARCHAR(30) NOT NULL DEFAULT "",
    title VARCHAR(180) NOT NULL,
    credit_hours TINYINT NOT NULL DEFAULT 3,
    semester TINYINT NOT NULL DEFAULT 1,
    type ENUM("theory","lab","both") NOT NULL DEFAULT "theory",
    prerequisite_id INT NOT NULL DEFAULT 0,
    description TEXT,
    status ENUM("active","inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dept (department_id),
    INDEX idx_sem (semester),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const CRS_TYPES = ['theory' => 'Theory', 'lab' => 'Lab', 'both' => 'Theory + Lab'];
const CRS_SEMESTERS = 8;

/** Fetch one course by id, or null. */
function crs_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('courses') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** [id => "CODE — Title"] of active courses (for the prerequisite picker). */
function crs_options(int $campus, int $excludeId = 0): array
{
    $out = [];
    $stmt = ums_db()->prepare('SELECT id, code, title FROM ' . tbl('courses') . ' WHERE campus_id = ? AND status = "active" ORDER BY title');
    $stmt->bind_param('i', $campus);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($x = $r->fetch_assoc()) {
        if ((int)$x['id'] === $excludeId) continue;
        $out[(int)$x['id']] = ($x['code'] !== '' ? $x['code'] . ' — ' : '') . $x['title'];
    }
    $stmt->close();
    return $out;
}

/** Module URL helper. */
function crs_url(string $path = ''): string
{
    return UMS_URL . '/modules/courses/' . $path;
}
