<?php
declare(strict_types=1);
/** Shared course form — included by create.php and edit.php.
 *  Expects: $c (values), $isEdit (bool). */
$c = $c ?? [];
$v = fn(string $k, string $def = '') => e((string)($c[$k] ?? $def));
$campus  = (int)$user['campus_id'];
$depts   = dept_options($campus);
$prereqs = crs_options($campus, (int)($c['id'] ?? 0));
?>
<form method="post" action="<?= crs_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><?php endif; ?>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-book-open" style="color:var(--primary)"></i> Course Details</h2></div>

    <?php if (!$depts): ?>
      <div class="u-flash info" style="margin-bottom:1rem">
        <i class="fa-solid fa-circle-info"></i>
        No active departments yet — <a href="<?= dept_url('create.php') ?>" style="color:inherit;text-decoration:underline">add a department</a> first so courses can be organised under it.
      </div>
    <?php endif; ?>

    <div class="u-form-grid">
      <div class="u-fld">
        <label>Course Title <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= $v('title') ?>" placeholder="e.g. Data Structures & Algorithms">
      </div>
      <div class="u-fld">
        <label>Course Code</label>
        <input type="text" name="code" value="<?= $v('code') ?>" placeholder="e.g. CS-201" style="text-transform:uppercase">
      </div>
      <div class="u-fld">
        <label>Department</label>
        <select name="department_id">
          <option value="0">— Unassigned —</option>
          <?php foreach ($depts as $id => $name): ?>
            <option value="<?= $id ?>" <?= (int)($c['department_id'] ?? 0) === $id ? 'selected' : '' ?>><?= e($name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="u-fld">
        <label>Semester</label>
        <select name="semester">
          <?php for ($s = 1; $s <= CRS_SEMESTERS; $s++): ?>
            <option value="<?= $s ?>" <?= (int)($c['semester'] ?? 1) === $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="u-fld">
        <label>Credit Hours</label>
        <input type="number" name="credit_hours" min="0" max="6" value="<?= $v('credit_hours', '3') ?>">
      </div>
      <div class="u-fld">
        <label>Type</label>
        <select name="type">
          <?php foreach (CRS_TYPES as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($c['type'] ?? 'theory') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="u-fld">
        <label>Prerequisite</label>
        <select name="prerequisite_id">
          <option value="0">— None —</option>
          <?php foreach ($prereqs as $id => $lbl): ?>
            <option value="<?= $id ?>" <?= (int)($c['prerequisite_id'] ?? 0) === $id ? 'selected' : '' ?>><?= e($lbl) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="hint">A course that must be completed before this one.</span>
      </div>
      <div class="u-fld">
        <label>Status</label>
        <select name="status">
          <option value="active" <?= ($c['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= ($c['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="u-fld col-full">
        <label>Description</label>
        <textarea name="description" rows="3" placeholder="Course outline / objectives…"><?= $v('description') ?></textarea>
      </div>
    </div>

    <div class="u-form-actions">
      <a href="<?= crs_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Changes' : 'Create Course' ?></button>
    </div>
  </div>
</form>
