<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'My Profile'; $active_nav = 'profile';
$deptName = '';
if ((int)$student['department_id'] > 0) { $deptName = dept_options((int)$authUser['campus_id'])[(int)$student['department_id']] ?? ''; }
$login = stu_login_find($studentId);
require __DIR__ . '/header.php';
?>

<?php if ($f = flash_get()): ?>
<div class="alert alert-<?= $f['type']==='success'?'success':'danger' ?> alert-dismissible fade show">
  <i class="fa-solid fa-circle-<?= $f['type']==='success'?'check':'exclamation' ?> me-2"></i><?= e($f['message']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-user-circle me-2 text-primary"></i>My Profile</h1><p>Your academic record and contact details.</p></div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="cardx text-center">
      <?php if ($student['photo'] !== ''): ?>
        <img src="<?= UMS_URL . '/' . e($student['photo']) ?>" style="width:110px;height:110px;border-radius:50%;object-fit:cover;object-position:center 15%;border:3px solid var(--line);margin-bottom:1rem">
      <?php else: ?>
        <div style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#e2eaf6,#c8d8ee);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;color:#8aa4cc;margin:0 auto 1rem;border:3px solid var(--line)"><?= e($initials) ?></div>
      <?php endif; ?>
      <h4 style="font-size:1.05rem;font-weight:800;color:var(--navy)"><?= e($student['name']) ?></h4>
      <p class="text-muted" style="font-size:.82rem"><?= e($student['program'] ?: '—') ?></p>
      <hr>
      <div class="text-start" style="font-size:.82rem">
        <div class="mb-2"><strong>Registration No:</strong> <code><?= e($student['registration_no']) ?></code></div>
        <div class="mb-2"><strong>Login Email:</strong> <?= e($login['email'] ?? '—') ?></div>
        <div class="mb-2"><strong>Department:</strong> <?= e($deptName ?: '—') ?></div>
        <div class="mb-2"><strong>Session:</strong> <?= e($student['session'] ?: '—') ?></div>
        <div class="mb-2"><strong>Batch:</strong> <?= e($student['batch'] ?: '—') ?></div>
        <div><strong>Current Semester:</strong> <?= (int)$student['current_semester'] ?></div>
      </div>
      <div class="mt-3">
        <a href="change_password.php" class="btn btn-outline-primary btn-sm w-100"><i class="fa-solid fa-key me-1"></i>Change Password</a>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="cardx">
      <div class="sec-hd"><h2><i class="fa-solid fa-pen me-2 text-primary"></i>Contact Details</h2></div>
      <p class="text-muted" style="font-size:.8rem;margin:-.4rem 0 1rem">Program, department and semester are managed by administration. You can update your contact details below.</p>
      <form method="POST" action="action.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile_save">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($student['email']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= e($student['phone']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-bold">Address</label>
            <input type="text" name="address" class="form-control" value="<?= e($student['address']) ?>">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
