<?php
declare(strict_types=1);
$page_title = 'Admissions';
$active_nav = 'admissions';
include 'header.php';

// ── Status filter
$filter  = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($filter, $allowed, true)) $filter = 'all';

$where      = $filter !== 'all' ? "WHERE status = '" . mysqli_real_escape_string($conn, $filter) . "'" : '';
$admissions = mysqli_query($conn, "SELECT * FROM admissions $where ORDER BY created_at DESC");

// ── Tab counts
$counts = [];
foreach ($allowed as $s) {
    $w          = $s !== 'all' ? "WHERE status='$s'" : '';
    $r          = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM admissions $w"));
    $counts[$s] = (int)($r[0] ?? 0);
}

$flash = $_GET['updated'] ?? '';

// ── Course list for edit modal (mirrors public admission form)
$_prog_res  = mysqli_query($conn, "SELECT name FROM courses WHERE status='active' ORDER BY name ASC");
$_prog_list = [];
if ($_prog_res) while ($_pr = mysqli_fetch_assoc($_prog_res)) $_prog_list[] = $_pr['name'];
if (empty($_prog_list)) $_prog_list = ['FSc Pre Medical', 'FSc Pre Engineering', 'ICS (Computer Science)', 'I.Com (Commerce)'];
?>

<style>
  /* Status badges */
  .sb-pending  { background:#fff3cd; color:#856404; }
  .sb-approved { background:#d1e7dd; color:#0a3622; }
  .sb-rejected { background:#f8d7da; color:#58151c; }

  /* Dropdown action button */
  .act-btn {
    font-size:.78rem; padding:.3rem .75rem; font-weight:600;
    border-radius:6px; white-space:nowrap;
  }
  .act-menu { min-width:160px; font-size:.82rem; }
  .act-menu .dropdown-item { display:flex; align-items:center; gap:.55rem; padding:.45rem 1rem; }
  .act-menu .dropdown-item i { width:14px; text-align:center; }
  .act-menu .dropdown-divider { margin:.25rem 0; }

  /* View detail modal */
  .detail-label { font-size:.72rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.05em; color:#6c757d; margin-bottom:.15rem; }
  .detail-value { font-size:.92rem; font-weight:600; color:var(--navy); }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-user-plus me-2 text-primary"></i>Admissions</h1>
    <p>Review and manage student admission applications</p>
  </div>
  <a href="../admissions.php" target="_blank" class="btn btn-outline-primary btn-sm">
    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Public Form
  </a>
</div>

<!-- Flash -->
<?php if ($flash === '1'): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fa-solid fa-circle-check me-2"></i>Status updated successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php elseif ($flash === 'deleted'): ?>
  <div class="alert alert-warning alert-dismissible fade show">
    <i class="fa-solid fa-trash me-2"></i>Application deleted.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php elseif ($flash === 'pw_reset'): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fa-solid fa-key me-2"></i>Student password updated successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php elseif ($flash === 'edited'): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fa-solid fa-floppy-disk me-2"></i>Student details updated successfully.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (($_GET['error'] ?? '') === 'pw_short'): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="fa-solid fa-triangle-exclamation me-2"></i>Password must be at least 6 characters.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php
// Show generated credentials (session flash — consumed once)
$new_creds = $_SESSION['new_creds'] ?? null;
unset($_SESSION['new_creds']);
if ($new_creds):
?>
<div class="card border-success mb-3 shadow-sm">
  <div class="card-header bg-success text-white d-flex align-items-center gap-2">
    <i class="fa-solid fa-key fa-lg"></i>
    <strong>Student Account Created — <?= htmlspecialchars($new_creds['name']) ?></strong>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      Share these credentials with the student. The password cannot be retrieved again — copy it now.
    </p>
    <div class="row g-3">
      <div class="col-sm-6">
        <label class="form-label fw-bold mb-1"><i class="fa-solid fa-id-badge me-1 text-primary"></i>Student ID</label>
        <div class="input-group">
          <input type="text" class="form-control fw-bold font-monospace"
                 id="cred_sid" value="<?= htmlspecialchars($new_creds['student_id'] ?? '') ?>" readonly>
          <button class="btn btn-outline-secondary btn-copy" data-target="cred_sid" title="Copy">
            <i class="fa-solid fa-copy"></i>
          </button>
        </div>
      </div>
      <div class="col-sm-6">
        <label class="form-label fw-bold mb-1"><i class="fa-solid fa-key me-1 text-primary"></i>Password</label>
        <div class="input-group">
          <input type="text" class="form-control fw-bold font-monospace"
                 id="cred_pass" value="<?= htmlspecialchars($new_creds['password']) ?>" readonly>
          <button class="btn btn-outline-secondary btn-copy" data-target="cred_pass" title="Copy">
            <i class="fa-solid fa-copy"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php
  $tab_cfg = [
    'all'      => ['All Applications', 'secondary'],
    'pending'  => ['Pending',          'warning'],
    'approved' => ['Approved',         'success'],
    'rejected' => ['Rejected',         'danger'],
  ];
  foreach ($tab_cfg as $k => [$label, $color]):
    $cls = $filter === $k ? "btn-$color" : "btn-outline-$color";
  ?>
    <a href="?status=<?= $k ?>" class="btn btn-sm <?= $cls ?>">
      <?= $label ?>
      <span class="badge bg-white text-dark ms-1"><?= $counts[$k] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Table -->
<div class="cardx p-0">
  <?php if (mysqli_num_rows($admissions) > 0): ?>
    <div class="table-wrap">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:42px">#</th>
            <th>Student</th>
            <th>Father's Name</th>
            <th>Program</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Status</th>
            <th>Applied</th>
            <th style="width:110px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($row = mysqli_fetch_assoc($admissions)):
            $st   = $row['status'];
            $rid  = (int)$row['id'];
            $uname = $row['username'] ?? '';
            $upass = $row['student_password'] ?? '';
            $usid  = $row['student_id'] ?? '';
          ?>
          <tr>
            <td class="text-muted small"><?= $i++ ?></td>

            <td>
              <div class="fw-bold" style="color:var(--navy)"><?= htmlspecialchars($row['student_name']) ?></div>
              <?php if ($usid !== ''): ?>
                <div class="mt-1">
                  <span class="badge" style="background:#071f40;color:#f6b221;font-size:.65rem;font-weight:800;letter-spacing:.03em;font-family:monospace">
                    <i class="fa-solid fa-hashtag me-1"></i><?= htmlspecialchars($usid) ?>
                  </span>
                </div>
              <?php elseif (!empty($row['address'])): ?>
                <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($row['address']) ?></div>
              <?php endif; ?>
            </td>

            <td class="text-muted small"><?= htmlspecialchars($row['father_name'] ?? '') ?></td>
            <td class="small"><?= htmlspecialchars($row['program'] ?? '') ?></td>
            <td class="small"><?= htmlspecialchars($row['phone'] ?? '') ?></td>
            <td class="small"><?= htmlspecialchars($row['email'] ?? '') ?></td>

            <!-- Status badge -->
            <td>
              <span class="status-badge sb-<?= $st ?>">
                <?php
                  $icons = ['pending'=>'fa-clock','approved'=>'fa-circle-check','rejected'=>'fa-circle-xmark'];
                ?>
                <i class="fa-solid <?= $icons[$st] ?? 'fa-circle' ?> me-1"></i>
                <?= ucfirst($st) ?>
              </span>
            </td>

            <td class="text-muted small text-nowrap">
              <?= date('d M Y', strtotime($row['created_at'])) ?>
            </td>

            <!-- Actions -->
            <td>
              <div class="btn-group btn-group-sm" role="group">

                <!-- View detail -->
                <button type="button" class="btn btn-outline-primary btn-view-detail"
                  title="View Details"
                  data-id="<?= $rid ?>"
                  data-name="<?= htmlspecialchars($row['student_name'], ENT_QUOTES) ?>"
                  data-father="<?= htmlspecialchars($row['father_name'] ?? '', ENT_QUOTES) ?>"
                  data-program="<?= htmlspecialchars($row['program'] ?? '', ENT_QUOTES) ?>"
                  data-phone="<?= htmlspecialchars($row['phone'] ?? '', ENT_QUOTES) ?>"
                  data-email="<?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES) ?>"
                  data-address="<?= htmlspecialchars($row['address'] ?? '', ENT_QUOTES) ?>"
                  data-message="<?= htmlspecialchars($row['message'] ?? '', ENT_QUOTES) ?>"
                  data-status="<?= htmlspecialchars($st, ENT_QUOTES) ?>"
                  data-date="<?= date('d M Y', strtotime($row['created_at'])) ?>"
                  data-username="<?= htmlspecialchars($uname, ENT_QUOTES) ?>"
                  data-password="<?= htmlspecialchars($upass, ENT_QUOTES) ?>"
                  data-studentid="<?= htmlspecialchars($usid, ENT_QUOTES) ?>"
                  data-filter="<?= htmlspecialchars($filter) ?>">
                  <i class="fa-solid fa-eye"></i>
                </button>

                <!-- Edit student (includes status change + password) -->
                <button type="button" class="btn btn-outline-success btn-edit-adm"
                  title="Edit"
                  data-id="<?= $rid ?>"
                  data-name="<?= htmlspecialchars($row['student_name'], ENT_QUOTES) ?>"
                  data-father="<?= htmlspecialchars($row['father_name'] ?? '', ENT_QUOTES) ?>"
                  data-program="<?= htmlspecialchars($row['program'] ?? '', ENT_QUOTES) ?>"
                  data-phone="<?= htmlspecialchars($row['phone'] ?? '', ENT_QUOTES) ?>"
                  data-email="<?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES) ?>"
                  data-address="<?= htmlspecialchars($row['address'] ?? '', ENT_QUOTES) ?>"
                  data-message="<?= htmlspecialchars($row['message'] ?? '', ENT_QUOTES) ?>"
                  data-status="<?= htmlspecialchars($st, ENT_QUOTES) ?>"
                  data-filter="<?= htmlspecialchars($filter) ?>"
                  data-photo="<?= htmlspecialchars($row['student_photo'] ?? '', ENT_QUOTES) ?>">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>

                <!-- Delete -->
                <button type="button" class="btn btn-outline-danger btn-del-adm"
                  title="Delete"
                  data-id="<?= $rid ?>"
                  data-name="<?= htmlspecialchars($row['student_name'], ENT_QUOTES) ?>"
                  data-filter="<?= htmlspecialchars($filter) ?>">
                  <i class="fa-solid fa-trash"></i>
                </button>

              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-inbox"></i>
      <p>
        No <?= $filter !== 'all' ? "<strong>" . ucfirst($filter) . "</strong>" : '' ?> applications found.<br>
        <?php if ($filter !== 'all'): ?>
          <a href="?status=all">View all admissions</a>
        <?php else: ?>
          <a href="../admissions.php" target="_blank">Share the admission form</a> to start receiving applications.
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>
</div>


<!-- ══════════════════════════════════════════
     View Detail Modal
══════════════════════════════════════════ -->
<div class="modal fade" id="detailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header" style="background:var(--navy)">
        <h5 class="modal-title text-white">
          <i class="fa-solid fa-user me-2"></i>Application Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-sm-6">
            <div class="detail-label">Student Name</div>
            <div class="detail-value" id="dv_name"></div>
          </div>
          <div class="col-sm-6">
            <div class="detail-label">Father's Name</div>
            <div class="detail-value" id="dv_father"></div>
          </div>
          <div class="col-sm-6">
            <div class="detail-label">Program</div>
            <div class="detail-value" id="dv_program"></div>
          </div>
          <div class="col-sm-6">
            <div class="detail-label">Status</div>
            <div id="dv_status"></div>
          </div>
          <div class="col-sm-6">
            <div class="detail-label">Phone</div>
            <div class="detail-value" id="dv_phone"></div>
          </div>
          <div class="col-sm-6">
            <div class="detail-label">Email</div>
            <div class="detail-value" id="dv_email"></div>
          </div>
          <div class="col-12">
            <div class="detail-label">Address</div>
            <div class="detail-value" id="dv_address"></div>
          </div>
          <div class="col-12" id="dv_message_wrap">
            <div class="detail-label">Message</div>
            <div class="detail-value" id="dv_message"
                 style="background:var(--soft);padding:.75rem 1rem;border-radius:8px;font-weight:400"></div>
          </div>
          <div class="col-12">
            <div class="detail-label">Applied On</div>
            <div class="detail-value" id="dv_date"></div>
          </div>

          <!-- Credentials (shown only if account created) -->
          <div class="col-12" id="dv_creds_wrap" style="display:none">
            <hr class="my-2">
            <div class="p-3 rounded" style="background:#edf4ff;border:1px solid #b8d4fb">
              <div class="fw-bold text-primary mb-2">
                <i class="fa-solid fa-key me-1"></i>Student Login Credentials
              </div>
              <div class="row g-2">
                <div class="col-sm-6">
                  <div class="detail-label">Student ID</div>
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control font-monospace fw-bold"
                           id="dv_studentid" readonly>
                    <button class="btn btn-outline-secondary btn-copy" data-target="dv_studentid">
                      <i class="fa-solid fa-copy"></i>
                    </button>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="detail-label">Password</div>
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control font-monospace fw-bold"
                           id="dv_password" readonly>
                    <button class="btn btn-outline-secondary btn-copy" data-target="dv_password">
                      <i class="fa-solid fa-copy"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning fw-semibold d-none" id="detail_reset_pw_btn">
          <i class="fa-solid fa-key me-1"></i>Reset Password
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     Edit Student Modal
══════════════════════════════════════════ -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header" style="background:var(--navy)">
        <h5 class="modal-title text-white">
          <i class="fa-solid fa-user-pen me-2"></i>Edit Student Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="admission_action.php" enctype="multipart/form-data">
        <div class="modal-body">
          <input type="hidden" name="action"          value="edit_student">
          <input type="hidden" name="id"              id="edit_id">
          <input type="hidden" name="redirect_status" id="edit_redirect">

          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label fw-semibold small">Student Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="student_name" id="edit_name" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold small">Father's Name</label>
              <input type="text" class="form-control" name="father_name" id="edit_father">
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold small">Program</label>
              <select class="form-select" name="program" id="edit_program">
                <option value="">— Select a Program —</option>
                <?php foreach ($_prog_list as $_p): ?>
                <option value="<?= htmlspecialchars($_p) ?>"><?= htmlspecialchars($_p) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small">Status</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="status" id="est_pending"  value="pending"  autocomplete="off">
                <label class="btn btn-outline-warning fw-semibold" for="est_pending">
                  <i class="fa-solid fa-clock me-1"></i>Pending
                </label>
                <input type="radio" class="btn-check" name="status" id="est_approved" value="approved" autocomplete="off">
                <label class="btn btn-outline-success fw-semibold" for="est_approved">
                  <i class="fa-solid fa-circle-check me-1"></i>Approved
                </label>
                <input type="radio" class="btn-check" name="status" id="est_rejected" value="rejected" autocomplete="off">
                <label class="btn btn-outline-danger fw-semibold" for="est_rejected">
                  <i class="fa-solid fa-circle-xmark me-1"></i>Rejected
                </label>
              </div>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold small">Phone</label>
              <input type="text" class="form-control" name="phone" id="edit_phone">
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold small">Email</label>
              <input type="email" class="form-control" name="email" id="edit_email">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small">Address</label>
              <input type="text" class="form-control" name="address" id="edit_address">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold small">Message / Note</label>
              <textarea class="form-control" name="message" id="edit_message" rows="2"></textarea>
            </div>
          </div>

          <hr class="my-3">

          <!-- Student Photo Upload -->
          <div class="fw-semibold small mb-2" style="color:var(--navy)">
            <i class="fa-solid fa-image me-1 text-success"></i>Student Photo
            <span class="text-muted fw-normal ms-1">— leave blank to keep current photo</span>
          </div>
          <div class="d-flex align-items-start gap-3 mb-1">
            <div id="edit_photo_preview_wrap" style="flex-shrink:0">
              <div id="edit_photo_preview"
                   style="width:72px;height:72px;border-radius:50%;background:#e8edf5;border:2px dashed #b8d4fb;display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#90a8cc;overflow:hidden">
                <i class="fa-solid fa-user-graduate" id="edit_photo_icon"></i>
                <img id="edit_photo_img" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;border-radius:50%">
              </div>
            </div>
            <div style="flex:1">
              <input type="file" class="form-control form-control-sm" name="student_photo" id="edit_photo_input"
                     accept="image/jpeg,image/png,image/webp,image/gif">
              <div class="form-text">JPG, PNG or WEBP · Max 2 MB · Recommended: square crop</div>
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
                       name="new_password" id="edit_new_pw"
                       placeholder="Enter new password or auto-generate">
                <button type="button" class="btn btn-outline-secondary" id="edit_gen_btn" title="Auto-generate">
                  <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate
                </button>
              </div>
              <div class="form-text">Minimum 6 characters if changing.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-bold">
            <i class="fa-solid fa-floppy-disk me-1"></i>Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     Delete Confirm Modal
══════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Application</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Delete application from <strong id="del_name"></strong>?</p>
        <small class="text-muted">This cannot be undone.</small>
      </div>
      <div class="modal-footer">
        <form method="POST" action="admission_action.php" id="deleteForm">
          <input type="hidden" name="action"          value="delete">
          <input type="hidden" name="id"              id="del_id">
          <input type="hidden" name="redirect_status" id="del_filter">
          <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa-solid fa-trash me-1"></i>Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- ══ Password Reset Modal ════════════════════════════════ -->
<div class="modal fade" id="pwResetModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header text-white" style="background:#071f40">
        <h5 class="modal-title"><i class="fa-solid fa-key me-2"></i>Reset Student Password</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="admission_action.php">
        <div class="modal-body">
          <p class="text-muted small mb-3">
            Resetting login password for <strong id="pr_name"></strong>.
            The student signs in using their Student ID.
          </p>
          <input type="hidden" name="action"          value="reset_password">
          <input type="hidden" name="id"              id="pr_id">
          <input type="hidden" name="redirect_status" id="pr_redirect">

          <div class="mb-3">
            <label class="form-label fw-semibold small">Student ID</label>
            <input type="text" class="form-control form-control-sm font-monospace fw-bold"
                   id="pr_sid_show" readonly style="background:#f4f7fb;color:#071f40">
          </div>

          <div class="mb-1">
            <label class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="text" class="form-control font-monospace"
                     name="new_password" id="pr_new_pw"
                     placeholder="Enter or generate a password" required minlength="6">
              <button type="button" class="btn btn-outline-secondary" id="pr_gen_btn" title="Auto-generate">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate
              </button>
            </div>
            <div class="form-text">Minimum 6 characters.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning fw-bold">
            <i class="fa-solid fa-key me-1"></i>Update Password
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
window.addEventListener('load', function () {

  function getModal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
  }

  var badgeMap = {
    pending:  { cls: 'sb-pending',  icon: 'fa-clock',        label: 'Pending'  },
    approved: { cls: 'sb-approved', icon: 'fa-circle-check', label: 'Approved' },
    rejected: { cls: 'sb-rejected', icon: 'fa-circle-xmark', label: 'Rejected' },
  };

  /* ── View Detail */
  document.querySelectorAll('.btn-view-detail').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('dv_name').textContent    = d.name    || '—';
      document.getElementById('dv_father').textContent  = d.father  || '—';
      document.getElementById('dv_program').textContent = d.program || '—';
      document.getElementById('dv_phone').textContent   = d.phone   || '—';
      document.getElementById('dv_email').textContent   = d.email   || '—';
      document.getElementById('dv_address').textContent = d.address || '—';
      document.getElementById('dv_date').textContent    = d.date    || '—';

      var msgWrap = document.getElementById('dv_message_wrap');
      var msgEl   = document.getElementById('dv_message');
      if (d.message && d.message.trim() !== '') {
        msgEl.textContent = d.message;
        msgWrap.style.display = '';
      } else {
        msgWrap.style.display = 'none';
      }

      var stEl = document.getElementById('dv_status');
      var info = badgeMap[d.status] || { cls: '', icon: 'fa-circle', label: d.status };
      stEl.innerHTML = '<span class="status-badge ' + info.cls + '">'
        + '<i class="fa-solid ' + info.icon + ' me-1"></i>' + info.label + '</span>';

      /* ── Credentials section */
      var credsWrap = document.getElementById('dv_creds_wrap');
      if (d.studentid && d.studentid.trim() !== '' && d.password && d.password.trim() !== '') {
        document.getElementById('dv_studentid').value = d.studentid || '';
        document.getElementById('dv_password').value  = d.password || '';
        credsWrap.style.display = '';
      } else {
        credsWrap.style.display = 'none';
      }

      /* ── Reset Password button in footer */
      var detailResetBtn = document.getElementById('detail_reset_pw_btn');
      if (d.status === 'approved') {
        detailResetBtn.classList.remove('d-none');
        detailResetBtn.dataset.id        = d.id;
        detailResetBtn.dataset.name      = d.name;
        detailResetBtn.dataset.studentid = d.studentid || '';
        detailResetBtn.dataset.filter    = d.filter || '';
      } else {
        detailResetBtn.classList.add('d-none');
      }

      getModal('detailModal').show();
    });
  });

  /* ── Delete confirm */
  document.querySelectorAll('.btn-del-adm').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('del_name').textContent = btn.dataset.name;
      document.getElementById('del_id').value         = btn.dataset.id;
      document.getElementById('del_filter').value     = btn.dataset.filter;
      getModal('deleteModal').show();
    });
  });

  /* ── Edit Student */
  document.querySelectorAll('.btn-edit-adm').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('edit_id').value       = d.id;
      document.getElementById('edit_redirect').value = d.filter;
      document.getElementById('edit_name').value     = d.name;
      document.getElementById('edit_father').value   = d.father   || '';
      document.getElementById('edit_program').value  = d.program  || '';
      document.getElementById('edit_phone').value    = d.phone    || '';
      document.getElementById('edit_email').value    = d.email    || '';
      document.getElementById('edit_address').value  = d.address  || '';
      document.getElementById('edit_message').value  = d.message  || '';
      var radioEl = document.getElementById('est_' + (d.status || 'pending'));
      if (radioEl) radioEl.checked = true;
      document.getElementById('edit_new_pw').value   = '';
      /* photo preview */
      document.getElementById('edit_photo_input').value = '';
      var img  = document.getElementById('edit_photo_img');
      var icon = document.getElementById('edit_photo_icon');
      if (d.photo && d.photo.trim() !== '') {
        img.src = '../' + d.photo;
        img.style.display = 'block';
        icon.style.display = 'none';
      } else {
        img.src = ''; img.style.display = 'none';
        icon.style.display = '';
      }
      getModal('editModal').show();
    });
  });

  /* Live photo preview from file picker */
  document.getElementById('edit_photo_input').addEventListener('change', function () {
    var file = this.files[0];
    var img  = document.getElementById('edit_photo_img');
    var icon = document.getElementById('edit_photo_icon');
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      img.src = e.target.result;
      img.style.display = 'block';
      icon.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });

  /* ── Auto-generate password in edit modal */
  document.getElementById('edit_gen_btn').addEventListener('click', function () {
    var upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    var lower  = 'abcdefghjkmnpqrstuvwxyz';
    var digits = '23456789';
    var pw = 'Ms@'
      + upper[Math.floor(Math.random() * upper.length)]
      + digits[Math.floor(Math.random() * digits.length)]
      + lower[Math.floor(Math.random() * lower.length)]
      + digits[Math.floor(Math.random() * digits.length)];
    document.getElementById('edit_new_pw').value = pw;
  });

  /* ── Reset Password from detail modal footer */
  document.getElementById('detail_reset_pw_btn').addEventListener('click', function () {
    var d = this.dataset;
    getModal('detailModal').hide();
    document.getElementById('pr_name').textContent   = d.name;
    document.getElementById('pr_id').value           = d.id;
    document.getElementById('pr_redirect').value     = d.filter;
    document.getElementById('pr_sid_show').value     = d.studentid;
    document.getElementById('pr_new_pw').value       = '';
    setTimeout(function () { getModal('pwResetModal').show(); }, 300);
  });

  /* ── Auto-generate password */
  var genBtn = document.getElementById('pr_gen_btn');
  if (genBtn) {
    genBtn.addEventListener('click', function () {
      var upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
      var lower  = 'abcdefghjkmnpqrstuvwxyz';
      var digits = '23456789';
      var all    = upper + lower + digits;
      var pw = 'Ms@'
        + upper[Math.floor(Math.random() * upper.length)]
        + digits[Math.floor(Math.random() * digits.length)]
        + lower[Math.floor(Math.random() * lower.length)]
        + digits[Math.floor(Math.random() * digits.length)];
      document.getElementById('pr_new_pw').value = pw;
    });
  }

  /* ── Copy buttons */
  document.querySelectorAll('.btn-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.getElementById(btn.getAttribute('data-target'));
      if (!el) return;
      navigator.clipboard.writeText(el.value).then(function () {
        var icon = btn.querySelector('i');
        icon.className = 'fa-solid fa-check text-success';
        setTimeout(function () { icon.className = 'fa-solid fa-copy'; }, 1500);
      });
    });
  });

});
</script>

<?php include 'footer.php'; ?>
