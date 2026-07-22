<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

// ── Require login for every AdminCP page ─────────────────
if (empty($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

// ── Current admin (topbar profile) ───────────────────────
ensure_column($conn, 'admins', 'name', "VARCHAR(100) DEFAULT ''");
$current_admin = ['id' => 0, 'name' => 'Administrator', 'email' => ''];
try {
    $_ast = mysqli_prepare($conn, "SELECT id, email, COALESCE(name,'') AS name FROM admins WHERE id = ?");
    mysqli_stmt_bind_param($_ast, 'i', $_SESSION['admin_id']);
    mysqli_stmt_execute($_ast);
    $_arow = mysqli_fetch_assoc(mysqli_stmt_get_result($_ast));
    mysqli_stmt_close($_ast);
    if (!$_arow) {
        session_destroy();
        header('Location: ../admin/login.php');
        exit;
    }
    $current_admin = [
        'id'    => (int)$_arow['id'],
        'name'  => $_arow['name'] !== '' ? $_arow['name'] : 'Administrator',
        'email' => $_arow['email'],
    ];
    $_SESSION['admin_email'] = $_arow['email'];
} catch (Exception $e) { /* admins table corrupt — keep defaults */ }
$admin_initial = mb_strtoupper(mb_substr($current_admin['name'], 0, 1));

// ── Theme (light/dark, stored in a cookie) ───────────────
$admin_theme = ($_COOKIE['admincp_theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

// Auto-repair corrupt InnoDB tables, then recreate them
$_all_tables = ['courses','admissions','contacts','events','news','gallery','notifications',
                'datesheets','downloads','teachers','teacher_subject_assignments',
                'teacher_quizzes','teacher_assignments','teacher_tests','teacher_announcements'];
foreach ($_all_tables as $_t) repair_if_corrupt($conn, $_t);
unset($_all_tables, $_t);

// Auto-create tables
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(100) DEFAULT 'fa-solid fa-book',
    description TEXT,
    full_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(255) NOT NULL,
    father_name VARCHAR(255) DEFAULT '',
    program VARCHAR(255) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    address TEXT,
    message TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    subject VARCHAR(255) DEFAULT '',
    message TEXT,
    date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Patch pre-existing tables that may be missing columns (safe on all MySQL versions)
ensure_column($conn, 'admissions', 'father_name', "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'admissions', 'program',     "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'admissions', 'phone',       "VARCHAR(20) DEFAULT ''");
ensure_column($conn, 'admissions', 'email',       "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'admissions', 'address',     "TEXT");
ensure_column($conn, 'admissions', 'message',     "TEXT");
ensure_column($conn, 'admissions', 'status',           "ENUM('pending','approved','rejected') DEFAULT 'pending'");
ensure_column($conn, 'admissions', 'created_at',      "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
ensure_column($conn, 'admissions', 'username',         "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'admissions', 'student_password', "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'admissions', 'student_id',       "VARCHAR(30) DEFAULT ''");
ensure_column($conn, 'admissions', 'student_photo',    "VARCHAR(200) DEFAULT ''");

// Retroactively assign unique Student IDs to any student who doesn't have one yet
try {
    $_no_sid = mysqli_query($conn, "SELECT id, created_at FROM admissions WHERE student_id = '' OR student_id IS NULL");
    if ($_no_sid && mysqli_num_rows($_no_sid) > 0) {
        while ($_sr = mysqli_fetch_assoc($_no_sid)) {
            $_yr  = date('Y', strtotime($_sr['created_at'] ?: 'now'));
            $_sid = 'MS-' . $_yr . '-' . str_pad((string)$_sr['id'], 4, '0', STR_PAD_LEFT);
            mysqli_query($conn, "UPDATE admissions SET student_id='" . mysqli_real_escape_string($conn, $_sid) . "' WHERE id=" . (int)$_sr['id']);
        }
    }
} catch (Exception $e) { /* table corrupt or missing — skip */ }
unset($_no_sid, $_sr, $_yr, $_sid);

ensure_column($conn, 'courses', 'icon',             "VARCHAR(100) DEFAULT 'fa-solid fa-book'");
ensure_column($conn, 'courses', 'description',      "TEXT");
ensure_column($conn, 'courses', 'full_description', "TEXT");
ensure_column($conn, 'courses', 'duration',         "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'courses', 'fee',              "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'courses', 'status',           "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'courses', 'created_at',       "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

ensure_column($conn, 'contacts', 'email',      "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'contacts', 'phone',      "VARCHAR(20) DEFAULT ''");
ensure_column($conn, 'contacts', 'subject',    "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'contacts', 'message',    "TEXT");
ensure_column($conn, 'contacts', 'date',       "DATE");
ensure_column($conn, 'contacts', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Events table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE,
    event_time VARCHAR(50) DEFAULT '',
    location VARCHAR(255) DEFAULT '',
    image VARCHAR(255) DEFAULT '',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
ensure_column($conn, 'events', 'description', "TEXT");
ensure_column($conn, 'events', 'event_date',  "DATE");
ensure_column($conn, 'events', 'event_time',  "VARCHAR(50) DEFAULT ''");
ensure_column($conn, 'events', 'location',    "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'events', 'image',       "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'events', 'status',      "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'events', 'created_at',  "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// News & Announcements table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    category ENUM('news','announcement') DEFAULT 'news',
    image VARCHAR(255) DEFAULT '',
    news_date DATE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
ensure_column($conn, 'news', 'content',    "TEXT");
ensure_column($conn, 'news', 'category',   "ENUM('news','announcement') DEFAULT 'news'");
ensure_column($conn, 'news', 'image',      "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'news', 'news_date',  "DATE");
ensure_column($conn, 'news', 'status',     "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'news', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Gallery table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
ensure_column($conn, 'gallery', 'description', "TEXT");
ensure_column($conn, 'gallery', 'image',       "VARCHAR(255) NOT NULL DEFAULT ''");
ensure_column($conn, 'gallery', 'category',    "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'gallery', 'sort_order',  "INT DEFAULT 0");
ensure_column($conn, 'gallery', 'status',      "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'gallery', 'created_at',  "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Notifications table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'info',
    icon VARCHAR(80) DEFAULT 'fa-solid fa-bell',
    show_pages TEXT DEFAULT 'all',
    link_text VARCHAR(100) DEFAULT '',
    link_url VARCHAR(255) DEFAULT '',
    start_date DATE,
    end_date DATE,
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
ensure_column($conn, 'notifications', 'type',       "VARCHAR(20) DEFAULT 'info'");
ensure_column($conn, 'notifications', 'icon',       "VARCHAR(80) DEFAULT 'fa-solid fa-bell'");
ensure_column($conn, 'notifications', 'show_pages', "TEXT DEFAULT 'all'");
ensure_column($conn, 'notifications', 'link_text',  "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'notifications', 'link_url',   "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'notifications', 'start_date', "DATE");
ensure_column($conn, 'notifications', 'end_date',   "DATE");
ensure_column($conn, 'notifications', 'sort_order', "INT DEFAULT 0");
ensure_column($conn, 'notifications', 'status',     "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'notifications', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Datesheets table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS datesheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    program VARCHAR(100) DEFAULT 'All Programs',
    exam_type VARCHAR(100) DEFAULT '',
    session VARCHAR(50) DEFAULT '',
    start_date DATE,
    end_date DATE,
    file VARCHAR(255) DEFAULT '',
    file_type VARCHAR(20) DEFAULT '',
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
ensure_column($conn, 'datesheets', 'program',    "VARCHAR(100) DEFAULT 'All Programs'");
ensure_column($conn, 'datesheets', 'exam_type',  "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'datesheets', 'session',    "VARCHAR(50) DEFAULT ''");
ensure_column($conn, 'datesheets', 'start_date', "DATE");
ensure_column($conn, 'datesheets', 'end_date',   "DATE");
ensure_column($conn, 'datesheets', 'file',       "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'datesheets', 'file_type',  "VARCHAR(20) DEFAULT ''");
ensure_column($conn, 'datesheets', 'sort_order', "INT DEFAULT 0");
ensure_column($conn, 'datesheets', 'status',     "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'datesheets', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Downloads table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file VARCHAR(255) NOT NULL DEFAULT '',
    file_type VARCHAR(20) DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    download_date DATE,
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
ensure_column($conn, 'downloads', 'description',   "TEXT");
ensure_column($conn, 'downloads', 'file',          "VARCHAR(255) NOT NULL DEFAULT ''");
ensure_column($conn, 'downloads', 'file_type',     "VARCHAR(20) DEFAULT ''");
ensure_column($conn, 'downloads', 'category',      "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'downloads', 'download_date', "DATE");
ensure_column($conn, 'downloads', 'sort_order',    "INT DEFAULT 0");
ensure_column($conn, 'downloads', 'status',        "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'downloads', 'created_at',    "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Teachers table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teachers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    designation   VARCHAR(255) DEFAULT '',
    department    VARCHAR(255) DEFAULT '',
    qualification VARCHAR(255) DEFAULT '',
    experience    VARCHAR(100) DEFAULT '',
    email         VARCHAR(255) DEFAULT '',
    phone         VARCHAR(20)  DEFAULT '',
    photo         VARCHAR(255) DEFAULT '',
    bio           TEXT,
    status        ENUM('active','inactive') DEFAULT 'active',
    sort_order    INT DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
ensure_column($conn, 'teachers', 'designation',   "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'teachers', 'department',    "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'teachers', 'qualification', "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'teachers', 'experience',    "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'teachers', 'email',         "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'teachers', 'phone',         "VARCHAR(20) DEFAULT ''");
ensure_column($conn, 'teachers', 'photo',         "VARCHAR(255) DEFAULT ''");
ensure_column($conn, 'teachers', 'bio',           "TEXT");
ensure_column($conn, 'teachers', 'status',        "ENUM('active','inactive') DEFAULT 'active'");
ensure_column($conn, 'teachers', 'sort_order',    "INT DEFAULT 0");
ensure_column($conn, 'teachers', 'created_at',    "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// Teacher ↔ Subject assignment table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teacher_subject_assignments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_subject (teacher_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Teacher portal credential columns
ensure_column($conn, 'teachers', 'teacher_username',      "VARCHAR(50) DEFAULT NULL");
ensure_column($conn, 'teachers', 'teacher_password_hash', "VARCHAR(255) DEFAULT NULL");
ensure_column($conn, 'teachers', 'teacher_password',      "VARCHAR(100) DEFAULT ''");

// Teacher portal tables
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teacher_quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL, course_id INT DEFAULT 0, subject_id INT DEFAULT 0,
    title VARCHAR(255) NOT NULL, description TEXT,
    duration_minutes INT DEFAULT 30, total_marks INT DEFAULT 100,
    status ENUM('draft','active','closed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teacher_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL, course_id INT DEFAULT 0, subject_id INT DEFAULT 0,
    title VARCHAR(255) NOT NULL, description TEXT,
    due_date DATE, total_marks INT DEFAULT 100,
    status ENUM('draft','active','closed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teacher_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL, course_id INT DEFAULT 0, subject_id INT DEFAULT 0,
    title VARCHAR(255) NOT NULL, description TEXT,
    test_date DATETIME, duration_minutes INT DEFAULT 60,
    total_marks INT DEFAULT 100, venue VARCHAR(255) DEFAULT '',
    status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teacher_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL, course_id INT DEFAULT 0,
    title VARCHAR(255) NOT NULL, content TEXT,
    priority ENUM('normal','important','urgent') DEFAULT 'normal',
    status ENUM('active','archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Auto-generate portal credentials for teachers that don't have them yet
function tcp_make_password(int $len = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $p = '';
    for ($i = 0; $i < $len; $i++) $p .= $chars[random_int(0, strlen($chars) - 1)];
    return $p;
}
try {
    $_no_creds = mysqli_query($conn, "SELECT id, name FROM teachers WHERE teacher_username IS NULL OR teacher_username=''");
    if ($_no_creds && mysqli_num_rows($_no_creds) > 0) {
        $bulk = [];
        while ($_tc = mysqli_fetch_assoc($_no_creds)) {
            $_tid  = (int)$_tc['id'];
            $_user = 'TCH' . str_pad((string)$_tid, 3, '0', STR_PAD_LEFT);
            $_pass = tcp_make_password();
            $_hash = password_hash($_pass, PASSWORD_DEFAULT);
            $_us = mysqli_prepare($conn, "UPDATE teachers SET teacher_username=?, teacher_password_hash=? WHERE id=?");
            mysqli_stmt_bind_param($_us, 'ssi', $_user, $_hash, $_tid);
            mysqli_stmt_execute($_us);
            mysqli_stmt_close($_us);
            // create teacher upload folder
            $_tdir = __DIR__ . "/../assets/uploads/teachers/$_tid/";
            if (!is_dir($_tdir)) @mkdir($_tdir, 0755, true);
            $bulk[] = ['name' => $_tc['name'], 'username' => $_user, 'password' => $_pass, 'id' => $_tid];
        }
        if (!empty($bulk) && empty($_SESSION['bulk_creds'])) $_SESSION['bulk_creds'] = $bulk;
    }
} catch (Exception $e) { /* table corrupt or missing — skip */ }
unset($_no_creds, $_tc, $_tid, $_user, $_pass, $_hash, $_us, $_tdir, $bulk);

$page_title = $page_title ?? 'Admin';
$active_nav = $active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $admin_theme ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?> - Malik Solution AdminCP</title>
  <?php $_favicon = get_setting($conn, 'favicon_path', ''); if ($_favicon !== ''): ?>
  <link rel="icon" href="../<?= htmlspecialchars($_favicon) ?>">
  <?php endif; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <style>
    :root {
      --navy: #071f40;
      --blue: #0d6efd;
      --soft: #f4f7fb;
      --line: #dfe7f1;
      --ink: #172033;
      --sidebar-w: 262px;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: var(--soft); color: var(--ink); }

    /* ── Topbar ─────────────────────────────────────────── */
    .admin-topbar {
      position: fixed; top: 0; left: 0; right: 0; height: 60px;
      background: #fff; border-bottom: 1px solid var(--line);
      display: flex; align-items: center; padding: 0 1.25rem; gap: 1rem;
      z-index: 900; box-shadow: 0 2px 12px rgba(0,0,0,.06);
    }
    @media (min-width: 992px) { .admin-topbar { left: var(--sidebar-w); } }
    .topbar-brand { font-weight: 800; color: var(--navy); font-size: 1rem; }
    .topbar-right { margin-left: auto; display: flex; align-items: center; gap: .75rem; }

    /* ── Hamburger ──────────────────────────────────────── */
    .hamburger {
      background: none; border: 1px solid var(--line); color: var(--navy);
      border-radius: 7px; width: 38px; height: 38px; font-size: 1.1rem;
      display: none; align-items: center; justify-content: center; cursor: pointer;
      flex-shrink: 0;
    }
    @media (max-width: 991px) { .hamburger { display: flex; } }

    /* ── Layout wrapper ─────────────────────────────────── */
    .admin-wrapper { display: flex; padding-top: 60px; min-height: 100vh; }

    /* ── Sidebar ─────────────────────────────────────────── */
    .admin-sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: var(--sidebar-w);
      background: var(--navy); color: #dce8fb;
      z-index: 950; overflow-y: auto;
      transition: transform .28s cubic-bezier(.4,0,.2,1);
      display: flex; flex-direction: column;
      scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.15) transparent;
    }
    @media (max-width: 991px) {
      .admin-sidebar { transform: translateX(-100%); }
      .admin-sidebar.open { transform: translateX(0); }
    }

    .sidebar-brand {
      display: flex; align-items: center; gap: .75rem;
      padding: 1.1rem 1rem; min-height: 60px;
      border-bottom: 1px solid rgba(255,255,255,.1); flex-shrink: 0;
    }
    .sidebar-brand img {
      width: 40px; height: 40px; object-fit: contain;
      background: #fff; border-radius: 8px; flex-shrink: 0;
    }
    .sidebar-brand strong { display: block; color: #fff; font-size: .9rem; line-height: 1.3; }
    .sidebar-brand small { color: #94a9c4; font-size: .72rem; }

    .sidebar-nav { padding: .85rem .6rem; flex: 1; }
    .sidebar-section { color: #5e7a9e; font-size: .68rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .07em;
      padding: .6rem .85rem .3rem; margin-top: .5rem; }

    /* nav items */
    .s-link {
      display: flex; align-items: center; gap: .7rem;
      padding: .65rem .85rem; border-radius: 7px;
      color: #bdd3ee; text-decoration: none;
      font-size: .875rem; font-weight: 600;
      margin-bottom: .1rem; cursor: pointer;
      transition: background .14s, color .14s;
      border: none; background: none; width: 100%; text-align: left; line-height: 1.4;
    }
    .s-link i { width: 17px; text-align: center; flex-shrink: 0; font-size: .875rem; }
    .s-link:hover, .s-link.active { background: rgba(255,255,255,.11); color: #fff; }
    .s-link.active { background: rgba(13,110,253,.35); color: #fff; }
    .s-link.sub { padding-left: 2.55rem; font-weight: 500; font-size: .82rem; color: #94b9d9; }
    .s-link.sub:hover { color: #fff; }

    .s-arrow { margin-left: auto; font-size: .68rem; transition: transform .22s; flex-shrink: 0; }
    .s-group.open .s-arrow { transform: rotate(180deg); }
    .s-submenu { display: none; }
    .s-group.open .s-submenu { display: block; }
    .s-divider { border-color: rgba(255,255,255,.1); margin: .6rem .85rem; }

    /* ── Overlay ─────────────────────────────────────────── */
    .sidebar-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(7,31,64,.5); z-index: 940;
    }
    .sidebar-overlay.active { display: block; }

    /* ── Main content ─────────────────────────────────────── */
    .admin-main { flex: 1; margin-left: var(--sidebar-w); padding: 1.5rem; }
    @media (max-width: 991px) { .admin-main { margin-left: 0; padding: 1rem .85rem; } }

    /* ── Cards ─────────────────────────────────────────────── */
    .cardx {
      background: #fff; border: 1px solid var(--line);
      border-radius: 10px; box-shadow: 0 4px 20px rgba(23,32,51,.055);
      padding: 1.25rem; height: 100%;
    }

    /* ── Stat cards ─────────────────────────────────────────── */
    .stat-card { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .stat-icon {
      width: 50px; height: 50px; display: grid; place-items: center;
      border-radius: 10px; font-size: 1.15rem; flex-shrink: 0;
    }
    .si-blue   { background: #edf4ff; color: #0d6efd; }
    .si-green  { background: #edfff5; color: #198754; }
    .si-orange { background: #fff8ed; color: #fd7e14; }
    .si-red    { background: #fff0f0; color: #dc3545; }
    .si-purple { background: #f5f0ff; color: #6f42c1; }
    .si-cyan   { background: #eafcff; color: #0dcaf0; }
    .si-teal   { background: #eafff9; color: #20c997; }
    .si-pink   { background: #fff0f6; color: #d63384; }
    .stat-card h3 { font-weight: 900; margin: 0; color: var(--navy); font-size: 1.55rem; }
    .stat-card p  { margin-bottom: .2rem; color: #6c757d; font-size: .8rem; }

    /* ── Table ───────────────────────────────────────────────── */
    .table-wrap { overflow-x: auto; }
    .table { font-size: .875rem; margin-bottom: 0; }
    .table th { font-weight: 700; color: var(--navy); border-bottom-width: 2px; white-space: nowrap; }

    /* ── Status badges ───────────────────────────────────────── */
    .status-badge {
      display: inline-block; font-size: .72rem; font-weight: 700;
      border-radius: 20px; padding: .28rem .75rem;
      text-transform: uppercase; letter-spacing: .03em; white-space: nowrap;
    }
    .sb-pending  { background: #fff3cd; color: #856404; }
    .sb-approved { background: #d1e7dd; color: #0a3622; }
    .sb-rejected { background: #f8d7da; color: #58151c; }
    .sb-active   { background: #d1e7dd; color: #0a3622; }
    .sb-inactive { background: #e9ecef; color: #495057; }

    /* ── Section/page headers ────────────────────────────────── */
    .section-hd {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 1.25rem; flex-wrap: wrap; gap: .5rem;
    }
    .section-hd h2 { font-weight: 800; color: var(--navy); margin: 0; font-size: 1.05rem; }
    .page-hd {
      background: #fff; border: 1px solid var(--line); border-radius: 10px;
      padding: 1.1rem 1.3rem; margin-bottom: 1.25rem;
      display: flex; align-items: center; justify-content: space-between;
      gap: 1rem; flex-wrap: wrap; box-shadow: 0 4px 16px rgba(23,32,51,.05);
    }
    .page-hd h1 { font-size: 1.25rem; font-weight: 800; color: var(--navy); margin: 0; }
    .page-hd p  { color: #6c757d; margin: .15rem 0 0; font-size: .83rem; }

    /* ── Form ────────────────────────────────────────────────── */
    .form-label { font-weight: 600; font-size: .85rem; }
    .form-control, .form-select { border-color: var(--line); font-size: .9rem; }
    .form-control:focus, .form-select:focus {
      border-color: var(--blue); box-shadow: 0 0 0 .18rem rgba(13,110,253,.18);
    }

    /* ── Misc ────────────────────────────────────────────────── */
    .btn-xs { font-size: .72rem; padding: .22rem .55rem; line-height: 1.5; border-radius: 5px; }
    .text-navy { color: var(--navy) !important; }
    .empty-state { text-align: center; padding: 3rem 1rem; }
    .empty-state i { font-size: 2.5rem; color: #cdd6e0; margin-bottom: 1rem; display: block; }
    .empty-state p { color: #6c757d; margin: 0; }

    /* ── Notification Bell ────────────────────────────────── */
    .notif-bell-wrap { position: relative; }
    .notif-bell-btn {
      position: relative; background: none; border: 1px solid var(--line);
      color: var(--navy); border-radius: 8px;
      width: 36px; height: 36px; font-size: 1rem;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: background .15s;
    }
    .notif-bell-btn:hover, .notif-bell-btn[aria-expanded="true"] {
      background: var(--soft); border-color: var(--blue);
    }
    .notif-count {
      position: absolute; top: -5px; right: -5px;
      background: #dc3545; color: #fff;
      font-size: .6rem; font-weight: 800; line-height: 1;
      border-radius: 20px; padding: .18rem .35rem;
      min-width: 16px; text-align: center;
      border: 2px solid #fff;
    }
    .notif-dropdown {
      width: 300px; padding: 0; border-radius: 12px; overflow: hidden;
      border: 1px solid var(--line); margin-top: .4rem !important;
    }
    .notif-dd-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: .75rem 1rem; background: var(--navy); color: #fff;
      font-weight: 700; font-size: .88rem;
    }
    .notif-dd-manage {
      font-size: .72rem; font-weight: 700; color: #94b9d9;
      text-decoration: none;
    }
    .notif-dd-manage:hover { color: #fff; }
    .notif-dd-item {
      display: flex; align-items: center; gap: .65rem;
      padding: .65rem 1rem; border-bottom: 1px solid var(--line);
      text-decoration: none; color: var(--ink);
      font-size: .83rem; transition: background .12s;
    }
    .notif-dd-item:hover { background: var(--soft); color: var(--navy); }
    .notif-dd-dot {
      width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
    }
    .notif-dd-text { flex: 1; font-weight: 600; line-height: 1.3; }
    .notif-dd-empty {
      padding: 1.2rem 1rem; text-align: center;
      color: #6c757d; font-size: .83rem;
    }
    .notif-dd-more {
      padding: .45rem 1rem; font-size: .75rem;
      color: #6c757d; font-style: italic; border-top: 1px solid var(--line);
    }
    .notif-dd-foot {
      padding: .6rem 1rem; background: var(--soft); border-top: 1px solid var(--line);
    }
    .notif-dd-foot a {
      font-size: .8rem; font-weight: 700; color: var(--navy); text-decoration: none;
    }
    .notif-dd-foot a:hover { color: var(--blue); }

    /* ── Topbar search ───────────────────────────────────── */
    .top-search { position: relative; flex: 1; max-width: 340px; display: none; }
    @media (min-width: 768px) { .top-search { display: block; } }
    .top-search .search-ico {
      position: absolute; left: .7rem; top: 50%; transform: translateY(-50%);
      color: #8898b3; font-size: .8rem; pointer-events: none;
    }
    .top-search input {
      width: 100%; height: 36px; border: 1px solid var(--line); border-radius: 8px;
      background: var(--soft); color: var(--ink);
      padding: 0 .8rem 0 2.1rem; font-size: .85rem; outline: none;
    }
    .top-search input:focus { border-color: var(--blue); background: #fff; }

    /* ── Profile dropdown ────────────────────────────────── */
    .profile-btn {
      display: flex; align-items: center; gap: .55rem;
      background: none; border: 1px solid var(--line); border-radius: 8px;
      padding: .25rem .6rem .25rem .3rem; cursor: pointer; transition: background .15s;
    }
    .profile-btn:hover, .profile-btn[aria-expanded="true"] { background: var(--soft); }
    .avatar-circle {
      width: 30px; height: 30px; border-radius: 50%;
      background: var(--blue); color: #fff;
      display: grid; place-items: center;
      font-weight: 800; font-size: .8rem; flex-shrink: 0;
    }
    .p-name {
      display: none; text-align: left; line-height: 1.15;
      font-size: .8rem; font-weight: 700; color: var(--navy);
    }
    @media (min-width: 768px) { .p-name { display: block; } }
    .p-role { display: block; font-size: .67rem; font-weight: 600; color: #8898b3; }
    .profile-menu { min-width: 210px; border-radius: 10px; border: 1px solid var(--line); padding: .35rem; }
    .profile-menu .dropdown-item { border-radius: 7px; font-size: .85rem; font-weight: 600; padding: .5rem .7rem; }

    /* ── Stat deltas ─────────────────────────────────────── */
    .stat-delta {
      font-size: .68rem; font-weight: 800; margin-left: .45rem;
      padding: .14rem .45rem; border-radius: 20px; vertical-align: middle; white-space: nowrap;
    }
    .stat-delta.up   { background: #d1e7dd; color: #0a3622; }
    .stat-delta.down { background: #f8d7da; color: #58151c; }
    .stat-sub { display: block; color: #8898b3; font-size: .7rem; margin-top: .15rem; }

    /* ── Activity feed ───────────────────────────────────── */
    .feed { display: flex; flex-direction: column; }
    .feed-item {
      display: flex; gap: .65rem; padding: .55rem 0;
      border-bottom: 1px solid var(--line); font-size: .83rem; line-height: 1.35;
    }
    .feed-item:last-child { border-bottom: none; }
    .feed-item .f-ico { flex-shrink: 0; }
    .feed-item small { display: block; color: #8898b3; font-size: .72rem; }

    /* ── SVG charts ──────────────────────────────────────── */
    .chart { width: 100%; height: auto; display: block; }
    .grid-line { stroke: var(--line); stroke-width: 1; }
    .axis-label { fill: #8898b3; font-size: 10px; }
    .series-line { fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .series-line.series-a { stroke: #0d6efd; }
    .series-line.series-b { stroke: #fd7e14; }
    .series-area.series-a { fill: rgba(13,110,253,.12); }
    .series-area.series-b { fill: rgba(253,126,20,.12); }
    .end-dot.series-a { fill: #0d6efd; }
    .end-dot.series-b { fill: #fd7e14; }
    .hover-col { fill: transparent; }
    .hover-col:hover { fill: rgba(13,110,253,.07); }
    .col-bar.col-primary { fill: #0d6efd; }
    .col-bar.col-accent  { fill: #6f42c1; }
    .col-bar.col-green   { fill: #198754; }
    .col-value { fill: #5b6b84; font-size: 10px; font-weight: 700; }
    .donut-wrap { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
    .donut { width: 150px; height: 150px; flex-shrink: 0; }
    .donut-track { stroke: var(--soft); }
    .donut-slice.slice-1 { stroke: #fd7e14; }
    .donut-slice.slice-2 { stroke: #198754; }
    .donut-slice.slice-3 { stroke: #0d6efd; }
    .donut-slice.slice-4 { stroke: #dc3545; }
    .donut-slice.slice-5 { stroke: #6f42c1; }
    .donut-center-num { fill: var(--ink); font-size: 24px; font-weight: 800; }
    .donut-center-label { fill: #8898b3; font-size: 10px; font-weight: 600; }
    .chart-legend {
      display: flex; gap: .5rem 1.1rem; flex-wrap: wrap;
      margin-top: .6rem; font-size: .78rem; color: #5b6b84; font-weight: 600;
    }
    .donut-legend { flex-direction: column; gap: .45rem; margin-top: 0; }
    .legend-item { display: flex; align-items: center; gap: .45rem; }
    .legend-key { width: 10px; height: 10px; border-radius: 3px; display: inline-block; flex-shrink: 0; }
    .legend-key.series-a, .legend-key.slice-3 { background: #0d6efd; }
    .legend-key.series-b, .legend-key.slice-1 { background: #fd7e14; }
    .legend-key.slice-2 { background: #198754; }
    .legend-key.slice-4 { background: #dc3545; }
    .legend-key.slice-5 { background: #6f42c1; }
    .bar-list { display: flex; flex-direction: column; gap: .7rem; }
    .bar-row { display: flex; align-items: center; gap: .65rem; font-size: .82rem; }
    .bar-label {
      width: 140px; overflow: hidden; text-overflow: ellipsis;
      white-space: nowrap; font-weight: 600; flex-shrink: 0;
    }
    .bar-track { flex: 1; background: var(--soft); border-radius: 20px; height: 10px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 20px; background: #0d6efd; min-width: 2px; }
    .bar-value { width: 34px; text-align: right; font-weight: 800; flex-shrink: 0; }

    /* ── Dark theme ──────────────────────────────────────── */
    html[data-theme="dark"] { --soft: #0e1626; --line: #263450; --ink: #d7e1f2; }
    html[data-theme="dark"] body { background: #0e1626; color: #d7e1f2; }
    html[data-theme="dark"] .admin-topbar,
    html[data-theme="dark"] .cardx,
    html[data-theme="dark"] .page-hd { background: #151f37; }
    html[data-theme="dark"] .admin-topbar { box-shadow: 0 2px 12px rgba(0,0,0,.35); }
    html[data-theme="dark"] .topbar-brand,
    html[data-theme="dark"] .hamburger,
    html[data-theme="dark"] .notif-bell-btn,
    html[data-theme="dark"] .p-name,
    html[data-theme="dark"] .page-hd h1,
    html[data-theme="dark"] .section-hd h2,
    html[data-theme="dark"] .stat-card h3,
    html[data-theme="dark"] .table th,
    html[data-theme="dark"] .text-navy { color: #e8effb !important; }
    html[data-theme="dark"] .top-search input { background: #0e1626; color: #d7e1f2; }
    html[data-theme="dark"] .top-search input:focus { background: #0e1626; }
    html[data-theme="dark"] .profile-btn:hover,
    html[data-theme="dark"] .profile-btn[aria-expanded="true"],
    html[data-theme="dark"] .notif-bell-btn:hover,
    html[data-theme="dark"] .notif-bell-btn[aria-expanded="true"] { background: #0e1626; }
    html[data-theme="dark"] .table { --bs-table-bg: transparent; --bs-table-color: #c6d2e6; --bs-table-border-color: #263450; --bs-table-striped-bg: rgba(255,255,255,.02); --bs-table-hover-bg: rgba(255,255,255,.04); --bs-table-striped-color: #c6d2e6; --bs-table-hover-color: #e8effb; }
    html[data-theme="dark"] .form-control, html[data-theme="dark"] .form-select {
      background: #0e1626; border-color: #263450; color: #e8effb;
    }
    html[data-theme="dark"] .form-control:focus, html[data-theme="dark"] .form-select:focus { background: #0e1626; color: #e8effb; }
    html[data-theme="dark"] .form-control::placeholder { color: #64748e; }
    html[data-theme="dark"] .dropdown-menu { background: #151f37; border-color: #263450; color: #d7e1f2; }
    html[data-theme="dark"] .dropdown-menu .dropdown-item { color: #c6d2e6; }
    html[data-theme="dark"] .dropdown-menu .dropdown-item:hover { background: #0e1626; color: #e8effb; }
    html[data-theme="dark"] .notif-dd-item { color: #c6d2e6; }
    html[data-theme="dark"] .notif-dd-item:hover { background: #0e1626; color: #e8effb; }
    html[data-theme="dark"] .notif-dd-foot { background: #0e1626; }
    html[data-theme="dark"] .notif-dd-foot a { color: #c6d2e6; }
    html[data-theme="dark"] .notif-count { border-color: #151f37; }
    html[data-theme="dark"] .text-muted, html[data-theme="dark"] .page-hd p,
    html[data-theme="dark"] .stat-card p, html[data-theme="dark"] .empty-state p { color: #90a0bd !important; }
    html[data-theme="dark"] .si-blue   { background: rgba(13,110,253,.15); }
    html[data-theme="dark"] .si-green  { background: rgba(25,135,84,.15); }
    html[data-theme="dark"] .si-orange { background: rgba(253,126,20,.15); }
    html[data-theme="dark"] .si-red    { background: rgba(220,53,69,.15); }
    html[data-theme="dark"] .si-purple { background: rgba(111,66,193,.18); }
    html[data-theme="dark"] .si-cyan   { background: rgba(13,202,240,.15); }
    html[data-theme="dark"] .si-teal   { background: rgba(32,201,151,.15); }
    html[data-theme="dark"] .si-pink   { background: rgba(214,51,132,.15); }
    html[data-theme="dark"] .stat-delta.up   { background: rgba(25,135,84,.22); color: #6fdca4; }
    html[data-theme="dark"] .stat-delta.down { background: rgba(220,53,69,.22); color: #f1919b; }
    html[data-theme="dark"] .sb-pending  { background: rgba(255,193,7,.18);  color: #ffd76b; }
    html[data-theme="dark"] .sb-approved { background: rgba(25,135,84,.22);  color: #6fdca4; }
    html[data-theme="dark"] .sb-rejected { background: rgba(220,53,69,.22);  color: #f1919b; }
    html[data-theme="dark"] .col-value { fill: #90a0bd; }
    html[data-theme="dark"] .donut-track { stroke: #0e1626; }
    html[data-theme="dark"] .btn-outline-secondary { --bs-btn-color: #c6d2e6; --bs-btn-border-color: #263450; }
    html[data-theme="dark"] .btn-outline-primary { --bs-btn-hover-color: #fff; }
    html[data-theme="dark"] .alert { border-color: #263450; }
  </style>
</head>
<body>

<!-- ── Topbar ────────────────────────────────────── -->
<nav class="admin-topbar">
  <button class="hamburger" id="sidebarToggle" aria-label="Toggle navigation">
    <i class="fa-solid fa-bars"></i>
  </button>
  <span class="topbar-brand d-none d-sm-block"><?= htmlspecialchars($page_title) ?></span>

  <!-- ── Global search ── -->
  <form class="top-search" method="get" action="search.php" role="search">
    <i class="fa-solid fa-magnifying-glass search-ico"></i>
    <input type="search" name="q" placeholder="Search students, contacts, teachers, courses…"
           value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>">
  </form>

  <div class="topbar-right">
    <!-- ── Dark mode toggle ── -->
    <button class="notif-bell-btn" id="themeToggle" type="button" title="Toggle dark mode">
      <i class="fa-solid <?= $admin_theme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
    </button>

    <!-- ── Notification Bell ── -->
    <?php
      $notif_count   = 0;
      $notif_preview = false;
      try {
          $nb = mysqli_fetch_row(mysqli_query($conn,
            "SELECT COUNT(*) FROM notifications WHERE status='active'
             AND (start_date IS NULL OR start_date <= CURDATE())
             AND (end_date   IS NULL OR end_date   >= CURDATE())"));
          $notif_count = (int)($nb[0] ?? 0);
          $notif_preview = mysqli_query($conn,
            "SELECT id, title, type FROM notifications WHERE status='active'
             AND (start_date IS NULL OR start_date <= CURDATE())
             AND (end_date   IS NULL OR end_date   >= CURDATE())
             ORDER BY sort_order ASC, created_at DESC LIMIT 5");
      } catch (Exception $e) { /* table corrupt or missing — skip */ }
    ?>
    <div class="dropdown notif-bell-wrap">
      <button class="notif-bell-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
        <i class="fa-solid fa-bell"></i>
        <?php if ($notif_count > 0): ?>
          <span class="notif-count"><?= $notif_count > 9 ? '9+' : $notif_count ?></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end notif-dropdown shadow">
        <div class="notif-dd-head">
          <span><i class="fa-solid fa-bell me-1"></i>Notifications</span>
          <a href="manage_notifications.php" class="notif-dd-manage">Manage</a>
        </div>
        <?php if ($notif_count === 0): ?>
          <div class="notif-dd-empty">No active notifications</div>
        <?php else: ?>
          <?php while ($nr = mysqli_fetch_assoc($notif_preview)): ?>
            <?php
              $tc = match($nr['type']) {
                'success' => '#198754', 'warning' => '#fd7e14',
                'danger'  => '#dc3545', default   => '#0d6efd',
              };
            ?>
            <a href="manage_notifications.php" class="notif-dd-item">
              <span class="notif-dd-dot" style="background:<?= $tc ?>"></span>
              <span class="notif-dd-text"><?= htmlspecialchars($nr['title']) ?></span>
            </a>
          <?php endwhile; ?>
          <?php if ($notif_count > 5): ?>
            <div class="notif-dd-more">+<?= $notif_count - 5 ?> more</div>
          <?php endif; ?>
        <?php endif; ?>
        <div class="notif-dd-foot">
          <a href="manage_notifications.php">
            <i class="fa-solid fa-gear me-1"></i>Manage All Notifications
          </a>
        </div>
      </div>
    </div>

    <a href="../index.php" target="_blank" class="notif-bell-btn d-none d-md-flex" title="View Website" style="text-decoration:none">
      <i class="fa-solid fa-globe"></i>
    </a>

    <!-- ── Profile dropdown ── -->
    <div class="dropdown">
      <button class="profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="avatar-circle"><?= htmlspecialchars($admin_initial) ?></span>
        <span class="p-name">
          <?= htmlspecialchars($current_admin['name']) ?>
          <span class="p-role"><?= htmlspecialchars($current_admin['email']) ?></span>
        </span>
      </button>
      <div class="dropdown-menu dropdown-menu-end profile-menu shadow">
        <a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2 text-primary"></i>My Profile</a>
        <a class="dropdown-item" href="settings.php"><i class="fa-solid fa-gear me-2 text-primary"></i>Site Settings</a>
        <a class="dropdown-item" href="users.php"><i class="fa-solid fa-users me-2 text-primary"></i>Admin Users</a>
        <a class="dropdown-item" href="../index.php" target="_blank"><i class="fa-solid fa-globe me-2 text-primary"></i>View Website</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Log Out</a>
      </div>
    </div>
  </div>
</nav>

<div class="admin-wrapper">

<!-- ── Sidebar ───────────────────────────────────── -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <img src="../<?= htmlspecialchars(get_setting($conn, 'logo_path', 'maliksolution.png')) ?>" alt="Malik Solution">
    <div>
      <strong>Malik Solution AdminCP</strong>
      <small>Management Panel</small>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>

    <a href="dashboard.php" class="s-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>">
      <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
    </a>

    <a href="analytics.php" class="s-link <?= $active_nav === 'analytics' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-line"></i><span>Website Analytics</span>
    </a>

    <div class="sidebar-section">Content</div>

    <a href="pages.php" class="s-link <?= $active_nav === 'pages' ? 'active' : '' ?>">
      <i class="fa-solid fa-file-pen"></i><span>Website Pages</span>
    </a>

    <div class="s-group <?= $active_nav === 'courses' ? 'open' : '' ?>">
      <button type="button" class="s-link s-group-toggle">
        <i class="fa-solid fa-book"></i><span>Courses</span>
        <i class="fa-solid fa-chevron-down s-arrow"></i>
      </button>
      <div class="s-submenu">
        <a href="courses.php" class="s-link sub">
          <i class="fa-solid fa-list-ul"></i> Manage Courses
        </a>
        <a href="courses.php?action=create" class="s-link sub">
          <i class="fa-solid fa-plus"></i> Create Course
        </a>
      </div>
    </div>

    <a href="manage_events.php" class="s-link <?= $active_nav === 'events' ? 'active' : '' ?>">
      <i class="fa-solid fa-calendar-days"></i><span>Manage Events</span>
    </a>

    <a href="manage_news.php" class="s-link <?= $active_nav === 'news' ? 'active' : '' ?>">
      <i class="fa-solid fa-newspaper"></i><span>News &amp; Announcements</span>
    </a>

    <a href="manage_gallery.php" class="s-link <?= $active_nav === 'gallery' ? 'active' : '' ?>">
      <i class="fa-solid fa-images"></i><span>Gallery</span>
    </a>

    <a href="manage_downloads.php" class="s-link <?= $active_nav === 'downloads' ? 'active' : '' ?>">
      <i class="fa-solid fa-download"></i><span>Downloads</span>
    </a>

    <a href="manage_datesheets.php" class="s-link <?= $active_nav === 'datesheets' ? 'active' : '' ?>">
      <i class="fa-solid fa-calendar-check"></i><span>Exam Date Sheets</span>
    </a>

    <a href="manage_notifications.php" class="s-link <?= $active_nav === 'notifications' ? 'active' : '' ?>">
      <i class="fa-solid fa-bell"></i><span>Notifications</span>
      <?php
        $sbn_count = 0;
        try {
            $sbn = mysqli_fetch_row(mysqli_query($conn,
              "SELECT COUNT(*) FROM notifications WHERE status='active'
               AND (start_date IS NULL OR start_date <= CURDATE())
               AND (end_date   IS NULL OR end_date   >= CURDATE())"));
            $sbn_count = (int)($sbn[0] ?? 0);
        } catch (Exception $e) {}
        if ($sbn_count > 0): ?>
        <span class="ms-auto badge" style="background:#dc3545;font-size:.6rem;padding:.2rem .45rem;border-radius:20px"><?= $sbn_count ?></span>
      <?php endif; ?>
    </a>

    <div class="sidebar-section">Faculty</div>

    <a href="teachers.php" class="s-link <?= $active_nav === 'teachers' ? 'active' : '' ?>">
      <i class="fa-solid fa-chalkboard-user"></i><span>Teachers</span>
      <?php
        $tbn_count = 0;
        try {
            $tbn = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teachers WHERE status='active'"));
            $tbn_count = (int)($tbn[0] ?? 0);
        } catch (Exception $e) {}
        if ($tbn_count > 0): ?>
        <span class="ms-auto badge" style="background:rgba(255,255,255,.15);font-size:.65rem;padding:.18rem .45rem;border-radius:20px"><?= $tbn_count ?></span>
      <?php endif; ?>
    </a>

    <div class="sidebar-section">Students</div>

    <a href="admissions.php" class="s-link <?= $active_nav === 'admissions' ? 'active' : '' ?>">
      <i class="fa-solid fa-user-plus"></i><span>Admissions</span>
    </a>

    <a href="contacts.php" class="s-link <?= $active_nav === 'contacts' ? 'active' : '' ?>">
      <i class="fa-solid fa-envelope"></i><span>Contact Queries</span>
    </a>

    <div class="sidebar-section">System</div>

    <a href="settings.php" class="s-link <?= $active_nav === 'settings' ? 'active' : '' ?>">
      <i class="fa-solid fa-gear"></i><span>Site Settings</span>
    </a>

    <a href="users.php" class="s-link <?= $active_nav === 'users' ? 'active' : '' ?>">
      <i class="fa-solid fa-users"></i><span>Admin Users</span>
    </a>

    <a href="activity.php" class="s-link <?= $active_nav === 'activity' ? 'active' : '' ?>">
      <i class="fa-solid fa-clock-rotate-left"></i><span>Activity Log</span>
    </a>

    <a href="backup.php" class="s-link <?= $active_nav === 'backup' ? 'active' : '' ?>">
      <i class="fa-solid fa-database"></i><span>Backup</span>
    </a>

    <hr class="s-divider">

    <a href="../index.php" target="_blank" class="s-link">
      <i class="fa-solid fa-arrow-up-right-from-square"></i><span>View Website</span>
    </a>
  </nav>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Main content start ────────────────────────── -->
<main class="admin-main">
<?php if (!empty($_SESSION['flash'])): $_flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert alert-<?= $_flash['type'] === 'error' ? 'danger' : htmlspecialchars($_flash['type']) ?> alert-dismissible fade show">
    <i class="fa-solid <?= $_flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
    <?= htmlspecialchars($_flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
