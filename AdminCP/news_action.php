<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_news.php');
    exit;
}

$action   = $_POST['action']   ?? '';
$news_id  = (int)($_POST['news_id']  ?? 0);
$title    = trim($_POST['title']    ?? '');
$content  = trim($_POST['content']  ?? '');
$category = $_POST['category']      ?? 'news';
$date     = trim($_POST['news_date'] ?? '');
$status   = $_POST['status']        ?? 'active';
$old_img  = basename($_POST['old_image'] ?? '');   // basename prevents path traversal

// Validate enums
if (!in_array($category, ['news', 'announcement'], true)) $category = 'news';
if (!in_array($status,   ['active', 'inactive'],   true)) $status   = 'active';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = null;

$upload_dir = __DIR__ . '/../assets/uploads/news/';
$msg        = 'error';

/* ─────────────────────────────────────────────────────────────
   Image upload helper
   Returns the filename to store (new one or existing one).
   ───────────────────────────────────────────────────────────── */
function handle_news_image(string $upload_dir, string $old_filename = ''): string
{
    if (empty($_FILES['image']['tmp_name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return $old_filename;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($_FILES['image']['tmp_name']);

    if (!in_array($mime, $allowed, true))          return $old_filename;
    if ($_FILES['image']['size'] > 3 * 1024 * 1024) return $old_filename;  // 3 MB

    $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $new_name = 'news_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $upload_dir . $new_name;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) return $old_filename;

    // Remove old file after new one is saved
    if ($old_filename && file_exists($upload_dir . $old_filename)) {
        @unlink($upload_dir . $old_filename);
    }

    return $new_name;
}

/* ── CREATE ─────────────────────────────────────────────────── */
if ($action === 'create' && $title !== '') {
    $img = handle_news_image($upload_dir);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO news (title, content, category, image, news_date, status)
         VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssssss', $title, $content, $category, $img, $date, $status);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── EDIT ────────────────────────────────────────────────────── */
elseif ($action === 'edit' && $news_id > 0 && $title !== '') {
    $img = handle_news_image($upload_dir, $old_img);

    $stmt = mysqli_prepare($conn,
        "UPDATE news
         SET title=?, content=?, category=?, image=?, news_date=?, status=?
         WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssssssi',
        $title, $content, $category, $img, $date, $status, $news_id);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── DELETE ──────────────────────────────────────────────────── */
elseif ($action === 'delete' && $news_id > 0) {
    // Grab image filename before deleting the row
    $r = mysqli_query($conn, "SELECT image FROM news WHERE id = $news_id");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $img_del = $row['image'] ?? '';
        if ($img_del && file_exists($upload_dir . $img_del)) {
            @unlink($upload_dir . $img_del);
        }
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM news WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $news_id);
    $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
    mysqli_stmt_close($stmt);
}

header("Location: manage_news.php?msg=$msg");
exit;
