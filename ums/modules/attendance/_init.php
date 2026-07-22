<?php
declare(strict_types=1);

/**
 * Attendance module — shared init.
 * Daily / subject-wise attendance marked per section, per date.
 * Owns ums_attendance. Reads Sections + Students + Courses.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'accountant']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('attendance') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    section_id INT NOT NULL,
    course_id INT NOT NULL DEFAULT 0,
    student_id INT NOT NULL,
    a_date DATE NOT NULL,
    status ENUM("present","absent","leave","late") NOT NULL DEFAULT "present",
    marked_by INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_mark (section_id, course_id, student_id, a_date),
    INDEX idx_date (a_date),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

/** status => [label, short, cssKey, badgeClass] */
const ATT_STATUS = [
    'present' => ['Present', 'P',  'p', 'st-approved'],
    'absent'  => ['Absent',  'A',  'a', 'st-rejected'],
    'leave'   => ['Leave',   'L',  'l', 'st-pending'],
    'late'    => ['Late',    'Lt', 't', 'st-enrolled'],
];

/** Active sections as [id => label]. */
function att_section_options(int $campus): array
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

/** One section row or null. */
function att_section(int $id): ?array
{
    try {
        $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('sections') . ' WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        return $row ?: null;
    } catch (Throwable $t) { return null; }
}

/** Active courses as [id => "CODE — Title"] for the optional subject picker. */
function att_course_options(int $campus): array
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

function att_url(string $path = ''): string
{
    return UMS_URL . '/modules/attendance/' . $path;
}
