<?php
declare(strict_types=1);

/**
 * Student Management module — shared init.
 * Student records linked to a department + program, with the semester
 * (batch/current-semester) model. Owns ums_students. Attendance, Results
 * and Fees will reference these records later. Can be created directly or
 * enrolled from an approved Admissions application.
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

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('students') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    department_id INT NOT NULL DEFAULT 0,
    admission_id INT NOT NULL DEFAULT 0,
    registration_no VARCHAR(30) NOT NULL DEFAULT "",
    name VARCHAR(150) NOT NULL,
    father_name VARCHAR(150) NOT NULL DEFAULT "",
    gender ENUM("male","female","other") NOT NULL DEFAULT "male",
    dob DATE NULL,
    cnic VARCHAR(20) NOT NULL DEFAULT "",
    email VARCHAR(190) NOT NULL DEFAULT "",
    phone VARCHAR(30) NOT NULL DEFAULT "",
    address VARCHAR(255) NOT NULL DEFAULT "",
    program VARCHAR(150) NOT NULL DEFAULT "",
    session VARCHAR(40) NOT NULL DEFAULT "",
    batch VARCHAR(20) NOT NULL DEFAULT "",
    current_semester TINYINT NOT NULL DEFAULT 1,
    photo VARCHAR(255) NOT NULL DEFAULT "",
    status ENUM("active","graduated","suspended","dropped") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept (department_id),
    INDEX idx_status (status),
    INDEX idx_sem (current_semester),
    INDEX idx_adm (admission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const STU_SEMESTERS = 8;
const STU_STATUS = [
    'active'    => ['Active', 'st-approved'],
    'graduated' => ['Graduated', 'st-enrolled'],
    'suspended' => ['Suspended', 'st-pending'],
    'dropped'   => ['Dropped', 'st-rejected'],
];

function stu_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('students') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

function stu_url(string $path = ''): string
{
    return UMS_URL . '/modules/students/' . $path;
}
