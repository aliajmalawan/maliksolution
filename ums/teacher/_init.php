<?php
declare(strict_types=1);

/**
 * Teacher Portal — shared init. Self-service faculty area, separate from
 * ums/admin/ (staff-only). Logs in via the same ums_users table (role =
 * "teacher"), linked to a real ums_teachers record via teacher_id.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/crud.php';
require_login(['teacher']);

$authUser  = ums_user();
$teacherId = (int)($authUser['teacher_id'] ?? 0);
$teacher   = $teacherId > 0 ? tch_find($teacherId) : null;
if (!$teacher || $teacher['status'] !== 'active') {
    ums_logout();
    redirect(UMS_URL . '/teacher/login.php?err=account');
}
$tid = $teacherId; // short alias used throughout the portal's queries

// Content tables the portal manages (owned here; ums_teachers untouched).
ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('teacher_subjects') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tsub (teacher_id, subject_id),
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('teacher_announcements') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL DEFAULT 0,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    priority ENUM("normal","important","urgent") NOT NULL DEFAULT "normal",
    status ENUM("active","archived") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('teacher_assignments') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL DEFAULT 0,
    subject_id INT NOT NULL DEFAULT 0,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE NULL,
    total_marks INT NOT NULL DEFAULT 100,
    status ENUM("draft","active","closed") NOT NULL DEFAULT "draft",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('teacher_quizzes') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL DEFAULT 0,
    subject_id INT NOT NULL DEFAULT 0,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL DEFAULT 30,
    total_marks INT NOT NULL DEFAULT 100,
    status ENUM("draft","active","closed") NOT NULL DEFAULT "draft",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('teacher_tests') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL DEFAULT 0,
    subject_id INT NOT NULL DEFAULT 0,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    test_date DATETIME NULL,
    duration_minutes INT NOT NULL DEFAULT 60,
    total_marks INT NOT NULL DEFAULT 100,
    venue VARCHAR(150) NOT NULL DEFAULT "",
    status ENUM("scheduled","completed","cancelled") NOT NULL DEFAULT "scheduled",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

function tp_url(string $path = ''): string
{
    return UMS_URL . '/teacher/' . $path;
}
