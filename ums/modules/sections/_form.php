<?php
declare(strict_types=1);
/** Shared section form — included by create.php / edit.php. Expects $s, $isEdit. */
$s = $s ?? [];
$v = fn(string $k, string $def = '') => e((string)($s[$k] ?? $def));
$campus   = (int)$user['campus_id'];
$depts    = dept_options($campus);
$teachers = sec_teacher_options($campus);
?>
<form method="post" action="<?= sec_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><?php endif; ?>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-chalkboard" style="color:var(--primary)"></i> Section Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld"><label>Program <span class="req">*</span></label>
        <select name="program" required><option value="">— Select program —</option>
          <?php foreach (program_list() as $p): ?><option value="<?= e($p) ?>" <?= ($s['program']??'')===$p?'selected':'' ?>><?= e($p) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Semester</label>
        <select name="semester"><?php for ($i=1;$i<=8;$i++): ?><option value="<?= $i ?>" <?= (int)($s['semester']??1)===$i?'selected':'' ?>>Semester <?= $i ?></option><?php endfor; ?></select></div>
      <div class="u-fld"><label>Section Name</label><input type="text" name="name" value="<?= $v('name','A') ?>" placeholder="e.g. A, B, Morning" maxlength="50"></div>
      <div class="u-fld"><label>Session</label>
        <select name="session"><?php foreach (session_list() as $ss): ?><option value="<?= e($ss) ?>" <?= ($s['session']??session_list()[0])===$ss?'selected':'' ?>><?= e($ss) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Department</label>
        <select name="department_id"><option value="0">— Unassigned —</option>
          <?php foreach ($depts as $id=>$name): ?><option value="<?= $id ?>" <?= (int)($s['department_id']??0)===$id?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Class Teacher</label>
        <select name="class_teacher_id"><option value="0">— None —</option>
          <?php foreach ($teachers as $id=>$name): ?><option value="<?= $id ?>" <?= (int)($s['class_teacher_id']??0)===$id?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Capacity</label><input type="number" name="capacity" min="1" max="500" value="<?= $v('capacity','50') ?>"></div>
      <div class="u-fld"><label>Room / Venue</label><input type="text" name="room" value="<?= $v('room') ?>" placeholder="e.g. Block A - Room 12"></div>
      <div class="u-fld"><label>Status</label>
        <select name="status"><option value="active" <?= ($s['status']??'active')==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= ($s['status']??'')==='inactive'?'selected':'' ?>>Inactive</option></select></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= $isEdit ? sec_url('view.php?id='.(int)$s['id']) : sec_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Changes' : 'Create Section' ?></button>
    </div>
  </div>
</form>
