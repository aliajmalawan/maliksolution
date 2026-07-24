<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if (($_GET['switch'] ?? '') === '1') { ums_logout(); redirect(UMS_URL . '/admin/login.php'); }

// Already signed in as staff (or via remember-me) → straight to the dashboard
$switchUser = null;
if ($u = ums_user()) {
    if (in_array($u['role'], UMS_ADMIN_ROLES, true)) { redirect(ums_role_home($u['role'])); }
    $switchUser = $u; // signed in as teacher/student — show a notice instead of silently bouncing away
}

$site = dirname(UMS_URL); // company website root

// Load company branding from the website CMS (read-only, graceful fallback)
$brand = ['name' => 'Malik Solution', 'logo_white' => ''];
try {
    $res = ums_db()->query("SELECT name, value FROM settings WHERE name IN ('site_name','logo_white_path')");
    while ($row = $res->fetch_assoc()) {
        if ($row['name'] === 'site_name' && $row['value'] !== '')       $brand['name'] = $row['value'];
        if ($row['name'] === 'logo_white_path' && $row['value'] !== '') $brand['logo_white'] = $row['value'];
    }
} catch (Throwable $t) { /* fallbacks are fine */ }
$logo = $brand['logo_white'] !== '' ? $site . '/' . $brand['logo_white'] : '';

