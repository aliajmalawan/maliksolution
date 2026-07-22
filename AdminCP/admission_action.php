<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admissions.php');
    exit;
}

$id              = (int)($_POST['id']             ?? 0);
$action          = trim($_POST['action']          ?? '');
$new_status      = trim($_POST['new_status']      ?? '');
$redirect_status = trim($_POST['redirect_status'] ?? 'all');

if (!in_array($redirect_status, ['all','pending','approved','rejected'], true)) {
    $redirect_status = 'all';
}

if ($id < 1) {
    header("Location: admissions.php?status=$redirect_status&error=1");
    exit;
}

/* ── Helpers ───────────────────────────────────────────────────── */

function make_username(mysqli $conn, string $name): string
{
    // "Muhammad Ali Khan" → "muhammad.ali.khan"
    $base = strtolower(preg_replace('/[^a-zA-Z\s]/', '', $name));
    $base = trim(preg_replace('/\s+/', '.', $base));
    if ($base === '') $base = 'student';

    $username = $base;
    $i = 1;
    while (true) {
        $safe = mysqli_real_escape_string($conn, $username);
        $r    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions WHERE username='$safe'"));
        if ((int)($r[0] ?? 0) === 0) break;
        $username = $base . $i++;
    }
    return $username;
}

function make_password(): string
{
    // e.g. Ms@7x3K
    $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower  = 'abcdefghjkmnpqrstuvwxyz';
    $digits = '23456789';
    return 'Ms@'
        . $upper[random_int(0, strlen($upper) - 1)]
        . $digits[random_int(0, strlen($digits) - 1)]
        . $lower[random_int(0, strlen($lower) - 1)]
        . $digits[random_int(0, strlen($digits) - 1)];
}

/* ── RESET STUDENT PASSWORD ────────────────────────────────────── */
if ($action === 'reset_password') {
    $new_pw = trim($_POST['new_password'] ?? '');
    if (strlen($new_pw) < 6) {
        header("Location: admissions.php?status=$redirect_status&error=pw_short");
        exit;
    }
    $stmt = mysqli_prepare($conn, "UPDATE admissions SET student_password = ? WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $new_pw, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: admissions.php?status=$redirect_status&updated=pw_reset");
    exit;
}

