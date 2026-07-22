<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

function tcp_make_password(int $len = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $p = '';
    for ($i = 0; $i < $len; $i++) $p .= $chars[random_int(0, strlen($chars) - 1)];
    return $p;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: teachers.php');
    exit;
}

$action      = $_POST['action']      ?? '';
$teacher_id  = (int)($_POST['teacher_id']  ?? 0);
$name        = trim($_POST['name']         ?? '');
$designation = trim($_POST['designation']  ?? '');
$department  = trim($_POST['department']   ?? '');
$qualification = trim($_POST['qualification'] ?? '');
$experience  = trim($_POST['experience']   ?? '');
$email       = trim($_POST['email']        ?? '');
$phone       = trim($_POST['phone']        ?? '');
$bio         = trim($_POST['bio']          ?? '');
$status      = $_POST['status']            ?? 'active';
$sort_order  = (int)($_POST['sort_order']  ?? 0);
$old_photo   = basename($_POST['old_photo'] ?? '');   // basename blocks path traversal

if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';
if ($sort_order < 0) $sort_order = 0;

$upload_dir = __DIR__ . '/../assets/uploads/teachers/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$msg = 'error';

/* ─────────────────────────────────────────────────────────
   Photo upload helper.
   Returns new filename on success, or $old_filename if no
   new file was provided or the upload was invalid.
───────────────────────────────────────────────────────── */
function handle_teacher_photo(string $upload_dir, string $old_filename = ''): string
{
    $uploaded = !empty($_FILES['photo']['tmp_name'])
                && $_FILES['photo']['error'] === UPLOAD_ERR_OK;

    if (!$uploaded) return $old_filename;

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($_FILES['photo']['tmp_name']);

    if (!in_array($mime, $allowed, true))             return $old_filename;
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024)  return $old_filename;  // 5 MB cap

    $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $new_name = 'teacher_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $upload_dir . $new_name;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) return $old_filename;

    // Remove old photo now that new one is safely saved
    if ($old_filename && file_exists($upload_dir . $old_filename)) {
        @unlink($upload_dir . $old_filename);
    }

    return $new_name;
}

