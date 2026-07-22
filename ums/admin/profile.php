<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_login(); // any signed-in user may view their own profile
require_once __DIR__ . '/../includes/crud.php';

/**
 * My Profile — the signed-in user views their account, edits name/email,
 * and changes their password. Self-posting with a POST→redirect→GET flow.
 * All writes use prepared statements + CSRF; email uniqueness is enforced.
 */

$db  = ums_db();
$uid = (int)ums_user()['id'];

/* Load the full account row. */
function profile_row(mysqli $db, int $uid): ?array
{
    $stmt = $db->prepare('SELECT * FROM ' . tbl('users') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $uid); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash_set('error', 'Invalid request. Please try again.'); redirect(UMS_URL . '/admin/profile.php'); }
    $form = (string)($_POST['form'] ?? '');

    if ($form === 'profile') {
        $name  = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        if ($name === '' || $email === '') {
            flash_set('error', 'Name and email are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Please enter a valid email address.');
        } else {
            // email must be unique across other accounts
            $chk = $db->prepare('SELECT id FROM ' . tbl('users') . ' WHERE email = ? AND id <> ? LIMIT 1');
            $chk->bind_param('si', $email, $uid); $chk->execute();
            $taken = $chk->get_result()->fetch_assoc(); $chk->close();
            if ($taken) {
                flash_set('error', 'That email is already in use by another account.');
            } else {
                $up = $db->prepare('UPDATE ' . tbl('users') . ' SET name = ?, email = ? WHERE id = ?');
                $up->bind_param('ssi', $name, $email, $uid); $up->execute(); $up->close();
                // keep the live session in sync
                $_SESSION['ums_user']['name']  = $name;
                $_SESSION['ums_user']['email'] = $email;
                ums_log('profile_update', 'Updated own profile');
                flash_set('success', 'Profile updated.');
            }
        }
        redirect(UMS_URL . '/admin/profile.php');
    }

    if ($form === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $row = profile_row($db, $uid);
        if (!$row || !password_verify($current, $row['password_hash'])) {
            flash_set('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash_set('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            flash_set('error', 'The new passwords do not match.');
        } elseif (password_verify($new, $row['password_hash'])) {
            flash_set('error', 'New password must be different from the current one.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $up = $db->prepare('UPDATE ' . tbl('users') . ' SET password_hash = ? WHERE id = ?');
            $up->bind_param('si', $hash, $uid); $up->execute(); $up->close();
            ums_log('password_change', 'Changed own password');
            flash_set('success', 'Password changed successfully.');
        }
        redirect(UMS_URL . '/admin/profile.php');
    }

    redirect(UMS_URL . '/admin/profile.php');
}

$me = profile_row($db, $uid);
if (!$me) { ums_logout(); redirect(UMS_URL . '/admin/login.php'); }

// Campus name (best effort)
$campusName = '';
try {
    $cs = $db->prepare('SELECT name FROM ' . tbl('campuses') . ' WHERE id = ? LIMIT 1');
    $cs->bind_param('i', $me['campus_id']); $cs->execute();
    $campusName = (string)($cs->get_result()->fetch_assoc()['name'] ?? ''); $cs->close();
} catch (Throwable $t) {}

$roleLabel = ucwords(str_replace('_', ' ', $me['role']));
$initials  = strtoupper(mb_substr($me['name'], 0, 1));
$np = preg_split('/\s+/', trim($me['name']));
if (count($np) > 1) $initials = strtoupper(mb_substr($np[0], 0, 1) . mb_substr($np[count($np) - 1], 0, 1));

$page_title = 'My Profile'; $active = '';
require __DIR__ . '/../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>My Profile</h1><p>Manage your account details and password</p></div>
  <div><a href="<?= UMS_URL ?>/admin/dashboard.php" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Dashboard</a></div>
</div>

<div class="u-grid g-two" style="align-items:start">
  <!-- Identity card -->
  <div class="u-card" style="text-align:center">
    <div class="pf-avatar"><?= e($initials) ?></div>
    <h2 style="margin:.2rem 0 .1rem"><?= e($me['name']) ?></h2>
    <p style="color:var(--muted);margin:0 0 .9rem"><?= e($me['email']) ?></p>
    <div style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;margin-bottom:1rem">
      <span class="st" style="background:rgba(99,102,241,.1);color:var(--primary)"><i class="fa-solid fa-user-shield"></i> <?= e($roleLabel) ?></span>
      <?= active_badge($me['status']) ?>
    </div>
    <div class="pf-meta">
      <div><span><i class="fa-solid fa-building-columns"></i> Campus</span><strong><?= e($campusName ?: ('#' . (int)$me['campus_id'])) ?></strong></div>
      <div><span><i class="fa-solid fa-calendar-plus"></i> Member since</span><strong><?= e($me['created_at'] ? date('d M Y', strtotime($me['created_at'])) : '—') ?></strong></div>
      <div><span><i class="fa-solid fa-clock-rotate-left"></i> Last login</span><strong><?= e($me['last_login'] ? date('d M Y, g:i A', strtotime($me['last_login'])) : '—') ?></strong></div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:1.1rem">
    <!-- Edit details -->
    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-id-card" style="color:var(--primary)"></i> Account Details</h2></div>
      <form method="post" action="<?= UMS_URL ?>/admin/profile.php" class="u-form-grid">
        <?= csrf_field() ?><input type="hidden" name="form" value="profile">
        <div class="u-fld col-full"><label>Full Name <span class="req">*</span></label>
          <input type="text" name="name" required value="<?= e($me['name']) ?>" placeholder="Your name"></div>
        <div class="u-fld col-full"><label>Email <span class="req">*</span></label>
          <input type="email" name="email" required value="<?= e($me['email']) ?>" placeholder="you@example.com"></div>
        <div class="u-fld col-full"><label>Role</label>
          <input type="text" value="<?= e($roleLabel) ?>" disabled title="Roles are managed by an administrator"></div>
        <div class="u-form-actions col-full">
          <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button></div>
      </form>
    </div>

    <!-- Change password -->
    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-lock" style="color:var(--primary)"></i> Change Password</h2></div>
      <form method="post" action="<?= UMS_URL ?>/admin/profile.php" class="u-form-grid" autocomplete="off">
        <?= csrf_field() ?><input type="hidden" name="form" value="password">
        <div class="u-fld col-full"><label>Current Password <span class="req">*</span></label>
          <input type="password" name="current_password" required autocomplete="current-password" placeholder="••••••••"></div>
        <div class="u-fld"><label>New Password <span class="req">*</span></label>
          <input type="password" name="new_password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters"></div>
        <div class="u-fld"><label>Confirm New Password <span class="req">*</span></label>
          <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password" placeholder="Re-enter new password"></div>
        <div class="u-form-actions col-full">
          <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-key"></i> Update Password</button></div>
      </form>
    </div>
  </div>
</div>

<style>
.pf-avatar { width: 84px; height: 84px; margin: .4rem auto 1rem; border-radius: 50%; background: var(--grad); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; box-shadow: 0 10px 24px rgba(99,102,241,.4); }
.pf-meta { text-align: left; border-top: 1px solid var(--line); padding-top: 1rem; display: flex; flex-direction: column; gap: .7rem; }
.pf-meta > div { display: flex; justify-content: space-between; align-items: center; gap: 1rem; font-size: .88rem; }
.pf-meta span { color: var(--muted); display: inline-flex; align-items: center; gap: .5rem; }
.pf-meta span i { color: var(--primary); width: 16px; text-align: center; }
</style>
<?php require __DIR__ . '/../includes/footer.php'; ?>
