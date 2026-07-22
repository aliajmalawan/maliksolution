<?php
declare(strict_types=1);
$page_title = 'Manage Notifications';
$active_nav = 'notifications';
include 'header.php';

$notifications = mysqli_query($conn,
    "SELECT * FROM notifications ORDER BY sort_order ASC, created_at DESC");
$count = mysqli_num_rows($notifications);

$page_list = [
    'index.php'      => 'Home',
    'about.php'      => 'About',
    'courses.php'    => 'Courses',
    'admissions.php' => 'Admissions',
    'faculty.php'    => 'Faculty',
    'campuses.php'   => 'Campuses',
    'resources.php'  => 'Resources',
    'gallery.php'    => 'Gallery',
    'contact.php'    => 'Contact',
    'datesheet.php'  => 'Date Sheets',
    'downloads.php'  => 'Downloads',
    'news.php'       => 'News',
];

$type_info = [
    'info'    => ['label' => 'Info',    'color' => '#0d6efd', 'bg' => '#edf4ff', 'icon' => 'fa-circle-info'],
    'success' => ['label' => 'Success', 'color' => '#198754', 'bg' => '#edfff5', 'icon' => 'fa-circle-check'],
    'warning' => ['label' => 'Warning', 'color' => '#fd7e14', 'bg' => '#fff8ed', 'icon' => 'fa-triangle-exclamation'],
    'danger'  => ['label' => 'Danger',  'color' => '#dc3545', 'bg' => '#fff0f0', 'icon' => 'fa-circle-xmark'],
];
?>

