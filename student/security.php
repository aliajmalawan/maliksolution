<?php
declare(strict_types=1);
session_start();
require_once '../includes/config.php';

if (empty($_SESSION['student_id'])) { header('Location: login.php'); exit; }

$sid = (int)$_SESSION['student_id'];
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT student_name, student_id, program, student_photo FROM admissions WHERE id=$sid AND status='approved'"));
if (!$row) { session_destroy(); header('Location: login.php?msg=session_expired'); exit; }

$name          = htmlspecialchars($row['student_name']);
$student_id    = htmlspecialchars($row['student_id']);
$program       = htmlspecialchars($row['program']);
$first_name    = htmlspecialchars(explode(' ', $row['student_name'])[0]);
$student_photo = $row['student_photo'] ?? '';
$pw_msg        = $_GET['pw'] ?? '';

$page_title = 'Change Password';
$active_nav = 'security';
include '_inc_head.php';

$pw_alerts = [
  'success'  => ['success', 'fa-circle-check',         'Password changed successfully.'],
  'wrong'    => ['danger',  'fa-triangle-exclamation', 'Current password is incorrect.'],
  'mismatch' => ['danger',  'fa-triangle-exclamation', 'New passwords do not match.'],
  'short'    => ['danger',  'fa-triangle-exclamation', 'Password must be at least 6 characters.'],
  'empty'    => ['warning', 'fa-triangle-exclamation', 'Please fill in all fields.'],
  'same'     => ['warning', 'fa-triangle-exclamation', 'New password must be different from current.'],
];
?>

<div class="page-hdr">
  <div>
    <h1><i class="fa-solid fa-key me-2 text-primary"></i>Change Password</h1>
    <p>Update your Student Portal login password</p>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">

    <?php if (isset($pw_alerts[$pw_msg])): [$ac,$ai,$at] = $pw_alerts[$pw_msg]; ?>
    <div class="alert alert-<?= $ac ?> d-flex align-items-center gap-2 mb-4" style="font-size:.85rem;border-radius:10px">
      <i class="fa-solid <?= $ai ?>"></i><?= $at ?>
    </div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-head">
        <div class="panel-head-title"><i class="fa-solid fa-shield-halved"></i> Security Settings</div>
      </div>
      <div class="panel-body">
        <form method="POST" action="change-password.php">
          <div class="mb-3">
            <label class="form-label" style="font-size:.73rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Current Password</label>
            <div class="pw-group">
              <input type="password" class="form-control" name="current_password" id="cp_cur" placeholder="Enter current password" required>
              <button type="button" class="pw-eye" data-target="cp_cur"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-size:.73rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">New Password</label>
            <div class="pw-group">
              <input type="password" class="form-control" name="new_password" id="cp_new" placeholder="Min. 6 characters" required minlength="6">
              <button type="button" class="pw-eye" data-target="cp_new"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label" style="font-size:.73rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Confirm New Password</label>
            <div class="pw-group">
              <input type="password" class="form-control" name="confirm_password" id="cp_con" placeholder="Repeat new password" required>
              <button type="button" class="pw-eye" data-target="cp_con"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <button type="submit" class="btn btn-primary px-4 fw-bold">
              <i class="fa-solid fa-key me-1"></i>Update Password
            </button>
            <span id="pw-match-msg" style="font-size:.76rem;font-weight:700"></span>
          </div>
        </form>
      </div>
    </div>

    <div class="mt-1">
      <a href="portal.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard
      </a>
    </div>

  </div>
</div>

<?php
$extra_js = <<<JS
document.querySelectorAll('.pw-eye').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var inp  = document.getElementById(btn.dataset.target);
    var icon = btn.querySelector('i');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
  });
});
var cpNew = document.getElementById('cp_new');
var cpCon = document.getElementById('cp_con');
var pwMsg = document.getElementById('pw-match-msg');
function checkMatch() {
  if (!cpCon.value) { pwMsg.textContent = ''; return; }
  if (cpNew.value === cpCon.value) { pwMsg.textContent = '✓ Passwords match'; pwMsg.style.color = '#16a34a'; }
  else { pwMsg.textContent = '✗ Passwords do not match'; pwMsg.style.color = '#dc2626'; }
}
cpNew.addEventListener('input', checkMatch);
cpCon.addEventListener('input', checkMatch);
JS;
include '_inc_foot.php';
?>
