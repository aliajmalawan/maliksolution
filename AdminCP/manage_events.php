<?php
declare(strict_types=1);
$page_title = 'Manage Events';
$active_nav = 'events';
include 'header.php';

$events = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date DESC, created_at DESC");
$count  = mysqli_num_rows($events);
?>

<style>
  .event-thumb {
    width: 60px; height: 48px; object-fit: cover;
    border-radius: 6px; border: 1px solid var(--line);
  }
  .thumb-placeholder {
    width: 60px; height: 48px; border-radius: 6px;
    background: var(--soft); border: 1px dashed var(--line);
    display: grid; place-items: center; color: #b0bec5; font-size: .85rem;
  }
  .preview-box {
    width: 100%; max-height: 180px; object-fit: cover;
    border-radius: 8px; border: 1px solid var(--line); display: none;
    margin-top: .5rem;
  }
  .current-img-wrap { margin-top: .5rem; }
  .current-img-wrap img {
    height: 90px; object-fit: cover;
    border-radius: 6px; border: 1px solid var(--line);
  }
  .sb-active   { background: #d1e7dd; color: #0a3622; }
  .sb-inactive { background: #e9ecef; color: #495057; }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Manage Events</h1>
    <p>Add, edit and remove events shown on the website</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openAddBtn">
    <i class="fa-solid fa-plus me-1"></i>Add Event
  </button>
</div>

<!-- Flash -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $map = [
      'saved'   => ['success', 'fa-circle-check',        'Event saved successfully.'],
      'deleted' => ['warning', 'fa-trash',               'Event deleted.'],
      'error'   => ['danger',  'fa-triangle-exclamation','An error occurred. Please try again.'],
    ];
    [$cls, $ico, $txt] = $map[$_GET['msg']] ?? ['danger','fa-triangle-exclamation','Unknown error.'];
  ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
    <i class="fa-solid <?= $ico ?> me-2"></i><?= $txt ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Events Table -->
<div class="cardx">
  <?php if ($count > 0): ?>
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:36px">#</th>
            <th style="width:76px">Image</th>
            <th>Event Title</th>
            <th>Date</th>
            <th>Time</th>
            <th>Location</th>
            <th>Status</th>
            <th>Added</th>
            <th style="width:120px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($ev = mysqli_fetch_assoc($events)): ?>
            <tr>
              <td class="text-muted small"><?= $i++ ?></td>
              <td>
                <?php if (!empty($ev['image']) && file_exists(__DIR__ . '/../assets/uploads/events/' . $ev['image'])): ?>
                  <img src="../assets/uploads/events/<?= htmlspecialchars($ev['image']) ?>"
                       alt="" class="event-thumb">
                <?php else: ?>
                  <div class="thumb-placeholder"><i class="fa-regular fa-image"></i></div>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= htmlspecialchars($ev['title']) ?></strong>
                <?php if (!empty($ev['description'])): ?>
                  <div class="text-muted small">
                    <?= htmlspecialchars(mb_substr($ev['description'], 0, 55)) . (mb_strlen($ev['description']) > 55 ? '…' : '') ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="small">
                <?= $ev['event_date'] ? '<span class="fw-semibold">' . date('d M Y', strtotime($ev['event_date'])) . '</span>' : '<span class="text-muted">—</span>' ?>
              </td>
              <td class="small"><?= $ev['event_time'] ? htmlspecialchars($ev['event_time']) : '<span class="text-muted">—</span>' ?></td>
              <td class="small"><?= $ev['location'] ? htmlspecialchars($ev['location']) : '<span class="text-muted">—</span>' ?></td>
              <td>
                <?php $st = $ev['status'] ?? 'active'; ?>
                <span class="status-badge sb-<?= $st ?>">
                  <i class="fa-solid <?= $st === 'active' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-1" style="font-size:.65rem"></i>
                  <?= ucfirst($st) ?>
                </span>
              </td>
              <td class="text-muted small"><?= date('d M Y', strtotime($ev['created_at'])) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary me-1 btn-edit"
                  data-id="<?= $ev['id'] ?>"
                  data-title="<?= htmlspecialchars($ev['title'], ENT_QUOTES) ?>"
                  data-desc="<?= htmlspecialchars($ev['description'] ?? '', ENT_QUOTES) ?>"
                  data-date="<?= htmlspecialchars($ev['event_date'] ?? '', ENT_QUOTES) ?>"
                  data-time="<?= htmlspecialchars($ev['event_time'] ?? '', ENT_QUOTES) ?>"
                  data-location="<?= htmlspecialchars($ev['location'] ?? '', ENT_QUOTES) ?>"
                  data-image="<?= htmlspecialchars($ev['image'] ?? '', ENT_QUOTES) ?>"
                  data-status="<?= htmlspecialchars($ev['status'] ?? 'active', ENT_QUOTES) ?>"
                  title="Edit">
                  <i class="fa-solid fa-pen fa-sm"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                  data-id="<?= $ev['id'] ?>"
                  data-title="<?= htmlspecialchars($ev['title'], ENT_QUOTES) ?>"
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
      <i class="fa-solid fa-calendar-xmark"></i>
      <p>No events yet.<br>Click <strong>Add Event</strong> to create your first event.</p>
    </div>
  <?php endif; ?>
</div>

<!-- ── Add / Edit Event Modal ────────────────────────────── -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">

      <form method="POST" action="event_action.php" enctype="multipart/form-data" id="eventForm">
        <input type="hidden" name="action"   id="ef_action"   value="create">
        <input type="hidden" name="event_id" id="ef_event_id" value="">
        <input type="hidden" name="old_image" id="ef_old_image" value="">

        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="eventModalLabel">
            <i class="fa-solid fa-calendar-plus me-2"></i>Add Event
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Title -->
            <div class="col-12">
              <label class="form-label">Event Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" id="ef_title" required
                placeholder="e.g. Annual Science Exhibition 2026">
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="ef_desc" rows="3"
                placeholder="Details about the event, activities, who should attend…"></textarea>
            </div>

            <!-- Date & Time -->
            <div class="col-md-6">
              <label class="form-label">Event Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="event_date" id="ef_date" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Event Time</label>
              <input type="text" class="form-control" name="event_time" id="ef_time"
                placeholder="e.g. 10:00 AM – 2:00 PM">
            </div>

            <!-- Location -->
            <div class="col-12">
              <label class="form-label">Location / Venue</label>
              <input type="text" class="form-control" name="location" id="ef_location"
                placeholder="e.g. Malik Solution Main Campus, Auditorium Hall">
            </div>

            <!-- Image Upload -->
            <div class="col-12">
              <label class="form-label">Event Image</label>
              <input type="file" class="form-control" name="image" id="ef_image"
                accept="image/jpeg,image/png,image/gif,image/webp">
              <div class="form-text">JPG, PNG, GIF or WEBP — max 3 MB. Leave blank to keep existing image.</div>
              <!-- Preview new image before submit -->
              <img id="img_preview" class="preview-box" alt="Preview">
              <!-- Current image (edit mode) -->
              <div id="current_img_wrap" class="current-img-wrap d-none">
                <div class="text-muted small mb-1">Current image:</div>
                <img id="current_img" src="" alt="Current">
              </div>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="ef_status">
                <option value="active">Active (shown on website)</option>
                <option value="inactive">Inactive (hidden)</option>
              </select>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="ef_submit_btn">
            <i class="fa-solid fa-plus me-1"></i>Add Event
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
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Event</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Delete <strong id="del_title"></strong>?<br>
          <small class="text-muted">This will also remove the event image. Cannot be undone.</small>
        </p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="event_action.php">
          <input type="hidden" name="action"   value="delete">
          <input type="hidden" name="event_id" id="del_id">
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

  /* ── Reset to Add mode ── */
  function openAdd() {
    document.getElementById('eventModalLabel').innerHTML =
      '<i class="fa-solid fa-calendar-plus me-2"></i>Add Event';
    document.getElementById('ef_action').value    = 'create';
    document.getElementById('ef_event_id').value  = '';
    document.getElementById('ef_old_image').value = '';
    document.getElementById('ef_title').value     = '';
    document.getElementById('ef_desc').value      = '';
    document.getElementById('ef_date').value      = '';
    document.getElementById('ef_time').value      = '';
    document.getElementById('ef_location').value  = '';
    document.getElementById('ef_status').value    = 'active';
    document.getElementById('ef_image').value     = '';
    document.getElementById('img_preview').style.display = 'none';
    document.getElementById('current_img_wrap').classList.add('d-none');
    document.getElementById('ef_submit_btn').innerHTML =
      '<i class="fa-solid fa-plus me-1"></i>Add Event';
    modal('eventModal').show();
  }

  document.getElementById('openAddBtn')?.addEventListener('click', openAdd);

  /* ── Edit buttons ── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('eventModalLabel').innerHTML =
        '<i class="fa-solid fa-pen me-2"></i>Edit Event';
      document.getElementById('ef_action').value    = 'edit';
      document.getElementById('ef_event_id').value  = d.id;
      document.getElementById('ef_old_image').value = d.image;
      document.getElementById('ef_title').value     = d.title;
      document.getElementById('ef_desc').value      = d.desc;
      document.getElementById('ef_date').value      = d.date;
      document.getElementById('ef_time').value      = d.time;
      document.getElementById('ef_location').value  = d.location;
      document.getElementById('ef_status').value    = d.status;
      document.getElementById('ef_image').value     = '';
      document.getElementById('img_preview').style.display = 'none';
      document.getElementById('ef_submit_btn').innerHTML =
        '<i class="fa-solid fa-save me-1"></i>Save Changes';

      /* Show current image if present */
      var wrap = document.getElementById('current_img_wrap');
      var cImg = document.getElementById('current_img');
      if (d.image) {
        cImg.src = '../assets/uploads/events/' + d.image;
        wrap.classList.remove('d-none');
      } else {
        wrap.classList.add('d-none');
      }

      modal('eventModal').show();
    });
  });

  /* ── Live image preview ── */
  document.getElementById('ef_image')?.addEventListener('change', function () {
    var file = this.files[0];
    var prev = document.getElementById('img_preview');
    if (file) {
      var reader = new FileReader();
      reader.onload = function (e) {
        prev.src = e.target.result;
        prev.style.display = 'block';
      };
      reader.readAsDataURL(file);
      /* Hide current image when new one chosen */
      document.getElementById('current_img_wrap').classList.add('d-none');
    } else {
      prev.style.display = 'none';
    }
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
