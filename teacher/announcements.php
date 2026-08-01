<?php
declare(strict_types=1);
$page_title = 'Announcements';
$active_nav = 'announcements';
include 'header.php';

$courses_r = mysqli_query($conn, "SELECT DISTINCT c.id, c.name FROM teacher_subject_assignments tsa JOIN course_subjects cs ON cs.id=tsa.subject_id JOIN courses c ON c.id=cs.course_id WHERE tsa.teacher_id=$tid ORDER BY c.id");
$my_courses = [];
if ($courses_r) while ($r = mysqli_fetch_assoc($courses_r)) $my_courses[$r['id']] = $r['name'];

$rows = mysqli_query($conn, "SELECT ta.*, c.name AS cname FROM teacher_announcements ta LEFT JOIN courses c ON c.id=ta.course_id WHERE ta.teacher_id=$tid ORDER BY ta.created_at DESC");
$auto_add = !empty($_GET['add']);
?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-<?= $_GET['msg']==='saved'?'success':'warning' ?> alert-dismissible fade show">
  <i class="fa-solid fa-<?= $_GET['msg']==='saved'?'circle-check':'trash' ?> me-2"></i>
  Announcement <?= $_GET['msg']==='saved'?'saved':'deleted' ?>.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-bullhorn me-2 text-info"></i>Announcements</h1><p>Post notices and important messages for your students.</p></div>
  <button class="btn btn-info btn-sm text-white" id="addBtn"><i class="fa-solid fa-plus me-1"></i>Post Announcement</button>
</div>

<div class="cardx">
  <?php if (mysqli_num_rows($rows) > 0): ?>
  <div class="tbl-wrap">
    <table class="table align-middle">
      <thead><tr><th>#</th><th>Title</th><th>Course</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while ($a = mysqli_fetch_assoc($rows)): ?>
        <tr>
          <td class="text-muted"><?= $i++ ?></td>
          <td>
            <strong><?= htmlspecialchars($a['title']) ?></strong>
            <?php if ($a['content']): ?><br><span class="text-muted" style="font-size:.72rem"><?= htmlspecialchars(mb_strimwidth($a['content'],0,80,'…')) ?></span><?php endif; ?>
          </td>
          <td class="small text-muted"><?= htmlspecialchars($a['cname'] ?? 'All Courses') ?></td>
          <td><span class="bs bs-<?= $a['priority'] ?>"><?= ucfirst($a['priority']) ?></span></td>
          <td><span class="bs bs-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
          <td class="small text-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
          <td>
            <button class="btn btn-sm btn-outline-info btn-edit"
              data-id="<?= $a['id'] ?>" data-title="<?= htmlspecialchars($a['title'],ENT_QUOTES) ?>"
              data-content="<?= htmlspecialchars($a['content']??'',ENT_QUOTES) ?>"
              data-course="<?= $a['course_id'] ?>" data-priority="<?= $a['priority'] ?>"
              data-status="<?= $a['status'] ?>"><i class="fa-solid fa-pen fa-sm"></i></button>
            <form method="POST" action="action.php" class="d-inline" onsubmit="return confirm('Delete this announcement?')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <input type="hidden" name="action" value="ann_delete">
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
    <div class="empty-st"><i class="fa-solid fa-bullhorn"></i><p>No announcements yet.</p></div>
  <?php endif; ?>
</div>

<div class="modal fade" id="annModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="action.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="ann_save">
        <input type="hidden" name="id" id="f_id" value="0">
        <div class="modal-header" style="background:#0e7490">
          <h5 class="modal-title text-white" id="modalLabel"><i class="fa-solid fa-bullhorn me-2"></i>Post Announcement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" id="f_title" class="form-control" required placeholder="Announcement heading">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Course</label>
              <select name="course_id" id="f_course" class="form-select">
                <option value="0">All Courses</option>
                <?php foreach ($my_courses as $cid2 => $cname2): ?>
                <option value="<?= $cid2 ?>"><?= htmlspecialchars($cname2) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Priority</label>
              <select name="priority" id="f_priority" class="form-select">
                <option value="normal">Normal</option>
                <option value="important">Important</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold">Status</label>
              <select name="status" id="f_status" class="form-select">
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">Message</label>
              <textarea name="content" id="f_content" class="form-control" rows="5" placeholder="Full announcement text…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info text-white" id="f_submit"><i class="fa-solid fa-paper-plane me-1"></i>Post Announcement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php $page_js = <<<JS
<script>
function openModal(d){d=d||{};document.getElementById('f_id').value=d.id||0;document.getElementById('f_title').value=d.title||'';document.getElementById('f_content').value=d.content||'';document.getElementById('f_course').value=d.course||0;document.getElementById('f_priority').value=d.priority||'normal';document.getElementById('f_status').value=d.status||'active';document.getElementById('modalLabel').innerHTML=d.id?'<i class="fa-solid fa-pen me-2"></i>Edit Announcement':'<i class="fa-solid fa-bullhorn me-2"></i>Post Announcement';document.getElementById('f_submit').innerHTML=d.id?'<i class="fa-solid fa-save me-1"></i>Save Changes':'<i class="fa-solid fa-paper-plane me-1"></i>Post Announcement';bootstrap.Modal.getOrCreateInstance(document.getElementById('annModal')).show();}
document.getElementById('addBtn').addEventListener('click',function(){openModal();});
document.querySelectorAll('.btn-edit').forEach(function(b){b.addEventListener('click',function(){openModal({id:b.dataset.id,title:b.dataset.title,content:b.dataset.content,course:b.dataset.course,priority:b.dataset.priority,status:b.dataset.status});});});
</script>
JS;
if ($auto_add) $page_js .= "<script>document.addEventListener('DOMContentLoaded',function(){openModal();})</script>";
?>
<?php include 'footer.php'; ?>
