<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$t  = tch_find($id);
if (!$t) { flash_set('error', 'Teacher not found.'); redirect(tch_url('index.php')); }

$deptName = '';
if ((int)$t['department_id'] > 0) { $deptName = dept_options((int)$user['campus_id'])[(int)$t['department_id']] ?? ''; }

$page_title = $t['name']; $active = 'teachers';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1><?= e($t['name']) ?> <?= status_badge($t['status'], TCH_STATUS) ?></h1>
    <p><?= e($t['employee_no']) ?> · <?= e($t['designation']) ?><?= $deptName ? ' · ' . e($deptName) : '' ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= tch_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <a href="<?= tch_url('edit.php?id='.$id) ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
  </div>
</div>

<div class="u-grid g-main">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-address-card" style="color:var(--primary)"></i> Details</h2></div>
    <div class="u-detail">
      <div class="row-x"><span class="k">Full Name</span><span class="v"><?= e($t['name']) ?></span></div>
      <div class="row-x"><span class="k">Gender</span><span class="v"><?= e(ucfirst($t['gender'])) ?></span></div>
      <div class="row-x"><span class="k">Date of Birth</span><span class="v"><?= $t['dob'] ? e(date('d M Y', strtotime($t['dob']))) : '—' ?></span></div>
      <div class="row-x"><span class="k">CNIC</span><span class="v"><?= e($t['cnic'] ?: '—') ?></span></div>
      <div class="row-x"><span class="k">Phone</span><span class="v"><?= e($t['phone'] ?: '—') ?></span></div>
      <div class="row-x"><span class="k">Email</span><span class="v"><?= e($t['email'] ?: '—') ?></span></div>
      <div class="row-x"><span class="k">Department</span><span class="v"><?= e($deptName ?: '—') ?></span></div>
      <div class="row-x"><span class="k">Designation</span><span class="v"><?= e($t['designation']) ?></span></div>
      <div class="row-x"><span class="k">Qualification</span><span class="v"><?= e($t['qualification'] ?: '—') ?></span></div>
      <div class="row-x"><span class="k">Joining Date</span><span class="v"><?= $t['joining_date'] ? e(date('d M Y', strtotime($t['joining_date']))) : '—' ?></span></div>
      <div class="row-x"><span class="k">Basic Salary</span><span class="v"><?= (float)$t['salary'] > 0 ? 'Rs ' . number_format((float)$t['salary']) : '—' ?></span></div>
      <div class="row-x"><span class="k">Address</span><span class="v"><?= e($t['address'] ?: '—') ?></span></div>
    </div>
  </div>
  <div class="u-card" style="text-align:center;height:fit-content">
    <?php if ($t['photo'] !== ''): ?>
      <img src="<?= UMS_URL.'/'.e($t['photo']) ?>" alt="Photo" style="width:130px;height:130px;object-fit:cover;border-radius:16px;border:1px solid var(--line)">
    <?php else: ?>
      <div style="width:130px;height:130px;border-radius:16px;margin:0 auto;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:2.4rem;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif"><?= e(ini2($t['name'])) ?></div>
    <?php endif; ?>
    <h3 style="margin:.8rem 0 .1rem;font-weight:800"><?= e($t['name']) ?></h3>
    <p style="color:var(--muted);font-size:.82rem;margin:0"><?= e($t['designation']) ?></p>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
