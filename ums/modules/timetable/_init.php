<?php
declare(strict_types=1);

/**
 * Timetable module — shared init.
 * Weekly class schedule per section: a period ties a day + time slot to a
 * course and a teacher in a room. Owns ums_timetable. Reads Sections,
 * Courses and Teachers, and rejects section/teacher time clashes.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('timetable') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    section_id INT NOT NULL,
    day_of_week TINYINT NOT NULL DEFAULT 1,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    course_id INT NOT NULL DEFAULT 0,
    teacher_id INT NOT NULL DEFAULT 0,
    room VARCHAR(60) NOT NULL DEFAULT "",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_day (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

/** Working days (Mon–Sat). */
const TT_DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

/** Active sections [id => label]. */
function tt_section_options(int $campus): array
{
    $out = [];
    try {
        $stmt = ums_db()->prepare('SELECT id, program, semester, name FROM ' . tbl('sections') . ' WHERE campus_id = ? AND status = "active" ORDER BY program, semester, name');
        $stmt->bind_param('i', $campus); $stmt->execute();
        $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['program'] . ' · Sem ' . (int)$x['semester'] . ' · ' . $x['name'];
        $stmt->close();
    } catch (Throwable $t) {}
    return $out;
}

/** Active courses [id => "CODE — Title"]. */
function tt_course_options(int $campus): array
{
    $out = [];
    try {
        $stmt = ums_db()->prepare('SELECT id, code, title FROM ' . tbl('courses') . ' WHERE campus_id = ? AND status = "active" ORDER BY title');
        $stmt->bind_param('i', $campus); $stmt->execute();
        $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = ($x['code'] !== '' ? $x['code'] . ' — ' : '') . $x['title'];
        $stmt->close();
    } catch (Throwable $t) {}
    return $out;
}

/** Active teachers [id => name]. */
function tt_teacher_options(int $campus): array
{
    $out = [];
    try {
        $stmt = ums_db()->prepare('SELECT id, name FROM ' . tbl('teachers') . ' WHERE campus_id = ? AND status = "active" ORDER BY name');
        $stmt->bind_param('i', $campus); $stmt->execute();
        $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['name'];
        $stmt->close();
    } catch (Throwable $t) {}
    return $out;
}

/** Format a stored TIME (H:i:s) as "h:i AM". */
function tt_time(string $t): string
{
    return date('g:i A', strtotime($t));
}

function tt_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('timetable') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

function tt_url(string $path = ''): string
{
    return UMS_URL . '/modules/timetable/' . $path;
}
