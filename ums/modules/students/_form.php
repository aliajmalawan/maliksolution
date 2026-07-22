<?php
declare(strict_types=1);
/** Shared student form — included by create.php / edit.php. Expects $s, $isEdit. */
$s = $s ?? [];
$v = fn(string $k, string $def = '') => e((string)($s[$k] ?? $def));
$depts = dept_options((int)$user['campus_id']);
?>
<form method="post" action="<?= stu_url('action.php') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><?php endif; ?>
  <input type="hidden" name="admission_id" value="<?= (int)($s['admission_id'] ?? 0) ?>">

  <?php if (!$isEdit && (int)($s['admission_id'] ?? 0) > 0): ?>
    <div class="u-flash info" style="margin-bottom:1.1rem"><i class="fa-solid fa-circle-info"></i>
      Enrolling from admission application — the form has been pre-filled. Review and complete the academic details below.</div>
  <?php endif; ?>

  <div class="u-card" style="margin-bottom:1.1rem">
    <div class="u-card-head"><h2><i class="fa-solid fa-user" style="color:var(--primary)"></i> Personal Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld"><label>Student Name <span class="req">*</span></label><input type="text" name="name" required value="<?= $v('name') ?>" placeholder="Full name"></div>
      <div class="u-fld"><label>Father / Guardian</label><input type="text" name="father_name" value="<?= $v('father_name') ?>" placeholder="Father's name"></div>
      <div class="u-fld"><label>Gender</label>
        <select name="gender"><?php foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $k=>$l): ?><option value="<?= $k ?>" <?= ($s['gender']??'male')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Date of Birth</label><input type="date" name="dob" value="<?= $v('dob') ?>"></div>
      <div class="u-fld"><label>CNIC / B-Form</label><input type="text" name="cnic" value="<?= $v('cnic') ?>" placeholder="00000-0000000-0"></div>
      <div class="u-fld"><label>Phone</label><input type="text" name="phone" value="<?= $v('phone') ?>" placeholder="+92 3xx xxxxxxx"></div>
      <div class="u-fld"><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>" placeholder="student@example.com"></div>
      <div class="u-fld"><label>Photo</label><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"><span class="hint">JPG/PNG/WEBP, max 2 MB<?= $isEdit && !empty($s['photo']) ? ' · a photo is on file' : '' ?></span></div>
      <div class="u-fld col-full"><label>Address</label><input type="text" name="address" value="<?= $v('address') ?>" placeholder="Postal address"></div>
    </div>
  </div>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-graduation-cap" style="color:var(--primary)"></i> Academic Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld"><label>Program</label>
        <select name="program"><option value="">— Select —</option>
          <?php foreach (program_list() as $p): ?><option value="<?= e($p) ?>" <?= ($s['program']??'')===$p?'selected':'' ?>><?= e($p) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Department</label>
        <select name="department_id"><option value="0">— Unassigned —</option>
          <?php foreach ($depts as $id=>$name): ?><option value="<?= $id ?>" <?= (int)($s['department_id']??0)===$id?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Session</label>
        <select name="session"><?php foreach (session_list() as $ss): ?><option value="<?= e($ss) ?>" <?= ($s['session']??session_list()[0])===$ss?'selected':'' ?>><?= e($ss) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Batch</label><input type="text" name="batch" value="<?= $v('batch', date('Y')) ?>" placeholder="e.g. 2026"></div>
      <div class="u-fld"><label>Current Semester</label>
        <select name="current_semester"><?php for ($i=1;$i<=STU_SEMESTERS;$i++): ?><option value="<?= $i ?>" <?= (int)($s['current_semester']??1)===$i?'selected':'' ?>>Semester <?= $i ?></option><?php endfor; ?></select></div>
      <div class="u-fld"><label>Status</label>
        <select name="status"><?php foreach (STU_STATUS as $k=>$m): ?><option value="<?= $k ?>" <?= ($s['status']??'active')===$k?'selected':'' ?>><?= e($m[0]) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= $isEdit ? stu_url('view.php?id='.(int)$s['id']) : stu_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Changes' : 'Enroll Student' ?></button>
    </div>
  </div>
</form>