/* ── EDIT STUDENT ──────────────────────────────────────────────── */
if ($action === 'edit_student') {
    $student_name = trim($_POST['student_name'] ?? '');
    $father_name  = trim($_POST['father_name']  ?? '');
    $program      = trim($_POST['program']      ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $email        = trim($_POST['email']        ?? '');
    $address      = trim($_POST['address']      ?? '');
    $message      = trim($_POST['message']      ?? '');
    $new_pw       = trim($_POST['new_password'] ?? '');
    $upd_status   = trim($_POST['status']       ?? '');

    // Fetch current row — DB values are the authoritative fallback
    $cur = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT status, username, student_id FROM admissions WHERE id = $id"));

    // If no valid status submitted (e.g., radio not checked), keep the DB value
    if (!in_array($upd_status, ['pending', 'approved', 'rejected'], true)) {
        $upd_status = $cur['status'] ?? 'pending';
    }

    // Update all personal fields + status
    $stmt = mysqli_prepare($conn,
        "UPDATE admissions SET student_name=?, father_name=?, program=?, phone=?, email=?, address=?, message=?, status=? WHERE id=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssssssssi',
            $student_name, $father_name, $program, $phone, $email, $address, $message, $upd_status, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Handle student photo upload
    if (!empty($_FILES['student_photo']['tmp_name'])) {
        $file     = $_FILES['student_photo'];
        $allowed  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        $mime     = mime_content_type($file['tmp_name']);
        if ($file['size'] <= 2097152 && isset($allowed[$mime])) {
            $ext = $allowed[$mime];
            $dir = __DIR__ . '/../assets/uploads/students/' . $id . '/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            foreach (glob($dir . 'photo.*') ?: [] as $old) @unlink($old);
            if (move_uploaded_file($file['tmp_name'], $dir . 'photo.' . $ext)) {
                $photo_path = 'assets/uploads/students/' . $id . '/photo.' . $ext;
                $ps2 = mysqli_prepare($conn, "UPDATE admissions SET student_photo=? WHERE id=?");
                if ($ps2) { mysqli_stmt_bind_param($ps2,'si',$photo_path,$id); mysqli_stmt_execute($ps2); mysqli_stmt_close($ps2); }
            }
        }
    }

    // Update password only if provided
    if ($new_pw !== '') {
        if (strlen($new_pw) < 6) {
            header("Location: admissions.php?status=$redirect_status&error=pw_short");
            exit;
        }
        $ps = mysqli_prepare($conn, "UPDATE admissions SET student_password = ? WHERE id = ?");
        if ($ps) {
            mysqli_stmt_bind_param($ps, 'si', $new_pw, $id);
            mysqli_stmt_execute($ps);
            mysqli_stmt_close($ps);
        }
    }

    // Generate credentials if status changed to approved and none exist yet
    if ($upd_status === 'approved' && empty($cur['username'])) {
        ensure_column($conn, 'admissions', 'username',         "VARCHAR(100) DEFAULT ''");
        ensure_column($conn, 'admissions', 'student_password', "VARCHAR(100) DEFAULT ''");
        ensure_column($conn, 'admissions', 'student_id',       "VARCHAR(30) DEFAULT ''");
        $username   = make_username($conn, $student_name ?: 'student');
        $password   = $new_pw !== '' ? $new_pw : make_password();
        $student_id = !empty($cur['student_id'])
            ? $cur['student_id']
            : 'MS-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        $upd2 = mysqli_prepare($conn, "UPDATE admissions SET username=?, student_password=?, student_id=? WHERE id=?");
        if ($upd2) {
            mysqli_stmt_bind_param($upd2, 'sssi', $username, $password, $student_id, $id);
            mysqli_stmt_execute($upd2);
            mysqli_stmt_close($upd2);
        }
        $_SESSION['new_creds'] = [
            'name'       => $student_name,
            'student_id' => $student_id,
            'username'   => $username,
            'password'   => $password,
        ];
    }

    header("Location: admissions.php?status=$redirect_status&updated=edited");
    exit;
}

/* ── DELETE ────────────────────────────────────────────────────── */
if ($action === 'delete') {
    $stmt = mysqli_prepare($conn, "DELETE FROM admissions WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: admissions.php?status=$redirect_status&updated=deleted");
    exit;
}

/* ── STATUS UPDATE ─────────────────────────────────────────────── */
$allowed = ['pending', 'approved', 'rejected'];
if (!in_array($new_status, $allowed, true)) {
    header("Location: admissions.php?status=$redirect_status");
    exit;
}

// Ensure columns exist for credentials
ensure_column($conn, 'admissions', 'username',         "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'admissions', 'student_password', "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'admissions', 'student_id',       "VARCHAR(30) DEFAULT ''");

// Fetch current row
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_name, username, student_password, student_id FROM admissions WHERE id = $id"));

// Update status
$stmt = mysqli_prepare($conn, "UPDATE admissions SET status = ? WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Generate credentials only when approving AND none exist yet
if ($new_status === 'approved' && empty($row['username'])) {
    $username   = make_username($conn, $row['student_name'] ?? 'student');
    $password   = make_password();
    $student_id = 'MS-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);

    $upd = mysqli_prepare($conn, "UPDATE admissions SET username=?, student_password=?, student_id=? WHERE id=?");
    if ($upd) {
        mysqli_stmt_bind_param($upd, 'sssi', $username, $password, $student_id, $id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
    }

    // Flash credentials so admissions.php can display them once
    $_SESSION['new_creds'] = [
        'name'       => $row['student_name'] ?? '',
        'student_id' => $student_id,
        'username'   => $username,
        'password'   => $password,
    ];
}

header("Location: admissions.php?status=$redirect_status&updated=1");
exit;
