<?php
declare(strict_types=1);
/** Shared route form — included by create.php / edit.php. Expects $r, $isEdit. */
$r = $r ?? [];
$v = fn(string $k, string $def = '') => e((string)($r[$k] ?? $def));
?>
<form method="post" action="<?= transport_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><?php endif; ?>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-route" style="color:var(--primary)"></i> Route Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld col-full"><label>Route Name <span class="req">*</span></label><input type="text" name="route_name" required value="<?= $v('route_name') ?>" placeholder="e.g. City Center — Campus"></div>
      <div class="u-fld"><label>Vehicle No</label><input type="text" name="vehicle_no" value="<?= $v('vehicle_no') ?>" placeholder="e.g. LEA-1234"></div>
      <div class="u-fld"><label>Capacity (seats)</label><input type="number" name="capacity" min="1" value="<?= $v('capacity','20') ?>"></div>
      <div class="u-fld"><label>Driver Name</label><input type="text" name="driver_name" value="<?= $v('driver_name') ?>" placeholder="Driver name"></div>
      <div class="u-fld"><label>Driver Phone</label><input type="text" name="driver_phone" value="<?= $v('driver_phone') ?>" placeholder="Contact number"></div>
      <div class="u-fld"><label>Monthly Fee (Rs)</label><input type="number" name="monthly_fee" min="0" step="0.01" value="<?= $v('monthly_fee','0') ?>"></div>
      <div class="u-fld"><label>Status</label>
        <select name="status"><option value="active" <?= ($r['status']??'active')==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= ($r['status']??'')==='inactive'?'selected':'' ?>>Inactive</option></select></div>
      <div class="u-fld col-full"><label>Stops <span style="color:var(--muted);font-weight:500">(comma separated)</span></label><input type="text" name="stops" value="<?= $v('stops') ?>" placeholder="Stop A, Stop B, Stop C"></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= transport_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Route' : 'Add Route' ?></button>
    </div>
  </div>
</form>
