<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if (($_GET['switch'] ?? '') === '1') { ums_logout(); redirect(UMS_URL . '/teacher/login.php'); }

$switchUser = null;
if ($u = ums_user()) {
    if ($u['role'] === 'teacher') { redirect(UMS_URL . '/teacher/portal.php'); }
    $switchUser = $u; // signed in as a different role — show a notice instead of silently bouncing away
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired — please try again.';
    } else {
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $error    = ums_attempt_login($email, $password, ['teacher']) ?? '';
        if ($error === '') { redirect(UMS_URL . '/teacher/portal.php'); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teacher Portal — <?= e(UMS_NAME) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#071f40 0%,#0b2b55 60%,#0d3265 100%);padding:1rem}
.login-card{background:#fff;border-radius:20px;padding:2.5rem 2rem;width:100%;max-width:420px;box-shadow:0 24px 64px rgba(0,0,0,.28)}
.login-logo{text-align:center;margin-bottom:1.75rem}
.login-logo-icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#071f40,#0d6efd);
  display:inline-flex;align-items:center;justify-content:center;font-size:1.6rem;color:#f6b221;margin-bottom:.85rem;box-shadow:0 8px 24px rgba(13,110,253,.3)}
.login-logo h1{font-size:1.4rem;font-weight:800;color:#071f40;margin:0}
.login-logo p{font-size:.8rem;color:#6c7a8d;margin:.2rem 0 0}
.form-label{font-size:.82rem;font-weight:700;color:#374151;margin-bottom:.3rem}
.form-control{border-color:#dfe7f1;border-radius:10px;padding:.65rem .9rem;font-size:.9rem}
.form-control:focus{border-color:#0d6efd;box-shadow:0 0 0 3px rgba(13,110,253,.12)}
.input-group-text{border-color:#dfe7f1;background:#f4f7fb;border-radius:10px 0 0 10px;color:#6c7a8d}
.btn-login{background:linear-gradient(135deg,#071f40,#0d6efd);border:none;color:#fff;width:100%;
  padding:.75rem;font-size:.95rem;font-weight:700;border-radius:10px;margin-top:1.25rem;transition:opacity .2s}
.btn-login:hover{opacity:.9;color:#fff}
.back-link{text-align:center;margin-top:1.25rem;font-size:.78rem;color:#6c7a8d}
.back-link a{color:#0d6efd;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">
    <div class="login-logo-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
    <h1>Teacher Portal</h1>
    <p><?= e(UMS_NAME) ?> — Faculty System</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.83rem;border-radius:10px">
    <i class="fa-solid fa-triangle-exclamation me-1"></i><?= e($error) ?>
  </div>
  <?php endif; ?>
  <?php if (($_GET['err'] ?? '') === 'account'): ?>
  <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.83rem;border-radius:10px">
    <i class="fa-solid fa-lock me-1"></i>Account inactive. Contact the administrator.
  </div>
  <?php endif; ?>

  <?php if ($switchUser): ?>
  <div class="alert alert-info py-2 px-3 mb-3" style="font-size:.83rem;border-radius:10px">
    <i class="fa-solid fa-circle-info me-1"></i>
    You're currently signed in as <strong><?= e($switchUser['name']) ?></strong> (<?= e(ucfirst(str_replace('_', ' ', $switchUser['role']))) ?>).
    <a href="?switch=1" style="font-weight:700">Log out</a> to sign in as a teacher instead.
  </div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label">Username / Email</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-user fa-sm"></i></span>
        <input type="email" name="email" class="form-control" placeholder="you@faculty.ums.local"
               value="<?= e((string)($_POST['email'] ?? '')) ?>" required autofocus style="border-radius:0 10px 10px 0">
      </div>
    </div>
    <div class="mb-1">
      <label class="form-label">Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-lock fa-sm"></i></span>
        <input type="password" name="password" id="passField" class="form-control" placeholder="Enter your password" required
               style="border-radius:0;border-right:none">
        <button type="button" class="input-group-text" onclick="togglePass()" style="border-radius:0 10px 10px 0;cursor:pointer;border-left:none" id="eyeBtn">
          <i class="fa-solid fa-eye fa-sm" id="eyeIcon"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn-login"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign In</button>
  </form>

  <div class="back-link">
    <a href="<?= UMS_URL ?>/index.php"><i class="fa-solid fa-arrow-left me-1"></i>Back to UMS Home</a>
  </div>
</div>
<script>
function togglePass() {
  var f = document.getElementById('passField');
  var i = document.getElementById('eyeIcon');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'fa-solid fa-eye fa-sm' : 'fa-solid fa-eye-slash fa-sm';
}
</script>
</body>
</html>