<style>
  .type-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; flex-shrink: 0; margin-right: .35rem;
  }
  .notif-type-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .72rem; font-weight: 700; border-radius: 20px;
    padding: .25rem .65rem; text-transform: uppercase; letter-spacing: .03em;
  }
  .sb-active   { background: #d1e7dd; color: #0a3622; }
  .sb-inactive { background: #e9ecef; color: #495057; }
  .pages-tags { display: flex; flex-wrap: wrap; gap: .25rem; }
  .page-tag {
    background: #edf4ff; color: #0d6efd;
    font-size: .7rem; font-weight: 600; border-radius: 4px;
    padding: .15rem .45rem; white-space: nowrap;
  }
  .page-tag.all-tag { background: #d1e7dd; color: #0a3622; }
  .notif-msg-preview { color: #6c757d; font-size: .8rem; max-width: 280px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .page-check-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: .5rem;
  }
  .page-check-item {
    display: flex; align-items: center; gap: .45rem;
    font-size: .85rem; background: var(--soft);
    border: 1px solid var(--line); border-radius: 7px;
    padding: .5rem .75rem; cursor: pointer; transition: border-color .14s, background .14s;
    user-select: none;
  }
  .page-check-item:has(input:checked) {
    border-color: var(--blue); background: #edf4ff;
  }
  .page-check-item input { width: 15px; height: 15px; flex-shrink: 0; accent-color: var(--blue); }

  /* Keep modal-body scrollable without relying on modal-dialog-scrollable */
  #notifModal .modal-body { max-height: 65vh; overflow-y: auto; }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-bell me-2 text-primary"></i>Manage Notifications</h1>
    <p>Create, edit and control notifications shown on the public website</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openAddBtn">
    <i class="fa-solid fa-plus me-1"></i>Add Notification
  </button>
</div>

<!-- Flash -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $map = [
      'saved'   => ['success', 'fa-circle-check',         'Notification saved successfully.'],
      'deleted' => ['warning', 'fa-trash',                'Notification deleted.'],
      'error'   => ['danger',  'fa-triangle-exclamation', 'An error occurred. Please try again.'],
    ];
    [$cls, $ico, $txt] = $map[$_GET['msg']] ?? ['danger','fa-triangle-exclamation','Unknown error.'];
  ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
    <i class="fa-solid <?= $ico ?> me-2"></i><?= $txt ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Notifications Table -->
<div class="cardx">
  <?php if ($count > 0): ?>
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:36px">#</th>
            <th>Title &amp; Message</th>
            <th>Type</th>
            <th>Shows On Pages</th>
            <th>Date Range</th>
            <th>Order</th>
            <th>Status</th>
            <th style="width:120px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($n = mysqli_fetch_assoc($notifications)): ?>
            <?php
              $ti   = $type_info[$n['type']] ?? $type_info['info'];
              $pages_val = $n['show_pages'] ?? 'all';
              if ($pages_val === 'all') {
                  $pages_display = '<span class="page-tag all-tag">All Pages</span>';
              } else {
                  $slugs  = array_filter(array_map('trim', explode(',', $pages_val)));
                  $tags   = [];
                  foreach ($slugs as $s) {
                      $label = $page_list[$s] ?? $s;
                      $tags[] = '<span class="page-tag">' . htmlspecialchars($label) . '</span>';
                  }
                  $pages_display = implode('', $tags);
              }
            ?>
            <tr>
              <td class="text-muted small"><?= $i++ ?></td>
              <td>
                <strong><?= htmlspecialchars($n['title']) ?></strong>
                <div class="notif-msg-preview"><?= htmlspecialchars($n['message']) ?></div>
              </td>
              <td>
                <span class="notif-type-badge"
                  style="color:<?= $ti['color'] ?>;background:<?= $ti['bg'] ?>">
                  <i class="fa-solid <?= $ti['icon'] ?>"></i><?= $ti['label'] ?>
                </span>
              </td>
              <td><div class="pages-tags"><?= $pages_display ?></div></td>
              <td class="small">
                <?php if ($n['start_date'] || $n['end_date']): ?>
                  <?= $n['start_date'] ? '<span class="fw-semibold">' . date('d M Y', strtotime($n['start_date'])) . '</span>' : '<span class="text-muted">—</span>' ?>
                  &rarr;
                  <?= $n['end_date'] ? '<span class="fw-semibold">' . date('d M Y', strtotime($n['end_date'])) . '</span>' : '<span class="text-muted">No end</span>' ?>
                <?php else: ?>
                  <span class="text-muted">Always</span>
                <?php endif; ?>
              </td>
              <td class="text-center small fw-semibold"><?= (int)$n['sort_order'] ?></td>
              <td>
                <?php $st = $n['status'] ?? 'active'; ?>
                <span class="status-badge sb-<?= $st ?>">
                  <i class="fa-solid <?= $st === 'active' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-1" style="font-size:.65rem"></i>
                  <?= ucfirst($st) ?>
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-outline-primary me-1 btn-edit"
                  data-id="<?= $n['id'] ?>"
                  data-title="<?= htmlspecialchars($n['title'], ENT_QUOTES) ?>"
                  data-message="<?= htmlspecialchars($n['message'], ENT_QUOTES) ?>"
                  data-type="<?= htmlspecialchars($n['type'], ENT_QUOTES) ?>"
                  data-icon="<?= htmlspecialchars($n['icon'] ?? '', ENT_QUOTES) ?>"
                  data-show-pages="<?= htmlspecialchars($n['show_pages'] ?? 'all', ENT_QUOTES) ?>"
                  data-link-text="<?= htmlspecialchars($n['link_text'] ?? '', ENT_QUOTES) ?>"
                  data-link-url="<?= htmlspecialchars($n['link_url'] ?? '', ENT_QUOTES) ?>"
                  data-start-date="<?= htmlspecialchars($n['start_date'] ?? '', ENT_QUOTES) ?>"
                  data-end-date="<?= htmlspecialchars($n['end_date'] ?? '', ENT_QUOTES) ?>"
                  data-sort-order="<?= (int)$n['sort_order'] ?>"
                  data-status="<?= htmlspecialchars($n['status'] ?? 'active', ENT_QUOTES) ?>"
                  title="Edit">
                  <i class="fa-solid fa-pen fa-sm"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                  data-id="<?= $n['id'] ?>"
                  data-title="<?= htmlspecialchars($n['title'], ENT_QUOTES) ?>"
                  title="Delete">
                  <i class="fa-solid fa-trash fa-sm"></i>
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-bell-slash"></i>
      <p>No notifications yet.<br>Click <strong>Add Notification</strong> to create one.</p>
    </div>
  <?php endif; ?>
</div>

<!-- ── Add / Edit Notification Modal ──────────────────────── -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">

      <form method="POST" action="notification_action.php" id="notifForm">
        <input type="hidden" name="action"  id="nf_action"  value="create">
        <input type="hidden" name="notif_id" id="nf_id"     value="">

        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="notifModalLabel">
            <i class="fa-solid fa-bell-plus me-2"></i>Add Notification
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Title -->
            <div class="col-12">
              <label class="form-label">Notification Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" id="nf_title" required
                placeholder="e.g. Admissions Open 2026">
            </div>

            <!-- Message -->
            <div class="col-12">
              <label class="form-label">Message <span class="text-danger">*</span></label>
              <textarea class="form-control" name="message" id="nf_message" rows="3" required
                placeholder="Full notification message text…"></textarea>
            </div>

            <!-- Type & Icon -->
            <div class="col-md-6">
              <label class="form-label">Type</label>
              <select class="form-select" name="type" id="nf_type">
                <option value="info">Info (Blue)</option>
                <option value="success">Success (Green)</option>
                <option value="warning">Warning (Orange)</option>
                <option value="danger">Danger (Red)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Icon <small class="text-muted">(FontAwesome class)</small></label>
              <input type="text" class="form-control" name="icon" id="nf_icon"
                placeholder="fa-solid fa-bell" value="fa-solid fa-bell">
              <div class="form-text">e.g. <code>fa-solid fa-bullhorn</code>, <code>fa-solid fa-star</code></div>
            </div>

            <!-- Show on Pages -->
            <div class="col-12">
              <label class="form-label">Show on Pages</label>
              <div class="mb-2">
                <label class="page-check-item fw-bold" style="background:#fff8ed;border-color:#fd7e14;color:#fd7e14">
                  <input type="checkbox" id="nf_pages_all" value="all">
                  <i class="fa-solid fa-globe"></i> All Pages
                </label>
              </div>
              <div class="page-check-grid" id="nf_pages_grid">
                <?php foreach ($page_list as $slug => $label): ?>
                  <label class="page-check-item">
                    <input type="checkbox" class="nf-page-cb" name="pages[]" value="<?= htmlspecialchars($slug) ?>">
                    <?= htmlspecialchars($label) ?>
                  </label>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="show_pages" id="nf_show_pages" value="all">
              <div class="form-text mt-1">Select specific pages, or check "All Pages" to show everywhere.</div>
            </div>

            <!-- Optional Link -->
            <div class="col-md-6">
              <label class="form-label">Link Text <small class="text-muted">(optional)</small></label>
              <input type="text" class="form-control" name="link_text" id="nf_link_text"
                placeholder="e.g. Apply Now">
            </div>
            <div class="col-md-6">
              <label class="form-label">Link URL <small class="text-muted">(optional)</small></label>
              <input type="text" class="form-control" name="link_url" id="nf_link_url"
                placeholder="e.g. admissions.php">
            </div>

            <!-- Date Range -->
            <div class="col-md-6">
              <label class="form-label">Start Date <small class="text-muted">(leave blank = always)</small></label>
              <input type="date" class="form-control" name="start_date" id="nf_start_date">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date <small class="text-muted">(leave blank = no end)</small></label>
              <input type="date" class="form-control" name="end_date" id="nf_end_date">
            </div>

            <!-- Order & Status -->
            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" id="nf_sort_order"
                value="0" min="0">
              <div class="form-text">Lower number = shown first.</div>
            </div>
            <div class="col-md-8">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="nf_status">
                <option value="active">Active (shown on website)</option>
                <option value="inactive">Inactive (hidden)</option>
              </select>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="nf_submit_btn">
            <i class="fa-solid fa-plus me-1"></i>Add Notification
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
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Notification</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Delete <strong id="del_title"></strong>?<br>
          <small class="text-muted">This cannot be undone.</small>
        </p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="notification_action.php">
          <input type="hidden" name="action"   value="delete">
          <input type="hidden" name="notif_id" id="del_id">
          <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">
            <i class="fa-solid fa-trash me-1"></i>Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
window.addEventListener('load', function () {

  function modal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
  }

  var allCb    = document.getElementById('nf_pages_all');
  var pageCbs  = document.querySelectorAll('.nf-page-cb');
  var hiddenPg = document.getElementById('nf_show_pages');

  function syncPagesHidden() {
    if (allCb.checked) {
      hiddenPg.value = 'all';
    } else {
      var selected = [];
      pageCbs.forEach(function(cb) { if (cb.checked) selected.push(cb.value); });
      hiddenPg.value = selected.length ? selected.join(',') : 'all';
    }
  }

  allCb.addEventListener('change', function () {
    if (allCb.checked) {
      pageCbs.forEach(function(cb) { cb.checked = false; });
    }
    syncPagesHidden();
  });

  pageCbs.forEach(function(cb) {
    cb.addEventListener('change', function () {
      if (cb.checked) allCb.checked = false;
      syncPagesHidden();
    });
  });

  function setPages(pagesVal) {
    allCb.checked = false;
    pageCbs.forEach(function(cb) { cb.checked = false; });
    if (!pagesVal || pagesVal === 'all') {
      allCb.checked = true;
    } else {
      var parts = pagesVal.split(',').map(function(s){ return s.trim(); });
      pageCbs.forEach(function(cb) {
        if (parts.indexOf(cb.value) !== -1) cb.checked = true;
      });
    }
    syncPagesHidden();
  }

  /* ── Reset to Add mode ── */
  function openAdd() {
    document.getElementById('notifModalLabel').innerHTML =
      '<i class="fa-solid fa-bell-plus me-2"></i>Add Notification';
    document.getElementById('nf_action').value     = 'create';
    document.getElementById('nf_id').value         = '';
    document.getElementById('nf_title').value      = '';
    document.getElementById('nf_message').value    = '';
    document.getElementById('nf_type').value       = 'info';
    document.getElementById('nf_icon').value       = 'fa-solid fa-bell';
    document.getElementById('nf_link_text').value  = '';
    document.getElementById('nf_link_url').value   = '';
    document.getElementById('nf_start_date').value = '';
    document.getElementById('nf_end_date').value   = '';
    document.getElementById('nf_sort_order').value = '0';
    document.getElementById('nf_status').value     = 'active';
    document.getElementById('nf_submit_btn').innerHTML =
      '<i class="fa-solid fa-plus me-1"></i>Add Notification';
    setPages('all');
    modal('notifModal').show();
  }

  document.getElementById('openAddBtn')?.addEventListener('click', openAdd);

  /* ── Edit buttons ── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('notifModalLabel').innerHTML =
        '<i class="fa-solid fa-pen me-2"></i>Edit Notification';
      document.getElementById('nf_action').value     = 'edit';
      document.getElementById('nf_id').value         = d.id;
      document.getElementById('nf_title').value      = d.title;
      document.getElementById('nf_message').value    = d.message;
      document.getElementById('nf_type').value       = d.type;
      document.getElementById('nf_icon').value       = d.icon || 'fa-solid fa-bell';
      document.getElementById('nf_link_text').value  = d.linkText  || '';
      document.getElementById('nf_link_url').value   = d.linkUrl   || '';
      document.getElementById('nf_start_date').value = d.startDate || '';
      document.getElementById('nf_end_date').value   = d.endDate   || '';
      document.getElementById('nf_sort_order').value = d.sortOrder || '0';
      document.getElementById('nf_status').value     = d.status    || 'active';
      document.getElementById('nf_submit_btn').innerHTML =
        '<i class="fa-solid fa-save me-1"></i>Save Changes';
      setPages(d.showPages || 'all');
      modal('notifModal').show();
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
