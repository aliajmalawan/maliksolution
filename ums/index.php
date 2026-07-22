<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

/*
 * ERP Landing Page — Malik Solution branding (blue & white).
 * Marketing page only: the Admin/Teacher/Student dashboards already
 * exist and are linked, never rebuilt here.
 */

$site = dirname(UMS_URL); // company website root, one level up

// Pull branding from the website CMS settings (read-only; graceful fallbacks)
$brand = ['name' => 'Malik Solution', 'logo_white' => '', 'logo' => ''];
try {
    $res = ums_db()->query("SELECT name, value FROM settings WHERE name IN ('site_name','logo_white_path','logo_path')");
    while ($row = $res->fetch_assoc()) {
        if ($row['name'] === 'site_name' && $row['value'] !== '')       $brand['name'] = $row['value'];
        if ($row['name'] === 'logo_white_path' && $row['value'] !== '') $brand['logo_white'] = $row['value'];
        if ($row['name'] === 'logo_path' && $row['value'] !== '')       $brand['logo'] = $row['value'];
    }
} catch (Throwable $t) { /* settings table unavailable — fallbacks are fine */ }

$logoNav = $brand['logo_white'] !== '' ? $site . '/' . $brand['logo_white'] : '';

$modules = [
    ['fa-user-plus', 'Admissions', 'Online applications, merit lists, and one-click enrollment.'],
    ['fa-sitemap', 'Departments & Programs', 'Faculties, departments, degree programs, and batches.'],
    ['fa-book-open', 'Courses', 'Semester-wise courses, credit hours, and prerequisites.'],
    ['fa-clipboard-user', 'Attendance', 'Daily student and staff attendance with live percentages.'],
    ['fa-table-cells', 'Timetable', 'Clash-free class scheduling for every section.'],
    ['fa-file-pen', 'Examination', 'Exams, marks entry, GPA/CGPA, and transcripts.'],
    ['fa-money-bill-wave', 'Fee Management', 'Fee structures, challans, collection, and defaulters.'],
    ['fa-calculator', 'Accounts', 'Income, expenses, and financial reporting.'],
    ['fa-book-bookmark', 'Library', 'Catalog, issue/return, and fine management.'],
    ['fa-bed', 'Hostel', 'Rooms, allotments, and hostel billing.'],
    ['fa-bus', 'Transport', 'Routes, vehicles, and transport fees.'],
    ['fa-file-invoice-dollar', 'HR & Payroll', 'Staff records, leave, and salary slips.'],
];

