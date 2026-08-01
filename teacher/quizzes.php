<?php
declare(strict_types=1);
$page_title = 'Quizzes';
$active_nav = 'quizzes';
include 'header.php';

$courses_r = mysqli_query($conn, "SELECT c.id, c.name, cs.id AS sid, cs.name AS sname FROM teacher_subject_assignments tsa JOIN course_subjects cs ON cs.id=tsa.subject_id JOIN courses c ON c.id=cs.course_id WHERE tsa.teacher_id=$tid ORDER BY c.id, cs.sort_order");
$my_courses = [];
if ($courses_r) while ($r = mysqli_fetch_assoc($courses_r)) {
    $my_courses[$r['id']] = $my_courses[$r['id']] ?? ['name' => $r['name'], 'subjects' => []];
    $my_courses[$r['id']]['subjects'][] = ['id' => $r['sid'], 'name' => $r['sname']];
}

$quizzes = mysqli_query($conn, "SELECT tq.*, c.name AS cname, cs.name AS sname FROM teacher_quizzes tq LEFT JOIN courses c ON c.id=tq.course_id LEFT JOIN course_subjects cs ON cs.id=tq.subject_id WHERE tq.teacher_id=$tid ORDER BY tq.created_at DESC");
$auto_add = !empty($_GET['add']);
?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-<?= $_GET['msg']==='saved'?'success':'warning' ?> alert-dismissible fade show">
  <i class="fa-solid fa-<?= $_GET['msg']==='saved'?'circle-check':'trash' ?> me-2"></i>
  Quiz <?= $_GET['msg']==='saved'?'saved':'deleted' ?> successfully.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-circle-question me-2 text-primary"></i>Quizzes</h1><p>Create and manage quizzes for your subjects.</p></div>
  <button class="btn btn-primary btn-sm" id="addBtn"><i class="fa-solid fa-plus me-1"></i>Add Quiz</button>
</div>

