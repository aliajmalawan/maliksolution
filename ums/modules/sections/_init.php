<?php
declare(strict_types=1);

/**
 * Classes & Sections module — shared init.
 * A section groups students of one program + semester into a class
 * (e.g. "BS Computer Science · Sem 3 · A") with a capacity and a class
 * teacher. Owns ums_sections and adds section_id to ums_students so
 * Attendance and Timetable can work per-section.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();

if (!function_exists('dept_options')) {
    function dept_options(int $campus): array
    {
        $out = [];
        try {
            $stmt = ums_db()->prepare('SELECT id, name FROM ' . tbl('departments') . ' WHERE campus_id = ? AND status = "active" ORDER BY name');
            $stmt->bind_param('i', $campus); $stmt->execute();
            $r = $stmt->get_result();
            while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['name'];
            $stmt->close();
        } catch (Throwable $t) {}
        return $out;
    }
}

/** Active teachers as [id => name] for the class-teacher picker. */
function sec_teacher_options(int $campus): array
{
    $out = [];
    try {
        $stmt = ums_db()->prepare('SELECT id, name FROM ' . tbl('teachers') . ' WHERE campus_id = ? AND status = "active" ORDER BY name');
        $stmt->bind_param('i', $campus); $stmt->execute();
        $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['name'];
        $stmt->close();
    } catch (Throwable $t) { /* teachers module not in use yet */ }
    return $out;
}

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('sections') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    department_id INT NOT NULL DEFAULT 0,
    program VARCHAR(150) NOT NULL DEFAULT "",
    semester TINYINT NOT NULL DEFAULT 1,
    session VARCHAR(40) NOT NULL DEFAULT "",
    name VARCHAR(50) NOT NULL DEFAULT "A",
    class_teacher_id INT NOT NULL DEFAULT 0,
    capacity INT NOT NULL DEFAULT 50,
    room VARCHAR(60) NOT NULL DEFAULT "",
    status ENUM("active","inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prog_sem (program, semester),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

// Additive link: give students a section_id (owned here; students module untouched).
try {
    $has = ums_db()->query("SHOW COLUMNS FROM " . tbl('students') . " LIKE 'section_id'");
    if ($has && $has->num_rows === 0) {
        ums_db()->query('ALTER TABLE ' . tbl('students') . ' ADD COLUMN section_id INT NOT NULL DEFAULT 0 AFTER department_id, ADD INDEX idx_section (section_id)');
    }
} catch (Throwable $t) { /* students table not created yet — created on first Students visit */ }

function sec_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('sections') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Human label for a section row. */
function sec_label(array $s): string
{
    return ($s['program'] !== '' ? $s['program'] : 'Program') . ' · Sem ' . (int)$s['semester'] . ' · ' . $s['name'];
}

function sec_url(string $path = ''): string
{
    return UMS_URL . '/modules/sections/' . $path;
}
