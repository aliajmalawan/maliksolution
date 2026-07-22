<?php
declare(strict_types=1);
/** Shared department form — included by create.php and edit.php.
 *  Expects: $d (values), $isEdit (bool). */
$d = $d ?? [];
$v = fn(string $k, string $def = '') => e((string)($d[$k] ?? $def));
?>
<form method="post" action="<?= dept_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><?php endif; ?>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-sitemap" style="color:var(--primary)"></i> Department Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld">
        <label>Department Name <span class="req">*</span></label>
        <input type="text" name="name" required value="<?= $v('name') ?>" placeholder="e.g. Computer Science">
      </div>
      <div class="u-fld">
        <label>Department Code</label>
        <input type="text" name="code" value="<?= $v('code') ?>" placeholder="e.g. CS" style="text-transform:uppercase">
      </div>
      <div class="u-fld">
        <label>Head of Department</label>
        <input type="text" name="head_name" value="<?= $v('head_name') ?>" placeholder="e.g. Dr. Imran Qureshi">
      </div>
      <div class="u-fld">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= ($d['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($d['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="u-fld">
        <label>Email</label>
        <input type="email" name="email" value="<?= $v('email') ?>" placeholder="dept@institution.edu">
      </div>
      <div class="u-fld">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= $v('phone') ?>" placeholder="+92 xx xxxxxxx">
      </div>
      <div class="u-fld col-full">
        <label>Description</label>
        <textarea name="description" rows="3" placeholder="Short description of the department…"><?= $v('description') ?></textarea>
      </div>
    </div>
    <div class="u-form-actions">
      <a href="<?= dept_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Changes' : 'Create Department' ?></button>
    </div>
  </div>
</form>