<div class="cardx">
  <?php if (mysqli_num_rows($quizzes) > 0): ?>
  <div class="tbl-wrap">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>Title</th><th>Course / Subject</th><th>Duration</th><th>Marks</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while ($q = mysqli_fetch_assoc($quizzes)): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($q['title']) ?></strong><?php if($q['description']): ?><br><span class="text-muted" style="font-size:.72rem"><?= htmlspecialchars(mb_strimwidth($q['description'],0,60,'…')) ?></span><?php endif; ?></td>
          <td class="text-muted small"><?= htmlspecialchars($q['cname']??'—') ?><br><?= htmlspecialchars($q['sname']??'—') ?></td>
          <td class="small"><?= $q['duration_minutes'] ?> min</td>
          <td class="small"><?= $q['total_marks'] ?></td>
          <td><span class="bs bs-<?= $q['status'] ?>"><?= ucfirst($q['status']) ?></span></td>
          <td class="text-muted small"><?= date('d M Y', strtotime($q['created_at'])) ?></td>
          <td>
            <button class="btn btn-sm btn-outline-primary btn-edit"
              data-id="<?= $q['id'] ?>" data-title="<?= htmlspecialchars($q['title'],ENT_QUOTES) ?>"
              data-desc="<?= htmlspecialchars($q['description']??'',ENT_QUOTES) ?>"
              data-course="<?= $q['course_id'] ?>" data-subject="<?= $q['subject_id'] ?>"
              data-dur="<?= $q['duration_minutes'] ?>" data-marks="<?= $q['total_marks'] ?>"
              data-status="<?= $q['status'] ?>"><i class="fa-solid fa-pen fa-sm"></i></button>
            <form method="POST" action="action.php" class="d-inline" onsubmit="return confirm('Delete this quiz?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="quiz_delete">
              <input type="hidden" name="id" value="<?= $q['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash fa-sm"></i></button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="empty-st"><i class="fa-solid fa-circle-question"></i><p>No quizzes yet.<br>Click <strong>Add Quiz</strong> to create your first quiz.</p></div>
  <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="quizModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="action.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="quiz_save">
        <input type="hidden" name="id" id="f_id" value="0">
        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="modalLabel"><i class="fa-solid fa-circle-question me-2"></i>Add Quiz</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold">Quiz Title <span class="text-danger">*</span></label>
              <input type="text" name="title" id="f_title" class="form-control" placeholder="e.g. Chapter 3 Quiz — Newton's Laws" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Course</label>
              <select name="course_id" id="f_course" class="form-select">
                <option value="0">— Select Course —</option>
                <?php foreach ($my_courses as $cid2 => $cd): ?>
                <option value="<?= $cid2 ?>"><?= htmlspecialchars($cd['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Subject</label>
              <select name="subject_id" id="f_subject" class="form-select">
                <option value="0">— Select Subject —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Duration (minutes)</label>
              <input type="number" name="duration_minutes" id="f_dur" class="form-control" value="30" min="1" max="300">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Total Marks</label>
              <input type="number" name="total_marks" id="f_marks" class="form-control" value="100" min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Status</label>
              <select name="status" id="f_status" class="form-select">
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="closed">Closed</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Description / Instructions</label>
              <textarea name="description" id="f_desc" class="form-control" rows="3" placeholder="Instructions for students…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="f_submit"><i class="fa-solid fa-save me-1"></i>Save Quiz</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$courses_json      = json_encode(array_map(fn($c) => ['name' => $c['name'], 'subjects' => $c['subjects']], $my_courses), JSON_HEX_TAG);
$course_keys_json  = json_encode(array_values(array_keys($my_courses)));
$page_js = <<<JS
<script>
var COURSES = $courses_json;
var COURSE_KEYS = $course_keys_json;

function getSubjectsForCourse(cid) {
  var idx = COURSE_KEYS.indexOf(parseInt(cid));
  if (idx < 0) return [];
  return Object.values(COURSES)[idx].subjects;
}

function fillSubjects(cid, selSid) {
  var sel = document.getElementById('f_subject');
  sel.innerHTML = '<option value="0">— Select Subject —</option>';
  getSubjectsForCourse(cid).forEach(function(s) {
    var o = document.createElement('option');
    o.value = s.id; o.textContent = s.name;
    if (s.id == selSid) o.selected = true;
    sel.appendChild(o);
  });
}

document.getElementById('f_course').addEventListener('change', function() { fillSubjects(this.value, 0); });

function openModal(data) {
  var d = data || {};
  document.getElementById('f_id').value      = d.id    || 0;
  document.getElementById('f_title').value   = d.title || '';
  document.getElementById('f_desc').value    = d.desc  || '';
  document.getElementById('f_dur').value     = d.dur   || 30;
  document.getElementById('f_marks').value   = d.marks || 100;
  document.getElementById('f_status').value  = d.status|| 'draft';
  document.getElementById('f_course').value  = d.course|| 0;
  fillSubjects(d.course||0, d.subject||0);
  document.getElementById('modalLabel').innerHTML = (d.id ? '<i class="fa-solid fa-pen me-2"></i>Edit Quiz' : '<i class="fa-solid fa-circle-question me-2"></i>Add Quiz');
  document.getElementById('f_submit').innerHTML   = d.id ? '<i class="fa-solid fa-save me-1"></i>Save Changes' : '<i class="fa-solid fa-save me-1"></i>Save Quiz';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('quizModal')).show();
}

document.getElementById('addBtn').addEventListener('click', function() { openModal(); });
document.querySelectorAll('.btn-edit').forEach(function(b) {
  b.addEventListener('click', function() {
    openModal({id:b.dataset.id, title:b.dataset.title, desc:b.dataset.desc,
      course:b.dataset.course, subject:b.dataset.subject,
      dur:b.dataset.dur, marks:b.dataset.marks, status:b.dataset.status});
  });
});
</script>
JS;
if ($auto_add) $page_js .= "<script>document.addEventListener('DOMContentLoaded',function(){openModal();})</script>";
?>
<?php include 'footer.php'; ?>
