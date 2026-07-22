<?php
declare(strict_types=1);
$page_title = 'Teachers';
$active_nav = 'teachers';
include 'header.php';

// Ensure teachers table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teachers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    designation   VARCHAR(255) DEFAULT '',
    department    VARCHAR(255) DEFAULT '',
    qualification VARCHAR(255) DEFAULT '',
    experience    VARCHAR(100) DEFAULT '',
    email         VARCHAR(255) DEFAULT '',
    phone         VARCHAR(20)  DEFAULT '',
    photo         VARCHAR(255) DEFAULT '',
    bio           TEXT,
    status        ENUM('active','inactive') DEFAULT 'active',
    sort_order    INT DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure assignment table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS teacher_subject_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL, subject_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_subject (teacher_id, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Teachers with subject count
$teachers_result = mysqli_query($conn, "
    SELECT t.*, COUNT(tsa.id) AS subj_count
    FROM teachers t
    LEFT JOIN teacher_subject_assignments tsa ON tsa.teacher_id = t.id
    GROUP BY t.id
    ORDER BY t.sort_order ASC, t.name ASC
");
$count = mysqli_num_rows($teachers_result);

// All active course subjects grouped by course (for assignment modal)
$courses_with_subjects = [];
$_cwr = mysqli_query($conn, "SELECT id, name FROM courses WHERE status='active' ORDER BY id ASC");
while ($_cw = mysqli_fetch_assoc($_cwr)) {
    $cid = (int)$_cw['id'];
    $subs = [];
    $_sr  = mysqli_query($conn, "SELECT id, name FROM course_subjects WHERE course_id=$cid ORDER BY sort_order ASC, id ASC");
    while ($_s = mysqli_fetch_assoc($_sr)) $subs[] = ['id' => (int)$_s['id'], 'name' => $_s['name']];
    if (!empty($subs)) $courses_with_subjects[(string)$cid] = ['name' => $_cw['name'], 'subjects' => $subs];
}

// Current assignments for every teacher
$all_assignments = [];
$_ar = mysqli_query($conn, "
    SELECT tsa.teacher_id, tsa.subject_id, cs.name AS subject_name, cs.course_id, c.name AS course_name
    FROM teacher_subject_assignments tsa
    JOIN course_subjects cs ON cs.id  = tsa.subject_id
    JOIN courses         c  ON c.id   = cs.course_id
    ORDER BY c.id ASC, cs.sort_order ASC, cs.id ASC
");
if ($_ar) while ($_a = mysqli_fetch_assoc($_ar)) {
    $all_assignments[(int)$_a['teacher_id']][] = [
        'subject_id'   => (int)$_a['subject_id'],
        'subject_name' => $_a['subject_name'],
        'course_id'    => (int)$_a['course_id'],
        'course_name'  => $_a['course_name'],
    ];
}

$auto_subj_open = (int)($_GET['subj_open'] ?? 0);
?>

<style>
/* ═══════════════════════════════════════════════
   TEACHERS PAGE — responsive fixes
═══════════════════════════════════════════════ */

/* ── KEY FIX: cap the modal-body height directly so the footer
      is always visible. 185px = header(~58px) + footer(~60px)
      + modal top+bottom margin(~67px). ── */
#teacherModal .modal-body {
  max-height: calc(100vh - 185px) !important;
  overflow-y: auto !important;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior-y: contain;
  padding: .9rem 1.1rem !important;
  /* visible thin scrollbar */
  scrollbar-width: thin;
  scrollbar-color: #c5d0e0 transparent;
}
#teacherModal .modal-body::-webkit-scrollbar { width: 5px; }
#teacherModal .modal-body::-webkit-scrollbar-thumb { background: #c5d0e0; border-radius: 4px; }

/* Subject modal body */
#subjectModal .modal-body {
  max-height: calc(100vh - 185px) !important;
  overflow-y: auto !important;
  -webkit-overflow-scrolling: touch;
}

/* Footer always sticks to bottom of the modal (outside the scrollable body) */
#teacherModal .modal-footer {
  flex-shrink: 0 !important;
  position: sticky;
  bottom: 0;
  background: #fff;
  border-top: 1px solid var(--line);
  padding: .65rem 1rem;
  gap: .5rem;
  z-index: 5;
}

