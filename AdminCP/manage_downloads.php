<?php
declare(strict_types=1);
$page_title = 'Downloads';
$active_nav = 'downloads';
include 'header.php';

/* ── helpers ── */
function filetype_badge(string $ext): array {
    return match(strtolower($ext)) {
        'pdf'                   => ['#dc3545', '#fff',    'PDF'],
        'doc', 'docx'           => ['#0d6efd', '#fff',    strtoupper($ext)],
        'xls', 'xlsx', 'csv'    => ['#198754', '#fff',    strtoupper($ext)],
        'ppt', 'pptx'           => ['#fd7e14', '#fff',    strtoupper($ext)],
        'zip', 'rar', '7z'      => ['#6f42c1', '#fff',    strtoupper($ext)],
        'jpg','jpeg','png','gif',
        'webp'                  => ['#0dcaf0', '#000',    'IMG'],
        default                 => ['#6c757d', '#fff',    strtoupper($ext) ?: 'FILE'],
    };
}

// Category filter
$filter  = trim($_GET['cat'] ?? 'all');
$where   = $filter !== 'all'
    ? "WHERE category = '" . mysqli_real_escape_string($conn, $filter) . "'"
    : '';

$items = mysqli_query($conn, "SELECT * FROM downloads $where ORDER BY sort_order ASC, created_at DESC");
$total = mysqli_num_rows($items);

// Distinct categories for tabs
$cat_res    = mysqli_query($conn, "SELECT DISTINCT category FROM downloads WHERE category != '' ORDER BY category ASC");
$categories = [];
while ($r = mysqli_fetch_assoc($cat_res)) $categories[] = $r['category'];

// Counts
$r_all     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM downloads"));
$count_all = (int)($r_all[0] ?? 0);
?>

