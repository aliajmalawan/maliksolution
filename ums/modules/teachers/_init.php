<?php
declare(strict_types=1);

/**
 * Teacher Management module — shared init.
 * Faculty/staff records, each linked to a department. Owns ums_teachers.
 * Attendance, Timetable and Payroll will reference these records later.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();

// Department options without pulling in the whole departments module init.
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
        } catch (Throwable $t) { /* departments module not in use yet */ }
        return $out;
    }
}

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('teachers') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    department_id INT NOT NULL DEFAULT 0,
    employee_no VARCHAR(30) NOT NULL DEFAULT "",
    name VARCHAR(150) NOT NULL,
    gender ENUM("male","female","other") NOT NULL DEFAULT "male",
    dob DATE NULL,
    cnic VARCHAR(20) NOT NULL DEFAULT "",
    email VARCHAR(190) NOT NULL DEFAULT "",
    phone VARCHAR(30) NOT NULL DEFAULT "",
    address VARCHAR(255) NOT NULL DEFAULT "",
    designation VARCHAR(60) NOT NULL DEFAULT "Lecturer",
    qualification VARCHAR(150) NOT NULL DEFAULT "",
    joining_date DATE NULL,
    salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    photo VARCHAR(255) NOT NULL DEFAULT "",
    status ENUM("active","on_leave","inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const TCH_DESIGNATIONS = ['Professor', 'Associate Professor', 'Assistant Professor', 'Lecturer', 'Lab Instructor', 'Visiting Faculty'];
const TCH_STATUS = [
    'active'   => ['Active', 'st-approved'],
    'on_leave' => ['On Leave', 'st-pending'],
    'inactive' => ['Inactive', 'st-rejected'],
];

// tch_find() now lives in includes/crud.php — shared with the Teacher Portal.

function tch_url(string $path = ''): string
{
    return UMS_URL . '/modules/teachers/' . $path;
}
