<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_downloads.php');
    exit;
}

$action      = $_POST['action']        ?? '';
$download_id = (int)($_POST['download_id'] ?? 0);
$title       = trim($_POST['title']        ?? '');
$desc        = trim($_POST['description']  ?? '');
$category    = trim($_POST['category']     ?? '');
$date_raw    = trim($_POST['download_date'] ?? '');
$sort_order  = (int)($_POST['sort_order']  ?? 0);
$status      = $_POST['status']            ?? 'active';
$old_file    = basename($_POST['old_file'] ?? '');   // basename prevents path traversal

if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';
if ($sort_order < 0) $sort_order = 0;
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_raw) ? $date_raw : null;

$upload_dir = __DIR__ . '/../assets/uploads/downloads/';
$msg        = 'error';

/* ─────────────────────────────────────────────────────────────
   Detect display extension label (PDF, DOCX, ZIP, etc.)
   ───────────────────────────────────────────────────────────── */
function detect_file_type(string $filename): string {
    return strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
}

/* ─────────────────────────────────────────────────────────────
   File upload helper
   Returns [new_filename, file_type_label] or ['', ''] on error.
   Pass $required=true to reject if no file uploaded.
   ───────────────────────────────────────────────────────────── */
function handle_download_file(string $upload_dir, string $old_filename = '', bool $required = false): array
{
    $uploaded = !empty($_FILES['file']['tmp_name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

    if (!$uploaded) {
        if ($required) return ['', ''];
        return [$old_filename, detect_file_type($old_filename)];
    }

    $allowed_mime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['file']['tmp_name']);

    if (!in_array($mime, $allowed_mime, true))              return $required ? ['',''] : [$old_filename, detect_file_type($old_filename)];
    if ($_FILES['file']['size'] > 10 * 1024 * 1024)        return $required ? ['',''] : [$old_filename, detect_file_type($old_filename)];

    $ext      = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $new_name = 'dl_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $upload_dir . $new_name;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest))
        return $required ? ['',''] : [$old_filename, detect_file_type($old_filename)];

    // Remove old file once new one is safely written
    if ($old_filename && file_exists($upload_dir . $old_filename)) {
        @unlink($upload_dir . $old_filename);
    }

    return [$new_name, strtoupper($ext)];
}

/* ── CREATE ─────────────────────────────────────────────────── */
if ($action === 'create' && $title !== '') {
    [$file, $file_type] = handle_download_file($upload_dir, '', true);

    if ($file !== '') {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO downloads (title, description, file, file_type, category, download_date, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssssIs',
            $title, $desc, $file, $file_type, $category, $date, $sort_order, $status);
        $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
        mysqli_stmt_close($stmt);
    }
}

/* ── EDIT ────────────────────────────────────────────────────── */
elseif ($action === 'edit' && $download_id > 0 && $title !== '') {
    [$file, $file_type] = handle_download_file($upload_dir, $old_file, false);

    $stmt = mysqli_prepare($conn,
        "UPDATE downloads
         SET title=?, description=?, file=?, file_type=?, category=?, download_date=?, sort_order=?, status=?
         WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssssssIsi',
        $title, $desc, $file, $file_type, $category, $date, $sort_order, $status, $download_id);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── DELETE ──────────────────────────────────────────────────── */
elseif ($action === 'delete' && $download_id > 0) {
    $r = mysqli_query($conn, "SELECT file FROM downloads WHERE id = $download_id");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $f = $row['file'] ?? '';
        if ($f && file_exists($upload_dir . $f)) @unlink($upload_dir . $f);
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM downloads WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $download_id);
    $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
    mysqli_stmt_close($stmt);
}

header("Location: manage_downloads.php?msg=$msg");
exit;
