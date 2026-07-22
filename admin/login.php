<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

// Already signed in → straight to the dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: ../AdminCP/dashboard.php');
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = mysqli_prepare($conn, "SELECT id, email, password FROM admins WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $valid = false;
    if ($admin) {
        if (password_verify($password, $admin['password'])) {
            $valid = true;
        } elseif (hash_equals($admin['password'], $password)) {
            // Legacy plaintext password — accept once and upgrade to a hash
            $valid = true;
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $up = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($up, 'si', $hash, $admin['id']);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);
        }
    }

    if ($valid) {
        session_regenerate_id(true);
        $_SESSION['admin_id']    = (int)$admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        log_activity($conn, 'login', 'Signed in to AdminCP');
        header('Location: ../AdminCP/dashboard.php');
        exit;
    }

    $_SESSION['admin_email'] = $email;
    log_activity($conn, 'login_failed', 'Failed AdminCP login attempt');
    unset($_SESSION['admin_email']);
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login — Malik Solution</title>
  <?php $favicon = get_setting($conn, 'favicon_path', ''); if ($favicon !== ''): ?>
  <link rel="icon" href="../<?= htmlspecialchars($favicon) ?>">
  <?php endif; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <style>
    body {
      min-height: 100vh; margin: 0;
      display: grid; place-items: center;
      font-family: 'Segoe UI', Arial, sans-serif;
      background: linear-gradient(135deg, #071f40 0%, #0d3a75 100%);
    }
    .login-card {
      width: min(400px, 92vw);
      background: #fff; border-radius: 14px;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      padding: 2.25rem 2rem;
    }
    .login-brand { text-align: center; margin-bottom: 1.5rem; }
    .login-brand img {
      width: 64px; height: 64px; object-fit: contain;
      border-radius: 12px; background: #f4f7fb; padding: 6px;
    }
    .login-brand h1 { font-size: 1.15rem; font-weight: 800; color: #071f40; margin: .75rem 0 0; }
    .login-brand p { color: #6c757d; font-size: .8rem; margin: .15rem 0 0; }
    .form-label { font-weight: 600; font-size: .85rem; }
    .btn-navy { background: #071f40; color: #fff; font-weight: 700; }
    .btn-navy:hover { background: #0d3a75; color: #fff; }
    .login-foot { text-align: center; margin-top: 1.25rem; font-size: .75rem; color: #8898b3; }
  </style>
</head>
<body>

<div class="login-card">
  <div class="login-brand">
    <img src="../<?= htmlspecialchars(get_setting($conn, 'logo_path', 'maliksolution.png')) ?>" alt="Malik Solution">
    <h1>Malik Solution AdminCP</h1>
    <p>Sign in to manage the website</p>
  </div>

  <?php if ($error !== ''): ?>
    <div class="alert alert-danger py-2 small">
      <i class="fa-solid fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label" for="email"><i class="fa-solid fa-envelope me-1 text-primary"></i>Email</label>
      <input type="email" class="form-control" id="email" name="email" required autofocus
             value="<?= htmlspecialchars((string)($_POST['email'] ?? '')) ?>" placeholder="admin@example.com">
    </div>
    <div class="mb-4">
      <label class="form-label" for="password"><i class="fa-solid fa-lock me-1 text-primary"></i>Password</label>
      <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
    </div>
    <button type="submit" name="login" value="1" class="btn btn-navy w-100">
      <i class="fa-solid fa-right-to-bracket me-1"></i>Sign In
    </button>
  </form>

  <div class="login-foot">&copy; <?= date('Y') ?> Malik Solution — Admin Control Panel</div>
</div>

</body>
</html>