/* ── Compact form fields ── */
#teacherModal .form-label  { font-size: .82rem; margin-bottom: .2rem; }
#teacherModal .form-text   { font-size: .72rem; margin-top: .18rem; }
#teacherModal .form-control,
#teacherModal .form-select { font-size: .875rem; padding: .32rem .65rem; }
#teacherModal textarea.form-control { resize: vertical; min-height: 60px; }
#teacherModal .row.g-3 { --bs-gutter-y: .55rem; }

/* Photo preview */
#teacherModal #photoPreview,
#teacherModal #photoPlaceholder {
  width: 56px !important; height: 56px !important; font-size: 1.25rem !important;
}

/* ── Mobile (≤767px): fullscreen ── */
@media (max-width: 767px) {
  #teacherModal .modal-body,
  #subjectModal .modal-body {
    max-height: calc(100vh - 130px) !important;
  }
  #teacherModal .modal-dialog,
  #subjectModal .modal-dialog {
    margin: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
  }
  #teacherModal .modal-content,
  #subjectModal .modal-content {
    border-radius: 0 !important;
    min-height: 100vh;
  }
  #teacherModal .modal-body { padding: .7rem .85rem !important; }
}

/* ── Small phones (≤480px) ── */
@media (max-width: 480px) {
  #teacherModal .modal-body { max-height: calc(100vh - 115px) !important; }
  #teacherModal .col-md-6,
  #teacherModal .col-md-4 { flex: 0 0 100%; max-width: 100%; }
}

/* ── Table: hide non-essential columns on small screens ── */
@media (max-width: 991px) {
  .th-hide-md, .td-hide-md { display: none !important; }
}
@media (max-width: 575px) {
  .th-hide-sm, .td-hide-sm { display: none !important; }
  .table { font-size: .8rem; }
}

@keyframes bounce { from { transform: translateY(0); } to { transform: translateY(4px); } }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-chalkboard-user me-2 text-primary"></i>Teachers</h1>
    <p>Manage faculty members and their subject assignments shown on the public Faculty page</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openCreateBtn">
    <i class="fa-solid fa-plus me-1"></i>Add Teacher
  </button>
</div>

<!-- Flash -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $msg_map = [
      'saved'        => ['success','fa-circle-check',        'Teacher saved successfully.'],
      'deleted'      => ['warning','fa-trash',               'Teacher deleted.'],
      'subj_assigned'=> ['success','fa-circle-check',        'Subject assigned successfully.'],
      'subj_removed' => ['warning','fa-circle-minus',        'Subject assignment removed.'],
      'creds_reset'  => ['success','fa-key',                 'Portal credentials reset successfully.'],
      'pw_short'     => ['danger', 'fa-triangle-exclamation','Password must be at least 6 characters.'],
      'error'        => ['danger', 'fa-triangle-exclamation','An error occurred. Please try again.'],
    ];
    [$cls, $ico, $txt] = $msg_map[$_GET['msg']] ?? ['danger','fa-triangle-exclamation','Unknown error.'];
  ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
    <i class="fa-solid <?= $ico ?> me-2"></i><?= $txt ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php
/* ── New-teacher credentials (one-time flash) ── */
if (!empty($_SESSION['new_teacher_creds'])):
    $nc = $_SESSION['new_teacher_creds'];
    unset($_SESSION['new_teacher_creds']);
