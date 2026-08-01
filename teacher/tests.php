<?php
declare(strict_types=1);
$page_title = 'Tests & Exams';
$active_nav = 'tests';
include 'header.php';

$courses_r = mysqli_query($conn, "SELECT c.id, c.name, cs.id AS sid, cs.name AS sname FROM teacher_subject_assignments tsa JOIN course_subjects cs ON cs.id=tsa.subject_id JOIN courses c ON c.id=cs.course_id WHERE tsa.teacher_id=$tid ORDER BY c.id, cs.sort_order");
$my_courses = [];
if ($courses_r) while ($r = mysqli_fetch_assoc($courses_r)) {
    $my_courses[$r['id']] = $my_courses[$r['id']] ?? ['name' => $r['name'], 'subjects' => []];
    $my_courses[$r['id']]['subjects'][] = ['id' => $r['sid'], 'name' => $r['sname']];
}

$rows = mysqli_query($conn, "SELECT tt.*, c.name AS cname, cs.name AS sname FROM teacher_tests tt LEFT JOIN courses c ON c.id=tt.course_id LEFT JOIN course_subjects cs ON cs.id=tt.subject_id WHERE tt.teacher_id=$tid ORDER BY tt.test_date DESC, tt.created_at DESC");
$auto_add = !empty($_GET['add']);
?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-<?= $_GET['msg']==='saved'?'success':'warning' ?> alert-dismissible fade show">
  <i class="fa-solid fa-<?= $_GET['msg']==='saved'?'circle-check':'trash' ?> me-2"></i>
  Test <?= $_GET['msg']==='saved'?'saved':'deleted' ?>.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-clipboard-list me-2 text-warning"></i>Tests &amp; Exams</h1><p>Schedule and manage tests, exams, and assessments.</p></div>
  <button class="btn btn-warning btn-sm text-dark" id="addBtn"><i class="fa-solid fa-plus me-1"></i>Schedule Test</button>
</div>

<div class="cardx">
  <?php if (mysqli_num_rows($rows) > 0): ?>
  <div class="tbl-wrap">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>Title</th><th>Course / Subject</th><th>Test Date</th><th>Duration</th><th>Marks</th><th>Venue</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while ($t = mysqli_fetch_assoc($rows)): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($t['title']) ?></strong></td>
          <td class="text-muted small"><?= htmlspecialchars($t['cname']??'—') ?><br><?= htmlspecialchars($t['sname']??'—') ?></td>
          <td class="small"><?= $t['test_date'] ? date('d M Y<\b\r>h:i A', strtotime($t['test_date'])) : '<span class="text-muted">TBD</span>' ?></td>
          <td class="small"><?= $t['duration_minutes'] ?> min</td>
          <td class="small"><?= $t['total_marks'] ?></td>
          <td class="small text-muted"><?= htmlspecialchars($t['venue']) ?: '—' ?></td>
          <td><span class="bs bs-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
          <td>
            <button class="btn btn-sm btn-outline-warning btn-edit"
              data-id="<?= $t['id'] ?>" data-title="<?= htmlspecialchars($t['title'],ENT_QUOTES) ?>"
              data-desc="<?= htmlspecialchars($t['description']??'',ENT_QUOTES) ?>"
              data-course="<?= $t['course_id'] ?>" data-subject="<?= $t['subject_id'] ?>"
              data-date="<?= $t['test_date'] ? date('Y-m-d\TH:i', strtotime($t['test_date'])) : '' ?>"
              data-dur="<?= $t['duration_minutes'] ?>" data-marks="<?= $t['total_marks'] ?>"
              data-venue="<?= htmlspecialchars($t['venue']??'',ENT_QUOTES) ?>"
              data-status="<?= $t['status'] ?>"><i class="fa-solid fa-pen fa-sm"></i></button>
            <form method="POST" action="action.php" class="d-inline" onsubmit="return confirm('Delete this test?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="test_delete">
              <input type="hidden" name="id" value="<?= $t['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash fa-sm"></i></button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="empty-st"><i class="fa-solid fa-clipboard-list"></i><p>No tests scheduled yet.</p></div>
  <?php endif; ?>