/* ── ASSIGN SUBJECT ─────────────────────────────────────── */
if ($action === 'assign_subject' && $teacher_id > 0) {
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    if ($subject_id > 0) {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teacher_subject_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL, subject_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_teacher_subject (teacher_id, subject_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = mysqli_prepare($conn,
            "INSERT IGNORE INTO teacher_subject_assignments (teacher_id, subject_id) VALUES (?,?)");
        mysqli_stmt_bind_param($stmt, 'ii', $teacher_id, $subject_id);
        $msg = mysqli_stmt_execute($stmt) ? 'subj_assigned' : 'error';
        mysqli_stmt_close($stmt);
    }
    header("Location: teachers.php?msg=$msg&subj_open=$teacher_id");
    exit;
}

/* ── REMOVE SUBJECT ──────────────────────────────────────── */
if ($action === 'remove_subject' && $teacher_id > 0) {
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    if ($subject_id > 0) {
        $stmt = mysqli_prepare($conn,
            "DELETE FROM teacher_subject_assignments WHERE teacher_id=? AND subject_id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $teacher_id, $subject_id);
        $msg = mysqli_stmt_execute($stmt) ? 'subj_removed' : 'error';
        mysqli_stmt_close($stmt);
    }
    header("Location: teachers.php?msg=$msg&subj_open=$teacher_id");
    exit;
}

/* ── RESET CREDENTIALS ───────────────────────────────────── */
if ($action === 'reset_creds' && $teacher_id > 0) {
    $r = mysqli_query($conn, "SELECT name FROM teachers WHERE id=$teacher_id");
    $trow = $r ? mysqli_fetch_assoc($r) : null;
    if ($trow) {
        $uname  = 'TCH' . str_pad((string)$teacher_id, 3, '0', STR_PAD_LEFT);
        $plain  = tcp_make_password();
        $hashed = password_hash($plain, PASSWORD_DEFAULT);
        $us = mysqli_prepare($conn, "UPDATE teachers SET teacher_username=?, teacher_password_hash=?, teacher_password=? WHERE id=?");
        mysqli_stmt_bind_param($us, 'sssi', $uname, $hashed, $plain, $teacher_id);
        mysqli_stmt_execute($us);
        mysqli_stmt_close($us);
        $_SESSION['new_teacher_creds'] = ['name' => $trow['name'], 'username' => $uname, 'password' => $plain, 'id' => $teacher_id];
    }
    header("Location: teachers.php?msg=creds_reset");
    exit;
}

/* ── CREATE ──────────────────────────────────────────────── */
if ($action === 'create' && $name !== '') {
    $photo = handle_teacher_photo($upload_dir, '');

    $stmt = mysqli_prepare($conn,
        "INSERT INTO teachers
             (name, designation, department, qualification, experience,
              email, phone, bio, photo, status, sort_order)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssssssssssi',
        $name, $designation, $department, $qualification, $experience,
        $email, $phone, $bio, $photo, $status, $sort_order);
    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        // Auto-generate portal credentials
        $uname  = 'TCH' . str_pad((string)$new_id, 3, '0', STR_PAD_LEFT);
        $plain  = tcp_make_password();
        $hashed = password_hash($plain, PASSWORD_DEFAULT);
        $cs = mysqli_prepare($conn, "UPDATE teachers SET teacher_username=?, teacher_password_hash=?, teacher_password=? WHERE id=?");
        mysqli_stmt_bind_param($cs, 'sssi', $uname, $hashed, $plain, $new_id);
        mysqli_stmt_execute($cs);
        mysqli_stmt_close($cs);
        // Create teacher upload folder
        $tdir = __DIR__ . "/../assets/uploads/teachers/$new_id/";
        if (!is_dir($tdir)) @mkdir($tdir, 0755, true);
        // Store for one-time display
        $_SESSION['new_teacher_creds'] = ['name' => $name, 'username' => $uname, 'password' => $plain, 'id' => $new_id];
        $msg = 'saved';
    } else {
        $msg = 'error';
    }
    mysqli_stmt_close($stmt);
}

/* ── EDIT ────────────────────────────────────────────────── */
elseif ($action === 'edit' && $teacher_id > 0 && $name !== '') {
    $photo    = handle_teacher_photo($upload_dir, $old_photo);
    $new_pw   = trim($_POST['new_password'] ?? '');

    $stmt = mysqli_prepare($conn,
        "UPDATE teachers
         SET name=?, designation=?, department=?, qualification=?, experience=?,
             email=?, phone=?, bio=?, photo=?, status=?, sort_order=?
         WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssssssssssii',
        $name, $designation, $department, $qualification, $experience,
        $email, $phone, $bio, $photo, $status, $sort_order, $teacher_id);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);

    // Update password if provided
    if ($msg === 'saved' && $new_pw !== '') {
        if (strlen($new_pw) < 6) {
            header("Location: teachers.php?msg=pw_short");
            exit;
        }
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $ps = mysqli_prepare($conn, "UPDATE teachers SET teacher_password_hash=?, teacher_password=? WHERE id=?");
        mysqli_stmt_bind_param($ps, 'ssi', $hashed, $new_pw, $teacher_id);
        mysqli_stmt_execute($ps);
        mysqli_stmt_close($ps);
    }
}

/* ── DELETE ──────────────────────────────────────────────── */
elseif ($action === 'delete' && $teacher_id > 0) {
    // Fetch photo filename before deleting the row
    $r = mysqli_query($conn, "SELECT photo FROM teachers WHERE id = $teacher_id");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $photo_file = $row['photo'] ?? '';
        if ($photo_file && file_exists($upload_dir . $photo_file)) {
            @unlink($upload_dir . $photo_file);
        }
    }

    // Remove subject assignments first
    $sdel = mysqli_prepare($conn, "DELETE FROM teacher_subject_assignments WHERE teacher_id=?");
    mysqli_stmt_bind_param($sdel, 'i', $teacher_id);
    mysqli_stmt_execute($sdel);
    mysqli_stmt_close($sdel);

    $stmt = mysqli_prepare($conn, "DELETE FROM teachers WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $teacher_id);
    $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
    mysqli_stmt_close($stmt);
}

header("Location: teachers.php?msg=$msg");
exit;
