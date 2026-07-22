<?php
declare(strict_types=1);

/**
 * Examination module — shared init.
 * Exams (midterm/final/quiz/…) tied to a section + course, and per-student
 * marks. Owns ums_exams + ums_exam_marks. Weightage feeds the Results
 * (GPA/CGPA) module next. Reads Sections, Courses, Students.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('exams') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    title VARCHAR(160) NOT NULL,
    exam_type VARCHAR(20) NOT NULL DEFAULT "midterm",
    section_id INT NOT NULL DEFAULT 0,
    course_id INT NOT NULL DEFAULT 0,
    session VARCHAR(40) NOT NULL DEFAULT "",
    exam_date DATE NULL,
    total_marks INT NOT NULL DEFAULT 100,
    passing_marks INT NOT NULL DEFAULT 40,
    weightage INT NOT NULL DEFAULT 100,
    status ENUM("scheduled","completed") NOT NULL DEFAULT "scheduled",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('exam_marks') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    obtained_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    absent TINYINT(1) NOT NULL DEFAULT 0,
    remarks VARCHAR(200) NOT NULL DEFAULT "",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_mark (exam_id, student_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const EXAM_TYPES = [
    'midterm'    => 'Midterm',
    'final'      => 'Final Term',
    'quiz'       => 'Quiz',
    'assignment' => 'Assignment',
    'sessional'  => 'Sessional',
];

/** Letter grade from a percentage (display only; Results adds GPA points). */
function exam_grade(float $pct): string
{
    return match (true) {
        $pct >= 85 => 'A',
        $pct >= 80 => 'A-',
        $pct >= 75 => 'B+',
        $pct >= 70 => 'B',
        $pct >= 65 => 'B-',
        $pct >= 60 => 'C+',
        $pct >= 55 => 'C',
        $pct >= 50 => 'C-',
        $pct >= 40 => 'D',
        default    => 'F',
    };
}

function exam_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('exams') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Active sections [id => label]. */
function exam_section_options(int $campus): array
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
function exam_course_options(int $campus): array
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

function exam_url(string $path = ''): string
{
    return UMS_URL . '/modules/examination/' . $path;
}
