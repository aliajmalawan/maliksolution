<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$examId = (int)($_GET['exam'] ?? 0);
$exam = exam_find($examId);
if (!$exam) { flash_set('error', 'Exam not found.'); redirect(exam_url('index.php')); }

$page_title = 'Enter Marks'; $active = 'exams';
$db = ums_db(); $campus = (int)$user['campus_id'];
$sections = exam_section_options($campus);
$courses  = exam_course_options($campus);
$total    = (int)$exam['total_marks'];
$pass     = (int)$exam['passing_marks'];

// roster + existing marks
$roster = [];
$rs = $db->prepare('SELECT id, registration_no, name FROM ' . tbl('students') . ' WHERE section_id = ? AND campus_id = ? ORDER BY name');
$rs->bind_param('ii', $exam['section_id'], $campus); $rs->execute();
$roster = $rs->get_result()->fetch_all(MYSQLI_ASSOC); $rs->close();

$existing = [];
$ex = $db->prepare('SELECT student_id, obtained_marks, absent FROM ' . tbl('exam_marks') . ' WHERE exam_id = ?');
$ex->bind_param('i', $examId); $ex->execute();
$er = $ex->get_result();
while ($x = $er->fetch_assoc()) $existing[(int)$x['student_id']] = $x;
$ex->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Enter Marks</h1>
    <p><?= e($exam['title']) ?> · <?= e($sections[(int)$exam['section_id']] ?? '—') ?><?= (int)$exam['course_id'] ? ' · ' . e($courses[(int)$exam['course_id']] ?? '') : '' ?></p></div>
  <a href="<?= exam_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back to Exams</a>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-list-ol"></i></span><div><small>Total Marks</small><strong><?= $total ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-flag"></i></span><div><small>Passing</small><strong><?= $pass ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-users"></i></span><div><small>Students</small><strong><?= count($roster) ?></strong></div></div>
</div>

<?php if (!$roster): ?>
  <div class="u-card"><div class="u-empty"><i class="fa-solid fa-users"></i>
    <p>No students in this section. Assign them in <a href="<?= UMS_URL ?>/modules/sections/view.php?id=<?= (int)$exam['section_id'] ?>" style="color:var(--primary)">the section</a> first.</p></div></div>
<?php else: ?>
  <form method="post" action="<?= exam_url('action.php') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_marks">
    <input type="hidden" name="exam_id" value="<?= $examId ?>">
    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-pen-to-square" style="color:var(--primary)"></i> Marks Sheet</h2>
        <span class="hint">Out of <?= $total ?> · passing <?= $pass ?></span></div>
      <div style="overflow-x:auto"><table class="u-table" id="marksTable">
        <thead><tr><th>#</th><th>Reg. No.</th><th>Student</th><th style="width:130px">Marks</th><th style="width:70px">Absent</th><th>Result</th></tr></thead>
        <tbody>
          <?php foreach ($roster as $i => $st): $cur = $existing[(int)$st['id']] ?? null; $obt = $cur ? (float)$cur['obtained_marks'] : ''; $absent = $cur && (int)$cur['absent'] === 1; ?>
            <tr data-total="<?= $total ?>" data-pass="<?= $pass ?>">
              <td style="color:var(--muted)"><?= $i + 1 ?></td>
              <td style="color:var(--muted);font-weight:700"><?= e($st['registration_no']) ?></td>
              <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($st['name'])) ?></span><strong><?= e($st['name']) ?></strong></span></td>
              <td><input type="number" class="u-input mk" name="marks[<?= (int)$st['id'] ?>]" min="0" max="<?= $total ?>" step="0.5" value="<?= $absent ? '' : e((string)$obt) ?>" style="width:110px;height:36px" <?= $absent ? 'disabled' : '' ?>></td>
              <td style="text-align:center"><input type="checkbox" class="ab" name="absent[<?= (int)$st['id'] ?>]" value="1" <?= $absent ? 'checked' : '' ?>></td>
              <td class="res"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
      <div class="u-form-actions">
        <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Marks</button>
      </div>
    </div>
  </form>

  <script>
  (function(){
    function grade(pct){ return pct>=85?'A':pct>=75?'B+':pct>=70?'B':pct>=60?'C':pct>=50?'D':'F'; }
    function paint(row){
      var total=+row.dataset.total, pass=+row.dataset.pass;
      var ab=row.querySelector('.ab'), mk=row.querySelector('.mk'), res=row.querySelector('.res');
      mk.disabled = ab.checked;
      if(ab.checked){ res.innerHTML='<span class="st st-rejected">Absent</span>'; return; }
      var v=parseFloat(mk.value);
      if(isNaN(v)){ res.innerHTML=''; return; }
      var pct=total>0?Math.round(v/total*100):0;
      var ok=v>=pass;
      res.innerHTML='<span class="st '+(ok?'st-approved':'st-rejected')+'">'+pct+'% · '+grade(pct)+' · '+(ok?'Pass':'Fail')+'</span>';
    }
    document.querySelectorAll('#marksTable tbody tr').forEach(function(row){
      paint(row);
      row.querySelector('.mk').addEventListener('input',function(){paint(row);});
      row.querySelector('.ab').addEventListener('change',function(){paint(row);});
    });
  })();
  </script>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
