<?php
declare(strict_types=1);
/** Shared book form — included by create.php / edit.php. Expects $b, $isEdit. */
$b = $b ?? [];
$v = fn(string $k, string $def = '') => e((string)($b[$k] ?? $def));
?>
<form method="post" action="<?= lib_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><?php endif; ?>
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-book" style="color:var(--primary)"></i> Book Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld col-full"><label>Title <span class="req">*</span></label><input type="text" name="title" required value="<?= $v('title') ?>" placeholder="Book title"></div>
      <div class="u-fld"><label>Author</label><input type="text" name="author" value="<?= $v('author') ?>" placeholder="Author name"></div>
      <div class="u-fld"><label>ISBN</label><input type="text" name="isbn" value="<?= $v('isbn') ?>" placeholder="ISBN"></div>
      <div class="u-fld"><label>Category</label>
        <select name="category"><?php foreach (LIB_CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= ($b['category']??'General')===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Publisher</label><input type="text" name="publisher" value="<?= $v('publisher') ?>" placeholder="Publisher"></div>
      <div class="u-fld"><label>Total Copies</label><input type="number" name="total_copies" min="1" value="<?= $v('total_copies','1') ?>"></div>
      <div class="u-fld"><label>Shelf / Location</label><input type="text" name="shelf" value="<?= $v('shelf') ?>" placeholder="e.g. Rack A-3"></div>
      <div class="u-fld"><label>Status</label>
        <select name="status"><option value="active" <?= ($b['status']??'active')==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= ($b['status']??'')==='inactive'?'selected':'' ?>>Inactive</option></select></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= lib_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Book' : 'Add Book' ?></button>
    </div>
  </div>
</form>
