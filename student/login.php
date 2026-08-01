<?php
declare(strict_types=1);
session_start();
require_once '../includes/config.php';

ensure_column($conn, 'admissions', 'username',         "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'admissions', 'student_password', "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'admissions', 'student_id',       "VARCHAR(30) DEFAULT ''");

if (!empty($_SESSION['student_id'])) {
    header('Location: portal.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login_id === '' || $password === '') {
        $error = 'Please enter your Student ID and password.';
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id, student_name, program, student_id, username
             FROM admissions
             WHERE student_id = ?
               AND student_password = ?
               AND status = 'approved'
             LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $login_id, $password);
        mysqli_stmt_execute($stmt);
        $res     = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($student) {
            session_regenerate_id(true);
            $_SESSION['student_id']   = $student['id'];
            $_SESSION['student_name'] = $student['student_name'];
            $_SESSION['student_user'] = $student['username'];
            $_SESSION['student_sid']  = $student['student_id'];
            $_SESSION['student_prog'] = $student['program'];
            header('Location: portal.php');
            exit;
        } else {
            $error = 'Invalid credentials or account not approved. Please contact the admissions office.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Login — Malik Solution</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    :root { --navy:#071f40; --gold:#f6b221; --soft:#f4f7fb; }

    body {
      margin: 0; font-family: 'Poppins', sans-serif;
      min-height: 100vh; display: flex; background: var(--soft);
    }

    /* ══════════════════════════════
       LEFT BRANDING PANEL (desktop)
    ══════════════════════════════ */
    .login-brand {
      width: 420px; flex-shrink: 0;
      background: var(--navy);
      display: flex; flex-direction: column;
      justify-content: center; align-items: center;
      padding: 3rem 2.5rem;
      position: relative; overflow: hidden;
    }
    .login-brand::before {
      content: ''; position: absolute;
      width: 340px; height: 340px; border-radius: 50%;
      background: rgba(246,178,33,.08); top: -80px; right: -100px;
    }
    .login-brand::after {
      content: ''; position: absolute;
      width: 260px; height: 260px; border-radius: 50%;
      background: rgba(246,178,33,.06); bottom: -60px; left: -70px;
    }
    .brand-logo {
      width: 90px; height: 90px; border-radius: 50%;
      background: #fff; display: flex; align-items: center; justify-content: center;
      margin-bottom: 1.5rem; box-shadow: 0 8px 32px rgba(0,0,0,.25);
      position: relative; z-index: 1; overflow: hidden; padding: 8px;
    }
    .brand-logo img { width: 100%; height: 100%; object-fit: contain; }
    .brand-name {
      font-size: 1.35rem; font-weight: 800; color: #fff;
      text-align: center; line-height: 1.25;
      position: relative; z-index: 1; margin-bottom: .4rem;
    }
    .brand-tagline {
      font-size: .78rem; color: rgba(255,255,255,.55);
      text-transform: uppercase; letter-spacing: .12em;
      text-align: center; font-weight: 600;
      position: relative; z-index: 1; margin-bottom: 2.5rem;
    }
    .brand-divider {
      width: 48px; height: 3px; border-radius: 2px;
      background: var(--gold); margin: 0 auto 2.5rem;
      position: relative; z-index: 1;
    }
    .brand-feature {
      display: flex; align-items: flex-start; gap: .8rem;
      color: rgba(255,255,255,.75); font-size: .82rem;
      margin-bottom: 1rem; position: relative; z-index: 1;
    }
    .brand-feature i { color: var(--gold); font-size: .95rem; width: 20px; flex-shrink: 0; margin-top: .1rem; }
    .brand-copy {
      font-size: .7rem; color: rgba(255,255,255,.35);
      text-align: center; margin-top: auto; padding-top: 2rem;
      position: relative; z-index: 1;
    }

    /* The floating back-link above the card (mobile only) */
    #back-link-mobile { display: none; }

    /* ══════════════════════════════
       RIGHT FORM PANEL (desktop)
    ══════════════════════════════ */
    .login-form-wrap {
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 2rem 1.5rem;
    }
    .login-card { width: 100%; max-width: 440px; }
    .login-card-head { margin-bottom: 2rem; }
    .back-link {
      font-size: .78rem; color: #6c757d; font-weight: 600;
      text-decoration: none; display: inline-flex; align-items: center; gap: .35rem;
      margin-bottom: 1.5rem; transition: color .2s;
    }
    .back-link:hover { color: var(--navy); }
    .login-card-head h1 { font-size: 1.7rem; font-weight: 800; color: var(--navy); margin: 0 0 .3rem; line-height: 1.2; }
    .login-card-head p  { font-size: .85rem; color: #6c757d; margin: 0; }

    /* Mobile-only logo inside the card (hidden on desktop) */
    .mobile-brand {
      display: none;
      flex-direction: column; align-items: center;
      padding-bottom: 1.5rem; margin-bottom: 1.5rem;
      border-bottom: 1.5px solid #f0f4f9;
    }
    .mobile-brand img {
      width: 64px; height: 64px; border-radius: 50%;
      background: var(--navy); padding: 6px;
      box-shadow: 0 6px 20px rgba(7,31,64,.25);
      margin-bottom: .6rem;
    }
    .mobile-brand-name { font-size: 1.05rem; font-weight: 800; color: var(--navy); text-align: center; line-height: 1.2; }
    .mobile-brand-sub  { font-size: .62rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-top: .2rem; }

    /* ══════════════════════════════
       FORM ELEMENTS
    ══════════════════════════════ */
    .login-field { margin-bottom: 1.25rem; }
    .login-field label {
      font-size: .75rem; font-weight: 700; color: var(--navy);
      text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: .45rem;
    }
    .login-input-wrap { position: relative; }
    .login-input-wrap .field-icon {
      position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: .95rem; pointer-events: none;
    }
    .login-input-wrap input {
      width: 100%; padding: .82rem 1rem .82rem 2.8rem;
      font-size: .93rem; font-family: 'Poppins', sans-serif;
      border: 2px solid #dfe7f1; border-radius: 10px;
      background: #fff; color: var(--navy);
      transition: border-color .2s, box-shadow .2s; outline: none;
    }
    .login-input-wrap input:focus { border-color: var(--navy); box-shadow: 0 0 0 4px rgba(7,31,64,.08); }
    .toggle-pw {
      position: absolute; right: .85rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: #94a3b8;
      cursor: pointer; font-size: .9rem; padding: .35rem; transition: color .2s;
    }
    .toggle-pw:hover { color: var(--navy); }

    .btn-login {
      width: 100%; padding: .9rem; font-size: .97rem; font-weight: 700;
      background: var(--navy); color: #fff; border: none; border-radius: 10px;
      cursor: pointer; transition: background .2s, transform .15s;
      display: flex; align-items: center; justify-content: center; gap: .55rem;
      font-family: 'Poppins', sans-serif; margin-top: 1.5rem;
      letter-spacing: .02em;
    }
    .btn-login:hover  { background: #0d2d5c; transform: translateY(-1px); }
    .btn-login:active { transform: translateY(0); }

    .error-box {
      background: #fff0f0; border: 1.5px solid #f5c2c7; border-radius: 10px;
      padding: .85rem 1.1rem; font-size: .83rem; color: #842029;
      display: flex; align-items: flex-start; gap: .6rem; margin-bottom: 1.25rem;
      line-height: 1.5;
    }
    .error-box i { flex-shrink: 0; margin-top: .1rem; }

    .login-hint {
      margin-top: 1.5rem; padding: 1rem 1.1rem;
      background: #edf4ff; border-radius: 10px; border: 1.5px solid #c3d9ff;
      font-size: .79rem; color: #1a3a6b; line-height: 1.55;
    }
    .login-hint strong { font-weight: 700; display: block; margin-bottom: .2rem; }

    /* ══════════════════════════════
       RESPONSIVE — TABLET ≤ 900px
    ══════════════════════════════ */
    @media (max-width: 900px) {
      .login-brand { width: 340px; padding: 2rem 1.75rem; }
      .brand-name  { font-size: 1.15rem; }
    }

    /* ══════════════════════════════
       RESPONSIVE — MOBILE ≤ 768px
    ══════════════════════════════ */
    @media (max-width: 768px) {
      body {
        display: block;
        background: linear-gradient(150deg, #071f40 0%, #0c2a55 45%, #1a3a6b 100%);
        min-height: 100vh;
        padding: 1.5rem 1rem 2.5rem;
      }

      /* Hide the desktop left panel */
      .login-brand { display: none; }

      /* Form wrap: full width, no flex centering */
      .login-form-wrap {
        display: block;
        padding: 0;
        max-width: 440px;
        margin: 0 auto;
      }

      /* Card becomes a white floating card */
      .login-card {
        background: #fff;
        border-radius: 18px;
        padding: 1.75rem 1.5rem 2rem;
        box-shadow: 0 24px 64px rgba(0,0,0,.3);
        max-width: 100%;
      }

      /* Show the mobile logo */
      .mobile-brand { display: flex; }

      /* Adjust heading */
      .login-card-head { margin-bottom: 1.5rem; }
      .login-card-head h1 { font-size: 1.4rem; }

      /* Hide desktop back-link, show floating one above card */
      #back-link-desktop { display: none; }
      #back-link-mobile  {
        display: inline-flex;
        color: rgba(255,255,255,.6);
        margin-bottom: 1rem;
        font-size: .76rem;
      }
      #back-link-mobile:hover { color: #fff; }

      /* Input sizing for touch */
      .login-input-wrap input { padding: .9rem 1rem .9rem 2.85rem; font-size: .9rem; }
      .btn-login { padding: 1rem; font-size: .95rem; }
    }

    /* ══════════════════════════════
       RESPONSIVE — SMALL ≤ 400px
    ══════════════════════════════ */
    @media (max-width: 400px) {
      body { padding: 1rem .75rem 2rem; }
      .login-card { padding: 1.4rem 1.1rem 1.6rem; border-radius: 14px; }
      .mobile-brand img { width: 54px; height: 54px; }
      .mobile-brand-name { font-size: .95rem; }
      .login-card-head h1 { font-size: 1.2rem; }
    }
  </style>
</head>
<body>

<div class="login-brand">
  <div class="brand-logo">
    <img src="../assets/images/Logo.png" alt="Malik Solution Logo">
  </div>
  <div class="brand-name">Malik<br>Solution</div>
  <div class="brand-tagline">Education · Skills · Character</div>
  <div class="brand-divider"></div>
  <div class="brand-feature"><i class="fa-solid fa-graduation-cap"></i><span>Access your admission status and academic information</span></div>
  <div class="brand-feature"><i class="fa-solid fa-download"></i><span>Download date sheets, results, and study resources</span></div>
  <div class="brand-feature"><i class="fa-solid fa-shield-halved"></i><span>Secure portal — your data is protected</span></div>
  <div class="brand-feature"><i class="fa-solid fa-headset"></i><span>Reach out to admin for account support</span></div>
  <div class="brand-copy">&copy; <?= date('Y') ?> Malik Solution. All rights reserved.</div>
</div>

<div class="login-form-wrap">

  <!-- Back link sits above the card on mobile (inside form-wrap, outside card) -->
  <a href="../index.php" class="back-link" id="back-link-mobile">
    <i class="fa-solid fa-arrow-left"></i> Back to Website
  </a>

  <div class="login-card">

    <!-- Logo shown only on mobile (hidden on desktop via CSS) -->
    <div class="mobile-brand">
      <img src="../assets/images/Logo.png" alt="Malik Solution Logo">
      <div class="mobile-brand-name">Malik Solution</div>
      <div class="mobile-brand-sub">Student Portal</div>
    </div>

    <div class="login-card-head">
      <a href="../index.php" class="back-link" id="back-link-desktop"><i class="fa-solid fa-arrow-left"></i> Back to Website</a>
      <h1>Student Login</h1>
      <p>Sign in with your Student ID and the password provided by the admissions office.</p>
    </div>

    <?php if ($error): ?>
      <div class="error-box">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="login-field">
        <label for="login_id">Student ID</label>
        <div class="login-input-wrap">
          <i class="fa-solid fa-id-card field-icon"></i>
          <input type="text" id="login_id" name="login_id" autocomplete="username"
                 placeholder="e.g. MS-2026-0001"
                 value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>" required>
        </div>
      </div>
      <div class="login-field">
        <label for="password">Password</label>
        <div class="login-input-wrap">
          <i class="fa-solid fa-lock field-icon"></i>
          <input type="password" id="password" name="password"
                 autocomplete="current-password" placeholder="Enter your password" required>
          <button type="button" class="toggle-pw" aria-label="Show password">
            <i class="fa-solid fa-eye" id="pw-icon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn-login">
        <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
      </button>
    </form>

    <div class="login-hint">
      <strong><i class="fa-solid fa-circle-info me-1"></i>First Time Logging In?</strong>
      Your Student ID and password were given to you when your admission was approved.
      Contact the <a href="../contact.php" style="color:#0d6efd;font-weight:700">admissions office</a> if you need help.
    </div>

  </div>
</div>

<script>
document.querySelector('.toggle-pw').addEventListener('click', function () {
  var input = document.getElementById('password');
  var icon  = document.getElementById('pw-icon');
  if (input.type === 'password') { input.type = 'text'; icon.className = 'fa-solid fa-eye-slash'; }
  else                           { input.type = 'password'; icon.className = 'fa-solid fa-eye'; }
});
</script>
</body>
</html>
