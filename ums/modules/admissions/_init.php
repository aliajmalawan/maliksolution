<?php
declare(strict_types=1);

/**
 * Admissions module — shared init.
 * Loaded by every page in this module. Boots the app, gates access to
 * staff roles, ensures the module's own table exists, and provides the
 * constants + helpers all admissions pages share.
 *
 * Follows the project rule: each module owns (creates) its own tables.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'accountant']);

/** Current user — available to every page that includes this init. */
$user = ums_user();

/** Ensure the admissions table exists (idempotent). */
(function (): void {
    ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('admissions') . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campus_id INT NOT NULL DEFAULT 1,
        application_no VARCHAR(30) NOT NULL DEFAULT "",
        student_name VARCHAR(150) NOT NULL,
        father_name VARCHAR(150) NOT NULL DEFAULT "",
        gender ENUM("male","female","other") NOT NULL DEFAULT "male",
        dob DATE NULL,
        cnic VARCHAR(20) NOT NULL DEFAULT "",
        email VARCHAR(190) NOT NULL DEFAULT "",
        phone VARCHAR(30) NOT NULL DEFAULT "",
        address VARCHAR(255) NOT NULL DEFAULT "",
        program_id INT NOT NULL DEFAULT 0,
        program VARCHAR(150) NOT NULL DEFAULT "",
        session VARCHAR(40) NOT NULL DEFAULT "",
        last_qualification VARCHAR(120) NOT NULL DEFAULT "",
        obtained_marks INT NOT NULL DEFAULT 0,
        total_marks INT NOT NULL DEFAULT 0,
        board_university VARCHAR(150) NOT NULL DEFAULT "",
        merit_score DECIMAL(6,2) NOT NULL DEFAULT 0,
        photo VARCHAR(255) NOT NULL DEFAULT "",
        remarks TEXT,
        status ENUM("pending","approved","rejected","enrolled") NOT NULL DEFAULT "pending",
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_session (session),
        INDEX idx_program (program_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // Add approved_at (set automatically when an application is approved).
    $has = ums_db()->query("SHOW COLUMNS FROM " . tbl('admissions') . " LIKE 'approved_at'");
    if ($has && $has->num_rows === 0) {
        ums_db()->query('ALTER TABLE ' . tbl('admissions') . ' ADD COLUMN approved_at DATETIME NULL AFTER status');
        // Backfill existing approved/enrolled rows with their last-updated date
        ums_db()->query('UPDATE ' . tbl('admissions') . ' SET approved_at = updated_at
                         WHERE status IN ("approved","enrolled") AND approved_at IS NULL');
    }
})();

/**
 * Programs offered. Until the Academic/Programs module (Phase 2) ships,
 * the list is defined here; the admissions table already carries
 * program_id so records can be linked to real programs later.
 */
const ADM_PROGRAMS = [
    'BS Computer Science', 'BS Software Engineering', 'BS Information Technology',
    'BS Data Science', 'BS Artificial Intelligence', 'BS Cyber Security',
    'BBA', 'BS Accounting & Finance', 'BS Economics',
    'BS Mathematics', 'BS Physics', 'BS English',
];

/** Academic sessions available for application. */
const ADM_SESSIONS = ['Fall 2026', 'Spring 2027', 'Fall 2027'];

/** Status metadata: label, badge class, icon. */
const ADM_STATUS = [
    'pending'  => ['Pending',  'st-pending',  'fa-clock'],
    'approved' => ['Approved', 'st-approved', 'fa-circle-check'],
    'rejected' => ['Rejected', 'st-rejected', 'fa-circle-xmark'],
    'enrolled' => ['Enrolled', 'st-enrolled', 'fa-user-graduate'],
];

/** Render a status badge. */
function adm_badge(string $status): string
{
    [$label, $cls] = ADM_STATUS[$status] ?? ['Unknown', 'st'];
    return '<span class="st ' . $cls . '">' . e($label) . '</span>';
}

/** Initials from a name. */
function adm_ini(string $name): string
{
    $p = preg_split('/\s+/', trim($name));
    return strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[count($p) - 1] ?? '', 0, 1));
}

/** Fetch one application by id, or null. */
function adm_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('admissions') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Module URL helper. */
function adm_url(string $path = ''): string
{
    return UMS_URL . '/modules/admissions/' . $path;
}

/** The enrolled student record linked to this application, if one exists. */
function adm_linked_student(int $admissionId): ?array
{
    try {
        $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('students') . ' WHERE admission_id = ? LIMIT 1');
        $stmt->bind_param('i', $admissionId); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
        return $row ?: null;
    } catch (Throwable $t) {
        return null; // students table not created yet
    }
}
