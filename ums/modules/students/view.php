<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$s  = stu_find($id);
if (!$s) { flash_set('error', 'Student not found.'); redirect(stu_url('index.php')); }

$deptName = '';
if ((int)$s['department_id'] > 0) { $deptName = dept_options((int)$user['campus_id'])[(int)$s['department_id']] ?? ''; }

$page_title = $s['name']; $active = 'students';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1><?= e($s['name']) ?> <?= status_badge($s['status'], STU_STATUS) ?></h1>
    <p><?= e($s['registration_no']) ?> · <?= e($s['program'] ?: '—') ?> · Semester <?= (int)$s['current_semester'] ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= stu_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <a href="<?= stu_url('edit.php?id='.$id) ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
  </div>
</div>

<div class="u-grid g-main">
  <div>
    <div class="u-card" style="margin-bottom:1.1rem">
      <div class="u-card-head"><h2><i class="fa-solid fa-user" style="color:var(--primary)"></i> Personal</h2></div>
      <div class="u-detail">
        <div class="row-x"><span class="k">Full Name</span><span class="v"><?= e($s['name']) ?></span></div>
        <div class="row-x"><span class="k">Father / Guardian</span><span class="v"><?= e($s['father_name'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Gender</span><span class="v"><?= e(ucfirst($s['gender'])) ?></span></div>
        <div class="row-x"><span class="k">Date of Birth</span><span class="v"><?= $s['dob'] ? e(date('d M Y', strtotime($s['dob']))) : '—' ?></span></div>
        <div class="row-x"><span class="k">CNIC / B-Form</span><span class="v"><?= e($s['cnic'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Phone</span><span class="v"><?= e($s['phone'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Email</span><span class="v"><?= e($s['email'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Address</span><span class="v"><?= e($s['address'] ?: '—') ?></span></div>
      </div>
    </div>
    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-graduation-cap" style="color:var(--primary)"></i> Academic</h2></div>
      <div class="u-detail">
        <div class="row-x"><span class="k">Registration No.</span><span class="v"><?= e($s['registration_no']) ?></span></div>
        <div class="row-x"><span class="k">Program</span><span class="v"><?= e($s['program'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Department</span><span class="v"><?= e($deptName ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Session</span><span class="v"><?= e($s['session'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Batch</span><span class="v"><?= e($s['batch'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Current Semester</span><span class="v"><?= (int)$s['current_semester'] ?></span></div>
        <?php if ((int)$s['admission_id'] > 0): ?>
          <div class="row-x"><span class="k">Admission</span><span class="v"><a href="<?= UMS_URL ?>/modules/admissions/view.php?id=<?= (int)$s['admission_id'] ?>" style="color:var(--primary)">View application</a></span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="u-card" style="text-align:center;height:fit-content">
    <?php if ($s['photo'] !== ''): ?>
      <img src="<?= UMS_URL.'/'.e($s['photo']) ?>" alt="Photo" style="width:130px;height:130px;object-fit:cover;border-radius:16px;border:1px solid var(--line)">
    <?php else: ?>
      <div style="width:130px;height:130px;border-radius:16px;margin:0 auto;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:2.4rem;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif"><?= e(ini2($s['name'])) ?></div>
    <?php endif; ?>
    <h3 style="margin:.8rem 0 .1rem;font-weight:800"><?= e($s['name']) ?></h3>
    <p style="color:var(--muted);font-size:.82rem;margin:0"><?= e($s['registration_no']) ?></p>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
