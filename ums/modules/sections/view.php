<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id  = (int)($_GET['id'] ?? 0);
$sec = sec_find($id);
if (!$sec) { flash_set('error', 'Section not found.'); redirect(sec_url('index.php')); }

$db = ums_db(); $campus = (int)$user['campus_id'];
$teacherName = (int)$sec['class_teacher_id'] > 0 ? (sec_teacher_options($campus)[(int)$sec['class_teacher_id']] ?? '') : '';

// Enrolled students in this section
$enrolled = [];
$es = $db->prepare('SELECT id, registration_no, name, phone, photo FROM ' . tbl('students') . ' WHERE section_id = ? AND campus_id = ? ORDER BY name');
$es->bind_param('ii', $id, $campus); $es->execute();
$enrolled = $es->get_result()->fetch_all(MYSQLI_ASSOC); $es->close();

// Eligible unassigned students (same program + semester, no section yet)
$eligible = [];
$prog = $sec['program']; $sem = (int)$sec['semester'];
$el = $db->prepare('SELECT id, registration_no, name FROM ' . tbl('students') . '
    WHERE campus_id = ? AND section_id = 0 AND program = ? AND current_semester = ? AND status = "active" ORDER BY name');
$el->bind_param('isi', $campus, $prog, $sem); $el->execute();
$eligible = $el->get_result()->fetch_all(MYSQLI_ASSOC); $el->close();

$fill = (int)$sec['capacity'] > 0 ? min(100, (int)round(count($enrolled) / (int)$sec['capacity'] * 100)) : 0;

$page_title = sec_label($sec); $active = 'classes';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1><?= e($sec['program']) ?> — Section <?= e($sec['name']) ?> <?= active_badge($sec['status']) ?></h1>
    <p>Semester <?= (int)$sec['semester'] ?> · <?= e($sec['session'] ?: '—') ?><?= $teacherName ? ' · Class Teacher: ' . e($teacherName) : '' ?><?= $sec['room'] ? ' · ' . e($sec['room']) : '' ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= sec_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <a href="<?= sec_url('edit.php?id='.$id) ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-users"></i></span><div><small>Enrolled</small><strong><?= count($enrolled) ?> / <?= (int)$sec['capacity'] ?></strong></div></div>
  <div class="u-chip" style="flex:2"><div style="width:100%"><small style="color:var(--muted);font-weight:800;text-transform:uppercase;font-size:.68rem">Fill Rate — <?= $fill ?>%</small>
    <div class="u-prog-track" style="margin-top:.4rem"><div class="u-prog-fill" style="width:<?= $fill ?>%"></div></div></div></div>
</div>

<div class="u-grid g-main">
  <!-- Enrolled students -->
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-user-graduate" style="color:var(--primary)"></i> Enrolled Students</h2>
      <span class="hint"><?= count($enrolled) ?></span></div>
    <?php if (!$enrolled): ?>
      <div class="u-empty" style="padding:2rem"><i class="fa-solid fa-user-graduate"></i><p>No students in this section yet. Add them from the panel on the right.</p></div>
    <?php else: ?>
      <div style="overflow-x:auto"><table class="u-table">
        <thead><tr><th>Reg. No.</th><th>Student</th><th>Phone</th><th style="text-align:right">Remove</th></tr></thead>
        <tbody>
          <?php foreach ($enrolled as $st): ?>
            <tr>
              <td style="color:var(--muted);font-weight:700"><?= e($st['registration_no']) ?></td>
              <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($st['name'])) ?></span><strong><?= e($st['name']) ?></strong></span></td>
              <td style="color:var(--muted)"><?= e($st['phone'] ?: '—') ?></td>
              <td style="text-align:right">
                <form method="post" action="<?= sec_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Remove this student from the section?')">
                  <?= csrf_field() ?><input type="hidden" name="action" value="unassign_student"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="student_id" value="<?= (int)$st['id'] ?>">
                  <button type="submit" class="del" style="width:32px;height:32px;border-radius:8px;border:1px solid var(--line);background:var(--surface);cursor:pointer" title="Remove"><i class="fa-solid fa-xmark"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>

  <!-- Add students -->
  <div class="u-card" style="height:fit-content">
    <div class="u-card-head"><h2><i class="fa-solid fa-user-plus" style="color:var(--primary)"></i> Add Students</h2></div>
    <p style="color:var(--muted);font-size:.78rem;margin:0 0 .8rem">Unassigned active students of <strong><?= e($prog) ?></strong>, Semester <?= $sem ?>.</p>
    <?php if (!$eligible): ?>
      <div class="u-empty" style="padding:1.5rem"><i class="fa-solid fa-user-check"></i><p style="font-size:.82rem">No eligible students to add. Enroll students in this program &amp; semester first.</p></div>
    <?php else: ?>
      <form method="post" action="<?= sec_url('action.php') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="assign_students"><input type="hidden" name="id" value="<?= $id ?>">
        <div style="max-height:320px;overflow-y:auto;border:1px solid var(--line);border-radius:10px;padding:.5rem .7rem">
          <?php foreach ($eligible as $st): ?>
            <label style="display:flex;align-items:center;gap:.55rem;padding:.4rem 0;font-size:.84rem;cursor:pointer;border-bottom:1px solid var(--line)">
              <input type="checkbox" name="student_ids[]" value="<?= (int)$st['id'] ?>">
              <span><strong><?= e($st['name']) ?></strong> <span style="color:var(--muted)">· <?= e($st['registration_no']) ?></span></span>
            </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="u-btn u-btn-primary" style="width:100%;margin-top:.8rem"><i class="fa-solid fa-plus"></i> Add Selected</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