<style>
  .ft-badge {
    display: inline-block; font-size: .7rem; font-weight: 800;
    padding: .2rem .55rem; border-radius: 5px; letter-spacing: .04em;
    white-space: nowrap;
  }
  .dl-link { font-size: .82rem; color: #0d6efd; text-decoration: none; font-weight: 600; }
  .dl-link:hover { text-decoration: underline; }
  .cur-file-info { margin-top: .5rem; }
  .cur-file-info a { font-size: .82rem; color: #0d6efd; font-weight: 600; }
  .sb-active   { background:#d1e7dd; color:#0a3622; }
  .sb-inactive { background:#e9ecef; color:#495057; }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-download me-2 text-primary"></i>Downloads</h1>
    <p>Manage downloadable files shown on the public downloads page</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openAddBtn">
    <i class="fa-solid fa-plus me-1"></i>Add Download
  </button>
</div>

<!-- Flash message -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $map = [
      'saved'   => ['success', 'fa-circle-check',         'Download saved successfully.'],
      'deleted' => ['warning', 'fa-trash',                'Download deleted.'],
      'error'   => ['danger',  'fa-triangle-exclamation', 'An error occurred. Please try again.'],
    ];
    [$cls, $ico, $txt] = $map[$_GET['msg']] ?? ['danger', 'fa-triangle-exclamation', 'Unknown error.'];
  ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
    <i class="fa-solid <?= $ico ?> me-2"></i><?= $txt ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Category filter tabs -->
<div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
  <a href="?cat=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
    All <span class="badge bg-white text-dark ms-1"><?= $count_all ?></span>
  </a>
  <?php foreach ($categories as $cat):
    $c_r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM downloads WHERE category='" . mysqli_real_escape_string($conn, $cat) . "'"));
    $c_cnt = (int)($c_r[0] ?? 0);
  ?>
    <a href="?cat=<?= urlencode($cat) ?>"
       class="btn btn-sm <?= $filter === $cat ? 'btn-secondary' : 'btn-outline-secondary' ?>">
      <?= htmlspecialchars($cat) ?>
      <span class="badge bg-white text-dark ms-1"><?= $c_cnt ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Table -->
<?php if ($total > 0): ?>
<div class="cardx p-0">
  <div class="table-wrap">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:42px">#</th>
          <th>Title &amp; Description</th>
          <th>Category</th>
          <th>File Type</th>
          <th>Date</th>
          <th>Status</th>
          <th>Added</th>
          <th style="width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; while ($item = mysqli_fetch_assoc($items)): ?>
          <?php
            $ext = strtolower(pathinfo($item['file'] ?? '', PATHINFO_EXTENSION));
            $stored_type = $item['file_type'] ?? '';
            $display_ext = $stored_type ?: $ext;
            [$bg, $fg, $label] = filetype_badge($display_ext);
            $has_file = $item['file'] !== '' && file_exists(__DIR__ . '/../assets/uploads/downloads/' . $item['file']);
            $st = $item['status'] ?? 'active';
          ?>
          <tr>
            <td class="text-muted small"><?= $i++ ?></td>
            <td>
              <div class="fw-700 text-navy" style="font-weight:700; color:var(--navy)">
                <?= htmlspecialchars($item['title']) ?>
              </div>
              <?php if (!empty($item['description'])): ?>
                <div class="text-muted small mt-1">
                  <?= htmlspecialchars(mb_substr($item['description'], 0, 80)) ?>
                  <?= mb_strlen($item['description']) > 80 ? '…' : '' ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($item['category'])): ?>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['category']) ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="ft-badge" style="background:<?= $bg ?>;color:<?= $fg ?>">
                <?= htmlspecialchars($label) ?>
              </span>
            </td>
            <td class="small">
              <?= $item['download_date'] ? date('d M Y', strtotime($item['download_date'])) : '<span class="text-muted">—</span>' ?>
            </td>
            <td>
              <span class="status-badge sb-<?= $st ?>">
                <?= ucfirst($st) ?>
              </span>
            </td>
            <td class="text-muted small text-nowrap">
              <?= date('d M Y', strtotime($item['created_at'])) ?>
            </td>
            <td class="text-nowrap">
              <?php if ($has_file): ?>
                <a href="../assets/uploads/downloads/<?= urlencode($item['file']) ?>"
                   target="_blank"
                   class="btn btn-sm btn-outline-success me-1"
                   title="Download file">
                  <i class="fa-solid fa-download"></i>
                </a>
              <?php endif; ?>
              <button class="btn btn-sm btn-outline-primary me-1 btn-edit"
                data-id="<?= $item['id'] ?>"
                data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>"
                data-desc="<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES) ?>"
                data-category="<?= htmlspecialchars($item['category'] ?? '', ENT_QUOTES) ?>"
                data-date="<?= htmlspecialchars($item['download_date'] ?? '', ENT_QUOTES) ?>"
                data-order="<?= (int)$item['sort_order'] ?>"
                data-status="<?= htmlspecialchars($st, ENT_QUOTES) ?>"
                data-file="<?= htmlspecialchars($item['file'], ENT_QUOTES) ?>"
                title="Edit">
                <i class="fa-solid fa-pen"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger btn-delete"
                data-id="<?= $item['id'] ?>"
                data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>"
                title="Delete">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
  <div class="cardx empty-state">
    <i class="fa-solid fa-file-arrow-down"></i>
    <p>
      No downloads<?= $filter !== 'all' ? " in <strong>" . htmlspecialchars($filter) . "</strong>" : '' ?> yet.<br>
      <?php if ($filter !== 'all'): ?>
        <a href="?cat=all">View all</a> or
      <?php endif; ?>
      Click <strong>Add Download</strong> to upload the first file.
    </p>
  </div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════
     Add / Edit Modal
══════════════════════════════════════════════════ -->
<div class="modal fade" id="dlModal" tabindex="-1" aria-labelledby="dlModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">

      <form method="POST" action="download_action.php" enctype="multipart/form-data" id="dlForm">
        <input type="hidden" name="action"       id="df_action"   value="create">
        <input type="hidden" name="download_id"  id="df_id"       value="">
        <input type="hidden" name="old_file"     id="df_old_file" value="">

        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="dlModalLabel">
            <i class="fa-solid fa-plus me-2"></i>Add Download
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Title -->
            <div class="col-md-8">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" id="df_title" required
                placeholder="e.g. Admission Form 2025, Date Sheet Midterm">
            </div>

            <!-- Sort Order -->
            <div class="col-md-4">
              <label class="form-label">Display Order</label>
              <input type="number" class="form-control" name="sort_order" id="df_order"
                min="0" value="0">
              <div class="form-text">Lower = shown first.</div>
            </div>

            <!-- Category -->
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <input type="text" class="form-control" name="category" id="df_category"
                placeholder="e.g. Prospectus, Date Sheet, Forms, Results, Policies"
                list="cat_suggestions_dl">
              <datalist id="cat_suggestions_dl">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
                <option value="Prospectus">
                <option value="Date Sheet">
                <option value="Admission Forms">
                <option value="Results">
                <option value="Policies">
                <option value="Circulars">
                <option value="Fee Structure">
                <option value="Scholarships">
              </datalist>
            </div>

            <!-- Date -->
            <div class="col-md-3">
              <label class="form-label">Date</label>
              <input type="date" class="form-control" name="download_date" id="df_date">
            </div>

            <!-- Status -->
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="df_status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label">Description <span class="text-muted small fw-normal">(optional)</span></label>
              <textarea class="form-control" name="description" id="df_desc" rows="2"
                placeholder="Brief description of the file contents"></textarea>
            </div>

            <!-- File Upload -->
            <div class="col-12">
              <label class="form-label">
                File <span class="text-danger" id="file_req_star">*</span>
                <span class="text-muted small fw-normal" id="file_opt_note" style="display:none">(leave blank to keep current file)</span>
              </label>
              <input type="file" class="form-control" name="file" id="df_file"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.rar,.7z,.jpg,.jpeg,.png,.gif,.webp">
              <div class="form-text">
                PDF, Word, Excel, PowerPoint, ZIP, RAR — max 10 MB.
              </div>

              <!-- Current file info (edit mode) -->
              <div id="df_cur_wrap" class="cur-file-info d-none mt-2">
                <i class="fa-solid fa-paperclip me-1 text-muted"></i>
                <span class="text-muted small">Current file: </span>
                <a href="#" id="df_cur_link" target="_blank" class="small fw-600"></a>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="df_submit_btn">
            <i class="fa-solid fa-upload me-1"></i>Upload &amp; Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     Delete Confirm Modal
══════════════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Download</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Delete <strong id="del_title"></strong>?</p>
        <small class="text-muted">The file will also be removed from the server. Cannot be undone.</small>
      </div>
      <div class="modal-footer">
        <form method="POST" action="download_action.php">
          <input type="hidden" name="action"      value="delete">
          <input type="hidden" name="download_id" id="del_id">
          <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa-solid fa-trash me-1"></i>Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     JavaScript
══════════════════════════════════════════════════ -->
<script>
window.addEventListener('load', function () {

  function modal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
  }

  /* ── Open in Add mode ── */
  function openAdd() {
    document.getElementById('dlModalLabel').innerHTML =
      '<i class="fa-solid fa-plus me-2"></i>Add Download';
    document.getElementById('df_action').value   = 'create';
    document.getElementById('df_id').value       = '';
    document.getElementById('df_old_file').value = '';
    document.getElementById('df_title').value    = '';
    document.getElementById('df_desc').value     = '';
    document.getElementById('df_category').value = '';
    document.getElementById('df_date').value     = '';
    document.getElementById('df_order').value    = '0';
    document.getElementById('df_status').value   = 'active';
    document.getElementById('df_file').value     = '';
    document.getElementById('df_file').required  = true;
    document.getElementById('file_req_star').style.display = '';
    document.getElementById('file_opt_note').style.display = 'none';
    document.getElementById('df_cur_wrap').classList.add('d-none');
    document.getElementById('df_submit_btn').innerHTML =
      '<i class="fa-solid fa-upload me-1"></i>Upload &amp; Save';
    modal('dlModal').show();
  }

  document.getElementById('openAddBtn')?.addEventListener('click', openAdd);

  /* ── Edit buttons ── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('dlModalLabel').innerHTML =
        '<i class="fa-solid fa-pen me-2"></i>Edit Download';
      document.getElementById('df_action').value   = 'edit';
      document.getElementById('df_id').value       = d.id;
      document.getElementById('df_old_file').value = d.file;
      document.getElementById('df_title').value    = d.title;
      document.getElementById('df_desc').value     = d.desc;
      document.getElementById('df_category').value = d.category;
      document.getElementById('df_date').value     = d.date;
      document.getElementById('df_order').value    = d.order;
      document.getElementById('df_status').value   = d.status;
      document.getElementById('df_file').value     = '';
      document.getElementById('df_file').required  = false;
      document.getElementById('file_req_star').style.display = 'none';
      document.getElementById('file_opt_note').style.display = '';
      document.getElementById('df_submit_btn').innerHTML =
        '<i class="fa-solid fa-save me-1"></i>Save Changes';

      var wrap = document.getElementById('df_cur_wrap');
      var link = document.getElementById('df_cur_link');
      if (d.file) {
        link.href        = '../assets/uploads/downloads/' + encodeURIComponent(d.file);
        link.textContent = d.file;
        wrap.classList.remove('d-none');
      } else {
        wrap.classList.add('d-none');
      }

      modal('dlModal').show();
    });
  });

  /* ── Delete buttons ── */
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
