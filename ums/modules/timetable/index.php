<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Timetable'; $active = 'timetable';
$db = ums_db(); $campus = (int)$user['campus_id'];

$sections = tt_section_options($campus);
$courses  = tt_course_options($campus);
$teachers = tt_teacher_options($campus);

$secId = (int)($_GET['section'] ?? 0);
if ($secId && !isset($sections[$secId])) $secId = 0;
$editId = (int)($_GET['edit'] ?? 0);
$editRow = null;

$byDay = array_fill_keys(array_keys(TT_DAYS), []);
if ($secId) {
    $ls = $db->prepare('SELECT tt.*, c.code AS ccode, c.title AS ctitle, t.name AS tname
        FROM ' . tbl('timetable') . ' tt
        LEFT JOIN ' . tbl('courses') . ' c ON c.id = tt.course_id
        LEFT JOIN ' . tbl('teachers') . ' t ON t.id = tt.teacher_id
        WHERE tt.section_id = ? ORDER BY tt.start_time');
    $ls->bind_param('i', $secId); $ls->execute();
    $r = $ls->get_result();
    while ($x = $r->fetch_assoc()) {
        if (isset($byDay[(int)$x['day_of_week']])) $byDay[(int)$x['day_of_week']][] = $x;
        if ($editId && (int)$x['id'] === $editId) $editRow = $x;
    }
    $ls->close();
}
$todayDow = (int)date('N');

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Timetable</h1><p>Weekly class schedule per section</p></div>
  <?php if ($secId): ?>
    <div style="display:flex;gap:.5rem">
      <a href="<?= tt_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-user-clock"></i> Teacher Workload</a>
      <button onclick="window.print()" class="u-btn u-btn-soft"><i class="fa-solid fa-print"></i> Print</button>
    </div>
  <?php endif; ?>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="att-picker">
    <div class="u-fld"><label>Section</label>
      <select name="section" class="u-select" onchange="this.form.submit()">
        <option value="0">— Select section —</option>
        <?php foreach ($sections as $id => $label): ?><option value="<?= $id ?>" <?= $secId === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select></div>
    <div class="u-fld"><button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-eye"></i> View</button></div>
  </form>
  <?php if (!$sections): ?>
    <p style="color:var(--muted);font-size:.82rem;margin:.9rem 0 0"><i class="fa-solid fa-circle-info"></i> No active sections. Create one in <a href="<?= UMS_URL ?>/modules/sections/index.php" style="color:var(--primary)">Classes &amp; Sections</a> first.</p>
  <?php endif; ?>
</div>

<?php if ($secId): ?>
  <!-- Weekly grid -->
  <div class="u-card no-print-border" style="margin-bottom:1.1rem">
    <div class="u-card-head"><h2><i class="fa-solid fa-table-cells" style="color:var(--primary)"></i> <?= e($sections[$secId]) ?></h2></div>
    <div class="tt-grid">
      <?php foreach (TT_DAYS as $dow => $dname): ?>
        <div class="tt-day <?= $dow === $todayDow ? 'today' : '' ?>">
          <h4><?= e($dname) ?></h4>
          <?php if (!$byDay[$dow]): ?>
            <div class="tt-empty-day">—</div>
          <?php else: foreach ($byDay[$dow] as $p): ?>
            <div class="tt-period">
              <span class="tt-time"><?= e(tt_time($p['start_time'])) ?> – <?= e(tt_time($p['end_time'])) ?></span>
              <div class="c" style="margin-top:.3rem"><?= e($p['ctitle'] ? ($p['ccode'] ? $p['ccode'] . ' — ' : '') . $p['ctitle'] : 'Class') ?></div>
              <div class="t"><i class="fa-solid fa-user"></i> <?= e($p['tname'] ?: 'No teacher') ?><?= $p['room'] ? ' · ' . e($p['room']) : '' ?></div>
              <div class="row no-print">
                <span></span>
                <span class="acts">
                  <a href="<?= tt_url('index.php?section=' . $secId . '&edit=' . (int)$p['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                  <form method="post" action="<?= tt_url('action.php') ?>" onsubmit="return confirm('Remove this period?')">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="del" title="Remove"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </span>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Add / edit period -->
  <div class="u-card no-print" id="periodForm">
    <div class="u-card-head"><h2><i class="fa-solid fa-<?= $editRow ? 'pen' : 'plus' ?>" style="color:var(--primary)"></i> <?= $editRow ? 'Edit Period' : 'Add Period' ?></h2>
      <?php if ($editRow): ?><a href="<?= tt_url('index.php?section=' . $secId) ?>" class="u-btn u-btn-soft" style="padding:.35rem .8rem;font-size:.74rem">Cancel edit</a><?php endif; ?></div>
    <form method="post" action="<?= tt_url('action.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'create' ?>">
      <input type="hidden" name="section_id" value="<?= $secId ?>">
      <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>"><?php endif; ?>
      <div class="u-form-grid">
        <div class="u-fld"><label>Day <span class="req">*</span></label>
          <select name="day_of_week"><?php foreach (TT_DAYS as $dow => $dn): ?><option value="<?= $dow ?>" <?= (int)($editRow['day_of_week'] ?? 1) === $dow ? 'selected' : '' ?>><?= e($dn) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld"><label>Room</label><input type="text" name="room" value="<?= e($editRow['room'] ?? '') ?>" placeholder="e.g. Block A - Room 12"></div>
        <div class="u-fld"><label>Start Time <span class="req">*</span></label><input type="time" name="start_time" required value="<?= $editRow ? e(substr($editRow['start_time'], 0, 5)) : '09:00' ?>"></div>
        <div class="u-fld"><label>End Time <span class="req">*</span></label><input type="time" name="end_time" required value="<?= $editRow ? e(substr($editRow['end_time'], 0, 5)) : '10:00' ?>"></div>
        <div class="u-fld"><label>Course / Subject</label>
          <select name="course_id"><option value="0">— None —</option>
            <?php foreach ($courses as $id => $label): ?><option value="<?= $id ?>" <?= (int)($editRow['course_id'] ?? 0) === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld"><label>Teacher</label>
          <select name="teacher_id"><option value="0">— None —</option>
            <?php foreach ($teachers as $id => $name): ?><option value="<?= $id ?>" <?= (int)($editRow['teacher_id'] ?? 0) === $id ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="u-form-actions">
        <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $editRow ? 'Save Period' : 'Add Period' ?></button>
      </div>
    </form>
  </div>
<?php endif; ?>

<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.no-print,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}.tt-grid{gap:.3rem}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
