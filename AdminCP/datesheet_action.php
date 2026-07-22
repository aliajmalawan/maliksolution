<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_datesheets.php');
    exit;
}

$action    = $_POST['action']    ?? '';
$ds_id     = (int)($_POST['ds_id']    ?? 0);
$title     = trim($_POST['title']     ?? '');
$program   = trim($_POST['program']   ?? 'All Programs');
$exam_type = trim($_POST['exam_type'] ?? '');
$session   = trim($_POST['session']   ?? '');
$status    = $_POST['status']         ?? 'active';
$old_file  = basename($_POST['old_file'] ?? '');

if (!in_array($status, ['active','inactive'], true)) $status = 'active';

$upload_dir = __DIR__ . '/../assets/uploads/datesheets/';
$msg        = 'error';

/* ── Ensure datesheet_subjects table exists ── */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `datesheet_subjects` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `datesheet_id` INT          NOT NULL,
    `subject_name` VARCHAR(255) NOT NULL DEFAULT '',
    `exam_date`    DATE         DEFAULT NULL,
    `start_time`   TIME         DEFAULT NULL,
    `end_time`     TIME         DEFAULT NULL,
    `venue`        VARCHAR(255) DEFAULT '',
    `sort_order`   INT          DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── File upload helper ── */
function handle_ds_file(string $upload_dir, string $old = '', bool $required = false): array
{
    $uploaded = !empty($_FILES['file']['tmp_name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
    if (!$uploaded) {
        return $required ? ['',''] : [$old, strtoupper(pathinfo($old, PATHINFO_EXTENSION))];
    }
    $allowed = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg','image/png','image/webp','image/gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['file']['tmp_name']);
    if (!in_array($mime, $allowed, true) || $_FILES['file']['size'] > 10*1024*1024)
        return $required ? ['',''] : [$old, strtoupper(pathinfo($old, PATHINFO_EXTENSION))];

    $ext      = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $new_name = 'ds_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $new_name))
        return $required ? ['',''] : [$old, strtoupper(pathinfo($old, PATHINFO_EXTENSION))];

    if ($old && file_exists($upload_dir . $old)) @unlink($upload_dir . $old);
    return [$new_name, strtoupper($ext)];
}

/* ── Save subjects helper ── */
function save_subjects(mysqli $conn, int $datesheet_id): void
{
    // Delete existing subjects for this datesheet
    $del = mysqli_prepare($conn, "DELETE FROM datesheet_subjects WHERE datesheet_id = ?");
    if ($del) { mysqli_stmt_bind_param($del, 'i', $datesheet_id); mysqli_stmt_execute($del); mysqli_stmt_close($del); }

    $subjects = $_POST['subjects'] ?? [];
    if (!is_array($subjects)) return;

    $order = 0;
    foreach ($subjects as $subj) {
        $name  = trim($subj['name']       ?? '');
        if ($name === '') continue;

        $date  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $subj['date']       ?? '') ? $subj['date']       : null;
        $start = preg_match('/^\d{2}:\d{2}/',         $subj['start_time'] ?? '') ? $subj['start_time'] : null;
        $end   = preg_match('/^\d{2}:\d{2}/',         $subj['end_time']   ?? '') ? $subj['end_time']   : null;
        $venue = trim($subj['venue'] ?? '');

        $ins = mysqli_prepare($conn,
            "INSERT INTO datesheet_subjects
             (datesheet_id, subject_name, exam_date, start_time, end_time, venue, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($ins) {
            mysqli_stmt_bind_param($ins, 'isssssi',
                $datesheet_id, $name, $date, $start, $end, $venue, $order);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        }
        $order++;
    }
}

/* ── CREATE ── */
if ($action === 'create' && $title !== '') {
    [$file, $file_type] = handle_ds_file($upload_dir, '', false);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO datesheets (title, program, exam_type, session, file, file_type, sort_order, status)
         VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssssss',
            $title, $program, $exam_type, $session, $file, $file_type, $status);
        if (mysqli_stmt_execute($stmt)) {
            $new_id = (int)mysqli_insert_id($conn);
            save_subjects($conn, $new_id);
            $msg = 'saved';
        }
        mysqli_stmt_close($stmt);
    }
}

/* ── EDIT ── */
elseif ($action === 'edit' && $ds_id > 0 && $title !== '') {
    [$file, $file_type] = handle_ds_file($upload_dir, $old_file, false);

    $stmt = mysqli_prepare($conn,
        "UPDATE datesheets
         SET title=?, program=?, exam_type=?, session=?, file=?, file_type=?, status=?
         WHERE id=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssssssi',
            $title, $program, $exam_type, $session, $file, $file_type, $status, $ds_id);
        if (mysqli_stmt_execute($stmt)) {
            save_subjects($conn, $ds_id);
            $msg = 'saved';
        }
        mysqli_stmt_close($stmt);
    }
}

/* ── DELETE ── */
elseif ($action === 'delete' && $ds_id > 0) {
    // Delete file
    $r = mysqli_query($conn, "SELECT file FROM datesheets WHERE id = $ds_id");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $f = $row['file'] ?? '';
        if ($f && file_exists($upload_dir . $f)) @unlink($upload_dir . $f);
    }
    // Delete subjects (CASCADE not guaranteed, so explicit delete)
    mysqli_query($conn, "DELETE FROM datesheet_subjects WHERE datesheet_id = $ds_id");
    // Delete datesheet
    $stmt = mysqli_prepare($conn, "DELETE FROM datesheets WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $ds_id);
        $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
        mysqli_stmt_close($stmt);
    }
}

header("Location: manage_datesheets.php?msg=$msg");
exit;