</div>

<div class="modal fade" id="testModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="action.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="test_save">
        <input type="hidden" name="id" id="f_id" value="0">
        <div class="modal-header" style="background:#92400e">
          <h5 class="modal-title text-white" id="modalLabel"><i class="fa-solid fa-clipboard-list me-2"></i>Schedule Test</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold">Test Title <span class="text-danger">*</span></label>
              <input type="text" name="title" id="f_title" class="form-control" required placeholder="e.g. Mid-Term Exam — Physics">
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
              <select name="subject_id" id="f_subject" class="form-select"><option value="0">— Select Subject —</option></select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Test Date &amp; Time</label>
              <input type="datetime-local" name="test_date" id="f_date" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Duration (min)</label>
              <input type="number" name="duration_minutes" id="f_dur" class="form-control" value="60" min="1">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Total Marks</label>
              <input type="number" name="total_marks" id="f_marks" class="form-control" value="100" min="1">
            </div>
            <div class="col-md-8">
              <label class="form-label fw-bold">Venue / Room</label>
              <input type="text" name="venue" id="f_venue" class="form-control" placeholder="e.g. Room 201, Block A">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Status</label>
              <select name="status" id="f_status" class="form-select">
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Notes / Description</label>
              <textarea name="description" id="f_desc" class="form-control" rows="3" placeholder="Coverage, allowed materials, instructions…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning text-dark" id="f_submit"><i class="fa-solid fa-save me-1"></i>Save Test</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$courses_json = json_encode(array_values(array_map(fn($c) => ['subjects' => $c['subjects']], $my_courses)), JSON_HEX_TAG);
$course_keys  = json_encode(array_keys($my_courses));
$page_js = <<<JS
<script>
var COURSES=$courses_json; var CKEYS=$course_keys;
function fillSubjects(cid,sid){var sel=document.getElementById('f_subject');sel.innerHTML='<option value="0">— Select Subject —</option>';var idx=CKEYS.indexOf(parseInt(cid));if(idx<0)return;COURSES[idx].subjects.forEach(function(s){var o=document.createElement('option');o.value=s.id;o.textContent=s.name;if(s.id==sid)o.selected=true;sel.appendChild(o)});}
document.getElementById('f_course').addEventListener('change',function(){fillSubjects(this.value,0);});
function openModal(d){d=d||{};document.getElementById('f_id').value=d.id||0;document.getElementById('f_title').value=d.title||'';document.getElementById('f_desc').value=d.desc||'';document.getElementById('f_date').value=d.date||'';document.getElementById('f_dur').value=d.dur||60;document.getElementById('f_marks').value=d.marks||100;document.getElementById('f_venue').value=d.venue||'';document.getElementById('f_status').value=d.status||'scheduled';document.getElementById('f_course').value=d.course||0;fillSubjects(d.course||0,d.subject||0);document.getElementById('modalLabel').innerHTML=d.id?'<i class="fa-solid fa-pen me-2"></i>Edit Test':'<i class="fa-solid fa-clipboard-list me-2"></i>Schedule Test';document.getElementById('f_submit').innerHTML=d.id?'<i class="fa-solid fa-save me-1"></i>Save Changes':'<i class="fa-solid fa-save me-1"></i>Save Test';bootstrap.Modal.getOrCreateInstance(document.getElementById('testModal')).show();}
document.getElementById('addBtn').addEventListener('click',function(){openModal();});
document.querySelectorAll('.btn-edit').forEach(function(b){b.addEventListener('click',function(){openModal({id:b.dataset.id,title:b.dataset.title,desc:b.dataset.desc,course:b.dataset.course,subject:b.dataset.subject,date:b.dataset.date,dur:b.dataset.dur,marks:b.dataset.marks,venue:b.dataset.venue,status:b.dataset.status});});});
</script>
JS;
if ($auto_add) $page_js .= "<script>document.addEventListener('DOMContentLoaded',function(){openModal();})</script>";
?>
<?php include 'footer.php'; ?>
