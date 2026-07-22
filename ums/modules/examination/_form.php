<?php
declare(strict_types=1);
/** Shared exam form — included by create.php / edit.php. Expects $x, $isEdit. */
$x = $x ?? [];
$v = fn(string $k, string $def = '') => e((string)($x[$k] ?? $def));
$campus   = (int)$user['campus_id'];
$sections = exam_section_options($campus);
$courses  = exam_course_options($campus);
?>
<form method="post" action="<?= exam_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><?php endif; ?>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-file-pen" style="color:var(--primary)"></i> Exam Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld col-full"><label>Exam Title <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= $v('title') ?>" placeholder="e.g. Midterm — Data Structures"></div>
      <div class="u-fld"><label>Exam Type</label>
        <select name="exam_type"><?php foreach (EXAM_TYPES as $k => $lbl): ?><option value="<?= $k ?>" <?= ($x['exam_type']??'midterm')===$k?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Session</label>
        <select name="session"><?php foreach (session_list() as $s): ?><option value="<?= e($s) ?>" <?= ($x['session']??session_list()[0])===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Section <span class="req">*</span></label>
        <select name="section_id" required><option value="0">— Select section —</option>
          <?php foreach ($sections as $id=>$label): ?><option value="<?= $id ?>" <?= (int)($x['section_id']??0)===$id?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Course / Subject</label>
        <select name="course_id"><option value="0">— None —</option>
          <?php foreach ($courses as $id=>$label): ?><option value="<?= $id ?>" <?= (int)($x['course_id']??0)===$id?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Exam Date</label><input type="date" name="exam_date" value="<?= $v('exam_date') ?>"></div>
      <div class="u-fld"><label>Total Marks</label><input type="number" name="total_marks" min="1" value="<?= $v('total_marks','100') ?>"></div>
      <div class="u-fld"><label>Passing Marks</label><input type="number" name="passing_marks" min="0" value="<?= $v('passing_marks','40') ?>"></div>
      <div class="u-fld"><label>Weightage (%)</label><input type="number" name="weightage" min="0" max="100" value="<?= $v('weightage','100') ?>">
        <span class="hint">Contribution to the final grade (used by Results).</span></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= exam_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Exam' : 'Create Exam &amp; Enter Marks' ?></button>
    </div>
  </div>
</form>
