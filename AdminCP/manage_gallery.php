<?php
declare(strict_types=1);
$page_title = 'Gallery';
$active_nav = 'gallery';
include 'header.php';

// Category filter
$filter  = trim($_GET['cat'] ?? 'all');
$where   = $filter !== 'all'
    ? "WHERE category = '" . mysqli_real_escape_string($conn, $filter) . "'"
    : '';

$items = mysqli_query($conn, "SELECT * FROM gallery $where ORDER BY sort_order ASC, created_at DESC");
$total = mysqli_num_rows($items);

// All distinct categories for filter tabs
$cat_res = mysqli_query($conn, "SELECT DISTINCT category FROM gallery WHERE category != '' ORDER BY category ASC");
$categories = [];
while ($r = mysqli_fetch_assoc($cat_res)) $categories[] = $r['category'];

// Counts
$r_all = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM gallery"));
$count_all = (int)($r_all[0] ?? 0);
?>

<style>
  /* ── Gallery card grid in admin ── */
  .gal-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:1rem; }
  .gal-card {
    background:#fff; border:1px solid var(--line);
    border-radius:10px; overflow:hidden;
    box-shadow:0 4px 16px rgba(23,32,51,.06);
    display:flex; flex-direction:column;
    transition:box-shadow .2s;
  }
  .gal-card:hover { box-shadow:0 8px 28px rgba(23,32,51,.12); }
  .gal-img-wrap { position:relative; aspect-ratio:4/3; overflow:hidden; background:var(--soft); }
  .gal-img-wrap img { width:100%; height:100%; object-fit:cover; display:block; }
  .gal-img-placeholder {
    width:100%; height:100%;
    display:grid; place-items:center;
    color:#b0bec5; font-size:2.5rem;
  }
  .gal-order-badge {
    position:absolute; top:8px; left:8px;
    background:rgba(7,31,64,.75); color:#fff;
    font-size:.68rem; font-weight:700;
    border-radius:4px; padding:.15rem .45rem;
  }
  .gal-status-dot {
    position:absolute; top:8px; right:8px;
    width:10px; height:10px; border-radius:50%;
    border:2px solid #fff;
  }
  .gal-status-dot.active   { background:#198754; }
  .gal-status-dot.inactive { background:#adb5bd; }
  .gal-body { padding:.85rem; flex:1; display:flex; flex-direction:column; gap:.3rem; }
  .gal-title { font-weight:700; font-size:.9rem; color:var(--navy); line-height:1.3; }
  .gal-cat   { font-size:.72rem; font-weight:700; color:#0d6efd; background:#edf4ff;
               border-radius:20px; padding:.15rem .55rem; display:inline-block; }
  .gal-desc  { font-size:.78rem; color:#6c757d; flex:1; }
  .gal-actions { display:flex; gap:.4rem; padding:.65rem .85rem; border-top:1px solid var(--line); }
  .gal-actions .btn { flex:1; font-size:.78rem; padding:.3rem .5rem; }

  /* Modal image preview */
  .preview-box {
    width:100%; max-height:200px; object-fit:cover;
    border-radius:8px; border:1px solid var(--line);
    margin-top:.5rem; display:none;
  }
  .cur-img-wrap { margin-top:.5rem; }
  .cur-img-wrap img {
    height:90px; object-fit:cover;
    border-radius:6px; border:1px solid var(--line);
  }

  /* Status badges */
  .sb-active   { background:#d1e7dd; color:#0a3622; }
  .sb-inactive { background:#e9ecef; color:#495057; }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-images me-2 text-primary"></i>Gallery</h1>
    <p>Manage gallery cards shown on the public gallery page</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openAddBtn">
    <i class="fa-solid fa-plus me-1"></i>Add Photo
  </button>
</div>

<!-- Flash message -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $map = [
      'saved'   => ['success','fa-circle-check',        'Gallery card saved successfully.'],
      'deleted' => ['warning','fa-trash',               'Gallery card deleted.'],
      'error'   => ['danger', 'fa-triangle-exclamation','An error occurred. Please try again.'],
    ];
    [$cls, $ico, $txt] = $map[$_GET['msg']] ?? ['danger','fa-triangle-exclamation','Unknown error.'];
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
    $cnt_r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM gallery WHERE category='" . mysqli_real_escape_string($conn, $cat) . "'"));
    $cnt = (int)($cnt_r[0] ?? 0);
  ?>
    <a href="?cat=<?= urlencode($cat) ?>"
       class="btn btn-sm <?= $filter === $cat ? 'btn-secondary' : 'btn-outline-secondary' ?>">
      <?= htmlspecialchars($cat) ?>
      <span class="badge bg-white text-dark ms-1"><?= $cnt ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Gallery Card Grid -->
<?php if ($total > 0): ?>
  <div class="gal-grid">
    <?php while ($item = mysqli_fetch_assoc($items)): ?>
      <?php
        $img_file = $item['image'] ?? '';
        $img_path = __DIR__ . '/../assets/uploads/gallery/' . $img_file;
        $has_img  = $img_file !== '' && file_exists($img_path);
        $st       = $item['status'] ?? 'active';
      ?>
      <div class="gal-card">
        <!-- Image -->
        <div class="gal-img-wrap">
          <?php if ($has_img): ?>
            <img src="../assets/uploads/gallery/<?= htmlspecialchars($img_file) ?>"
                 alt="<?= htmlspecialchars($item['title']) ?>">
          <?php else: ?>
            <div class="gal-img-placeholder"><i class="fa-regular fa-image"></i></div>
          <?php endif; ?>
          <span class="gal-order-badge">#<?= (int)$item['sort_order'] ?: $item['id'] ?></span>
          <span class="gal-status-dot <?= $st ?>"></span>
        </div>

        <!-- Info -->
        <div class="gal-body">
          <div class="gal-title"><?= htmlspecialchars($item['title']) ?></div>
          <?php if (!empty($item['category'])): ?>
            <div><span class="gal-cat"><?= htmlspecialchars($item['category']) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($item['description'])): ?>
            <div class="gal-desc">
              <?= htmlspecialchars(mb_substr($item['description'], 0, 70)) ?>
              <?= mb_strlen($item['description']) > 70 ? '…' : '' ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="gal-actions">
          <button class="btn btn-outline-primary btn-edit"
            data-id="<?= $item['id'] ?>"
            data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>"
            data-desc="<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES) ?>"
            data-category="<?= htmlspecialchars($item['category'] ?? '', ENT_QUOTES) ?>"
            data-order="<?= (int)$item['sort_order'] ?>"
            data-status="<?= htmlspecialchars($st, ENT_QUOTES) ?>"
            data-image="<?= htmlspecialchars($img_file, ENT_QUOTES) ?>">
            <i class="fa-solid fa-pen me-1"></i>Edit
          </button>
          <button class="btn btn-outline-danger btn-delete"
            data-id="<?= $item['id'] ?>"
            data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

<?php else: ?>
  <div class="cardx empty-state">
    <i class="fa-solid fa-images"></i>
    <p>
      No gallery <?= $filter !== 'all' ? "cards in <strong>" . htmlspecialchars($filter) . "</strong>" : 'cards' ?> yet.<br>
      <?php if ($filter !== 'all'): ?>
        <a href="?cat=all">View all</a> or
      <?php endif; ?>
      Click <strong>Add Photo</strong> to upload the first image.
    </p>
  </div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════
     Add / Edit Modal
══════════════════════════════════════════════════ -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">

      <form method="POST" action="gallery_action.php" enctype="multipart/form-data" id="galleryForm">
        <input type="hidden" name="action"     id="gf_action"    value="create">
        <input type="hidden" name="gallery_id" id="gf_id"        value="">
        <input type="hidden" name="old_image"  id="gf_old_image" value="">

        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="galleryModalLabel">
            <i class="fa-solid fa-plus me-2"></i>Add Photo
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Title -->
            <div class="col-md-8">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" id="gf_title" required
                placeholder="e.g. Campus View, Science Lab, Annual Sports Day">
            </div>

            <!-- Sort Order -->
            <div class="col-md-4">
              <label class="form-label">Display Order</label>
              <input type="number" class="form-control" name="sort_order" id="gf_order"
                min="0" placeholder="0" value="0">
              <div class="form-text">Lower number shows first.</div>
            </div>

            <!-- Category -->
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <input type="text" class="form-control" name="category" id="gf_category"
                placeholder="e.g. Campus, Events, Sports, Lab"
                list="cat_suggestions">
              <datalist id="cat_suggestions">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>">
                <?php endforeach; ?>
                <option value="Campus">
                <option value="Events">
                <option value="Sports">
                <option value="Lab">
                <option value="Classroom">
                <option value="Achievements">
              </datalist>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="gf_status">
                <option value="active">Active (shown on gallery page)</option>
                <option value="inactive">Inactive (hidden)</option>
              </select>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="form-label">Description <span class="text-muted small fw-normal">(optional)</span></label>
              <textarea class="form-control" name="description" id="gf_desc" rows="2"
                placeholder="Brief caption shown on the gallery card"></textarea>
            </div>

            <!-- Image Upload -->
            <div class="col-12">
              <label class="form-label">
                Photo <span class="text-danger" id="img_required_star">*</span>
                <span class="text-muted small fw-normal" id="img_optional_note" style="display:none">(leave blank to keep current)</span>
              </label>
              <input type="file" class="form-control" name="image" id="gf_image"
                accept="image/jpeg,image/png,image/gif,image/webp">
              <div class="form-text">JPG, PNG, GIF or WEBP — max 5 MB.</div>

              <!-- Live preview -->
              <img id="gf_preview" class="preview-box" alt="Preview">

              <!-- Current image (edit mode) -->
              <div id="gf_cur_wrap" class="cur-img-wrap d-none">
                <div class="text-muted small mb-1 mt-2">Current image:</div>
                <img id="gf_cur_img" src="" alt="Current">
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="gf_submit_btn">
            <i class="fa-solid fa-plus me-1"></i>Add Photo
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
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Photo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Delete <strong id="del_title"></strong>?</p>
        <small class="text-muted">The image file will also be removed. Cannot be undone.</small>
      </div>
      <div class="modal-footer">
        <form method="POST" action="gallery_action.php">
          <input type="hidden" name="action"     value="delete">
          <input type="hidden" name="gallery_id" id="del_id">
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
    document.getElementById('galleryModalLabel').innerHTML =
      '<i class="fa-solid fa-plus me-2"></i>Add Photo';
    document.getElementById('gf_action').value    = 'create';
    document.getElementById('gf_id').value        = '';
    document.getElementById('gf_old_image').value = '';
    document.getElementById('gf_title').value     = '';
    document.getElementById('gf_desc').value      = '';
    document.getElementById('gf_category').value  = '';
    document.getElementById('gf_order').value     = '0';
    document.getElementById('gf_status').value    = 'active';
    document.getElementById('gf_image').value     = '';
    document.getElementById('gf_preview').style.display = 'none';
    document.getElementById('gf_cur_wrap').classList.add('d-none');
    document.getElementById('gf_submit_btn').innerHTML =
      '<i class="fa-solid fa-plus me-1"></i>Add Photo';
    // Image required in create mode
    document.getElementById('gf_image').required = true;
    document.getElementById('img_required_star').style.display = '';
    document.getElementById('img_optional_note').style.display = 'none';
    modal('galleryModal').show();
  }

  document.getElementById('openAddBtn')?.addEventListener('click', openAdd);

  /* ── Edit buttons ── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;
      document.getElementById('galleryModalLabel').innerHTML =
        '<i class="fa-solid fa-pen me-2"></i>Edit Photo';
      document.getElementById('gf_action').value    = 'edit';
      document.getElementById('gf_id').value        = d.id;
      document.getElementById('gf_old_image').value = d.image;
      document.getElementById('gf_title').value     = d.title;
      document.getElementById('gf_desc').value      = d.desc;
      document.getElementById('gf_category').value  = d.category;
      document.getElementById('gf_order').value     = d.order;
      document.getElementById('gf_status').value    = d.status;
      document.getElementById('gf_image').value     = '';
      document.getElementById('gf_preview').style.display = 'none';
      document.getElementById('gf_submit_btn').innerHTML =
        '<i class="fa-solid fa-save me-1"></i>Save Changes';
      // Image optional in edit mode
      document.getElementById('gf_image').required = false;
      document.getElementById('img_required_star').style.display = 'none';
      document.getElementById('img_optional_note').style.display = '';

      var wrap = document.getElementById('gf_cur_wrap');
      var cImg = document.getElementById('gf_cur_img');
      if (d.image) {
        cImg.src = '../assets/uploads/gallery/' + d.image;
        wrap.classList.remove('d-none');
      } else {
        wrap.classList.add('d-none');
      }

      modal('galleryModal').show();
    });
  });

  /* ── Live image preview ── */
  document.getElementById('gf_image')?.addEventListener('change', function () {
    var file = this.files[0];
    var prev = document.getElementById('gf_preview');
    if (file) {
      var reader = new FileReader();
      reader.onload = function (e) {
        prev.src = e.target.result;
        prev.style.display = 'block';
      };
      reader.readAsDataURL(file);
      document.getElementById('gf_cur_wrap').classList.add('d-none');
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
