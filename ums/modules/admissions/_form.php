<?php
declare(strict_types=1);
/**
 * Shared admission form fields — included by create.php and edit.php.
 * Expects: $adm (array of current values), $isEdit (bool), $formAction (string).
 */
$adm = $adm ?? [];
$val = fn(string $k, string $d = '') => e((string)($adm[$k] ?? $d));
?>
<form method="post" action="<?= adm_url('action.php') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$adm['id'] ?>"><?php endif; ?>

  <div class="u-card" style="margin-bottom:1.1rem">
    <div class="u-card-head"><h2><i class="fa-solid fa-user" style="color:var(--primary)"></i> Applicant Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld">
        <label>Student Name <span class="req">*</span></label>
        <input type="text" name="student_name" required value="<?= $val('student_name') ?>" placeholder="Full name">
      </div>
      <div class="u-fld">
        <label>Father / Guardian Name</label>
        <input type="text" name="father_name" value="<?= $val('father_name') ?>" placeholder="Father's name">
      </div>
      <div class="u-fld">
        <label>Gender</label>
        <select name="gender">
          <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($adm['gender'] ?? 'male') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="u-fld">
        <label>Date of Birth</label>
        <input type="date" name="dob" value="<?= $val('dob') ?>">
      </div>
      <div class="u-fld">
        <label>CNIC / B-Form</label>
        <input type="text" name="cnic" value="<?= $val('cnic') ?>" placeholder="00000-0000000-0">
      </div>
      <div class="u-fld">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= $val('phone') ?>" placeholder="+92 3xx xxxxxxx">
      </div>
      <div class="u-fld">
        <label>Email</label>
        <input type="email" name="email" value="<?= $val('email') ?>" placeholder="you@example.com">
      </div>
      <div class="u-fld">
        <label>Applicant Photo</label>
        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
        <span class="hint">JPG/PNG/WEBP, max 2 MB<?= $isEdit && !empty($adm['photo']) ? ' · a photo is already on file' : '' ?></span>
      </div>
      <div class="u-fld col-full">
        <label>Address</label>
        <input type="text" name="address" value="<?= $val('address') ?>" placeholder="Postal address">
      </div>
    </div>
  </div>

  <div class="u-card" style="margin-bottom:1.1rem">
    <div class="u-card-head"><h2><i class="fa-solid fa-graduation-cap" style="color:var(--primary)"></i> Program &amp; Academics</h2></div>
    <div class="u-form-grid">
      <div class="u-fld">
        <label>Applying For (Program) <span class="req">*</span></label>
        <select name="program" required>
          <option value="">— Select program —</option>
          <?php foreach (ADM_PROGRAMS as $p): ?>
            <option value="<?= e($p) ?>" <?= ($adm['program'] ?? '') === $p ? 'selected' : '' ?>><?= e($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="u-fld">
        <label>Academic Session</label>
        <select name="session">
          <?php foreach (ADM_SESSIONS as $s): ?>
            <option value="<?= e($s) ?>" <?= ($adm['session'] ?? ADM_SESSIONS[0]) === $s ? 'selected' : '' ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="u-fld">
        <label>Last Qualification</label>
        <input type="text" name="last_qualification" value="<?= $val('last_qualification') ?>" placeholder="e.g. FSc Pre-Engineering">
      </div>
      <div class="u-fld">
        <label>Board / University</label>
        <input type="text" name="board_university" value="<?= $val('board_university') ?>" placeholder="e.g. BISE Lahore">
      </div>
      <div class="u-fld">
        <label>Obtained Marks</label>
        <input type="number" name="obtained_marks" min="0" value="<?= $val('obtained_marks', '0') ?>">
      </div>
      <div class="u-fld">
        <label>Total Marks</label>
        <input type="number" name="total_marks" min="0" value="<?= $val('total_marks', '0') ?>">
        <span class="hint">Merit % is calculated automatically from these two.</span>
      </div>
      <div class="u-fld col-full">
        <label>Remarks</label>
        <textarea name="remarks" rows="3" placeholder="Any notes about this application…"><?= $val('remarks') ?></textarea>
      </div>
    </div>

    <div class="u-form-actions">
      <a href="<?= $isEdit ? adm_url('view.php?id=' . (int)$adm['id']) : adm_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Changes' : 'Create Application' ?></button>
    </div>
  </div>
</form>
