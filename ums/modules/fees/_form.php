<?php
declare(strict_types=1);
/** Shared challan form — included by create.php / edit.php. Expects $c, $isEdit. */
$c = $c ?? [];
$v = fn(string $k, string $def = '') => e((string)($c[$k] ?? $def));
$campus = (int)$user['campus_id'];
$students = fee_student_options($campus);
?>
<form method="post" action="<?= fee_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><?php endif; ?>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-file-invoice-dollar" style="color:var(--primary)"></i> Challan Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld col-full"><label>Student <span class="req">*</span></label>
        <select name="student_id" required><option value="0">— Select student —</option>
          <?php foreach ($students as $id=>$label): ?><option value="<?= $id ?>" <?= (int)($c['student_id']??0)===$id?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Session</label>
        <select name="session"><?php foreach (session_list() as $s): ?><option value="<?= e($s) ?>" <?= ($c['session']??session_list()[0])===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Semester</label>
        <select name="semester"><?php for ($i=1;$i<=8;$i++): ?><option value="<?= $i ?>" <?= (int)($c['semester']??1)===$i?'selected':'' ?>>Semester <?= $i ?></option><?php endfor; ?></select></div>
      <div class="u-fld col-full"><label>Title / Description</label>
        <input type="text" name="title" value="<?= $v('title') ?>" placeholder="e.g. Tuition Fee — Fall 2026"></div>
      <div class="u-fld"><label>Total Amount (Rs) <span class="req">*</span></label>
        <input type="number" name="total_amount" min="0" step="0.01" required value="<?= $v('total_amount','0') ?>"></div>
      <div class="u-fld"><label>Discount (Rs)</label>
        <input type="number" name="discount" min="0" step="0.01" value="<?= $v('discount','0') ?>"></div>
      <div class="u-fld"><label>Fine / Late Fee (Rs)</label>
        <input type="number" name="fine" min="0" step="0.01" value="<?= $v('fine','0') ?>"></div>
      <div class="u-fld"><label>Due Date</label><input type="date" name="due_date" value="<?= $v('due_date') ?>"></div>
      <div class="u-fld col-full"><label>Remarks</label><input type="text" name="remarks" value="<?= $v('remarks') ?>" placeholder="Optional note"></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= $isEdit ? fee_url('view.php?id='.(int)$c['id']) : fee_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Challan' : 'Create Challan' ?></button>
    </div>
  </div>
</form>
