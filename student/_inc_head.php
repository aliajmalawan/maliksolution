<?php
// Required before including:
// $page_title  - string
// $active_nav  - 'dashboard' | 'profile' | 'security'
// $name, $first_name, $program, $student_id - student data (already htmlspecialchars'd)
// $my_subjects - array (optional, defaults to [])
$my_subjects = $my_subjects ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title) ?> — Student Portal</title>
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
}
body { font-family: 'Poppins', sans-serif; background: var(--soft); color: var(--text); min-height: 100vh; }
a { text-decoration: none; color: inherit; }

/* ── Sidebar ── */
.sidebar {
  position: fixed; top: 0; left: 0; bottom: 0; width: var(--sb-w);
  background: var(--navy); z-index: 400;
  display: flex; flex-direction: column;
  overflow-y: auto; overflow-x: hidden;
  scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.1) transparent;
  transition: transform .28s cubic-bezier(.4,0,.2,1);
}
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 4px; }
.sb-brand { display: flex; align-items: center; gap: .7rem; padding: 1.1rem 1.25rem 1rem; border-bottom: 1px solid rgba(255,255,255,.07); flex-shrink: 0; }
.sb-brand img { width: 38px; height: 38px; border-radius: 50%; background: #fff; padding: 3px; flex-shrink: 0; }
.sb-brand-name { font-size: .82rem; font-weight: 800; color: #fff; line-height: 1.2; }
.sb-brand-sub  { font-size: .62rem; font-weight: 600; color: var(--gold); text-transform: uppercase; letter-spacing: .06em; }
.sb-profile { display: flex; align-items: center; gap: .75rem; padding: .9rem 1.25rem; background: rgba(255,255,255,.04); border-bottom: 1px solid rgba(255,255,255,.07); flex-shrink: 0; }
.sb-avatar { width: 42px; height: 42px; border-radius: 50%; background: rgba(246,178,33,.15); border: 2px solid rgba(246,178,33,.3); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--gold); flex-shrink: 0; }
.sb-pname { font-size: .8rem; font-weight: 700; color: #fff; line-height: 1.3; }
.sb-pid   { font-size: .63rem; color: rgba(255,255,255,.45); font-family: monospace; }
.sb-badge { margin-top: .3rem; display: inline-flex; align-items: center; gap: .25rem; background: rgba(34,197,94,.15); color: #4ade80; font-size: .58rem; font-weight: 700; border-radius: 20px; padding: .12rem .5rem; border: 1px solid rgba(34,197,94,.2); text-transform: uppercase; letter-spacing: .04em; }
.sb-nav { flex: 1; padding: .6rem 0; }
.sb-section-label { font-size: .57rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.28); padding: .8rem 1.25rem .35rem; }
.sb-link { display: flex; align-items: center; gap: .75rem; padding: .65rem 1.25rem; font-size: .8rem; font-weight: 600; color: var(--sb-link); border-left: 3px solid transparent; transition: background .15s, color .15s, border-color .15s; cursor: pointer; }
.sb-link i { width: 18px; text-align: center; font-size: .88rem; flex-shrink: 0; }
.sb-link:hover { background: rgba(255,255,255,.06); color: #fff; }
.sb-link.active { background: rgba(246,178,33,.1); color: var(--gold); border-left-color: var(--gold); font-weight: 700; }
.sb-link.active i { color: var(--gold); }
.sb-link.sb-logout:hover { background: rgba(239,68,68,.12); color: #f87171; }
.sb-footer { padding: .85rem 1.25rem; border-top: 1px solid rgba(255,255,255,.07); font-size: .65rem; color: rgba(255,255,255,.25); flex-shrink: 0; line-height: 1.6; }

/* ── Overlay ── */
.sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 399; backdrop-filter: blur(2px); }
.sb-overlay.show { display: block; }

/* ── Main ── */
.main-wrap { margin-left: var(--sb-w); min-height: 100vh; display: flex; flex-direction: column; transition: margin-left .28s cubic-bezier(.4,0,.2,1); }
.top-hdr { height: var(--hdr-h); background: #fff; border-bottom: 1px solid var(--line); display: flex; align-items: center; padding: 0 1.5rem; gap: .85rem; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 6px rgba(7,31,64,.06); flex-shrink: 0; }
.hdr-hamburger { display: none; background: none; border: none; color: var(--navy); cursor: pointer; font-size: 1.1rem; padding: .4rem .5rem; border-radius: 8px; transition: background .15s; line-height: 1; flex-shrink: 0; }
.hdr-hamburger:hover { background: var(--soft); }
.hdr-title { font-size: .88rem; font-weight: 800; color: var(--navy); flex: 1; }
.hdr-right { display: flex; align-items: center; gap: .75rem; margin-left: auto; }
.hdr-user { display: flex; align-items: center; gap: .6rem; background: var(--soft); border: 1.5px solid var(--line); border-radius: 30px; padding: .3rem .75rem .3rem .4rem; cursor: pointer; transition: border-color .15s, background .15s; }
.hdr-user:hover { background: #e8edf5; border-color: #c5d0e0; }
.hdr-user::after { display: none; }
.hdr-avatar { width: 30px; height: 30px; border-radius: 50%; background: var(--navy); color: var(--gold); display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
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

/* ── Page / Panels ── */
.page-content { flex: 1; padding: 1.5rem 1.75rem 3rem; }
.page-hdr { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.page-hdr h1 { font-size: 1.2rem; font-weight: 800; color: var(--navy); margin: 0 0 .2rem; }
.page-hdr p  { font-size: .78rem; color: var(--muted); margin: 0; }
.panel { background: #fff; border: 1.5px solid var(--line); border-radius: var(--radius); overflow: hidden; box-shadow: 0 2px 10px rgba(7,31,64,.05); margin-bottom: 1.35rem; }
.panel-head { display: flex; align-items: center; justify-content: space-between; padding: .8rem 1.2rem; border-bottom: 1px solid var(--line); background: #fafbfd; }
.panel-head-title { font-size: .78rem; font-weight: 800; color: var(--navy); display: flex; align-items: center; gap: .45rem; }
.panel-head-title i { color: #0d6efd; font-size: .82rem; }
.panel-body { padding: .85rem 1.2rem; }
.panel-body-flush { padding: 0; }
.sec-hdr { display: flex; align-items: center; justify-content: space-between; margin-bottom: .85rem; }
.sec-title { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .09em; color: var(--muted); display: flex; align-items: center; gap: .4rem; }
.sec-link { font-size: .73rem; font-weight: 700; color: #0d6efd; display: inline-flex; align-items: center; gap: .25rem; }
.sec-link:hover { color: #0056d2; text-decoration: underline; }

/* ── Welcome Banner ── */
.welcome-banner { background: linear-gradient(135deg,var(--navy) 0%,#0d2d5c 60%,#1a3a6b 100%); border-radius: var(--radius); padding: 1.4rem 1.75rem; color: #fff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.35rem; position: relative; overflow: hidden; }
.welcome-banner::before { content: ''; position: absolute; width: 220px; height: 220px; border-radius: 50%; background: rgba(246,178,33,.07); right: -50px; top: -70px; pointer-events: none; }
.wb-greeting { font-size: 1.25rem; font-weight: 800; margin-bottom: .35rem; }
.wb-meta { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
.wb-pill { display: inline-flex; align-items: center; gap: .28rem; font-size: .72rem; font-weight: 700; border-radius: 20px; padding: .2rem .75rem; }
.wb-pill-prog { background: rgba(246,178,33,.15); color: var(--gold); border: 1px solid rgba(246,178,33,.25); }
.wb-pill-id { background: rgba(255,255,255,.08); color: rgba(255,255,255,.7); border: 1px solid rgba(255,255,255,.12); font-family: monospace; }
.wb-avatar { width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,.1); border: 2px solid rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--gold); flex-shrink: 0; }

/* ── Stats ── */
.stats-row { display: flex; gap: .85rem; margin-bottom: 1.35rem; flex-wrap: wrap; }
.stat-card { flex: 1; min-width: 130px; background: #fff; border-radius: var(--radius); border: 1.5px solid var(--line); padding: 1rem 1.2rem; display: flex; align-items: center; gap: .85rem; box-shadow: 0 2px 8px rgba(7,31,64,.05); transition: border-color .15s, box-shadow .15s; }
.stat-card:hover { border-color: #c5d3e8; box-shadow: 0 4px 16px rgba(7,31,64,.09); }
.stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.stat-info-val { font-size: 1.35rem; font-weight: 800; color: var(--navy); line-height: 1; }
.stat-info-lbl { font-size: .65rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-top: .25rem; }

/* ── Subjects / Downloads / Datesheets ── */
.subj-table { width: 100%; border-collapse: collapse; }
.subj-table th { background: var(--soft); font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); padding: .55rem 1rem; border-bottom: 1.5px solid var(--line); text-align: left; }
.subj-table td { padding: .65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .83rem; vertical-align: middle; }
.subj-table tbody tr:last-child td { border-bottom: none; }
.subj-table tbody tr:hover td { background: #f8faff; }
.subj-sn { width: 36px; color: var(--muted); font-size: .72rem; font-weight: 700; text-align: center; }
.subj-name-cell { font-weight: 700; color: var(--navy); }
.subj-desc-cell { color: var(--muted); font-size: .76rem; }
.subj-no-data { text-align: center; padding: 2.5rem 1rem; color: var(--muted); font-size: .83rem; }
.subj-no-data i { font-size: 2rem; color: #c5d0e0; display: block; margin-bottom: .6rem; }
.ds-item { display: flex; align-items: flex-start; gap: .85rem; padding: .75rem 1.2rem; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; transition: background .12s; }
.ds-item:last-child { border-bottom: none; }
.ds-item:hover { background: #f8faff; }
.ds-icon { width: 36px; height: 36px; border-radius: 9px; background: #edf4ff; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-size: .88rem; flex-shrink: 0; }
.ds-title { font-size: .82rem; font-weight: 700; color: var(--navy); line-height: 1.3; }
.ds-meta  { font-size: .68rem; color: var(--muted); margin-top: .1rem; display: flex; gap: .5rem; flex-wrap: wrap; }
.ds-badge { display: inline-flex; align-items: center; font-size: .63rem; font-weight: 700; border-radius: 5px; padding: .1rem .45rem; white-space: nowrap; }
.dl-item { display: flex; align-items: center; gap: .85rem; padding: .7rem 1.2rem; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; transition: background .12s; }
.dl-item:last-child { border-bottom: none; }
.dl-item:hover { background: #f8faff; }
.dl-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .88rem; flex-shrink: 0; color: #fff; }
.dl-title { font-size: .82rem; font-weight: 700; color: var(--navy); line-height: 1.3; }
.dl-meta  { font-size: .68rem; color: var(--muted); }
.dl-arrow { margin-left: auto; color: #c5d0e0; font-size: .8rem; flex-shrink: 0; }

/* ── Quick Links ── */
.ql-item { display: flex; align-items: center; gap: .75rem; padding: .7rem 1rem; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; transition: background .12s; }
.ql-item:last-child { border-bottom: none; }
.ql-item:hover { background: #f8faff; }
.ql-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; }
.ql-text { font-size: .82rem; font-weight: 700; }
.ql-sub  { font-size: .68rem; color: var(--muted); }

/* ── Detail / Password ── */
.detail-table { width: 100%; border-collapse: collapse; }
.detail-table tr td { padding: .55rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: .83rem; }
.detail-table tr:last-child td { border-bottom: none; }
.detail-table tr:hover td { background: #f8faff; }
.dt-label { width: 38%; font-size: .7rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
.dt-val { font-weight: 600; color: var(--text); }
.pw-group { position: relative; }
.pw-eye { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: .82rem; padding: .2rem; transition: color .12s; line-height: 1; }
.pw-eye:hover { color: var(--navy); }
.badge-approved { display: inline-flex; align-items: center; gap: .3rem; background: #dcfce7; color: #166534; border-radius: 20px; padding: .25rem .75rem; font-size: .72rem; font-weight: 700; }

/* ── Sidebar dropdown (Quick Links) ── */
.sb-dropdown-toggle { cursor: pointer; user-select: none; }
.sb-dd-chevron { margin-left: auto; font-size: .6rem; color: rgba(255,255,255,.3); transition: transform .25s; flex-shrink: 0; }
.sb-dropdown-toggle.open .sb-dd-chevron { transform: rotate(180deg); }
.sb-sub-menu { overflow: hidden; max-height: 0; transition: max-height .28s cubic-bezier(.4,0,.2,1); }
.sb-sub-menu.open { max-height: 220px; }
.sb-sub-link {
  display: flex; align-items: center; gap: .65rem;
  padding: .48rem 1.25rem .48rem 2.85rem;
  font-size: .76rem; font-weight: 600;
  color: rgba(255,255,255,.48);
  border-left: 3px solid transparent;
  transition: background .15s, color .15s;
}
.sb-sub-link i { width: 14px; text-align: center; font-size: .76rem; flex-shrink: 0; }
.sb-sub-link:hover { background: rgba(255,255,255,.05); color: rgba(255,255,255,.85); }

/* ── Responsive ── */
@media (max-width: 991px) {
  .sidebar { transform: translateX(-100%); box-shadow: 4px 0 24px rgba(0,0,0,.2); }
  .sidebar.open { transform: translateX(0); }
  .main-wrap { margin-left: 0; }
  .hdr-hamburger { display: flex; align-items: center; justify-content: center; }
  .hdr-right { display: none; }
  .page-content { padding: 1.1rem 1rem 3rem; }
}
@media (max-width: 600px) {
  .wb-greeting { font-size: 1.05rem; }
  .wb-avatar { width: 44px; height: 44px; font-size: 1.1rem; }
  .stats-row { gap: .5rem; }
  .stat-card { min-width: calc(50% - .25rem); flex: none; padding: .8rem; }
  .page-content { padding: .9rem .85rem 3rem; }
}
</style>
</head>
<body>

<div class="sb-overlay" id="sbOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <img src="../assets/images/Logo.png" alt="Malik Solution">
    <div>
      <div class="sb-brand-name">Malik Solution</div>
      <div class="sb-brand-sub">Student Portal</div>
    </div>
  </div>
  <div class="sb-profile">
    <div class="sb-avatar">
      <?php if (!empty($student_photo)): ?>
        <img src="../<?= htmlspecialchars($student_photo) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
      <?php else: ?>
        <i class="fa-solid fa-user-graduate"></i>
      <?php endif; ?>
    </div>
    <div style="min-width:0">
      <div class="sb-pname"><?= $name ?></div>
      <div class="sb-pid"><?= $student_id ?: 'Student' ?></div>
      <span class="sb-badge"><i class="fa-solid fa-circle-check"></i> Approved</span>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section-label">Main Menu</div>
    <a href="portal.php" class="sb-link <?= $active_nav==='dashboard'?'active':'' ?>">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    <?php if (!empty($my_subjects)): ?>
    <a href="portal.php#sec-subjects" class="sb-link">
      <i class="fa-solid fa-book-open"></i> My Subjects
      <span style="margin-left:auto;background:rgba(246,178,33,.2);color:var(--gold);font-size:.6rem;font-weight:800;border-radius:10px;padding:.1rem .45rem"><?= count($my_subjects) ?></span>
    </a>
    <?php endif; ?>
    <div class="sb-link sb-dropdown-toggle" id="qlToggle">
      <i class="fa-solid fa-bolt"></i> Quick Links
      <i class="fa-solid fa-chevron-down sb-dd-chevron"></i>
    </div>
    <div class="sb-sub-menu" id="qlMenu">
      <a href="../datesheet.php" class="sb-sub-link"><i class="fa-solid fa-calendar-check"></i> Date Sheets</a>
      <a href="../downloads.php" class="sb-sub-link"><i class="fa-solid fa-download"></i> Downloads</a>
      <a href="../courses.php"   class="sb-sub-link"><i class="fa-solid fa-graduation-cap"></i> Courses</a>
      <a href="../contact.php"   class="sb-sub-link"><i class="fa-solid fa-headset"></i> Support</a>
    </div>
    <div class="sb-section-label" style="margin-top:.5rem">Account</div>
    <a href="profile.php" class="sb-link <?= $active_nav==='profile'?'active':'' ?>">
      <i class="fa-solid fa-circle-user"></i> My Profile
    </a>
    <a href="security.php" class="sb-link <?= $active_nav==='security'?'active':'' ?>">
      <i class="fa-solid fa-key"></i> Change Password
    </a>
    <a href="logout.php" class="sb-link sb-logout">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </nav>
  <div class="sb-footer">&copy; <?= date('Y') ?> Malik Solution<br>All rights reserved</div>
</aside>

<div class="main-wrap">
  <header class="top-hdr">
    <button class="hdr-hamburger" id="hamburger" aria-label="Toggle menu">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div class="hdr-title">
      <i class="fa-solid fa-<?= $active_nav==='profile'?'circle-user':($active_nav==='security'?'key':'gauge-high') ?> me-2 text-primary" style="font-size:.8rem"></i>
      <?= htmlspecialchars($page_title) ?>
    </div>
    <div class="hdr-right">
      <div class="dropdown">
        <button class="hdr-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="hdr-avatar">
            <?php if (!empty($student_photo)): ?>
              <img src="../<?= htmlspecialchars($student_photo) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            <?php else: ?>
              <i class="fa-solid fa-user-graduate"></i>
            <?php endif; ?>
          </div>
          <div>
            <div class="hdr-uname"><?= $first_name ?></div>
            <div class="hdr-uprog"><?= $program ?></div>
          </div>
          <i class="fa-solid fa-chevron-down hdr-chevron"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end hdr-dd-menu">
          <li><a class="hdr-dd-item" href="profile.php"><i class="fa-solid fa-circle-user"></i>Personal Information</a></li>
          <li><a class="hdr-dd-item" href="security.php"><i class="fa-solid fa-key"></i>Change Password</a></li>
          <li><hr class="dropdown-divider mx-2 my-1"></li>
          <li><a class="hdr-dd-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </header>
  <main class="page-content">