?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" style="border-radius:12px">
  <div class="d-flex align-items-start gap-3 flex-wrap">
    <i class="fa-solid fa-key fa-lg mt-1" style="color:#198754"></i>
    <div style="flex:1;min-width:0">
      <strong class="d-block mb-1">Teacher Portal Credentials Generated</strong>
      <p class="mb-2 text-muted small">Share these login details with <strong><?= htmlspecialchars($nc['name']) ?></strong>. The password will not be shown again — copy it now.</p>
      <div class="d-flex flex-wrap gap-3">
        <div class="p-2 px-3 rounded" style="background:#e8f5e9;font-family:monospace">
          <span class="text-muted" style="font-size:.72rem">USERNAME</span><br>
          <strong style="font-size:1rem;color:#1b5e20;letter-spacing:.05em"><?= htmlspecialchars($nc['username']) ?></strong>
        </div>
        <div class="p-2 px-3 rounded" style="background:#e8f5e9;font-family:monospace">
          <span class="text-muted" style="font-size:.72rem">PASSWORD</span><br>
          <strong style="font-size:1rem;color:#1b5e20;letter-spacing:.1em"><?= htmlspecialchars($nc['password']) ?></strong>
        </div>
        <div class="p-2 px-3 rounded" style="background:#e8f5e9;font-family:monospace">
          <span class="text-muted" style="font-size:.72rem">PORTAL URL</span><br>
          <strong style="font-size:.9rem;color:#1b5e20">../Teacher/login.php</strong>
        </div>
      </div>
    </div>
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php
/* ── Bulk credentials (existing teachers on first load) ── */
if (!empty($_SESSION['bulk_creds'])):
    $bc = $_SESSION['bulk_creds'];
    unset($_SESSION['bulk_creds']);
?>
<div class="alert alert-info alert-dismissible fade show border-0 shadow-sm" style="border-radius:12px">
  <strong><i class="fa-solid fa-key me-2"></i>Portal Credentials Auto-Generated for Existing Teachers</strong>
  <p class="mt-1 mb-2 small">These are one-time generated credentials. Copy them and share with your teachers. Passwords will not be shown again — use "Reset Password" in the actions column if needed.</p>
  <div class="table-responsive">
    <table class="table table-sm table-bordered mb-0" style="font-size:.82rem;background:#fff;border-radius:8px;overflow:hidden">
      <thead style="background:#e3f2fd"><tr><th>#</th><th>Name</th><th>Username</th><th>Password</th></tr></thead>
      <tbody>
        <?php foreach ($bc as $bi => $brow): ?>
        <tr>
          <td><?= $bi+1 ?></td>
          <td><?= htmlspecialchars($brow['name']) ?></td>
          <td><code><?= htmlspecialchars($brow['username']) ?></code></td>
          <td><code><?= htmlspecialchars($brow['password']) ?></code></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Teachers Table ── -->
