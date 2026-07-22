<?php
declare(strict_types=1);
/** Shared room form — included by create.php / edit.php. Expects $r, $isEdit. */
$r = $r ?? [];
$v = fn(string $k, string $def = '') => e((string)($r[$k] ?? $def));
?>
<form method="post" action="<?= hostel_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><?php endif; ?>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-door-open" style="color:var(--primary)"></i> Room Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld"><label>Block</label><input type="text" name="block" value="<?= $v('block') ?>" placeholder="e.g. A-Block"></div>
      <div class="u-fld"><label>Room No <span class="req">*</span></label><input type="text" name="room_no" required value="<?= $v('room_no') ?>" placeholder="e.g. 101"></div>
      <div class="u-fld"><label>Room Type</label>
        <select name="room_type"><?php foreach (HOSTEL_ROOM_TYPES as $k => $lbl): ?><option value="<?= $k ?>" <?= ($r['room_type']??'double')===$k?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Capacity (beds)</label><input type="number" name="capacity" min="1" value="<?= $v('capacity','2') ?>"></div>
      <div class="u-fld"><label>Monthly Fee (Rs)</label><input type="number" name="monthly_fee" min="0" step="0.01" value="<?= $v('monthly_fee','0') ?>"></div>
      <div class="u-fld"><label>Status</label>
        <select name="status"><option value="active" <?= ($r['status']??'active')==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= ($r['status']??'')==='inactive'?'selected':'' ?>>Inactive</option></select></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= hostel_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Room' : 'Add Room' ?></button>
    </div>
  </div>
</form>
