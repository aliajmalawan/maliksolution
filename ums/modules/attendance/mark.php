<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Mark Attendance'; $active = 'attendance';
$db = ums_db(); $campus = (int)$user['campus_id'];

$sections = att_section_options($campus);
$courses  = att_course_options($campus);

$secId    = (int)($_GET['section'] ?? 0);
$courseId = max(0, (int)($_GET['course'] ?? 0));
$date     = (string)($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$roster = [];
$existing = [];
$sec = null;
if ($secId > 0 && isset($sections[$secId])) {
    $sec = att_section($secId);
    // students in the section
    $rs = $db->prepare('SELECT id, registration_no, name, photo FROM ' . tbl('students') . ' WHERE section_id = ? AND campus_id = ? ORDER BY name');
    $rs->bind_param('ii', $secId, $campus); $rs->execute();
    $roster = $rs->get_result()->fetch_all(MYSQLI_ASSOC); $rs->close();
    // existing attendance for this section/course/date
    $ex = $db->prepare('SELECT student_id, status FROM ' . tbl('attendance') . ' WHERE section_id = ? AND course_id = ? AND a_date = ?');
    $ex->bind_param('iis', $secId, $courseId, $date); $ex->execute();
    $er = $ex->get_result();
    while ($x = $er->fetch_assoc()) $existing[(int)$x['student_id']] = $x['status'];
    $ex->close();
}
$alreadyMarked = !empty($existing);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Mark Attendance</h1><p>Choose a section and date, then mark each student</p></div>
  <a href="<?= att_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-table-list"></i> Register</a>
</div>

<!-- Section / date / course picker -->
<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="att-picker">
    <div class="u-fld"><label>Section</label>
      <select name="section" class="u-select" required>
        <option value="0">— Select section —</option>
        <?php foreach ($sections as $id => $label): ?><option value="<?= $id ?>" <?= $secId === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select></div>
    <div class="u-fld"><label>Date</label><input type="date" name="date" class="u-input" value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>"></div>
    <div class="u-fld"><label>Subject <span style="color:var(--muted);font-weight:500">(optional)</span></label>
      <select name="course" class="u-select">
        <option value="0">Daily attendance</option>
        <?php foreach ($courses as $id => $label): ?><option value="<?= $id ?>" <?= $courseId === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select></div>
    <div class="u-fld"><button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-arrow-right"></i> Load Roster</button></div>
  </form>
  <?php if (!$sections): ?>
    <p style="color:var(--muted);font-size:.82rem;margin:.9rem 0 0"><i class="fa-solid fa-circle-info"></i> No active sections yet. Create one in <a href="<?= UMS_URL ?>/modules/sections/index.php" style="color:var(--primary)">Classes &amp; Sections</a> and assign students.</p>
  <?php endif; ?>
</div>

<?php if ($secId > 0 && $sec): ?>
  <?php if (!$roster): ?>
    <div class="u-card"><div class="u-empty"><i class="fa-solid fa-users"></i>
      <p>No students assigned to this section. Add them in <a href="<?= UMS_URL ?>/modules/sections/view.php?id=<?= $secId ?>" style="color:var(--primary)">the section</a> first.</p></div></div>
  <?php else: ?>
    <form method="post" action="<?= att_url('action.php') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="section_id" value="<?= $secId ?>">
      <input type="hidden" name="course_id" value="<?= $courseId ?>">
      <input type="hidden" name="a_date" value="<?= e($date) ?>">
      <div class="u-card">
        <div class="u-card-head">
          <h2><i class="fa-solid fa-clipboard-user" style="color:var(--primary)"></i>
            <?= e($sections[$secId]) ?> · <?= e(date('d M Y', strtotime($date))) ?>
            <?php if ($alreadyMarked): ?><span class="st st-approved" style="font-size:.6rem;margin-left:.4rem">MARKED</span><?php endif; ?>
          </h2>
          <div style="display:flex;gap:.5rem;align-items:center">
            <button type="button" class="u-btn u-btn-soft" style="padding:.4rem .9rem;font-size:.76rem" onclick="markAll('present')"><i class="fa-solid fa-check-double"></i> All Present</button>
            <span class="hint"><?= count($roster) ?> students</span>
          </div>
        </div>
        <div style="overflow-x:auto"><table class="u-table">
          <thead><tr><th>#</th><th>Reg. No.</th><th>Student</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($roster as $i => $st): $cur = $existing[(int)$st['id']] ?? 'present'; ?>
              <tr>
                <td style="color:var(--muted)"><?= $i + 1 ?></td>
                <td style="color:var(--muted);font-weight:700"><?= e($st['registration_no']) ?></td>
                <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($st['name'])) ?></span><strong><?= e($st['name']) ?></strong></span></td>
                <td>
                  <div class="att-seg" data-sid="<?= (int)$st['id'] ?>">
                    <?php foreach (ATT_STATUS as $key => [$label, $short, $cls]): ?>
                      <label class="att-opt <?= $cls ?>" title="<?= e($label) ?>">
                        <input type="radio" name="status[<?= (int)$st['id'] ?>]" value="<?= $key ?>" <?= $cur === $key ? 'checked' : '' ?>><?= e($short) ?>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table></div>
        <div class="u-form-actions">
          <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Attendance</button>
        </div>
      </div>
    </form>
    <script>
      function markAll(val){
        document.querySelectorAll('.att-seg').forEach(function(seg){
          var r = seg.querySelector('input[value="'+val+'"]'); if (r) r.checked = true;
        });
      }
    </script>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