<div class="cardx">
  <?php if ($count > 0): ?>
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th class="th-hide-sm" style="width:36px">#</th>
            <th style="width:56px">Photo</th>
            <th>Name</th>
            <th class="th-hide-sm">Designation</th>
            <th class="th-hide-md">Department</th>
            <th class="th-hide-md">Qualification</th>
            <th class="th-hide-md">Experience</th>
            <th>Status</th>
            <th class="th-hide-md" style="width:56px">Order</th>
            <th style="width:130px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($t = mysqli_fetch_assoc($teachers_result)): ?>
            <tr>
              <td class="text-muted small td-hide-sm"><?= $i++ ?></td>
              <td>
                <?php if (!empty($t['photo'])): ?>
                  <img src="../assets/uploads/teachers/<?= htmlspecialchars($t['photo']) ?>"
                       width="44" height="44"
                       style="object-fit:cover;border-radius:8px;border:1px solid var(--line)">
                <?php else: ?>
                  <div style="width:44px;height:44px;border-radius:8px;background:var(--soft);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;color:#a0aec0">
                    <i class="fa-solid fa-user"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= htmlspecialchars($t['name']) ?></strong>
                <?php if (!empty($t['email'])): ?>
                  <br><span class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($t['email']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-muted small td-hide-sm"><?= htmlspecialchars($t['designation'])   ?: '<span class="text-muted">—</span>' ?></td>
              <td class="text-muted small td-hide-md"><?= htmlspecialchars($t['department'])    ?: '<span class="text-muted">—</span>' ?></td>
              <td class="text-muted small td-hide-md"><?= htmlspecialchars($t['qualification']) ?: '<span class="text-muted">—</span>' ?></td>
              <td class="text-muted small td-hide-md"><?= htmlspecialchars($t['experience'])    ?: '<span class="text-muted">—</span>' ?></td>
              <td>
                <span class="status-badge <?= $t['status'] === 'active' ? 'sb-approved' : 'sb-rejected' ?>">
                  <?= ucfirst($t['status']) ?>
                </span>
              </td>
              <td class="small text-muted text-center td-hide-md"><?= (int)$t['sort_order'] ?></td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-primary btn-edit"
                    data-id="<?=           $t['id'] ?>"
                    data-name="<?=         htmlspecialchars($t['name'],                ENT_QUOTES) ?>"
                    data-designation="<?=  htmlspecialchars($t['designation'],         ENT_QUOTES) ?>"
                    data-department="<?=   htmlspecialchars($t['department'],          ENT_QUOTES) ?>"
                    data-qualification="<?=htmlspecialchars($t['qualification'],       ENT_QUOTES) ?>"
                    data-experience="<?=   htmlspecialchars($t['experience'],          ENT_QUOTES) ?>"
                    data-email="<?=        htmlspecialchars($t['email'],               ENT_QUOTES) ?>"
                    data-phone="<?=        htmlspecialchars($t['phone'],               ENT_QUOTES) ?>"
                    data-bio="<?=          htmlspecialchars($t['bio'] ?? '',           ENT_QUOTES) ?>"
                    data-status="<?=       htmlspecialchars($t['status'],              ENT_QUOTES) ?>"
                    data-order="<?=        (int)$t['sort_order'] ?>"
                    data-photo="<?=        htmlspecialchars($t['photo'],               ENT_QUOTES) ?>"
                    data-username="<?=     htmlspecialchars($t['teacher_username'] ?? '',  ENT_QUOTES) ?>"
                    data-password="<?=     htmlspecialchars($t['teacher_password'] ?? '',  ENT_QUOTES) ?>"
                    title="Edit Teacher"><i class="fa-solid fa-pen fa-sm"></i></button>

                  <button class="btn btn-outline-success btn-subjects"
                    data-id="<?= $t['id'] ?>"
                    data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>"
                    title="Assign Subjects">
                    <i class="fa-solid fa-book-open fa-sm"></i>
                    <span class="ms-1 badge rounded-pill"
                          style="background:<?= (int)$t['subj_count'] > 0 ? '#198754' : '#6c757d' ?>;font-size:.65rem">
                      <?= (int)$t['subj_count'] ?>
                    </span>
                  </button>

                  <button class="btn btn-outline-danger btn-delete"
                    data-id="<?= $t['id'] ?>"
                    data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>"
                    title="Delete Teacher"><i class="fa-solid fa-trash fa-sm"></i></button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-chalkboard-user"></i>
      <p>No teachers added yet.<br>Click <strong>Add Teacher</strong> to add your first faculty member.</p>
    </div>
  <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════
     CREATE / EDIT MODAL
══════════════════════════════════════════ -->
<div class="modal fade" id="teacherModal" tabindex="-1" aria-labelledby="teacherModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="teacher_action.php" enctype="multipart/form-data" id="teacherForm">
        <input type="hidden" name="action"     id="f_action"    value="create">
        <input type="hidden" name="teacher_id" id="f_id"        value="">
        <input type="hidden" name="old_photo"  id="f_old_photo" value="">

        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="teacherModalLabel">
            <i class="fa-solid fa-plus me-2"></i>Add Teacher
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Photo -->
            <div class="col-12">
              <label class="form-label fw-semibold">Photo</label>
              <div class="d-flex align-items-center gap-3">
                <div style="flex-shrink:0">
                  <img id="photoPreview" src="" alt="" width="72" height="72"
                       style="object-fit:cover;border-radius:10px;border:1px solid var(--line);display:none">
                  <div id="photoPlaceholder"
                       style="width:72px;height:72px;border-radius:10px;border:2px dashed var(--line);background:var(--soft);display:flex;align-items:center;justify-content:center;color:#b0bec5;font-size:1.6rem">
                    <i class="fa-solid fa-user"></i>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <input type="file" class="form-control form-control-sm" name="photo"
                         id="f_photo" accept="image/jpeg,image/png,image/webp,image/gif">
                  <div class="form-text">JPG, PNG or WebP — max 5 MB. Leave blank when editing to keep current photo.</div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="f_name" required
                     placeholder="e.g. Dr. Ahmed Khan">
            </div>
            <div class="col-md-6">
              <label class="form-label">Designation</label>
              <input type="text" class="form-control" name="designation" id="f_designation"
                     placeholder="e.g. Head of Department, Lecturer">
            </div>
            <div class="col-md-6">
              <label class="form-label">Department / Subject</label>
              <input type="text" class="form-control" name="department" id="f_department"
                     placeholder="e.g. Biology, Mathematics">
            </div>
            <div class="col-md-6">
              <label class="form-label">Qualification</label>
              <input type="text" class="form-control" name="qualification" id="f_qualification"
                     placeholder="e.g. MBBS, M.Sc Chemistry, PhD">
            </div>
            <div class="col-md-4">
              <label class="form-label">Experience</label>
              <input type="text" class="form-control" name="experience" id="f_experience"
                     placeholder="e.g. 10 Years">
            </div>
            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" id="f_email"
                     placeholder="teacher@maliksolution.edu.pk">
            </div>
            <div class="col-md-4">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" name="phone" id="f_phone"
                     placeholder="e.g. 0300-0000000">
            </div>
            <div class="col-12">
              <label class="form-label">Short Bio</label>
              <textarea class="form-control" name="bio" id="f_bio" rows="2"
                placeholder="Brief background and teaching expertise..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="f_status">
                <option value="active">Active — shown on website</option>
                <option value="inactive">Inactive — hidden from website</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" class="form-control" name="sort_order" id="f_sort_order" min="0" value="0">
              <div class="form-text">Lower number = shown first on faculty page.</div>
            </div>

            <!-- Portal Credentials — only shown when editing an existing teacher -->
            <div class="col-12" id="portalCredsSection" style="display:none">
              <hr class="my-2">
              <div class="p-3 rounded-3" style="background:#edf4ff;border:1px solid #b8d4fb">
                <div class="fw-bold mb-2" style="color:var(--navy)">
                  <i class="fa-solid fa-key me-1 text-primary"></i>Teacher Portal Credentials
                </div>
                <div class="row g-2">
                  <div class="col-sm-6">
                    <label class="form-label fw-bold mb-1 small">Teacher ID / Username</label>
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control font-monospace fw-bold"
                             id="f_cred_username" readonly>
                      <button class="btn btn-outline-secondary btn-copy" data-target="f_cred_username" type="button" title="Copy">
                        <i class="fa-solid fa-copy"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label fw-bold mb-1 small">Password</label>
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control font-monospace fw-bold"
                             id="f_cred_password" readonly>
                      <button class="btn btn-outline-secondary btn-copy" data-target="f_cred_password" type="button" title="Copy">
                        <i class="fa-solid fa-copy"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <hr class="my-3">
              <div class="fw-semibold small mb-2" style="color:var(--navy)">
                <i class="fa-solid fa-key me-1 text-warning"></i>Change Password
                <span class="text-muted fw-normal ms-1">— leave blank to keep current password</span>
              </div>
              <div class="row g-2">
                <div class="col-sm-8">
                  <div class="input-group">
                    <input type="text" class="form-control font-monospace"
                           name="new_password" id="f_new_pw"
                           placeholder="Enter new password or auto-generate">
                    <button type="button" class="btn btn-outline-secondary" id="f_gen_pw_btn" title="Auto-generate">
                      <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate
                    </button>
                  </div>
                  <div class="form-text">Minimum 6 characters if changing.</div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
          <!-- scroll hint — hidden once user reaches bottom -->
          <small class="text-muted d-flex align-items-center gap-1" id="scrollHint" style="font-size:.72rem">
            <i class="fa-solid fa-angles-down fa-xs" style="animation:bounce .9s infinite alternate"></i>
            Scroll up to fill all fields
          </small>
          <div class="d-flex gap-2 ms-auto">
            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm" id="f_submit_btn">
              <i class="fa-solid fa-plus me-1"></i>Add Teacher
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════
     SUBJECT ASSIGNMENT MODAL
══════════════════════════════════════════ -->
<div class="modal fade" id="subjectModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
    <div class="modal-content border-0 shadow">

      <div class="modal-header" style="background:var(--navy)">
        <h5 class="modal-title text-white">
          <i class="fa-solid fa-book-open me-2"></i>Subject Assignments &mdash;
          <span id="subj_teacher_name"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- Current assignments -->
        <div id="subj_assignments_wrap" class="mb-3"></div>

        <!-- Assign form -->
        <div class="border-top pt-3">
          <div class="fw-semibold mb-2" style="font-size:.82rem;color:var(--navy)">
            <i class="fa-solid fa-plus-circle me-1 text-success"></i>Assign a Subject
          </div>
          <form method="POST" action="teacher_action.php" id="assignSubjForm">
            <input type="hidden" name="action"     value="assign_subject">
            <input type="hidden" name="teacher_id" id="asf_tid">
            <div class="row g-2 align-items-end">
              <div class="col-md-4">
                <label class="form-label form-label-sm fw-semibold mb-1">Course</label>
                <select class="form-select form-select-sm" id="asf_course_sel">
                  <option value="">Select course…</option>
                  <?php foreach ($courses_with_subjects as $cid => $course): ?>
                    <option value="<?= $cid ?>"><?= htmlspecialchars($course['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-5">
                <label class="form-label form-label-sm fw-semibold mb-1">Subject <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" name="subject_id" id="asf_subj_sel" required>
                  <option value="">— select a course first —</option>
                </select>
              </div>
              <div class="col-md-3">
                <button type="submit" class="btn btn-success btn-sm w-100">
                  <i class="fa-solid fa-plus me-1"></i>Assign
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

<!-- ══════════════════════════════════════════
     DELETE CONFIRM MODAL
══════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Teacher</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Delete <strong id="del_name"></strong>?<br>
          <small class="text-muted">Photo and all subject assignments will be removed. Cannot be undone.</small></p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="teacher_action.php">
          <input type="hidden" name="action"     value="delete">
          <input type="hidden" name="teacher_id" id="del_id">
          <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i>Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
/* ── Scroll-hint: hide arrow once body is scrolled near bottom ── */
(function () {
  var hint  = document.getElementById('scrollHint');
  var mBody = document.querySelector('#teacherModal .modal-body');
  if (!mBody) { if (hint) hint.style.display = 'none'; return; }
  function checkScroll() {
    if (!hint) return;
    hint.style.display = (mBody.scrollTop + mBody.clientHeight >= mBody.scrollHeight - 20) ? 'none' : '';
  }
  mBody.addEventListener('scroll', checkScroll, { passive: true });
  document.getElementById('teacherModal').addEventListener('shown.bs.modal', function () {
    checkScroll();
    if (hint && mBody.scrollHeight <= mBody.clientHeight) hint.style.display = 'none';
  });
})();

var ALL_COURSES_SUBJECTS = <?= json_encode($courses_with_subjects, JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var ALL_ASSIGNMENTS      = <?= json_encode($all_assignments,       JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var AUTO_SUBJ_OPEN       = <?= $auto_subj_open ?>;

window.addEventListener('load', function () {
  'use strict';

  function modal(id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); }
  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── Photo helpers ── */
  function resetPhoto() {
    document.getElementById('f_photo').value = '';
    document.getElementById('photoPreview').style.display    = 'none';
    document.getElementById('photoPlaceholder').style.display = 'flex';
  }
  function showPhotoUrl(url) {
    var p = document.getElementById('photoPreview');
    p.src = url; p.style.display = 'block';
    document.getElementById('photoPlaceholder').style.display = 'none';
  }

  /* ── Create teacher ── */
  function openCreate() {
    document.getElementById('teacherModalLabel').innerHTML =
      '<i class="fa-solid fa-plus me-2"></i>Add Teacher';
    var fields = {f_action:'create',f_id:'',f_old_photo:'',f_name:'',f_designation:'',
      f_department:'',f_qualification:'',f_experience:'',f_email:'',f_phone:'',f_bio:'',
      f_status:'active',f_sort_order:'0'};
    for (var k in fields) document.getElementById(k).value = fields[k];
    document.getElementById('f_submit_btn').innerHTML =
      '<i class="fa-solid fa-plus me-1"></i>Add Teacher';
    document.getElementById('portalCredsSection').style.display = 'none';
    resetPhoto();
    modal('teacherModal').show();
  }
  document.getElementById('openCreateBtn')?.addEventListener('click', openCreate);

  /* ── Edit teacher ── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('teacherModalLabel').innerHTML =
        '<i class="fa-solid fa-pen me-2"></i>Edit Teacher';
      var map = {f_action:'edit',f_id:d.id,f_old_photo:d.photo,f_name:d.name,
        f_designation:d.designation,f_department:d.department,
        f_qualification:d.qualification,f_experience:d.experience,
        f_email:d.email,f_phone:d.phone,f_bio:d.bio,
        f_status:d.status,f_sort_order:d.order};
      for (var k in map) document.getElementById(k).value = map[k];
      document.getElementById('f_photo').value = '';
      document.getElementById('f_submit_btn').innerHTML =
        '<i class="fa-solid fa-save me-1"></i>Save Changes';
      if (d.photo) showPhotoUrl('../assets/uploads/teachers/' + encodeURIComponent(d.photo));
      else resetPhoto();
      // Portal credentials panel
      document.getElementById('f_cred_username').value = d.username || '(not set)';
      document.getElementById('f_cred_password').value = d.password || '(unknown — use Change Password below)';
      document.getElementById('f_new_pw').value = '';
      document.getElementById('portalCredsSection').style.display = '';
      modal('teacherModal').show();
    });
  });

  /* ── Auto-generate password in edit modal ── */
  document.getElementById('f_gen_pw_btn').addEventListener('click', function () {
    var upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    var lower  = 'abcdefghjkmnpqrstuvwxyz';
    var digits = '23456789';
    var pw = 'Ms@'
      + upper[Math.floor(Math.random() * upper.length)]
      + digits[Math.floor(Math.random() * digits.length)]
      + lower[Math.floor(Math.random() * lower.length)]
      + digits[Math.floor(Math.random() * digits.length)];
    document.getElementById('f_new_pw').value = pw;
  });

  /* ── Copy buttons ── */
  document.querySelectorAll('.btn-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.getElementById(btn.getAttribute('data-target'));
      if (!el) return;
      navigator.clipboard.writeText(el.value).then(function () {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        setTimeout(function () { btn.innerHTML = orig; }, 1500);
      });
    });
  });

  /* ── Delete teacher ── */
  document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('del_id').value         = btn.dataset.id;
      document.getElementById('del_name').textContent = btn.dataset.name;
      modal('deleteModal').show();
    });
  });

  /* ── Live photo preview ── */
  document.getElementById('f_photo')?.addEventListener('change', function () {
    var f = this.files[0]; if (!f) return;
    var r = new FileReader();
    r.onload = function (e) { showPhotoUrl(e.target.result); };
    r.readAsDataURL(f);
  });

  /* ═══════════════════════════════════════════
     SUBJECT ASSIGNMENT MODAL
  ═══════════════════════════════════════════ */
  var currentTid = 0;

  function renderAssignments(tid) {
    var list = ALL_ASSIGNMENTS[tid] || [];
    var wrap = document.getElementById('subj_assignments_wrap');

    if (!list.length) {
      wrap.innerHTML =
        '<div class="text-center py-3 text-muted">'
        + '<i class="fa-solid fa-book-open d-block mb-2" style="font-size:1.8rem;color:#c5d0e0"></i>'
        + 'No subjects assigned yet. Use the form below to assign one.</div>';
      return;
    }

    // Group by course
    var byCourse = {};
    list.forEach(function (a) {
      if (!byCourse[a.course_id]) byCourse[a.course_id] = { name: a.course_name, items: [] };
      byCourse[a.course_id].items.push(a);
    });

    var html = '';
    Object.keys(byCourse).forEach(function (cid) {
      var g = byCourse[cid];
      html += '<div class="mb-3">';
      html += '<div class="fw-semibold text-navy mb-2" style="font-size:.82rem">'
            + '<i class="fa-solid fa-graduation-cap me-1 text-primary"></i>'
            + esc(g.name) + '</div>';
      html += '<div class="d-flex flex-wrap gap-2">';
      g.items.forEach(function (a) {
        html +=
          '<span class="badge border d-inline-flex align-items-center gap-1 px-2 py-1" '
          + 'style="background:#edf4ff;color:#0a3478;border-color:#c4d9f5!important;font-size:.8rem;font-weight:600">'
          + esc(a.subject_name)
          + '<form method="POST" action="teacher_action.php" class="d-inline"'
          + '      onsubmit="return confirm(\'Remove ' + esc(a.subject_name) + ' from this teacher?\')">'
          + '<input type="hidden" name="action"     value="remove_subject">'
          + '<input type="hidden" name="teacher_id" value="' + tid + '">'
          + '<input type="hidden" name="subject_id" value="' + a.subject_id + '">'
          + '<button type="submit" class="btn p-0 ms-1 lh-1 border-0 bg-transparent"'
          + ' style="color:#dc3545;font-size:.7rem" title="Remove"><i class="fa-solid fa-xmark"></i></button>'
          + '</form>'
          + '</span>';
      });
      html += '</div></div>';
    });

    wrap.innerHTML = html;
  }

  /* Course dropdown → populate subjects (skip already-assigned ones) */
  document.getElementById('asf_course_sel').addEventListener('change', function () {
    var cid      = this.value;
    var subjSel  = document.getElementById('asf_subj_sel');
    subjSel.innerHTML = '<option value="">— select subject —</option>';

    if (!cid || !ALL_COURSES_SUBJECTS[cid]) return;

    var assigned = (ALL_ASSIGNMENTS[currentTid] || []).map(function (a) { return a.subject_id; });
    ALL_COURSES_SUBJECTS[cid].subjects.forEach(function (s) {
      if (!assigned.includes(s.id)) {
        var opt = document.createElement('option');
        opt.value = s.id; opt.textContent = s.name;
        subjSel.appendChild(opt);
      }
    });

    if (subjSel.options.length === 1) {
      subjSel.innerHTML = '<option value="">All subjects already assigned</option>';
    }
  });

  /* Open subjects modal */
  document.querySelectorAll('.btn-subjects').forEach(function (btn) {
    btn.addEventListener('click', function () {
      currentTid = parseInt(btn.dataset.id);
      document.getElementById('subj_teacher_name').textContent = btn.dataset.name;
      document.getElementById('asf_tid').value   = currentTid;
      document.getElementById('asf_course_sel').value    = '';
      document.getElementById('asf_subj_sel').innerHTML  =
        '<option value="">— select a course first —</option>';
      renderAssignments(currentTid);
      modal('subjectModal').show();
    });
  });

  /* Auto-open after assign/remove redirect */
  if (AUTO_SUBJ_OPEN > 0) {
    var autoBtn = document.querySelector('.btn-subjects[data-id="' + AUTO_SUBJ_OPEN + '"]');
    if (autoBtn) autoBtn.click();
  }
});
</script>

<?php include 'footer.php'; ?>
