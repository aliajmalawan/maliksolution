<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_gallery.php');
    exit;
}

$action     = $_POST['action']     ?? '';
$gallery_id = (int)($_POST['gallery_id'] ?? 0);
$title      = trim($_POST['title']       ?? '');
$desc       = trim($_POST['description'] ?? '');
$category   = trim($_POST['category']    ?? '');
$sort_order = (int)($_POST['sort_order'] ?? 0);
$status     = $_POST['status']           ?? 'active';
$old_img    = basename($_POST['old_image'] ?? '');   // basename blocks path traversal

if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';
if ($sort_order < 0) $sort_order = 0;

$upload_dir = __DIR__ . '/../assets/uploads/gallery/';
$msg        = 'error';

/* ─────────────────────────────────────────────────────────────
   Image upload helper
   Returns new filename, or $old_filename if nothing uploaded.
   ───────────────────────────────────────────────────────────── */
function handle_gallery_image(string $upload_dir, string $old_filename = '', bool $required = false): string
{
    $uploaded = !empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;

    if (!$uploaded) {
        return $required ? '' : $old_filename;  // empty string signals "required but missing"
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($_FILES['image']['tmp_name']);

    if (!in_array($mime, $allowed, true))           return $required ? '' : $old_filename;
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) return $required ? '' : $old_filename; // 5 MB

    $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $new_name = 'gallery_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $upload_dir . $new_name;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) return $required ? '' : $old_filename;

    // Delete old file once new one is saved successfully
    if ($old_filename && file_exists($upload_dir . $old_filename)) {
        @unlink($upload_dir . $old_filename);
    }

    return $new_name;
}

/* ── CREATE ─────────────────────────────────────────────────── */
if ($action === 'create' && $title !== '') {
    $img = handle_gallery_image($upload_dir, '', true);  // image required on create

    if ($img !== '') {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO gallery (title, description, image, category, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssssIs', $title, $desc, $img, $category, $sort_order, $status);
        $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
        mysqli_stmt_close($stmt);
    }
    // else $msg stays 'error' (missing/invalid image)
}

/* ── EDIT ────────────────────────────────────────────────────── */
elseif ($action === 'edit' && $gallery_id > 0 && $title !== '') {
    $img = handle_gallery_image($upload_dir, $old_img, false);  // image optional on edit

    $stmt = mysqli_prepare($conn,
        "UPDATE gallery
         SET title=?, description=?, image=?, category=?, sort_order=?, status=?
         WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssssIsi', $title, $desc, $img, $category, $sort_order, $status, $gallery_id);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── DELETE ──────────────────────────────────────────────────── */
elseif ($action === 'delete' && $gallery_id > 0) {
    $r = mysqli_query($conn, "SELECT image FROM gallery WHERE id = $gallery_id");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $img_del = $row['image'] ?? '';
        if ($img_del && file_exists($upload_dir . $img_del)) {
            @unlink($upload_dir . $img_del);
        }
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM gallery WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $gallery_id);
    $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
    mysqli_stmt_close($stmt);
}

header("Location: manage_gallery.php?msg=$msg");
exit;
