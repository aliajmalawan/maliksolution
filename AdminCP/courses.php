<?php
declare(strict_types=1);
$page_title = 'Courses';
$active_nav = 'courses';
include 'header.php';

// Ensure new columns exist on older installs
ensure_column($conn, 'courses', 'duration', "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'courses', 'fee',      "VARCHAR(100) DEFAULT ''");
ensure_column($conn, 'courses', 'status',   "ENUM('active','inactive') DEFAULT 'active'");

$courses = mysqli_query($conn, "SELECT * FROM courses ORDER BY created_at DESC");
$count   = mysqli_num_rows($courses);

/* ── Course subjects ── */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS course_subjects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    course_id   INT NOT NULL,
    name        VARCHAR(255) NOT NULL DEFAULT '',
    description VARCHAR(500) DEFAULT '',
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$subj_by_course = [];
$_sbr = mysqli_query($conn,
    "SELECT id, course_id, name, description FROM course_subjects ORDER BY sort_order ASC, id ASC");
if ($_sbr) while ($_s = mysqli_fetch_assoc($_sbr)) {
    $subj_by_course[(int)$_s['course_id']][] = [
        'id'   => (int)$_s['id'],
        'name' => $_s['name'],
        'desc' => $_s['description'] ?? '',
    ];
}
$auto_subj_open = (int)($_GET['subj_open'] ?? 0);
?>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-book me-2 text-primary"></i>Courses</h1>
    <p>Manage courses displayed on the public courses page</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openCreateBtn">
    <i class="fa-solid fa-plus me-1"></i>Create Course
  </button>
</div>

<!-- Flash message -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $msg_map = [
      'saved'        => ['success', 'fa-circle-check',        'Course saved successfully.'],
      'deleted'      => ['warning', 'fa-trash',               'Course deleted.'],
      'subj_added'   => ['success', 'fa-circle-check',  'Subject added successfully.'],
      'subj_saved'   => ['success', 'fa-circle-check',  'Subject updated.'],
      'subj_deleted' => ['warning', 'fa-trash',          'Subject removed.'],
      'error'        => ['danger',  'fa-triangle-exclamation','An error occurred. Please try again.'],
    ];
    [$cls, $ico, $txt] = $msg_map[$_GET['msg']] ?? ['danger','fa-triangle-exclamation','Unknown error.'];
  ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
    <i class="fa-solid <?= $ico ?> me-2"></i><?= $txt ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Courses Table -->
<div class="cardx">
  <?php if ($count > 0): ?>
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:36px">#</th>
            <th>Course Name</th>
            <th>Description</th>
            <th>Duration</th>
            <th>Fee</th>
            <th>Status</th>
            <th>Added</th>
            <th style="width:145px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($c = mysqli_fetch_assoc($courses)): ?>
            <tr>
              <td class="text-muted small"><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
              <td class="text-muted small" style="max-width:220px">
                <?php $d = $c['description'] ?? ''; ?>
                <?= htmlspecialchars(mb_strlen($d) > 65 ? mb_substr($d,0,65).'…' : $d) ?>
              </td>
              <td class="small"><?= htmlspecialchars($c['duration'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
              <td class="small"><?= htmlspecialchars($c['fee'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
              <td>
                <?php $st = $c['status'] ?? 'active'; ?>
                <span class="status-badge <?= $st === 'active' ? 'sb-approved' : 'sb-rejected' ?>">
                  <?= ucfirst($st) ?>
                </span>
              </td>
              <td class="text-muted small"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
              <?php $sc = count($subj_by_course[$c['id']] ?? []); ?>
              <td>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-primary btn-edit"
                    data-id="<?= $c['id'] ?>"
                    data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
                    data-desc="<?= htmlspecialchars($c['description'] ?? '', ENT_QUOTES) ?>"
                    data-duration="<?= htmlspecialchars($c['duration'] ?? '', ENT_QUOTES) ?>"
                    data-fee="<?= htmlspecialchars($c['fee'] ?? '', ENT_QUOTES) ?>"
                    data-status="<?= htmlspecialchars($c['status'] ?? 'active', ENT_QUOTES) ?>"
                    title="Edit Course"><i class="fa-solid fa-pen fa-sm"></i></button>
                  <button class="btn btn-outline-success btn-subjects"
                    data-id="<?= $c['id'] ?>"
                    data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
                    title="Manage Subjects">
                    <i class="fa-solid fa-list me-1"></i><?= $sc ?>
                  </button>
                  <button class="btn btn-outline-danger btn-delete"
                    data-id="<?= $c['id'] ?>"
                    data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
                    title="Delete Course"><i class="fa-solid fa-trash fa-sm"></i></button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-book-open"></i>
      <p>No courses yet.<br>Click <strong>Create Course</strong> to add your first course.</p>
    </div>
  <?php endif; ?>
</div>

<!-- ── Create / Edit Modal ───────────────────────────────── -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="course_action.php" id="courseForm">
        <input type="hidden" name="action"    id="f_action"    value="create">
        <input type="hidden" name="course_id" id="f_course_id" value="">

        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="courseModalLabel">
            <i class="fa-solid fa-book me-2"></i>Create Course
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Course Name -->
            <div class="col-12">
              <label class="form-label">Course Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="f_name" required
                placeholder="e.g. FSc Pre Medical">
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="f_desc" rows="3"
                placeholder="Brief description of the course, subjects covered, eligibility, etc."></textarea>
            </div>

            <!-- Duration & Fee -->
            <div class="col-md-6">
              <label class="form-label">Duration</label>
              <input type="text" class="form-control" name="duration" id="f_duration"
                placeholder="e.g. 2 Years">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fee</label>
              <input type="text" class="form-control" name="fee" id="f_fee"
                placeholder="e.g. Rs. 50,000 / year">
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="f_status">
                <option value="active">Active (shown on website)</option>
                <option value="inactive">Inactive (hidden from website)</option>
              </select>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="f_submit_btn">
            <i class="fa-solid fa-plus me-1"></i>Add Course
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Delete Confirm Modal ──────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Course</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Delete <strong id="del_name"></strong>?<br>
          <small class="text-muted">This cannot be undone.</small></p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="course_action.php">
          <input type="hidden" name="action"    value="delete">
          <input type="hidden" name="course_id" id="del_id">
          <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa-solid fa-trash me-1"></i>Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ── Manage Subjects Modal ─────────────────────────── -->
<div class="modal fade" id="subjectModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">

      <div class="modal-header" style="background:var(--navy)">
        <h5 class="modal-title text-white">
          <i class="fa-solid fa-list me-2"></i>Subjects &mdash; <span id="subj_course_name"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- Current subjects list -->
        <div id="subj_list_wrap" class="mb-3"></div>

        <!-- Add subject form -->
        <div class="border-top pt-3">
          <div class="fw-semibold mb-2" style="font-size:.82rem;color:var(--navy)">
            <i class="fa-solid fa-plus-circle me-1 text-success"></i>Add New Subject
          </div>
          <form method="POST" action="course_action.php" id="addSubjForm">
            <input type="hidden" name="action"    value="add_subject">
            <input type="hidden" name="course_id" id="asf_cid">
            <div class="row g-2 align-items-end">
              <div class="col-md-5">
                <label class="form-label form-label-sm fw-semibold mb-1">
                  Subject Name <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-sm" name="subj_name"
                  id="asf_name" placeholder="e.g. Biology, Physics…" required>
              </div>
              <div class="col-md-5">
                <label class="form-label form-label-sm fw-semibold mb-1">Description</label>
                <input type="text" class="form-control form-control-sm" name="subj_desc"
                  id="asf_desc" placeholder="Optional short description">
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-success btn-sm w-100">
                  <i class="fa-solid fa-plus me-1"></i>Add
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<script>
var COURSE_SUBJ     = <?= json_encode($subj_by_course, JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var AUTO_OPEN_SUBJ  = <?= $auto_subj_open ?>;

/* Runs after Bootstrap JS is loaded (footer.php fires window.load after Bootstrap) */
window.addEventListener('load', function () {

  /* Lazy helper — never fails because Bootstrap is guaranteed loaded here */
  function modal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
  }

  /* ── Reset & open in Create mode ── */
  function openCreate() {
    document.getElementById('courseModalLabel').innerHTML =
      '<i class="fa-solid fa-plus me-2"></i>Create Course';
    document.getElementById('f_action').value    = 'create';
    document.getElementById('f_course_id').value = '';
    document.getElementById('f_name').value      = '';
    document.getElementById('f_desc').value      = '';
    document.getElementById('f_duration').value  = '';
    document.getElementById('f_fee').value       = '';
    document.getElementById('f_status').value    = 'active';
    document.getElementById('f_submit_btn').innerHTML =
      '<i class="fa-solid fa-plus me-1"></i>Add Course';
    modal('courseModal').show();
  }

  /* Create Course button */
  document.getElementById('openCreateBtn')?.addEventListener('click', openCreate);

  /* Auto-open when page is visited via ?action=create (sidebar link) */
  if (new URLSearchParams(location.search).get('action') === 'create') {
    openCreate();
  }

  /* ── Edit buttons ── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('courseModalLabel').innerHTML =
        '<i class="fa-solid fa-pen me-2"></i>Edit Course';
      document.getElementById('f_action').value    = 'edit';
      document.getElementById('f_course_id').value = d.id;
      document.getElementById('f_name').value      = d.name;
      document.getElementById('f_desc').value      = d.desc;
      document.getElementById('f_duration').value  = d.duration;
      document.getElementById('f_fee').value       = d.fee;
      document.getElementById('f_status').value    = d.status;
      document.getElementById('f_submit_btn').innerHTML =
        '<i class="fa-solid fa-save me-1"></i>Save Changes';
      modal('courseModal').show();
    });
  });

  /* ── Delete buttons ── */
  document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('del_id').value         = btn.dataset.id;
      document.getElementById('del_name').textContent = btn.dataset.name;
      modal('deleteModal').show();
    });
  });

  /* ── HTML escaper ── */
  function esc(s) {
    return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Submit edit via hidden form (avoids value-escaping issues with innerHTML) ── */
  function submitEditSubj(sid, cid, name, desc) {
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = 'course_action.php';
    [['action','edit_subject'],['subj_id',sid],['course_id',cid],
     ['subj_name',name],['subj_desc',desc]].forEach(function(p) {
      var inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = p[0]; inp.value = p[1];
      f.appendChild(inp);
    });
    document.body.appendChild(f);
    f.submit();
  }

  /* ── Attach edit-button handlers after rendering ── */
  function attachSubjEditHandlers(cid) {
    document.querySelectorAll('#subj_list_wrap .btn-edit-subj').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var row  = btn.closest('tr');
        var sid  = btn.dataset.id;
        var num  = row.querySelector('td:first-child').textContent.trim();
        var name = btn.dataset.name;
        var desc = btn.dataset.desc;

        /* Replace row with inline inputs */
        row.innerHTML =
          '<td class="text-muted small">' + esc(num) + '</td>'
          + '<td><input type="text" class="form-control form-control-sm ei-name"'
          +   ' value="' + esc(name) + '" required style="min-width:110px"></td>'
          + '<td><input type="text" class="form-control form-control-sm ei-desc"'
          +   ' value="' + esc(desc) + '" style="min-width:110px" placeholder="Description"></td>'
          + '<td>'
          +   '<div class="d-flex gap-1 flex-nowrap">'
          +     '<button type="button" class="btn btn-sm btn-success ei-save" title="Save">'
          +       '<i class="fa-solid fa-check"></i>'
          +     '</button>'
          +     '<button type="button" class="btn btn-sm btn-outline-secondary ei-cancel" title="Cancel">'
          +       '<i class="fa-solid fa-xmark"></i>'
          +     '</button>'
          +   '</div>'
          + '</td>';

        row.querySelector('.ei-name').focus();

        row.querySelector('.ei-save').addEventListener('click', function() {
          var newName = row.querySelector('.ei-name').value.trim();
          if (!newName) { row.querySelector('.ei-name').classList.add('is-invalid'); return; }
          submitEditSubj(sid, cid, newName, row.querySelector('.ei-desc').value.trim());
        });

        /* Enter key saves, Escape cancels */
        row.addEventListener('keydown', function(e) {
          if (e.key === 'Enter')  { e.preventDefault(); row.querySelector('.ei-save').click(); }
          if (e.key === 'Escape') { renderSubjects(cid); }
        });

        row.querySelector('.ei-cancel').addEventListener('click', function() {
          renderSubjects(cid);
        });
      });
    });
  }

  /* ── Render subjects list inside modal ── */
  function renderSubjects(cid) {
    var list = COURSE_SUBJ[cid] || [];
    var wrap = document.getElementById('subj_list_wrap');
    if (!list.length) {
      wrap.innerHTML =
        '<div class="text-center py-3 text-muted">'
        + '<i class="fa-solid fa-book-open d-block mb-2" style="font-size:1.8rem;color:#c5d0e0"></i>'
        + 'No subjects yet. Add one below.</div>';
      return;
    }
    var html =
      '<div class="table-responsive">'
      + '<table class="table table-sm table-hover align-middle mb-0">'
      + '<thead class="table-light"><tr>'
      + '<th style="width:36px">#</th>'
      + '<th>Subject</th>'
      + '<th>Description</th>'
      + '<th style="width:88px"></th>'
      + '</tr></thead><tbody>';
    list.forEach(function(s, i) {
      html +=
        '<tr>'
        + '<td class="text-muted small">' + (i+1) + '</td>'
        + '<td><strong>' + esc(s.name) + '</strong></td>'
        + '<td class="text-muted small">' + (s.desc ? esc(s.desc) : '&mdash;') + '</td>'
        + '<td>'
        +   '<div class="d-flex gap-1 justify-content-end">'
        +     '<button class="btn btn-sm btn-outline-primary btn-edit-subj"'
        +       ' data-id="' + s.id + '"'
        +       ' data-name="' + esc(s.name) + '"'
        +       ' data-desc="' + esc(s.desc) + '"'
        +       ' data-cid="' + cid + '" title="Edit">'
        +       '<i class="fa-solid fa-pen fa-sm"></i>'
        +     '</button>'
        +     '<form method="POST" action="course_action.php" style="display:inline"'
        +           ' onsubmit="return confirm(\'Delete ' + esc(s.name) + '?\')">'
        +       '<input type="hidden" name="action"    value="del_subject">'
        +       '<input type="hidden" name="subj_id"   value="' + s.id + '">'
        +       '<input type="hidden" name="course_id" value="' + cid + '">'
        +       '<button class="btn btn-sm btn-outline-danger" title="Delete">'
        +         '<i class="fa-solid fa-trash fa-sm"></i>'
        +       '</button>'
        +     '</form>'
        +   '</div>'
        + '</td></tr>';
    });
    html += '</tbody></table></div>';
    wrap.innerHTML = html;
    attachSubjEditHandlers(cid);
  }

  /* ── Subjects buttons (table row → open modal) ── */
  document.querySelectorAll('.btn-subjects').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var cid = parseInt(btn.dataset.id);
      document.getElementById('subj_course_name').textContent = btn.dataset.name;
      document.getElementById('asf_cid').value  = cid;
      document.getElementById('asf_name').value = '';
      document.getElementById('asf_desc').value = '';
      renderSubjects(cid);
      modal('subjectModal').show();
    });
  });

  /* ── Auto-open subjects modal after add/edit/delete redirect ── */
  if (AUTO_OPEN_SUBJ > 0) {
    var autoBtn = document.querySelector('.btn-subjects[data-id="' + AUTO_OPEN_SUBJ + '"]');
    if (autoBtn) autoBtn.click();
  }

});
</script>

<?php include 'footer.php'; ?>
