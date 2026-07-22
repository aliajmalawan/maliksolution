<?php
declare(strict_types=1);
/** Shared teacher form — included by create.php / edit.php. Expects $t, $isEdit. */
$t = $t ?? [];
$v = fn(string $k, string $def = '') => e((string)($t[$k] ?? $def));
$depts = dept_options((int)$user['campus_id']);
?>
<form method="post" action="<?= tch_url('action.php') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><?php endif; ?>

  <div class="u-card" style="margin-bottom:1.1rem">
    <div class="u-card-head"><h2><i class="fa-solid fa-user" style="color:var(--primary)"></i> Personal Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld"><label>Full Name <span class="req">*</span></label><input type="text" name="name" required value="<?= $v('name') ?>" placeholder="e.g. Dr. Imran Qureshi"></div>
      <div class="u-fld"><label>Gender</label>
        <select name="gender"><?php foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $k=>$l): ?><option value="<?= $k ?>" <?= ($t['gender']??'male')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
      </div>
      <div class="u-fld"><label>Date of Birth</label><input type="date" name="dob" value="<?= $v('dob') ?>"></div>
      <div class="u-fld"><label>CNIC</label><input type="text" name="cnic" value="<?= $v('cnic') ?>" placeholder="00000-0000000-0"></div>
      <div class="u-fld"><label>Phone</label><input type="text" name="phone" value="<?= $v('phone') ?>" placeholder="+92 3xx xxxxxxx"></div>
      <div class="u-fld"><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>" placeholder="teacher@institution.edu"></div>
      <div class="u-fld"><label>Photo</label><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"><span class="hint">JPG/PNG/WEBP, max 2 MB<?= $isEdit && !empty($t['photo']) ? ' · a photo is on file' : '' ?></span></div>
      <div class="u-fld col-full"><label>Address</label><input type="text" name="address" value="<?= $v('address') ?>" placeholder="Postal address"></div>
    </div>
  </div>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-briefcase" style="color:var(--primary)"></i> Employment</h2></div>
    <div class="u-form-grid">
      <div class="u-fld"><label>Department</label>
        <select name="department_id"><option value="0">— Unassigned —</option>
          <?php foreach ($depts as $id=>$name): ?><option value="<?= $id ?>" <?= (int)($t['department_id']??0)===$id?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></select>
      </div>
      <div class="u-fld"><label>Designation</label>
        <select name="designation"><?php foreach (TCH_DESIGNATIONS as $d): ?><option value="<?= e($d) ?>" <?= ($t['designation']??'Lecturer')===$d?'selected':'' ?>><?= e($d) ?></option><?php endforeach; ?></select>
      </div>
      <div class="u-fld"><label>Highest Qualification</label><input type="text" name="qualification" value="<?= $v('qualification') ?>" placeholder="e.g. PhD Computer Science"></div>
      <div class="u-fld"><label>Joining Date</label><input type="date" name="joining_date" value="<?= $v('joining_date') ?>"></div>
      <div class="u-fld"><label>Basic Salary (Rs)</label><input type="number" name="salary" min="0" step="500" value="<?= $v('salary','0') ?>"><span class="hint">Used by the Payroll module later.</span></div>
      <div class="u-fld"><label>Status</label>
        <select name="status"><?php foreach (TCH_STATUS as $k=>$m): ?><option value="<?= $k ?>" <?= ($t['status']??'active')===$k?'selected':'' ?>><?= e($m[0]) ?></option><?php endforeach; ?></select>
      </div>
    </div>
    <div class="u-form-actions">
      <a href="<?= $isEdit ? tch_url('view.php?id='.(int)$t['id']) : tch_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Changes' : 'Add Teacher' ?></button>
    </div>
  </div>
</form>