$error = '';
$notice = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'login');

    if (!csrf_check()) {
        $error = 'Your session expired — please try again.';
    } elseif ($action === 'forgot') {
        // Forgot password — always generic response (no user enumeration)
        $email = trim((string)($_POST['reset_email'] ?? ''));
        $link  = $email !== '' ? ums_password_reset_request($email) : null;
        $notice = 'If an account exists for that email, a password reset link has been sent.';
        if ($link) { $resetLink = $link; } // local dev only — lets you test without email
    } else {
        // Standard login — role-gated to staff/admin roles only
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);
        $error    = ums_attempt_login($email, $password, UMS_ADMIN_ROLES, $remember) ?? '';
        if ($error === '') {
            redirect(ums_role_home(ums_user()['role']));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login · <?= e($brand['name']) ?> UMS</title>
  <meta name="robots" content="noindex">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy: #071f40; --navy-2: #0d3a75; --blue: #0d6efd; --cyan: #22d3ee;
      --ink: #172033; --muted: #64748b; --soft: #f4f7fb; --line: #e3eaf3;
      --grad: linear-gradient(135deg, #0d6efd 0%, #22d3ee 100%);
      --grad-dark: linear-gradient(135deg, #071f40 0%, #0d3a75 60%, #0d6efd 140%);
    }
    body { font-family: 'Inter', 'Segoe UI', sans-serif; color: var(--ink); background: var(--soft); }
    h1, h2, h3, h4, .brand-name { font-family: 'Poppins', sans-serif; }
    .login-wrap { min-height: 100vh; }

    /* ── Left brand panel ── */
    .brand-panel {
      background: var(--grad-dark); color: #fff; position: relative; overflow: hidden;
      padding: 3rem; display: flex; flex-direction: column;
    }
    .brand-panel::before {
      content: ""; position: absolute; inset: 0; pointer-events: none;
      background-image: linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
      background-size: 42px 42px;
      -webkit-mask-image: radial-gradient(ellipse 80% 70% at 30% 20%, #000 45%, transparent 100%);
      mask-image: radial-gradient(ellipse 80% 70% at 30% 20%, #000 45%, transparent 100%);
    }
    .blob { position: absolute; border-radius: 50%; filter: blur(12px); pointer-events: none; }
    .blob.b1 { width: 420px; height: 420px; top: -160px; right: -120px; background: radial-gradient(circle, rgba(34,211,238,.32), transparent 68%); animation: drift 12s ease-in-out infinite alternate; }
    .blob.b2 { width: 360px; height: 360px; bottom: -140px; left: -100px; background: radial-gradient(circle, rgba(13,110,253,.4), transparent 68%); animation: drift 14s ease-in-out 1s infinite alternate-reverse; }
    @keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(-26px,20px) scale(1.08); } }
    .brand-panel > * { position: relative; z-index: 1; }
    .brand-logo { height: 42px; width: auto; max-width: 200px; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,0,0,.4)); }
    .brand-mark { display: inline-flex; align-items: center; gap: .6rem; color: #fff; font-weight: 800; font-size: 1.15rem; }
    .brand-mark .ico { width: 42px; height: 42px; border-radius: 12px; background: var(--grad); display: grid; place-items: center; box-shadow: 0 8px 20px rgba(13,110,253,.5); }
    .brand-hero h2 { font-size: 2rem; font-weight: 800; line-height: 1.2; }
    .brand-hero .gr { background: linear-gradient(90deg, #22d3ee, #7dd3fc); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .brand-feat { display: flex; gap: .85rem; margin-top: 1.1rem; }
    .brand-feat i { width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0; background: rgba(34,211,238,.16); color: #7dd3fc; display: grid; place-items: center; font-size: .9rem; }
    .brand-feat strong { display: block; color: #fff; font-size: .92rem; }
    .brand-feat span { color: #a8bdda; font-size: .8rem; }

    /* ── Right form panel ── */
    .form-panel { display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.5rem; }
    .form-card { width: min(410px, 100%); }
    .form-card h1 { font-size: 1.5rem; font-weight: 800; color: var(--navy); }
    .form-label { font-weight: 600; font-size: .85rem; color: var(--navy); }
    .input-group-text { background: #fff; border-right: 0; color: var(--muted); }
    .form-control { border-left: 0; }
    .input-group .form-control { border-color: var(--line); }
    .input-group:focus-within .input-group-text,
    .input-group:focus-within .form-control { border-color: var(--blue); box-shadow: none; }
    .form-control:focus { box-shadow: none; }
    .input-group.big > * { height: 48px; }
    .btn-grad {
      background: var(--grad); color: #fff; font-weight: 700; border: none; border-radius: 11px; height: 48px;
      box-shadow: 0 8px 22px rgba(13,110,253,.35); transition: transform .16s, box-shadow .16s;
    }
    .btn-grad:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 12px 28px rgba(34,211,238,.45); }
    .toggle-pass { cursor: pointer; border: 1px solid var(--line); border-left: 0; background: #fff; color: var(--muted); border-radius: 0 8px 8px 0; }
    .link-blue { color: var(--blue); font-weight: 700; font-size: .82rem; text-decoration: none; }
    .link-blue:hover { text-decoration: underline; }
    .secure-note { color: var(--muted); font-size: .76rem; }
    .secure-note i { color: #16a34a; }
    @media (max-width: 991px) { .brand-panel { display: none; } }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row login-wrap g-0">

    <!-- ── Brand panel ── -->
    <div class="col-lg-6 brand-panel">
      <a href="<?= UMS_URL ?>/index.php" class="brand-mark text-decoration-none">
        <?php if ($logo !== ''): ?>
          <img src="<?= e($logo) ?>" alt="<?= e($brand['name']) ?>" class="brand-logo">
        <?php else: ?>
          <span class="ico"><i class="fa-solid fa-graduation-cap"></i></span> <?= e($brand['name']) ?>
        <?php endif; ?>
        <span style="color:#7dd3fc;font-weight:600;font-size:.78rem;border-left:1px solid rgba(255,255,255,.25);padding-left:.6rem">University ERP</span>
      </a>

      <div class="brand-hero my-auto" style="max-width:430px">
        <h2>Run your entire campus from <span class="gr">one dashboard</span></h2>
        <p style="color:#a8bdda;margin:.6rem 0 1.4rem">
          Admissions, academics, attendance, examinations, fees, and payroll — built on the
          semester system with GPA/CGPA.
        </p>
        <div class="brand-feat">
          <i class="fa-solid fa-shield-halved"></i>
          <div><strong>Secure by design</strong><span>Encrypted passwords, audit log, role-based access</span></div>
        </div>
        <div class="brand-feat">
          <i class="fa-solid fa-chart-line"></i>
          <div><strong>Live analytics</strong><span>Attendance, fees, and enrollment at a glance</span></div>
        </div>
        <div class="brand-feat">
          <i class="fa-solid fa-building-columns"></i>
          <div><strong>Multi-campus ready</strong><span>One system, every campus, one report</span></div>
        </div>
      </div>

      <div style="color:#5b7397;font-size:.76rem">&copy; <?= date('Y') ?> <?= e($brand['name']) ?> (Private) Limited</div>
    </div>

    <!-- ── Form panel ── -->
    <div class="col-lg-6 form-panel">
      <div class="form-card">
        <div class="d-lg-none brand-mark mb-4" style="color:var(--navy)">
          <span class="ico"><i class="fa-solid fa-graduation-cap"></i></span>
          <span class="brand-name"><?= e($brand['name']) ?> <span style="color:var(--muted);font-weight:600;font-size:.8rem">UMS</span></span>
        </div>

        <h1>Admin Sign In</h1>
        <p class="text-secondary mb-4" style="font-size:.88rem">Welcome back — access the ERP control panel.</p>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 py-2"><i class="fa-solid fa-circle-exclamation"></i><span style="font-size:.86rem"><?= e($error) ?></span></div>
        <?php elseif ($notice !== ''): ?>
          <div class="alert alert-success d-flex align-items-center gap-2 py-2"><i class="fa-solid fa-circle-check"></i><span style="font-size:.86rem"><?= e($notice) ?></span></div>
          <?php if ($resetLink !== ''): ?>
            <div class="alert alert-info py-2" style="font-size:.8rem">
              <strong>Local dev:</strong> reset link (would be emailed in production):<br>
              <a href="<?= e($resetLink) ?>" class="link-blue" style="word-break:break-all"><?= e($resetLink) ?></a>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($switchUser): ?>
          <div class="alert alert-info d-flex align-items-center gap-2 py-2" style="font-size:.86rem">
            <i class="fa-solid fa-circle-info"></i>
            <span>You're currently signed in as <strong><?= e($switchUser['name']) ?></strong> (<?= e(ucfirst($switchUser['role'])) ?>).
            <a href="?switch=1" style="font-weight:700">Log out</a> to sign in as staff instead.</span>
          </div>
        <?php endif; ?>

        <form method="post" autocomplete="on">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">

          <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <div class="input-group big">
              <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
              <input type="email" class="form-control" id="email" name="email" required autofocus
                     placeholder="you@institution.edu" value="<?= e((string)($_POST['email'] ?? '')) ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <div class="input-group big">
              <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
              <span class="input-group-text toggle-pass" id="togglePass" role="button" aria-label="Show password"><i class="fa-regular fa-eye"></i></span>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
              <label class="form-check-label" for="remember" style="font-size:.85rem">Remember me</label>
            </div>
            <a href="#" class="link-blue" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot password?</a>
          </div>

          <button type="submit" class="btn btn-grad w-100">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
          </button>
        </form>

        <div class="text-center mt-4">
          <p class="secure-note mb-1"><i class="fa-solid fa-shield-halved"></i> Protected area — all sign-ins are logged.</p>
          <a href="<?= UMS_URL ?>/index.php" class="link-blue">&larr; Back to UMS Home</a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ── Forgot password modal ── -->
<div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border:none;border-radius:16px">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="forgot">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" style="color:var(--navy)"><i class="fa-solid fa-key me-2 text-primary"></i>Reset Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-secondary" style="font-size:.86rem">Enter your account email. If it exists, we'll send a secure reset link that expires in 1 hour.</p>
          <label class="form-label" for="reset_email">Email Address</label>
          <div class="input-group big">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" class="form-control" id="reset_email" name="reset_email" required placeholder="you@institution.edu">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-grad px-4" style="height:auto;padding:.55rem 1.4rem"><i class="fa-solid fa-paper-plane me-1"></i>Send Reset Link</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Password show/hide
  var tp = document.getElementById('togglePass'), pw = document.getElementById('password');
  tp && tp.addEventListener('click', function () {
    var show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    tp.querySelector('i').className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
  });
  // If the reset modal was just used, re-open it isn't needed; focus handled by Bootstrap.
</script>

</body>
</html>
