<?php
declare(strict_types=1);
/** Teacher Portal shell — expects $page_title, $active_nav. Close with footer.php. */

$nav_subj   = (int)(ums_db()->query('SELECT COUNT(*) c FROM ' . tbl('teacher_subjects') . " WHERE teacher_id=$tid")->fetch_assoc()['c']);
$nav_quiz   = (int)(ums_db()->query('SELECT COUNT(*) c FROM ' . tbl('teacher_quizzes') . " WHERE teacher_id=$tid AND status='active'")->fetch_assoc()['c']);
$nav_assign = (int)(ums_db()->query('SELECT COUNT(*) c FROM ' . tbl('teacher_assignments') . " WHERE teacher_id=$tid AND status='active'")->fetch_assoc()['c']);
$nav_tests  = (int)(ums_db()->query('SELECT COUNT(*) c FROM ' . tbl('teacher_tests') . " WHERE teacher_id=$tid AND status='scheduled'")->fetch_assoc()['c']);

$words      = array_values(array_filter(explode(' ', $teacher['name']), fn($w) => ctype_alpha($w[0] ?? '')));
$initials   = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ''));
$first_name = e($words[0] ?? $teacher['name']);
$nav        = $active_nav ?? '';
$photo_src  = $teacher['photo'] !== '' ? UMS_URL . '/' . e($teacher['photo']) : '';
$desig      = e($teacher['designation'] ?: 'Teacher');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title ?? 'Dashboard') ?> — Teacher Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --sb-w: 260px; --hdr-h: 56px;
  --navy: #071f40; --navy2: #0b2a52; --gold: #f6b221;
  --soft: #f0f4f9; --line: #e2e8f0; --text: #1e293b; --muted: #64748b;
  --sb-link: rgba(255,255,255,.62); --radius: 12px;
  --bg: #f4f7fb;
}
body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
a { text-decoration: none; color: inherit; }
.sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: var(--sb-w); background: var(--navy); z-index: 400; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.1) transparent; transition: transform .28s cubic-bezier(.4,0,.2,1); }
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }
.sb-brand { display: flex; align-items: center; gap: .7rem; padding: 1.1rem 1.25rem 1rem; border-bottom: 1px solid rgba(255,255,255,.07); flex-shrink: 0; }
.sb-brand .ic { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg,var(--gold),#fbbf24); display: grid; place-items: center; color: var(--navy); font-size: 1.1rem; flex-shrink: 0; }
.sb-brand-name { font-size: .82rem; font-weight: 800; color: #fff; line-height: 1.2; }
.sb-brand-sub  { font-size: .62rem; font-weight: 600; color: var(--gold); text-transform: uppercase; letter-spacing: .06em; }
.sb-profile { display: flex; align-items: center; gap: .75rem; padding: .9rem 1.25rem; background: rgba(255,255,255,.04); border-bottom: 1px solid rgba(255,255,255,.07); flex-shrink: 0; }
.sb-avatar { width: 42px; height: 42px; border-radius: 50%; background: rgba(246,178,33,.15); border: 2px solid rgba(246,178,33,.3); display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 800; color: var(--gold); flex-shrink: 0; overflow: hidden; }
.sb-avatar img { width: 100%; height: 100%; object-fit: cover; object-position: center 15%; }
.sb-pname { font-size: .8rem; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.3; }
.sb-pid   { font-size: .63rem; color: rgba(255,255,255,.45); }
.sb-badge { margin-top: .3rem; display: inline-flex; align-items: center; gap: .25rem; background: rgba(34,197,94,.15); color: #4ade80; font-size: .58rem; font-weight: 700; border-radius: 20px; padding: .12rem .5rem; border: 1px solid rgba(34,197,94,.2); text-transform: uppercase; letter-spacing: .04em; }
.sb-nav { flex: 1; padding: .6rem 0; }
.sb-section-label { font-size: .57rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.28); padding: .8rem 1.25rem .35rem; }
.sb-link { display: flex; align-items: center; gap: .75rem; padding: .65rem 1.25rem; font-size: .8rem; font-weight: 600; color: var(--sb-link); border-left: 3px solid transparent; transition: background .15s, color .15s, border-color .15s; cursor: pointer; }
.sb-link i { width: 18px; text-align: center; font-size: .88rem; flex-shrink: 0; }
.sb-link:hover { background: rgba(255,255,255,.06); color: #fff; }
.sb-link.active { background: rgba(246,178,33,.1); color: var(--gold); border-left-color: var(--gold); font-weight: 700; }
.sb-link.active i { color: var(--gold); }
.sb-link.sb-logout:hover { background: rgba(239,68,68,.12); color: #f87171; }
.sb-nb { margin-left: auto; font-size: .58rem; font-weight: 800; background: var(--gold); color: var(--navy); border-radius: 20px; padding: .08rem .42rem; flex-shrink: 0; }
.sb-link:not(.active) .sb-nb { background: rgba(255,255,255,.18); color: #fff; }
.sb-footer { padding: .85rem 1.25rem; border-top: 1px solid rgba(255,255,255,.07); font-size: .65rem; color: rgba(255,255,255,.25); flex-shrink: 0; line-height: 1.6; }
.sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 399; backdrop-filter: blur(2px); }
.sb-overlay.show { display: block; }
.main-wrap { margin-left: var(--sb-w); min-height: 100vh; display: flex; flex-direction: column; transition: margin-left .28s cubic-bezier(.4,0,.2,1); }
.top-hdr { height: var(--hdr-h); background: #fff; border-bottom: 1px solid var(--line); display: flex; align-items: center; padding: 0 1.5rem; gap: .85rem; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 6px rgba(7,31,64,.06); flex-shrink: 0; }
.hdr-hamburger { display: none; background: none; border: none; color: var(--navy); cursor: pointer; font-size: 1.1rem; padding: .4rem .5rem; border-radius: 8px; transition: background .15s; line-height: 1; flex-shrink: 0; }
.hdr-hamburger:hover { background: var(--soft); }
.hdr-title { font-size: .88rem; font-weight: 800; color: var(--navy); flex: 1; }
.hdr-right { display: flex; align-items: center; gap: .75rem; margin-left: auto; }
.hdr-user { display: flex; align-items: center; gap: .6rem; background: var(--soft); border: 1.5px solid var(--line); border-radius: 30px; padding: .3rem .75rem .3rem .4rem; cursor: pointer; transition: border-color .15s, background .15s; }
.hdr-user:hover { background: #e8edf5; border-color: #c5d0e0; }
.hdr-user::after { display: none; }
.hdr-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--navy); color: var(--gold); display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 800; flex-shrink: 0; overflow: hidden; }
.hdr-avatar img { width: 100%; height: 100%; object-fit: cover; object-position: center 15%; }
.hdr-uname { font-size: .75rem; font-weight: 700; color: var(--navy); }
.hdr-uprog { font-size: .62rem; color: var(--muted); }
.hdr-chevron { font-size: .65rem; color: var(--muted); margin-left: .1rem; transition: transform .2s; }
.hdr-user[aria-expanded="true"] .hdr-chevron { transform: rotate(180deg); }
.hdr-dd-menu { min-width: 200px; border: 1.5px solid var(--line); border-radius: 12px; box-shadow: 0 8px 28px rgba(7,31,64,.13); padding: .4rem; margin-top: .4rem !important; }
.hdr-dd-item { display: flex; align-items: center; gap: .65rem; padding: .55rem .8rem; border-radius: 8px; font-size: .8rem; font-weight: 600; color: var(--text); transition: background .12s; }
.hdr-dd-item i { width: 16px; text-align: center; color: #0d6efd; font-size: .82rem; }
.hdr-dd-item:hover { background: var(--soft); color: var(--navy); }
.hdr-dd-logout { display: flex; align-items: center; gap: .65rem; padding: .55rem .8rem; border-radius: 8px; font-size: .8rem; font-weight: 600; color: #dc2626; transition: background .12s; }
.hdr-dd-logout i { width: 16px; text-align: center; font-size: .82rem; }
.hdr-dd-logout:hover { background: #fff0f0; color: #b91c1c; }
.page-content { flex: 1; padding: 1.5rem 1.75rem 3rem; }
.page-hd { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.35rem; gap: 1rem; flex-wrap: wrap; }
.page-hd h1 { font-size: 1.25rem; font-weight: 800; color: var(--navy); margin: 0; }
.page-hd p  { font-size: .8rem; color: var(--muted); margin: .2rem 0 0; }
.sec-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; gap: .75rem; flex-wrap: wrap; }
.sec-hd h2 { font-size: .95rem; font-weight: 800; color: var(--navy); margin: 0; }
.cardx { background: #fff; border: 1.5px solid var(--line); border-radius: var(--radius); padding: 1.25rem 1.4rem; box-shadow: 0 2px 10px rgba(7,31,64,.04); }
.stat-card { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.stat-card p { font-size: .73rem; font-weight: 600; color: var(--muted); margin: 0; }
.stat-card h3 { font-size: 1.7rem; font-weight: 800; color: var(--navy); margin: .08rem 0 0; }
.si { width: 46px; height: 46px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.si-b { background: #edf4ff; color: #0d6efd; } .si-g { background: #e8f7f0; color: #16a34a; }
.si-o { background: #fff8e6; color: #b88200; } .si-p { background: #f3eeff; color: #7c3aed; }
.si-t { background: #e0f7f5; color: #0d9488; } .si-r { background: #fff0f0; color: #dc3545; }
.tbl-wrap { overflow-x: auto; }
.table { font-size: .82rem; }
.table th { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); white-space: nowrap; }
.table td { vertical-align: middle; }
.bs { display: inline-block; font-size: .67rem; font-weight: 700; border-radius: 20px; padding: .16rem .6rem; }
.bs-active, .bs-scheduled { background: #e8f7f0; color: #166534; }
.bs-draft { background: #f4f7fb; color: #6c7a8d; }
.bs-closed, .bs-cancelled, .bs-completed { background: #fff0f0; color: #991b1b; }
.empty-st { text-align: center; padding: 3rem 1rem; }
.empty-st i { font-size: 2.5rem; color: #cdd6e0; margin-bottom: .85rem; display: block; }
.empty-st p { color: var(--muted); margin: 0; font-size: .85rem; }
@media (max-width: 991px) {
  .sidebar { transform: translateX(-100%); box-shadow: 4px 0 24px rgba(0,0,0,.2); }
  .sidebar.open { transform: translateX(0); }
  .main-wrap { margin-left: 0; }
  .hdr-hamburger { display: flex; align-items: center; justify-content: center; }
  .hdr-right { display: none; }
  .page-content { padding: 1.1rem 1rem 3rem; }
}
@media (max-width: 600px) { .page-content { padding: .9rem .85rem 3rem; } }
</style>
</head>
<body>

<div class="sb-overlay" id="sbOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <span class="ic"><i class="fa-solid fa-graduation-cap"></i></span>
    <div>
      <div class="sb-brand-name"><?= e(UMS_NAME) ?></div>
      <div class="sb-brand-sub">Teacher Portal</div>
    </div>
  </div>
  <div class="sb-profile">
    <div class="sb-avatar">
      <?php if ($photo_src): ?>
        <img src="<?= $photo_src ?>" alt="" onerror="this.style.display='none'">
      <?php else: ?>
        <?= $initials ?>
      <?php endif; ?>
    </div>
    <div style="min-width:0">
      <div class="sb-pname"><?= e($teacher['name']) ?></div>
      <div class="sb-pid"><?= e($teacher['employee_no']) ?></div>
      <span class="sb-badge"><i class="fa-solid fa-circle-check"></i> Active</span>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section-label">Main</div>
    <a href="portal.php" class="sb-link <?= $nav==='dashboard'?'active':'' ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="subjects.php" class="sb-link <?= $nav==='subjects'?'active':'' ?>">
      <i class="fa-solid fa-book-open"></i> My Subjects
      <?php if ($nav_subj > 0): ?><span class="sb-nb"><?= $nav_subj ?></span><?php endif; ?>
    </a>
    <div class="sb-section-label">Academic</div>
    <a href="quizzes.php" class="sb-link <?= $nav==='quizzes'?'active':'' ?>">
      <i class="fa-solid fa-circle-question"></i> Quizzes
      <?php if ($nav_quiz > 0): ?><span class="sb-nb"><?= $nav_quiz ?></span><?php endif; ?>
    </a>
    <a href="assignments.php" class="sb-link <?= $nav==='assignments'?'active':'' ?>">
      <i class="fa-solid fa-file-pen"></i> Assignments
      <?php if ($nav_assign > 0): ?><span class="sb-nb"><?= $nav_assign ?></span><?php endif; ?>
    </a>
    <a href="tests.php" class="sb-link <?= $nav==='tests'?'active':'' ?>">
      <i class="fa-solid fa-clipboard-list"></i> Tests &amp; Exams
      <?php if ($nav_tests > 0): ?><span class="sb-nb"><?= $nav_tests ?></span><?php endif; ?>
    </a>
    <a href="announcements.php" class="sb-link <?= $nav==='announcements'?'active':'' ?>"><i class="fa-solid fa-bullhorn"></i> Announcements</a>
    <div class="sb-section-label">Account</div>
    <a href="profile.php" class="sb-link <?= $nav==='profile'?'active':'' ?>"><i class="fa-solid fa-circle-user"></i> My Profile</a>
    <a href="change_password.php" class="sb-link <?= $nav==='change_password'?'active':'' ?>"><i class="fa-solid fa-key"></i> Change Password</a>
    <a href="logout.php" class="sb-link sb-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
  <div class="sb-footer">&copy; <?= date('Y') ?> <?= e(UMS_NAME) ?><br>All rights reserved</div>
</aside>

<div class="main-wrap">
  <header class="top-hdr">
    <button class="hdr-hamburger" id="hamburger" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
    <div class="hdr-title">
      <i class="fa-solid fa-<?= $nav==='profile'?'circle-user':($nav==='change_password'?'key':'gauge-high') ?> me-2 text-primary" style="font-size:.8rem"></i>
      <?= e($page_title ?? 'Dashboard') ?>
    </div>
    <div class="hdr-right">
      <div class="dropdown">
        <button class="hdr-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="hdr-avatar">
            <?php if ($photo_src): ?><img src="<?= $photo_src ?>" alt=""><?php else: ?><?= $initials ?><?php endif; ?>
          </div>
          <div>
            <div class="hdr-uname"><?= $first_name ?></div>
            <div class="hdr-uprog"><?= $desig ?></div>
          </div>
          <i class="fa-solid fa-chevron-down hdr-chevron"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end hdr-dd-menu">
          <li><a class="hdr-dd-item" href="profile.php"><i class="fa-solid fa-circle-user"></i>My Profile</a></li>
          <li><a class="hdr-dd-item" href="change_password.php"><i class="fa-solid fa-key"></i>Change Password</a></li>
          <li><hr class="dropdown-divider mx-2 my-1"></li>
          <li><a class="hdr-dd-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </header>
  <div class="page-content">
