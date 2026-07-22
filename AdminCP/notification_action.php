<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: ../admin/login.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_notifications.php');
    exit;
}

$action   = $_POST['action']    ?? '';
$notif_id = (int)($_POST['notif_id'] ?? 0);
$title    = trim($_POST['title']       ?? '');
$message  = trim($_POST['message']     ?? '');
$type     = trim($_POST['type']        ?? 'info');
$icon     = trim($_POST['icon']        ?? 'fa-solid fa-bell');
$show_pgs = trim($_POST['show_pages']  ?? 'all');
$link_txt = trim($_POST['link_text']   ?? '');
$link_url = trim($_POST['link_url']    ?? '');
$start    = trim($_POST['start_date']  ?? '');
$end      = trim($_POST['end_date']    ?? '');
$order    = (int)($_POST['sort_order'] ?? 0);
$status   = $_POST['status']           ?? 'active';

if (!in_array($type,   ['info','success','warning','danger'], true)) $type   = 'info';
if (!in_array($status, ['active','inactive'],                  true)) $status = 'active';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = null;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $end   = null;

// Validate show_pages: must be 'all' or comma-separated page slugs
$allowed_pages = ['index.php','about.php','courses.php','admissions.php','faculty.php',
                  'campuses.php','resources.php','gallery.php','contact.php',
                  'datesheet.php','downloads.php','news.php'];
if ($show_pgs !== 'all') {
    $parts = array_filter(array_map('trim', explode(',', $show_pgs)));
    $parts = array_values(array_filter($parts, fn($p) => in_array($p, $allowed_pages, true)));
    $show_pgs = $parts ? implode(',', $parts) : 'all';
}

$msg = 'error';

/* ── CREATE ─────────────────────────────────────────────── */
if ($action === 'create' && $title !== '' && $message !== '') {
    $stmt = mysqli_prepare($conn,
        "INSERT INTO notifications
         (title, message, type, icon, show_pages, link_text, link_url,
          start_date, end_date, sort_order, status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssssssssis',
        $title, $message, $type, $icon, $show_pgs,
        $link_txt, $link_url, $start, $end, $order, $status);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── EDIT ────────────────────────────────────────────────── */
elseif ($action === 'edit' && $notif_id > 0 && $title !== '' && $message !== '') {
    $stmt = mysqli_prepare($conn,
        "UPDATE notifications
         SET title=?, message=?, type=?, icon=?, show_pages=?,
             link_text=?, link_url=?, start_date=?, end_date=?,
             sort_order=?, status=?
         WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'sssssssssisi',
        $title, $message, $type, $icon, $show_pgs,
        $link_txt, $link_url, $start, $end, $order, $status, $notif_id);
    $msg = mysqli_stmt_execute($stmt) ? 'saved' : 'error';
    mysqli_stmt_close($stmt);
}

/* ── DELETE ──────────────────────────────────────────────── */
elseif ($action === 'delete' && $notif_id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $notif_id);
    $msg = mysqli_stmt_execute($stmt) ? 'deleted' : 'error';
    mysqli_stmt_close($stmt);
}

header("Location: manage_notifications.php?msg=$msg");
exit;
