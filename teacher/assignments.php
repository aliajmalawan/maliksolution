<?php
declare(strict_types=1);
$page_title = 'Assignments';
$active_nav = 'assignments';
include 'header.php';

$courses_r = mysqli_query($conn, "SELECT c.id, c.name, cs.id AS sid, cs.name AS sname FROM teacher_subject_assignments tsa JOIN course_subjects cs ON cs.id=tsa.subject_id JOIN courses c ON c.id=cs.course_id WHERE tsa.teacher_id=$tid ORDER BY c.id, cs.sort_order");
$my_courses = [];
if ($courses_r) while ($r = mysqli_fetch_assoc($courses_r)) {
    $my_courses[$r['id']] = $my_courses[$r['id']] ?? ['name' => $r['name'], 'subjects' => []];
    $my_courses[$r['id']]['subjects'][] = ['id' => $r['sid'], 'name' => $r['sname']];
}

$rows = mysqli_query($conn, "SELECT ta.*, c.name AS cname, cs.name AS sname FROM teacher_assignments ta LEFT JOIN courses c ON c.id=ta.course_id LEFT JOIN course_subjects cs ON cs.id=ta.subject_id WHERE ta.teacher_id=$tid ORDER BY ta.created_at DESC");
$auto_add = !empty($_GET['add']);
?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-<?= $_GET['msg']==='saved'?'success':'warning' ?> alert-dismissible fade show">
  <i class="fa-solid fa-<?= $_GET['msg']==='saved'?'circle-check':'trash' ?> me-2"></i>
  Assignment <?= $_GET['msg']==='saved'?'saved':'deleted' ?>.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-file-pen me-2 text-success"></i>Assignments</h1><p>Post and manage assignments for your students.</p></div>
  <button class="btn btn-success btn-sm" id="addBtn"><i class="fa-solid fa-plus me-1"></i>Add Assignment</button>
</div>

<div class="cardx">
  <?php if (mysqli_num_rows($rows) > 0): ?>
  <div class="tbl-wrap">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>Title</th><th>Course / Subject</th><th>Due Date</th><th>Marks</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while ($a = mysqli_fetch_assoc($rows)): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($a['title']) ?></strong><?php if($a['description']): ?><br><span class="text-muted" style="font-size:.72rem"><?= htmlspecialchars(mb_strimwidth($a['description'],0,60,'…')) ?></span><?php endif; ?></td>
          <td class="text-muted small"><?= htmlspecialchars($a['cname']??'—') ?><br><?= htmlspecialchars($a['sname']??'—') ?></td>
          <td class="small"><?= $a['due_date'] ? date('d M Y', strtotime($a['due_date'])) : '<span class="text-muted">—</span>' ?></td>
          <td class="small"><?= $a['total_marks'] ?></td>
          <td><span class="bs bs-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
          <td>
            <button class="btn btn-sm btn-outline-success btn-edit"
              data-id="<?= $a['id'] ?>" data-title="<?= htmlspecialchars($a['title'],ENT_QUOTES) ?>"
              data-desc="<?= htmlspecialchars($a['description']??'',ENT_QUOTES) ?>"
              data-course="<?= $a['course_id'] ?>" data-subject="<?= $a['subject_id'] ?>"
              data-due="<?= $a['due_date'] ?? '' ?>" data-marks="<?= $a['total_marks'] ?>"
              data-status="<?= $a['status'] ?>"><i class="fa-solid fa-pen fa-sm"></i></button>
            <form method="POST" action="action.php" class="d-inline" onsubmit="return confirm('Delete this assignment?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="assign_delete">
              <input type="hidden" name="id" value="<?= $a['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash fa-sm"></i></button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="empty-st"><i class="fa-solid fa-file-pen"></i><p>No assignments yet.</p></div>
  <?php endif; ?>
</div>

<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="action.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="assign_save">
        <input type="hidden" name="id" id="f_id" value="0">
        <div class="modal-header" style="background:#198754">
          <h5 class="modal-title text-white" id="modalLabel"><i class="fa-solid fa-file-pen me-2"></i>Add Assignment</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" id="f_title" class="form-control" required placeholder="e.g. Lab Report — Cell Division">
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
            <div class="col-md-4">
              <label class="form-label fw-bold">Due Date</label>
              <input type="date" name="due_date" id="f_due" class="form-control">
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
              <textarea name="description" id="f_desc" class="form-control" rows="4" placeholder="Assignment details and requirements…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" id="f_submit"><i class="fa-solid fa-save me-1"></i>Save Assignment</button>
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
function openModal(d){d=d||{};document.getElementById('f_id').value=d.id||0;document.getElementById('f_title').value=d.title||'';document.getElementById('f_desc').value=d.desc||'';document.getElementById('f_due').value=d.due||'';document.getElementById('f_marks').value=d.marks||100;document.getElementById('f_status').value=d.status||'draft';document.getElementById('f_course').value=d.course||0;fillSubjects(d.course||0,d.subject||0);document.getElementById('modalLabel').innerHTML=d.id?'<i class="fa-solid fa-pen me-2"></i>Edit Assignment':'<i class="fa-solid fa-file-pen me-2"></i>Add Assignment';document.getElementById('f_submit').innerHTML=d.id?'<i class="fa-solid fa-save me-1"></i>Save Changes':'<i class="fa-solid fa-save me-1"></i>Save Assignment';bootstrap.Modal.getOrCreateInstance(document.getElementById('assignModal')).show();}
document.getElementById('addBtn').addEventListener('click',function(){openModal();});
document.querySelectorAll('.btn-edit').forEach(function(b){b.addEventListener('click',function(){openModal({id:b.dataset.id,title:b.dataset.title,desc:b.dataset.desc,course:b.dataset.course,subject:b.dataset.subject,due:b.dataset.due,marks:b.dataset.marks,status:b.dataset.status});});});
</script>
JS;
if ($auto_add) $page_js .= "<script>document.addEventListener('DOMContentLoaded',function(){openModal();})</script>";
?>
<?php include 'footer.php'; ?>
