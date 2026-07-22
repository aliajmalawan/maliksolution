<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_events.php');
    exit;
}

$action   = $_POST['action']    ?? '';
$event_id = (int)($_POST['event_id'] ?? 0);
$title    = trim($_POST['title']       ?? '');
$desc     = trim($_POST['description'] ?? '');
$date     = trim($_POST['event_date']  ?? '');
$time     = trim($_POST['event_time']  ?? '');
$location = trim($_POST['location']    ?? '');
$status   = $_POST['status']           ?? 'active';
$old_img  = basename($_POST['old_image'] ?? '');   // sanitise to filename only

if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = null;

$upload_dir = __DIR__ . '/../assets/uploads/events/';
$msg        = 'error';

/* ── Image upload helper ─────────────────────────────────── */
function handle_image_upload(string $upload_dir, string $old_filename = ''): string
{
    if (empty($_FILES['image']['tmp_name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return $old_filename;   // no new file → keep existing
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['image']['tmp_name']);

    if (!in_array($mime, $allowed_types, true)) return $old_filename;
    if ($_FILES['image']['size'] > 3 * 1024 * 1024) return $old_filename; // 3 MB limit

    $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $new_name = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $dest     = $upload_dir . $new_name;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) return $old_filename;

    // Delete old image once new one is saved
    if ($old_filename && file_exists($upload_dir . $old_filename)) {
        @unlink($upload_dir . $old_filename);
    }

    return $new_name;
}

/* ── CREATE ─────────────────────────────────────────────── */
if ($action === 'create' && $title !== '') {
    $img_name = handle_image_upload($upload_dir);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO events (title, description, event_date, event_time, location, image, status)
         VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssssss',
        $title, $desc, $date, $time, $location, $img_name, $status);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── EDIT ────────────────────────────────────────────────── */
elseif ($action === 'edit' && $event_id > 0 && $title !== '') {
    $img_name = handle_image_upload($upload_dir, $old_img);

    $stmt = mysqli_prepare($conn,
        "UPDATE events
         SET title=?, description=?, event_date=?, event_time=?, location=?, image=?, status=?
         WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'sssssssi',
        $title, $desc, $date, $time, $location, $img_name, $status, $event_id);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── DELETE ──────────────────────────────────────────────── */
elseif ($action === 'delete' && $event_id > 0) {
    // Fetch image filename before deleting row
    $r = mysqli_query($conn, "SELECT image FROM events WHERE id=$event_id");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $img_to_del = $row['image'] ?? '';
        if ($img_to_del && file_exists($upload_dir . $img_to_del)) {
            @unlink($upload_dir . $img_to_del);
        }
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $event_id);
    $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
    mysqli_stmt_close($stmt);
}

header("Location: manage_events.php?msg=$msg");
exit;