$features = [
    ['fa-graduation-cap', 'Semester System', 'Credit hours, GPA/CGPA, prerequisites, and semester promotion — annual mode ready for the future.'],
    ['fa-building-columns', 'Multi-Campus Ready', 'One installation serves every campus with consolidated head-office reporting.'],
    ['fa-shield-halved', 'Bank-Grade Security', 'Encrypted passwords, prepared statements, CSRF protection, and a complete audit log.'],
    ['fa-chart-line', 'Live Analytics', 'Chart-powered dashboards for attendance, fee collection, and enrollment.'],
    ['fa-mobile-screen-button', 'Fully Responsive', 'Every screen works beautifully on desktop, tablet, and mobile.'],
    ['fa-headset', '24/7 Support', 'Backed by Malik Solution — training, maintenance, and round-the-clock help.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>University Management System — <?= e($brand['name']) ?></title>
  <meta name="description" content="Complete University Management System by Malik Solution: admissions, semester-based academics with GPA/CGPA, attendance, examinations, fees, payroll, library, hostel, and transport.">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* ══ Malik Solution branding — blue & white ══ */
    :root {
      --navy: #071f40; --navy-2: #0d3a75; --blue: #0d6efd; --cyan: #22d3ee;
      --ink: #172033; --muted: #64748b; --soft: #f4f7fb; --line: #e3eaf3;
      --grad: linear-gradient(135deg, #0d6efd 0%, #22d3ee 100%);
      --grad-dark: linear-gradient(135deg, #071f40 0%, #0d3a75 60%, #0d6efd 140%);
      --radius: 14px;
      --shadow: 0 10px 40px rgba(7, 31, 64, .08);
      --shadow-lg: 0 24px 70px rgba(7, 31, 64, .16);
    }
    body { font-family: 'Inter', 'Segoe UI', sans-serif; color: var(--ink); background: #fff; overflow-x: hidden; }
    h1, h2, h3, h4, h5 { font-family: 'Poppins', sans-serif; }

    /* buttons */
    .btn-grad {
      background: var(--grad); color: #fff; font-weight: 700; border: none;
      border-radius: 30px; padding: .78rem 1.8rem;
      box-shadow: 0 8px 24px rgba(13, 110, 253, .35);
      transition: transform .18s, box-shadow .18s;
    }
    .btn-grad:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 12px 30px rgba(34, 211, 238, .45); }
    .btn-ghost {
      background: transparent; color: #fff; font-weight: 700;
      border: 2px solid rgba(255, 255, 255, .45); border-radius: 30px; padding: .7rem 1.7rem;
      transition: background .18s, border-color .18s;
    }
    .btn-ghost:hover { background: rgba(255, 255, 255, .12); border-color: #fff; color: #fff; }
    .btn-pill-sm { border-radius: 30px; font-weight: 700; font-size: .84rem; padding: .5rem 1.15rem; }

    /* navbar */
    .ums-nav {
      background: linear-gradient(120deg, rgba(7, 31, 64, .97) 0%, rgba(13, 58, 117, .97) 70%, rgba(13, 110, 253, .85) 130%);
      backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, .08);
    }
    .ums-nav .navbar-brand { display: flex; align-items: center; gap: .65rem; color: #fff; font-family: 'Poppins', sans-serif; font-weight: 800; }
    .ums-nav .navbar-brand img { height: 40px; width: auto; max-width: 180px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,.35)); }
    .ums-nav .brand-tag { font-size: .68rem; color: #7dd3fc; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; border-left: 1px solid rgba(255,255,255,.25); padding-left: .65rem; }
    .ums-nav .nav-link { color: #d3e1f5 !important; font-weight: 600; font-size: .9rem; border-radius: 30px; padding: .5rem 1rem !important; }
    .ums-nav .nav-link:hover { color: #fff !important; background: rgba(255, 255, 255, .12); }

    /* hero */
    .hero {
      position: relative; background: var(--grad-dark); color: #fff;
      padding: 5rem 0 5.5rem; overflow: hidden;
    }
    .hero::before {
      content: ""; position: absolute; inset: 0; pointer-events: none;
      background-image: linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
      background-size: 44px 44px;
      mask-image: radial-gradient(ellipse 90% 80% at 50% 40%, #000 40%, transparent 100%);
      -webkit-mask-image: radial-gradient(ellipse 90% 80% at 50% 40%, #000 40%, transparent 100%);
    }
    .blob { position: absolute; border-radius: 50%; pointer-events: none; filter: blur(10px); }
    .blob.b1 { width: 560px; height: 560px; top: -220px; right: -140px; background: radial-gradient(circle, rgba(34,211,238,.28) 0%, transparent 68%); animation: drift 11s ease-in-out infinite alternate; }
    .blob.b2 { width: 460px; height: 460px; bottom: -220px; left: -130px; background: radial-gradient(circle, rgba(13,110,253,.38) 0%, transparent 68%); animation: drift 13s ease-in-out 2s infinite alternate-reverse; }
    @keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(-36px,26px) scale(1.08); } }
    .hero-kicker {
      display: inline-flex; align-items: center; gap: .5rem;
      background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
      color: #a8e8f5; border-radius: 30px; padding: .42rem 1.1rem;
      font-size: .76rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    }
    .pulse-dot { width: 8px; height: 8px; border-radius: 50%; background: #22d3ee; position: relative; }
    .pulse-dot::after { content: ""; position: absolute; inset: -4px; border-radius: 50%; border: 2px solid rgba(34,211,238,.5); animation: pulse 1.8s ease-out infinite; }
    @keyframes pulse { from { transform: scale(.6); opacity: 1; } to { transform: scale(1.6); opacity: 0; } }
    .hero h1 { font-size: clamp(2.1rem, 4.6vw, 3.4rem); font-weight: 800; line-height: 1.13; letter-spacing: -.02em; }
    .hero h1 .gr { background: linear-gradient(90deg, #22d3ee, #7dd3fc); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .hero .lead-x { color: #c2d4ee; font-size: 1.06rem; max-width: 640px; }
    .portal-links a {
      display: inline-flex; align-items: center; gap: .45rem;
      color: #cbd5e1; font-size: .82rem; font-weight: 700;
      border: 1px solid rgba(255,255,255,.2); border-radius: 30px; padding: .48rem 1.05rem;
      text-decoration: none; transition: background .15s, color .15s;
    }
    .portal-links a:hover { background: rgba(255,255,255,.12); color: #fff; }
    .portal-links a i { color: #22d3ee; }

    /* section scaffolding */
    .sec { padding: 4.5rem 0; }
    .sec.alt { background: var(--soft); }
    .kick { color: var(--blue); font-size: .76rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .title { font-size: clamp(1.55rem, 2.8vw, 2.15rem); font-weight: 800; color: var(--navy); letter-spacing: -.02em; }
    .title-bar { width: 56px; height: 4px; border-radius: 4px; background: var(--grad); margin: .9rem auto 1rem; }
    .sub { color: var(--muted); max-width: 640px; margin: 0 auto; }

    /* module & feature cards */
    .m-card {
      background: #fff; border: 1px solid var(--line); border-radius: var(--radius);
      padding: 1.5rem 1.3rem; height: 100%; position: relative; overflow: hidden;
      transition: transform .22s, box-shadow .22s, border-color .22s;
    }
    .m-card::after { content: ""; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: var(--grad); opacity: 0; transition: opacity .22s; }
    .m-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: transparent; }
    .m-card:hover::after { opacity: 1; }
    .m-ico {
      width: 52px; height: 52px; border-radius: 13px; margin-bottom: 1rem;
      background: linear-gradient(135deg, rgba(13,110,253,.1), rgba(34,211,238,.12));
      color: var(--blue); display: grid; place-items: center; font-size: 1.15rem;
    }
    .m-card h3 { font-size: 1rem; font-weight: 800; color: var(--navy); }
    .m-card p { color: var(--muted); font-size: .85rem; margin: 0; }

    /* preview frames */
    .frame { background: #0b1526; border: 1px solid rgba(255,255,255,.14); border-radius: 16px; overflow: hidden; box-shadow: var(--shadow-lg); }
    .frame-bar { display: flex; align-items: center; gap: .5rem; background: rgba(255,255,255,.05); border-bottom: 1px solid rgba(255,255,255,.08); padding: .55rem .9rem; }
    .frame-bar i { width: 9px; height: 9px; border-radius: 50%; display: block; }
    .frame-bar i:nth-child(1) { background: #ff5f57; } .frame-bar i:nth-child(2) { background: #febc2e; } .frame-bar i:nth-child(3) { background: #28c840; }
    .frame-url { flex: 1; max-width: 260px; margin: 0 auto; text-align: center; background: rgba(255,255,255,.07); border-radius: 20px; color: #9fb6d8; font-size: .66rem; font-weight: 700; padding: .2rem .8rem; }
    .frame-url i { width: auto; height: auto; background: none; color: #28c840; font-size: .58rem; }
    .pv { display: flex; gap: .7rem; padding: .85rem; aspect-ratio: 16 / 9.2; }
    .pv-side { width: 20%; border-right: 1px solid rgba(255,255,255,.08); padding-right: .7rem; display: flex; flex-direction: column; gap: .5rem; }
    .sk { border-radius: 6px; background: rgba(255,255,255,.12); display: block; }
    .sk.brand { background: var(--grad); }
    .pv-main { flex: 1; display: flex; flex-direction: column; gap: .6rem; min-width: 0; }
    .pv-cards { display: flex; gap: .55rem; }
    .pv-card { flex: 1; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09); border-radius: 9px; padding: .55rem .65rem; }
    .pv-card small { display: block; color: #7e95b8; font-size: .56rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
    .pv-card strong { color: #eaf3ff; font-size: .88rem; font-weight: 800; font-family: 'Poppins', sans-serif; }
    .pv-chart { flex: 1; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07); border-radius: 9px; display: flex; align-items: flex-end; gap: .4rem; padding: .6rem; min-height: 0; }
    .pv-chart i { flex: 1; border-radius: 4px 4px 0 0; background: linear-gradient(180deg, #22d3ee, #0d6efd); display: block; }
    .pv-rows { display: flex; flex-direction: column; gap: .4rem; }
    .pv-row { display: flex; gap: .5rem; align-items: center; background: rgba(255,255,255,.045); border-radius: 7px; padding: .42rem .55rem; }
    .pv-row i { width: 13px; height: 13px; border-radius: 4px; background: rgba(34,211,238,.45); flex-shrink: 0; }
    .pv-row b { height: 6px; border-radius: 4px; background: rgba(255,255,255,.13); }
    .pv-label { display: flex; align-items: center; gap: .6rem; font-weight: 800; color: var(--navy); margin-bottom: .7rem; }
    .pv-label i { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, rgba(13,110,253,.1), rgba(34,211,238,.12)); color: var(--blue); display: grid; place-items: center; font-size: .85rem; }
    .tech-chip {
      position: absolute; z-index: 2; display: flex; align-items: center; gap: .5rem;
      background: rgba(11,21,38,.9); border: 1px solid rgba(255,255,255,.16);
      color: #e6f2ff; border-radius: 30px; padding: .48rem .95rem;
      font-size: .74rem; font-weight: 700; white-space: nowrap;
      box-shadow: 0 12px 30px rgba(0,0,0,.4); backdrop-filter: blur(8px);
    }
    .tech-chip i { color: #22d3ee; }
    @keyframes floaty { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

    /* stats band */
    .stats-band { background: var(--grad-dark); color: #fff; position: relative; overflow: hidden; padding: 3.2rem 0; }
    .stat-x { text-align: center; }
    .stat-x strong { display: block; font-size: 2.2rem; font-weight: 800; font-family: 'Poppins', sans-serif; }
    .stat-x span { color: #9fb6d8; font-size: .82rem; font-weight: 600; }

    /* CTA band */
    .cta-band {
      background: var(--grad-dark); color: #fff; border-radius: 22px;
      padding: 3rem 2.4rem; position: relative; overflow: hidden;
      box-shadow: var(--shadow-lg);
    }
    .cta-band::before { content: ""; position: absolute; width: 380px; height: 380px; top: -160px; right: -100px; border-radius: 50%; background: radial-gradient(circle, rgba(34,211,238,.25) 0%, transparent 70%); }

    /* reveal animation */
    .rv { opacity: 0; transform: translateY(26px); transition: opacity .6s ease, transform .6s ease; }
    .rv.in { opacity: 1; transform: translateY(0); }

    .ums-foot { background: var(--navy); color: #7e95b8; font-size: .8rem; }
    .ums-foot a { color: #9fb6d8; text-decoration: none; }
    .ums-foot a:hover { color: #fff; }
    @media (max-width: 767px) { .tech-chip { display: none; } .pv-side { display: none; } }
  </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="navbar navbar-expand-lg navbar-dark ums-nav sticky-top py-2">
  <div class="container">
    <a class="navbar-brand" href="<?= UMS_URL ?>/index.php">
      <?php if ($logoNav !== ''): ?>
        <img src="<?= e($logoNav) ?>" alt="<?= e($brand['name']) ?>">
      <?php else: ?>
        <i class="fa-solid fa-graduation-cap"></i> <?= e($brand['name']) ?>
      <?php endif; ?>
      <span class="brand-tag">University ERP</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#umsMenu" aria-controls="umsMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="umsMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="#modules">Modules</a></li>
        <li class="nav-item"><a class="nav-link" href="#previews">Previews</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e($site) ?>/index.php">Malik Solution</a></li>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
          <a class="btn btn-grad btn-pill-sm" href="<?= UMS_URL ?>/admin/login.php"><i class="fa-solid fa-right-to-bracket me-1"></i>Admin Login</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ══ HERO ══ -->
<header class="hero">
  <div class="blob b1"></div>
  <div class="blob b2"></div>
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="hero-kicker rv"><span class="pulse-dot"></span> University Management System</span>
        <h1 class="mt-3 rv">The Complete ERP for <span class="gr">Modern Universities</span></h1>
        <p class="lead-x mt-3 rv">
          Admissions, semester-based academics, attendance, examinations with GPA/CGPA, fee
          management, payroll, library, hostel, and transport — one secure platform for your
          entire institution, built by <?= e($brand['name']) ?>.
        </p>
        <div class="d-flex gap-3 flex-wrap mt-4 rv">
          <a href="<?= UMS_URL ?>/admin/login.php" class="btn btn-grad"><i class="fa-solid fa-gauge-high me-2"></i>Admin Login</a>
          <a href="<?= e($site) ?>/demonstration.php#book-demo" class="btn btn-ghost"><i class="fa-solid fa-calendar-check me-2"></i>Book Live Demo</a>
        </div>
        <div class="portal-links d-flex gap-2 flex-wrap mt-3 rv">
          <a href="<?= e($site) ?>/Teacher/login.php"><i class="fa-solid fa-person-chalkboard"></i> Teacher Login</a>
          <a href="<?= e($site) ?>/student/login.php"><i class="fa-solid fa-user-graduate"></i> Student Login</a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="position-relative rv">
          <div class="frame">
            <div class="frame-bar"><i></i><i></i><i></i><span class="frame-url"><i class="fa-solid fa-lock"></i> maliksolution.com/ums/admin</span></div>
            <div class="pv">
              <div class="pv-side">
                <span class="sk brand" style="height:12px;width:75%"></span>
                <span class="sk" style="height:8px;width:88%"></span>
                <span class="sk" style="height:8px;width:70%"></span>
                <span class="sk" style="height:8px;width:80%"></span>
                <span class="sk" style="height:8px;width:62%"></span>
              </div>
              <div class="pv-main">
                <div class="pv-cards">
                  <div class="pv-card"><small>Students</small><strong>2,847</strong></div>
                  <div class="pv-card"><small>Attendance</small><strong>91.4%</strong></div>
                  <div class="pv-card"><small>Fees (Month)</small><strong>Rs 8.4M</strong></div>
                </div>
                <div class="pv-chart">
                  <?php foreach ([48, 62, 55, 70, 64, 82, 74, 92, 85, 96] as $h): ?><i style="height:<?= $h ?>%"></i><?php endforeach; ?>
                </div>
                <div class="pv-rows">
                  <div class="pv-row"><i></i><b style="width:44%"></b></div>
                  <div class="pv-row"><i></i><b style="width:32%"></b></div>
                </div>
              </div>
            </div>
          </div>
          <div class="tech-chip" style="top:10%;left:-24px;animation:floaty 5s ease-in-out infinite"><i class="fa-solid fa-clipboard-user"></i> Live Attendance</div>
          <div class="tech-chip" style="bottom:12%;right:-18px;animation:floaty 6s ease-in-out 1s infinite"><i class="fa-solid fa-money-bill-trend-up"></i> Fee Analytics</div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ══ MODULES ══ -->
<section class="sec" id="modules">
  <div class="container">
    <div class="text-center mb-5 rv">
      <div class="kick">Complete Coverage</div>
      <h2 class="title">Every Module Your University Needs</h2>
      <div class="title-bar"></div>
      <p class="sub">From the first admission application to the final transcript — nothing lives outside the system.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($modules as [$ic, $t, $d]): ?>
        <div class="col-md-6 col-xl-3 rv">
          <div class="m-card">
            <div class="m-ico"><i class="fa-solid <?= $ic ?>"></i></div>
            <h3><?= e($t) ?></h3>
            <p><?= e($d) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ PREVIEWS ══ -->
<section class="sec alt" id="previews">
  <div class="container">
    <div class="text-center mb-5 rv">
      <div class="kick">See It Before You Buy It</div>
      <h2 class="title">One Platform, Three Portals</h2>
      <div class="title-bar"></div>
      <p class="sub">A live administrator dashboard, plus dedicated portals for every teacher and student.</p>
    </div>

    <!-- Admin dashboard preview -->
    <div class="mx-auto rv" style="max-width:880px">
      <div class="pv-label">
        <i class="fa-solid fa-gauge-high"></i> Admin Dashboard
        <a href="<?= UMS_URL ?>/admin/login.php" class="btn btn-grad btn-pill-sm ms-auto">Open Live <i class="fa-solid fa-arrow-right ms-1"></i></a>
      </div>
      <div class="frame">
        <div class="frame-bar"><i></i><i></i><i></i><span class="frame-url"><i class="fa-solid fa-lock"></i> /ums/admin/dashboard</span></div>
        <div class="pv">
          <div class="pv-side">
            <span class="sk brand" style="height:12px;width:75%"></span>
            <span class="sk" style="height:8px;width:88%"></span>
            <span class="sk" style="height:8px;width:70%"></span>
            <span class="sk" style="height:8px;width:80%"></span>
            <span class="sk" style="height:8px;width:62%"></span>
            <span class="sk" style="height:8px;width:74%"></span>
          </div>
          <div class="pv-main">
            <div class="pv-cards">
              <div class="pv-card"><small>Total Students</small><strong>2,847</strong></div>
              <div class="pv-card"><small>Faculty</small><strong>148</strong></div>
              <div class="pv-card"><small>Attendance</small><strong>91.4%</strong></div>
              <div class="pv-card"><small>Fees (Month)</small><strong>Rs 8.4M</strong></div>
            </div>
            <div class="pv-chart">
              <?php foreach ([40, 55, 48, 66, 58, 75, 68, 88, 80, 95, 86, 98] as $h): ?><i style="height:<?= $h ?>%"></i><?php endforeach; ?>
            </div>
            <div class="pv-rows">
              <div class="pv-row"><i></i><b style="width:46%"></b></div>
              <div class="pv-row"><i></i><b style="width:35%"></b></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Teacher + Student portals -->
    <div class="row g-4 mt-3">
      <div class="col-lg-6 rv">
        <div class="pv-label">
          <i class="fa-solid fa-person-chalkboard"></i> Teacher Portal
          <a href="<?= e($site) ?>/Teacher/login.php" class="btn btn-outline-primary btn-pill-sm ms-auto" style="border-radius:30px">Teacher Login</a>
        </div>
        <div class="frame">
          <div class="frame-bar"><i></i><i></i><i></i><span class="frame-url">/teacher/portal</span></div>
          <div class="pv" style="aspect-ratio:16/8">
            <div class="pv-main">
              <div class="pv-cards">
                <div class="pv-card"><small>My Classes</small><strong>6</strong></div>
                <div class="pv-card"><small>Today's Lectures</small><strong>4</strong></div>
                <div class="pv-card"><small>Assignments</small><strong>12</strong></div>
              </div>
              <div class="pv-rows">
                <div class="pv-row"><i></i><b style="width:52%"></b></div>
                <div class="pv-row"><i></i><b style="width:40%"></b></div>
                <div class="pv-row"><i></i><b style="width:46%"></b></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 rv">
        <div class="pv-label">
          <i class="fa-solid fa-user-graduate"></i> Student Portal
          <a href="<?= e($site) ?>/student/login.php" class="btn btn-outline-primary btn-pill-sm ms-auto" style="border-radius:30px">Student Login</a>
        </div>
        <div class="frame">
          <div class="frame-bar"><i></i><i></i><i></i><span class="frame-url">/student/portal</span></div>
          <div class="pv" style="aspect-ratio:16/8">
            <div class="pv-main">
              <div class="pv-cards">
                <div class="pv-card"><small>CGPA</small><strong>3.62</strong></div>
                <div class="pv-card"><small>Attendance</small><strong>88%</strong></div>
                <div class="pv-card"><small>Fee Due</small><strong>Rs 12,500</strong></div>
              </div>
              <div class="pv-rows">
                <div class="pv-row"><i></i><b style="width:48%"></b></div>
                <div class="pv-row"><i></i><b style="width:36%"></b></div>
                <div class="pv-row"><i></i><b style="width:55%"></b></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ STATISTICS ══ -->
<section class="stats-band">
  <div class="container">
    <div class="row g-4">
      <div class="col-6 col-md-3 rv"><div class="stat-x"><strong><span class="cnt" data-count="25">0</span>+</strong><span>ERP Modules</span></div></div>
      <div class="col-6 col-md-3 rv"><div class="stat-x"><strong><span class="cnt" data-count="3">0</span></strong><span>Dedicated Portals</span></div></div>
      <div class="col-6 col-md-3 rv"><div class="stat-x"><strong><span class="cnt" data-count="100">0</span>%</strong><span>Semester System + GPA</span></div></div>
      <div class="col-6 col-md-3 rv"><div class="stat-x"><strong>24/7</strong><span>Support by Malik Solution</span></div></div>
    </div>
  </div>
</section>

<!-- ══ FEATURES ══ -->
<section class="sec" id="features">
  <div class="container">
    <div class="text-center mb-5 rv">
      <div class="kick">Why Malik UMS</div>
      <h2 class="title">Built Like Commercial ERP, Priced Like Local Software</h2>
      <div class="title-bar"></div>
    </div>
    <div class="row g-4">
      <?php foreach ($features as [$ic, $t, $d]): ?>
        <div class="col-md-6 col-xl-4 rv">
          <div class="m-card">
            <div class="m-ico"><i class="fa-solid <?= $ic ?>"></i></div>
            <h3><?= e($t) ?></h3>
            <p><?= e($d) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ CTA ══ -->
<section class="sec pt-0">
  <div class="container">
    <div class="cta-band rv">
      <div class="row align-items-center g-4 position-relative">
        <div class="col-lg-8">
          <h2 class="fw-bold mb-2" style="font-size:1.6rem">Ready to digitize your university?</h2>
          <p class="mb-0" style="color:#c2d4ee">Book a free live demonstration — we'll walk you through every module on a real system.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
            <a href="<?= e($site) ?>/demonstration.php#book-demo" class="btn btn-grad"><i class="fa-solid fa-calendar-check me-2"></i>Book Live Demo</a>
            <a href="<?= UMS_URL ?>/admin/login.php" class="btn btn-ghost">Admin Login</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ FOOTER ══ -->
<footer class="ums-foot text-center py-4">
  <?= e(UMS_NAME) ?> v<?= e(UMS_VERSION) ?> — a product of
  <a href="<?= e($site) ?>/index.php"><?= e($brand['name']) ?> (Private) Limited</a> · &copy; <?= date('Y') ?> All rights reserved
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  /* scroll-reveal */
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
    });
  }, { threshold: .12 });
  document.querySelectorAll('.rv').forEach(function (el, i) {
    el.style.transitionDelay = (i % 4) * 80 + 'ms';
    io.observe(el);
  });

  /* animated counters */
  var cio = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      var el = en.target, target = parseInt(el.dataset.count, 10) || 0, start = null;
      function tick(ts) {
        if (!start) start = ts;
        var p = Math.min((ts - start) / 1400, 1);
        el.textContent = Math.floor((1 - Math.pow(1 - p, 3)) * target);
        if (p < 1) requestAnimationFrame(tick); else el.textContent = target;
      }
      requestAnimationFrame(tick);
      cio.unobserve(el);
    });
  }, { threshold: .6 });
  document.querySelectorAll('.cnt').forEach(function (c) { cio.observe(c); });
});
</script>

</body>
</html>
