<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: courses.php');
    exit;
}

$action    = $_POST['action']    ?? '';
$course_id = (int)($_POST['course_id'] ?? 0);
$name      = trim($_POST['name']        ?? '');
$desc      = trim($_POST['description'] ?? '');
$duration  = trim($_POST['duration']    ?? '');
$fee       = trim($_POST['fee']         ?? '');
$status    = $_POST['status'] ?? 'active';

if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';

$msg = 'error';

/* ── Ensure course_subjects table ── */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS course_subjects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    course_id   INT NOT NULL,
    name        VARCHAR(255) NOT NULL DEFAULT '',
    description VARCHAR(500) DEFAULT '',
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── ADD SUBJECT ── */
if ($action === 'add_subject' && $course_id > 0) {
    $subj_name = trim($_POST['subj_name'] ?? '');
    $subj_desc = trim($_POST['subj_desc'] ?? '');
    if ($subj_name !== '') {
        $ord_r = mysqli_fetch_row(mysqli_query($conn,
            "SELECT COALESCE(MAX(sort_order)+1,0) FROM course_subjects WHERE course_id=$course_id"));
        $sort  = (int)($ord_r[0] ?? 0);
        $stmt  = mysqli_prepare($conn,
            "INSERT INTO course_subjects (course_id, name, description, sort_order) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'issi', $course_id, $subj_name, $subj_desc, $sort);
        $msg = mysqli_stmt_execute($stmt) ? 'subj_added' : 'error';
        mysqli_stmt_close($stmt);
    }
    header("Location: courses.php?msg=$msg&subj_open=$course_id");
    exit;
}

/* ── EDIT SUBJECT ── */
if ($action === 'edit_subject') {
    $subj_id   = (int)($_POST['subj_id']   ?? 0);
    $subj_name = trim($_POST['subj_name']  ?? '');
    $subj_desc = trim($_POST['subj_desc']  ?? '');
    if ($subj_id > 0 && $subj_name !== '') {
        $stmt = mysqli_prepare($conn,
            "UPDATE course_subjects SET name=?, description=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssi', $subj_name, $subj_desc, $subj_id);
        $msg = mysqli_stmt_execute($stmt) ? 'subj_saved' : 'error';
        mysqli_stmt_close($stmt);
    }
    header("Location: courses.php?msg=$msg&subj_open=$course_id");
    exit;
}

/* ── DELETE SUBJECT ── */
if ($action === 'del_subject') {
    $subj_id = (int)($_POST['subj_id'] ?? 0);
    if ($subj_id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM course_subjects WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $subj_id);
        $msg = mysqli_stmt_execute($stmt) ? 'subj_deleted' : 'error';
        mysqli_stmt_close($stmt);
    }
    header("Location: courses.php?msg=$msg&subj_open=$course_id");
    exit;
}

if ($action === 'create' && $name !== '') {
    // Ensure new columns exist before inserting
    ensure_column($conn, 'courses', 'duration', "VARCHAR(100) DEFAULT ''");
    ensure_column($conn, 'courses', 'fee',      "VARCHAR(100) DEFAULT ''");
    ensure_column($conn, 'courses', 'status',   "ENUM('active','inactive') DEFAULT 'active'");

    $stmt = mysqli_prepare($conn,
        "INSERT INTO courses (name, description, duration, fee, status) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssss', $name, $desc, $duration, $fee, $status);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);

} elseif ($action === 'edit' && $course_id > 0 && $name !== '') {
    $stmt = mysqli_prepare($conn,
        "UPDATE courses SET name=?, description=?, duration=?, fee=?, status=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'sssssi', $name, $desc, $duration, $fee, $status, $course_id);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);

} elseif ($action === 'delete' && $course_id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM courses WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $course_id);
    $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
    mysqli_stmt_close($stmt);
}

header("Location: courses.php?msg=$msg");
exit;
