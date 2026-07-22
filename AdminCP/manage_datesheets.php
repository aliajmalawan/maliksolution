<?php
declare(strict_types=1);
$page_title = 'Exam Date Sheets';
$active_nav = 'datesheets';
include 'header.php';

/* ── Exam type options ── */
const EXAM_TYPES = [
    'Midterm','Final Term','Annual',
    'Weekly Test','Monthly Test','Practical','Supply / Supplementary',
];

/* ── Ensure courses.status column exists (added lazily by courses.php) ── */
ensure_column($conn, 'courses', 'status', "ENUM('active','inactive') DEFAULT 'active'");

/* ── Seed MDCAT Preparation course if it doesn't exist yet ── */
$_chk = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM courses WHERE name='MDCAT Preparation'"));
if ((int)($_chk[0] ?? 0) === 0) {
    mysqli_query($conn, "INSERT INTO courses (name, icon, status) VALUES ('MDCAT Preparation','fa-solid fa-stethoscope','active')");
}

/* ── Programs — load from courses table (mirrors public admission form) ── */
$_pr = mysqli_query($conn, "SELECT name FROM courses WHERE status='active' ORDER BY name ASC");
$PROGRAMS_LIST = ['All Programs'];
if ($_pr) while ($_row = mysqli_fetch_assoc($_pr)) $PROGRAMS_LIST[] = $_row['name'];
if (count($PROGRAMS_LIST) === 1)
    $PROGRAMS_LIST = array_merge($PROGRAMS_LIST,
        ['MDCAT Preparation','FSc Pre Medical','FSc Pre Engineering','ICS (Computer Science)','I.Com (Commerce)']);

/* ── Ensure datesheet_subjects table exists ── */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `datesheet_subjects` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `datesheet_id` INT         NOT NULL,
    `subject_name` VARCHAR(255) NOT NULL DEFAULT '',
    `exam_date`    DATE         DEFAULT NULL,
    `start_time`   TIME         DEFAULT NULL,
    `end_time`     TIME         DEFAULT NULL,
    `venue`        VARCHAR(255) DEFAULT '',
    `sort_order`   INT          DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* ── Helpers ── */
function ds_ft_badge(string $ext): array {
    return match(strtolower($ext)) {
        'pdf'               => ['#dc3545','#fff','PDF'],
        'doc','docx'        => ['#0d6efd','#fff',strtoupper($ext)],
        'xls','xlsx'        => ['#198754','#fff',strtoupper($ext)],
        'jpg','jpeg','png','webp' => ['#0dcaf0','#000','IMG'],
        default             => ['#6c757d','#fff',strtoupper($ext) ?: 'FILE'],
    };
}
function prog_color(string $p): string {
    $l = strtolower($p);
    if (str_contains($l,'medical'))     return 'success';
    if (str_contains($l,'engineering')) return 'primary';
    if (str_contains($l,'ics'))         return 'info';
    if (str_contains($l,'com'))         return 'warning';
    if (str_contains($l,'matric'))      return 'dark';
    if (str_contains($l,'fa'))          return 'secondary';
    return 'secondary';
}
function etype_color(string $t): string {
    return match($t) {
        'Midterm'                    => 'primary',
        'Final Term','Annual'        => 'danger',
        'Weekly Test','Monthly Test' => 'info',
        'Practical'                  => 'success',
        'Supply / Supplementary'     => 'warning',
        default                      => 'secondary',
    };
}

