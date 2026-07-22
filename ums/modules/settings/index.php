<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'System Settings'; $active = 'settings';
$db = ums_db();
$logo = ums_setting('logo_path');

$groupIcons = ['Institute Profile' => 'fa-building-columns', 'Academic' => 'fa-graduation-cap', 'Finance' => 'fa-wallet'];

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>System Settings</h1><p>Institute profile, academic &amp; finance defaults, and branding</p></div>
</div>

<form method="post" action="<?= set_url('action.php') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="u-grid" style="grid-template-columns:2fr 1fr;align-items:start">
    <div>
      <?php foreach (ums_setting_groups() as $group => $fields): ?>
        <div class="u-card" style="margin-bottom:1.1rem">
          <div class="u-card-head"><h2><i class="fa-solid <?= $groupIcons[$group] ?? 'fa-gear' ?>" style="color:var(--primary)"></i> <?= e($group) ?></h2></div>
          <div class="u-form-grid">
            <?php foreach ($fields as $key => [$label, $type, $ph]):
              $val = ums_setting($key, ums_setting_default($db, $key));
              $wide = $type === 'textarea'; ?>
              <div class="u-fld <?= $wide ? 'col-full' : '' ?>">
                <label><?= e($label) ?></label>
                <?php if ($type === 'textarea'): ?>
                  <textarea name="<?= e($key) ?>" rows="2" placeholder="<?= e($ph) ?>"><?= e($val) ?></textarea>
                <?php else: ?>
                  <input type="<?= e($type) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>" placeholder="<?= e($ph) ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
    </div>

    <div>
      <!-- Branding -->
      <div class="u-card" style="margin-bottom:1.1rem">
        <div class="u-card-head"><h2><i class="fa-solid fa-image" style="color:var(--primary)"></i> Logo</h2></div>
        <div style="text-align:center;margin-bottom:1rem">
          <?php if ($logo !== ''): ?>
            <img src="<?= UMS_URL . '/' . e($logo) ?>" alt="Logo" style="max-width:150px;max-height:90px;object-fit:contain;background:#fff;border:1px solid var(--line);border-radius:12px;padding:8px">
          <?php else: ?>
            <div style="width:150px;height:90px;margin:0 auto;border:1px dashed var(--line);border-radius:12px;display:grid;place-items:center;color:var(--muted)"><i class="fa-solid fa-image" style="font-size:1.6rem"></i></div>
          <?php endif; ?>
        </div>
        <div class="u-fld" style="margin-bottom:.6rem"><label>Upload Logo</label><input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp"></div>
        <span class="hint" style="color:var(--muted);font-size:.72rem">PNG/JPG/WEBP · max 2 MB. Shown on transcripts &amp; slips.</span>
        <?php if ($logo !== ''): ?>
          <label style="display:flex;align-items:center;gap:.4rem;margin-top:.7rem;font-size:.8rem;cursor:pointer"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
        <?php endif; ?>
      </div>

      <!-- Grading scale (read-only reference) -->
      <div class="u-card" style="height:fit-content">
        <div class="u-card-head"><h2><i class="fa-solid fa-ranking-star" style="color:var(--primary)"></i> Grading Scale</h2></div>
        <p style="color:var(--muted);font-size:.76rem;margin:0 0 .7rem">Applied by Results &amp; GPA (4.0 scale).</p>
        <table class="u-table" style="font-size:.8rem">
          <thead><tr><th>%</th><th>Grade</th><th style="text-align:right">Points</th></tr></thead>
          <tbody>
            <?php foreach ([[85,'A','4.00'],[80,'A-','3.70'],[75,'B+','3.30'],[70,'B','3.00'],[65,'B-','2.70'],[60,'C+','2.30'],[55,'C','2.00'],[50,'C-','1.70'],[40,'D','1.00'],[0,'F','0.00']] as [$m,$g,$p]): ?>
              <tr><td style="color:var(--muted)"><?= $m ?>+</td><td><strong><?= $g ?></strong></td><td style="text-align:right"><?= $p ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</form>
<style>@media (max-width:991px){ form .u-grid{grid-template-columns:1fr!important} }</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