/* ── Seed MDCAT Preparation datesheet if none exists yet ── */
$_ds_chk = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM datesheets WHERE program='MDCAT Preparation'"));
if ((int)($_ds_chk[0] ?? 0) === 0) {
    mysqli_query($conn,
        "INSERT INTO datesheets (title, program, exam_type, session, file, file_type, sort_order, status)
         VALUES ('MDCAT Preparation Schedule " . date('Y') . "','MDCAT Preparation','Midterm','" . date('Y') . "','','',0,'active')");
    $_new_ds_id = (int)mysqli_insert_id($conn);
    if ($_new_ds_id > 0) {
        $mdcat_subjects = [
            'Biology (Paper I)',
            'Biology (Paper II)',
            'Chemistry (Paper I)',
            'Chemistry (Paper II)',
            'Physics',
            'English',
            'Logical Reasoning',
        ];
        foreach ($mdcat_subjects as $_ord => $_subj) {
            mysqli_query($conn,
                "INSERT INTO datesheet_subjects (datesheet_id, subject_name, sort_order)
                 VALUES ($_new_ds_id, '" . mysqli_real_escape_string($conn, $_subj) . "', $_ord)");
        }
    }
    unset($_new_ds_id, $_ord, $_subj, $mdcat_subjects);
}
unset($_ds_chk, $_chk);

/* ── Filters ── */
$fp  = trim($_GET['prog'] ?? 'all');
$ft  = trim($_GET['type'] ?? 'all');
$wps = [];
if ($fp !== 'all') $wps[] = "program='"   . mysqli_real_escape_string($conn, $fp) . "'";
if ($ft !== 'all') $wps[] = "exam_type='" . mysqli_real_escape_string($conn, $ft) . "'";
$where = $wps ? 'WHERE ' . implode(' AND ', $wps) : '';

$items = mysqli_query($conn, "SELECT * FROM datesheets $where ORDER BY sort_order ASC, created_at DESC");
$total = mysqli_num_rows($items);

/* ── Load all subjects keyed by datesheet_id ── */
$all_subj = [];
$_sr = mysqli_query($conn, "SELECT * FROM datesheet_subjects ORDER BY datesheet_id, sort_order, exam_date");
if ($_sr) while ($_s = mysqli_fetch_assoc($_sr)) $all_subj[(int)$_s['datesheet_id']][] = $_s;

/* ── Filter pills data ── */
$used_progs = [];
$_r = mysqli_query($conn, "SELECT DISTINCT program FROM datesheets WHERE program!='' ORDER BY program");
if ($_r) while ($_row = mysqli_fetch_assoc($_r)) $used_progs[] = $_row['program'];

$used_types = [];
$_r = mysqli_query($conn, "SELECT DISTINCT exam_type FROM datesheets WHERE exam_type!='' ORDER BY exam_type");
if ($_r) while ($_row = mysqli_fetch_assoc($_r)) $used_types[] = $_row['exam_type'];
?>

<style>
  /* ── Filter tabs ── */
  .ds-filter-wrap { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; margin-bottom:1.25rem; }
  .ds-filter-wrap .filter-sep { width:1px; height:22px; background:#dee2e6; margin:0 .25rem; }

  /* ── Date sheet cards ── */
  .ds-card {
    background:#fff; border-radius:14px; border:1.5px solid #e8edf5;
    padding:1.25rem 1.35rem; display:flex; flex-direction:column; gap:.85rem;
    transition:box-shadow .2s, border-color .2s;
  }
  .ds-card:hover { box-shadow:0 6px 24px rgba(7,31,64,.09); border-color:#c5d3e8; }
  .ds-card-head  { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
  .ds-card-title { font-size:.97rem; font-weight:700; color:var(--navy); line-height:1.3; flex:1; }
  .ds-card-meta  { display:flex; flex-wrap:wrap; gap:.4rem; align-items:center; }
  .ds-card-foot  { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem;
                   padding-top:.75rem; border-top:1px solid #f0f3f8; }
  .ds-card-foot-left  { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .ds-card-foot-right { display:flex; gap:.4rem; }

  .ft-badge { display:inline-block;font-size:.68rem;font-weight:800;padding:.2rem .5rem;
              border-radius:5px;letter-spacing:.04em;white-space:nowrap; }
  .sb-active   { background:#d1e7dd;color:#0a3622; }
  .sb-inactive { background:#e9ecef;color:#495057; }
  /* ── Modal form scroll fix ──
     The <form> wraps header+body+footer so Bootstrap's scrollable flex
     layout breaks. Restore it by making the form itself a flex column. ── */
  #dsForm {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
  }
  #dsForm .modal-body {
    flex: 1 1 auto;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }
  #dsForm .modal-footer { flex-shrink: 0; }

  /* ── Subject table ── */
  .subj-tbl input { font-size:.82rem; }
  #subjects_tbody tr td { padding:.3rem .4rem; vertical-align:middle; }

  /* ── Responsive subject rows: stack on small screens ── */
  @media (max-width: 640px) {
    #subjects_table thead { display: none; }
    #subjects_table, #subjects_table tbody,
    #subjects_table tr, #subjects_table td { display: block; width: 100%; }
    #subjects_table tr {
      border: 1.5px solid #dee2e6; border-radius: 8px;
      margin-bottom: .6rem; padding: .4rem .5rem; background: #fafbfc;
    }
    #subjects_table td { border: none; padding: .25rem .1rem; }
    #subjects_table td:last-child {
      text-align: right; padding-top: .3rem;
    }
    #subjects_table td input { font-size: .85rem; }
  }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Exam Date Sheets</h1>
    <p>Create subject-wise exam schedules and manage date sheets</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openAddBtn">
    <i class="fa-solid fa-plus me-1"></i>Add Date Sheet
  </button>
</div>

<!-- Flash -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $map = [
      'saved'   => ['success','fa-circle-check',         'Date sheet saved successfully.'],
      'deleted' => ['warning','fa-trash',                'Date sheet deleted.'],
      'error'   => ['danger', 'fa-triangle-exclamation', 'An error occurred. Please try again.'],
    ];
    [$cls,$ico,$txt] = $map[$_GET['msg']] ?? ['danger','fa-triangle-exclamation','Unknown error.'];
  ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
    <i class="fa-solid <?= $ico ?> me-2"></i><?= $txt ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Filter tabs -->
<?php if ($used_progs || $used_types): ?>
<div class="ds-filter-wrap">
  <a href="?prog=all&type=all"
     class="btn btn-sm <?= ($fp==='all'&&$ft==='all') ? 'btn-primary' : 'btn-outline-secondary' ?>">
    <i class="fa-solid fa-border-all me-1"></i>All
  </a>

  <?php foreach ($used_progs as $p): ?>
    <a href="?prog=<?= urlencode($p) ?>&type=<?= urlencode($ft) ?>"
       class="btn btn-sm <?= $fp===$p ? 'btn-'.prog_color($p) : 'btn-outline-secondary' ?>">
      <?= htmlspecialchars($p) ?>
    </a>
  <?php endforeach; ?>

  <?php if ($used_types): ?>
    <div class="filter-sep"></div>
    <?php foreach ($used_types as $t): ?>
      <a href="?prog=<?= urlencode($fp) ?>&type=<?= urlencode($t) ?>"
         class="btn btn-sm <?= $ft===$t ? 'btn-'.etype_color($t) : 'btn-outline-secondary' ?>"
         style="font-size:.76rem">
        <?= htmlspecialchars($t) ?>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Cards grid -->
<?php if ($total > 0): ?>
<div class="row g-3">
  <?php while ($item = mysqli_fetch_assoc($items)):
    $ext      = strtolower($item['file_type'] ?? '') ?: strtolower(pathinfo($item['file'] ?? '', PATHINFO_EXTENSION));
    [$bg,$fg,$label] = ds_ft_badge($ext);
    $has_file = !empty($item['file']) && file_exists(__DIR__.'/../assets/uploads/datesheets/'.$item['file']);
    $st       = $item['status'] ?? 'active';
    $prog     = $item['program']   ?? 'All Programs';
    $etype    = $item['exam_type'] ?? '';
    $ds_id    = (int)$item['id'];
    $subj_cnt = count($all_subj[$ds_id] ?? []);
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="ds-card">

      <!-- Head: title + status -->
      <div class="ds-card-head">
        <div class="ds-card-title"><?= htmlspecialchars($item['title']) ?></div>
        <span class="status-badge sb-<?= $st ?> flex-shrink-0"><?= ucfirst($st) ?></span>
      </div>

      <!-- Meta badges -->
      <div class="ds-card-meta">
        <span class="badge bg-<?= prog_color($prog) ?> bg-opacity-10
              text-<?= prog_color($prog) ?> border border-<?= prog_color($prog) ?>
              border-opacity-25" style="font-size:.72rem;font-weight:700">
          <i class="fa-solid fa-graduation-cap me-1"></i><?= htmlspecialchars($prog) ?>
        </span>
        <?php if ($etype): ?>
          <span class="badge bg-<?= etype_color($etype) ?>" style="font-size:.71rem">
            <?= htmlspecialchars($etype) ?>
          </span>
        <?php endif; ?>
        <?php if (!empty($item['session'])): ?>
          <span class="badge bg-light text-muted border" style="font-size:.7rem">
            <i class="fa-regular fa-calendar me-1"></i><?= htmlspecialchars($item['session']) ?>
          </span>
        <?php endif; ?>
      </div>

      <!-- Footer: schedule + file + actions -->
      <div class="ds-card-foot">
        <div class="ds-card-foot-left">
          <?php if ($subj_cnt > 0): ?>
            <button type="button" class="btn btn-sm btn-outline-info btn-view-sched"
              data-id="<?= $ds_id ?>"
              data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>">
              <i class="fa-solid fa-list-ul me-1"></i><?= $subj_cnt ?> subject<?= $subj_cnt!==1?'s':'' ?>
            </button>
          <?php else: ?>
            <span class="text-muted" style="font-size:.78rem">
              <i class="fa-solid fa-circle-info me-1"></i>No subjects yet
            </span>
          <?php endif; ?>

          <?php if ($has_file): ?>
            <a href="../assets/uploads/datesheets/<?= urlencode($item['file']) ?>"
               target="_blank" class="btn btn-sm btn-outline-success" title="Download File">
              <span class="ft-badge" style="background:<?= $bg ?>;color:<?= $fg ?>"><?= $label ?></span>
            </a>
          <?php endif; ?>
        </div>

        <div class="ds-card-foot-right">
          <a href="../datesheet_pdf.php?id=<?= $ds_id ?>" target="_blank"
             class="btn btn-sm btn-outline-danger" title="Print / Preview PDF">
            <i class="fa-solid fa-file-pdf"></i>
          </a>
          <button class="btn btn-sm btn-outline-primary btn-edit"
            data-id="<?= $ds_id ?>"
            data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>"
            data-program="<?= htmlspecialchars($prog, ENT_QUOTES) ?>"
            data-examtype="<?= htmlspecialchars($etype, ENT_QUOTES) ?>"
            data-session="<?= htmlspecialchars($item['session'] ?? '', ENT_QUOTES) ?>"
            data-status="<?= htmlspecialchars($st, ENT_QUOTES) ?>"
            data-file="<?= htmlspecialchars($item['file'] ?? '', ENT_QUOTES) ?>"
            title="Edit"><i class="fa-solid fa-pen"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger btn-delete"
            data-id="<?= $ds_id ?>"
            data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>"
            title="Delete"><i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>

    </div>
  </div>
  <?php endwhile; ?>
</div>


<?php else: ?>
  <div class="cardx empty-state">
    <i class="fa-solid fa-calendar-xmark"></i>
    <p>No date sheets<?= ($fp!=='all'||$ft!=='all') ? ' for the selected filter' : '' ?> yet.<br>
       Click <strong>Add Date Sheet</strong> to create the first one.</p>
  </div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════
     Add / Edit Modal
══════════════════════════════════════════════════ -->
<div class="modal fade" id="dsModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
    <div class="modal-content border-0 shadow">

      <form method="POST" action="datesheet_action.php" enctype="multipart/form-data" id="dsForm">
        <input type="hidden" name="action"   id="dsf_action"   value="create">
        <input type="hidden" name="ds_id"    id="dsf_id"       value="">
        <input type="hidden" name="old_file" id="dsf_old_file" value="">

        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="dsModalLabel">
            <i class="fa-solid fa-plus me-2"></i>Add Date Sheet
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Title -->
            <div class="col-12">
              <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" id="dsf_title" required
                placeholder="e.g. Midterm 2025 – FSc Pre-Medical">
            </div>

            <!-- Program -->
            <div class="col-md-4">
              <label class="form-label fw-semibold">Program / Class <span class="text-danger">*</span></label>
              <select class="form-select" name="program" id="dsf_program" required>
                <?php foreach ($PROGRAMS_LIST as $p): ?>
                  <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Exam Type -->
            <div class="col-md-4">
              <label class="form-label fw-semibold">Exam Type <span class="text-danger">*</span></label>
              <select class="form-select" name="exam_type" id="dsf_exam_type" required>
                <option value="">— Select Type —</option>
                <?php foreach (EXAM_TYPES as $t): ?>
                  <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Session -->
            <div class="col-md-2">
              <label class="form-label fw-semibold">Session</label>
              <input type="text" class="form-control" name="session" id="dsf_session"
                placeholder="2024-25" maxlength="20">
            </div>

            <!-- Status -->
            <div class="col-md-2">
              <label class="form-label fw-semibold">Status</label>
              <select class="form-select" name="status" id="dsf_status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <!-- Subject Schedule -->
            <div class="col-12">
              <hr class="mt-1 mb-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <span class="fw-bold" style="color:var(--navy)">
                    <i class="fa-solid fa-table-list me-1 text-primary"></i>Subject-wise Schedule
                  </span>
                  <span class="text-muted small ms-2">— add each subject with its exam date &amp; time</span>
                </div>
                <button type="button" class="btn btn-outline-success btn-sm" id="add_subj_btn">
                  <i class="fa-solid fa-plus me-1"></i>Add Subject
                </button>
              </div>

              <div class="table-responsive subj-tbl">
                <table class="table table-sm table-bordered align-middle mb-1" id="subjects_table">
                  <thead class="table-light">
                    <tr>
                      <th>Subject / Paper</th>
                      <th style="width:135px">Date</th>
                      <th style="width:105px">Start Time</th>
                      <th style="width:105px">End Time</th>
                      <th style="width:155px">Venue / Room</th>
                      <th style="width:38px"></th>
                    </tr>
                  </thead>
                  <tbody id="subjects_tbody"></tbody>
                </table>
              </div>
              <p id="no_subj_msg" class="text-muted small mt-1 mb-0">
                <i class="fa-solid fa-circle-info me-1"></i>No subjects added yet. Click "Add Subject" to start.
              </p>
            </div>

            <!-- Optional file upload -->
            <div class="col-12">
              <hr class="mt-1 mb-3">
              <label class="form-label fw-semibold">
                Attach File
                <span class="text-muted fw-normal small ms-1">— optional (PDF, Word, Image)</span>
                <span id="file_opt_note" class="text-muted fw-normal small ms-1" style="display:none">· leave blank to keep current</span>
              </label>
              <input type="file" class="form-control" name="file" id="dsf_file"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.xls,.xlsx">
              <div class="form-text">PDF recommended. Max 10 MB.</div>
              <div id="dsf_cur_wrap" class="d-none mt-2">
                <i class="fa-solid fa-paperclip me-1 text-muted"></i>
                <span class="text-muted small">Current file: </span>
                <a href="#" id="dsf_cur_link" target="_blank" class="small fw-semibold"></a>
              </div>
            </div>

          </div><!-- /row -->
        </div><!-- /modal-body -->

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold" id="dsf_submit_btn">
            <i class="fa-solid fa-floppy-disk me-1"></i>Save Date Sheet
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     View Schedule Modal
══════════════════════════════════════════════════ -->
<div class="modal fade" id="schedModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header" style="background:var(--navy)">
        <h5 class="modal-title text-white" id="schedModalTitle">
          <i class="fa-solid fa-calendar-check me-2"></i>Exam Schedule
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:40px">#</th>
                <th>Subject / Paper</th>
                <th>Date</th>
                <th>Time</th>
                <th>Venue / Room</th>
              </tr>
            </thead>
            <tbody id="sched_tbody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     Delete Modal
══════════════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Date Sheet</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Delete <strong id="del_title"></strong>?</p>
        <small class="text-muted">All subjects and the uploaded file will also be removed. Cannot be undone.</small>
      </div>
      <div class="modal-footer">
        <form method="POST" action="datesheet_action.php">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="ds_id"  id="del_id">
          <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-1"></i>Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>


<script>
/* ── All subjects from PHP ── */
var DS_SUBJECTS = <?= json_encode($all_subj, JSON_UNESCAPED_UNICODE) ?>;

window.addEventListener('load', function () {

  function modal(id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); }

  /* ────────────────── Subject row management ────────────────── */
  var subjIdx = 0;

  function escAttr(v) {
    return String(v||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;');
  }
  function escHtml(v) {
    return String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function makeRow(idx, data) {
    data = data || {};
    var tr = document.createElement('tr');
    tr.dataset.rowIdx = idx;
    tr.innerHTML =
      '<td><input type="text" class="form-control form-control-sm" name="subjects['+idx+'][name]"'
        +' value="'+escAttr(data.subject_name||'')+'" placeholder="e.g. Biology" required></td>'
      +'<td><input type="date" class="form-control form-control-sm" name="subjects['+idx+'][date]"'
        +' value="'+(data.exam_date||'')+'"></td>'
      +'<td><input type="time" class="form-control form-control-sm" name="subjects['+idx+'][start_time]"'
        +' value="'+(data.start_time||'')+'"></td>'
      +'<td><input type="time" class="form-control form-control-sm" name="subjects['+idx+'][end_time]"'
        +' value="'+(data.end_time||'')+'"></td>'
      +'<td><input type="text" class="form-control form-control-sm" name="subjects['+idx+'][venue]"'
        +' value="'+escAttr(data.venue||'')+'" placeholder="Hall A"></td>'
      +'<td class="text-center">'
        +'<button type="button" class="btn btn-sm btn-outline-danger btn-rm-subj">'
          +'<i class="fa-solid fa-times"></i>'
        +'</button>'
      +'</td>';
    return tr;
  }

  function addRow(data) {
    document.getElementById('subjects_tbody').appendChild(makeRow(subjIdx++, data));
    syncNoSubjMsg();
  }

  function syncNoSubjMsg() {
    var n = document.getElementById('subjects_tbody').children.length;
    document.getElementById('no_subj_msg').style.display = n ? 'none' : '';
  }

  document.getElementById('add_subj_btn').addEventListener('click', function () { addRow({}); });

  document.getElementById('subjects_tbody').addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-rm-subj');
    if (btn) { btn.closest('tr').remove(); syncNoSubjMsg(); }
  });

  /* ────────────────── Open in Add mode ────────────────── */
  function openAdd() {
    document.getElementById('dsModalLabel').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Add Date Sheet';
    document.getElementById('dsf_action').value    = 'create';
    document.getElementById('dsf_id').value        = '';
    document.getElementById('dsf_old_file').value  = '';
    document.getElementById('dsf_title').value     = '';
    document.getElementById('dsf_program').value   = '<?= htmlspecialchars($PROGRAMS_LIST[0]) ?>';
    document.getElementById('dsf_exam_type').value = '';
    document.getElementById('dsf_session').value   = '';
    document.getElementById('dsf_status').value    = 'active';
    document.getElementById('dsf_file').value      = '';
    document.getElementById('file_opt_note').style.display = 'none';
    document.getElementById('dsf_cur_wrap').classList.add('d-none');
    document.getElementById('dsf_submit_btn').innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Save Date Sheet';
    // Clear subjects
    document.getElementById('subjects_tbody').innerHTML = '';
    subjIdx = 0;
    syncNoSubjMsg();
    modal('dsModal').show();
  }

  document.getElementById('openAddBtn').addEventListener('click', openAdd);

  /* ────────────────── Edit buttons ────────────────── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('dsModalLabel').innerHTML = '<i class="fa-solid fa-pen me-2"></i>Edit Date Sheet';
      document.getElementById('dsf_action').value    = 'edit';
      document.getElementById('dsf_id').value        = d.id;
      document.getElementById('dsf_old_file').value  = d.file || '';
      document.getElementById('dsf_title').value     = d.title;
      document.getElementById('dsf_program').value   = d.program;
      document.getElementById('dsf_exam_type').value = d.examtype;
      document.getElementById('dsf_session').value   = d.session  || '';
      document.getElementById('dsf_status').value    = d.status;
      document.getElementById('dsf_file').value      = '';
      document.getElementById('file_opt_note').style.display = '';
      document.getElementById('dsf_submit_btn').innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Save Changes';

      var wrap = document.getElementById('dsf_cur_wrap');
      var link = document.getElementById('dsf_cur_link');
      if (d.file) {
        link.href        = '../assets/uploads/datesheets/' + encodeURIComponent(d.file);
        link.textContent = d.file;
        wrap.classList.remove('d-none');
      } else {
        wrap.classList.add('d-none');
      }

      // Load subjects for this datesheet
      document.getElementById('subjects_tbody').innerHTML = '';
      subjIdx = 0;
      var subjs = DS_SUBJECTS[d.id] || [];
      subjs.forEach(function (s) { addRow(s); });
      syncNoSubjMsg();

      modal('dsModal').show();
    });
  });

  /* ────────────────── View Schedule ────────────────── */
  function fmtTime(t) {
    if (!t) return '';
    var p = t.split(':'); var h = parseInt(p[0]); var m = p[1];
    var ap = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12;
    return h + ':' + m + ' ' + ap;
  }
  function fmtDate(d) {
    if (!d) return '—';
    var dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
  }

  document.querySelectorAll('.btn-view-sched').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id    = btn.dataset.id;
      var title = btn.dataset.title;
      document.getElementById('schedModalTitle').innerHTML =
        '<i class="fa-solid fa-calendar-check me-2"></i>' + escHtml(title);

      var subjs = DS_SUBJECTS[id] || [];
      var tbody = document.getElementById('sched_tbody');
      tbody.innerHTML = '';

      if (!subjs.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No subjects added.</td></tr>';
      } else {
        subjs.forEach(function (s, i) {
          var time = '';
          if (s.start_time) {
            time = fmtTime(s.start_time);
            if (s.end_time) time += ' – ' + fmtTime(s.end_time);
          }
          tbody.innerHTML +=
            '<tr>'
            + '<td class="text-muted small">'+(i+1)+'</td>'
            + '<td class="fw-semibold" style="color:var(--navy)">'+escHtml(s.subject_name)+'</td>'
            + '<td>'+fmtDate(s.exam_date)+'</td>'
            + '<td>'+(time||'—')+'</td>'
            + '<td class="text-muted">'+(s.venue ? escHtml(s.venue) : '—')+'</td>'
            + '</tr>';
        });
      }
      modal('schedModal').show();
    });
  });

  /* ────────────────── Delete ────────────────── */
  document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('del_id').value          = btn.dataset.id;
      document.getElementById('del_title').textContent = btn.dataset.title;
      modal('deleteModal').show();
    });
  });

});
</script>

<?php include 'footer.php'; ?>
