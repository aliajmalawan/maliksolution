<?php
declare(strict_types=1);
$page_title = 'News & Announcements';
$active_nav = 'news';
include 'header.php';

// Filter by category
$filter  = $_GET['cat'] ?? 'all';
$allowed = ['all', 'news', 'announcement'];
if (!in_array($filter, $allowed, true)) $filter = 'all';

$where = $filter !== 'all' ? "WHERE category = '" . mysqli_real_escape_string($conn, $filter) . "'" : '';
$items = mysqli_query($conn, "SELECT * FROM news $where ORDER BY news_date DESC, created_at DESC");
$total = mysqli_num_rows($items);

// Tab counts
$counts = [];
foreach ($allowed as $c) {
    $w = $c !== 'all' ? "WHERE category='$c'" : '';
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM news $w"));
    $counts[$c] = (int)($r[0] ?? 0);
}
?>

<style>
  .news-thumb {
    width: 64px; height: 48px; object-fit: cover;
    border-radius: 6px; border: 1px solid var(--line);
  }
  .thumb-ph {
    width: 64px; height: 48px; border-radius: 6px;
    background: var(--soft); border: 1px dashed var(--line);
    display: grid; place-items: center; color: #b0bec5; font-size: .85rem;
  }
  .preview-box {
    width: 100%; max-height: 190px; object-fit: cover;
    border-radius: 8px; border: 1px solid var(--line);
    margin-top: .5rem; display: none;
  }
  .current-img-wrap { margin-top: .5rem; }
  .current-img-wrap img {
    height: 88px; object-fit: cover;
    border-radius: 6px; border: 1px solid var(--line);
  }
  /* Category badges */
  .cat-news         { background: #e8f4fd; color: #0a58ca; }
  .cat-announcement { background: #fff3cd; color: #856404; }
  /* Status */
  .sb-active   { background: #d1e7dd; color: #0a3622; }
  .sb-inactive { background: #e9ecef; color: #495057; }
</style>

<!-- Page Header -->
<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-newspaper me-2 text-primary"></i>News &amp; Announcements</h1>
    <p>Manage news articles and announcements shown on the website</p>
  </div>
  <button class="btn btn-primary btn-sm" id="openAddBtn">
    <i class="fa-solid fa-plus me-1"></i>Add New
  </button>
</div>

<!-- Flash message -->
<?php if (!empty($_GET['msg'])): ?>
  <?php
    $map = [
      'saved'   => ['success', 'fa-circle-check',        'Entry saved successfully.'],
      'deleted' => ['warning', 'fa-trash',               'Entry deleted.'],
      'error'   => ['danger',  'fa-triangle-exclamation','An error occurred. Please try again.'],
    ];
    [$cls, $ico, $txt] = $map[$_GET['msg']] ?? ['danger','fa-triangle-exclamation','Unknown error.'];
  ?>
  <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
    <i class="fa-solid <?= $ico ?> me-2"></i><?= $txt ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Category Filter Tabs -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php
  $tab_cfg = [
    'all'          => ['secondary', 'All'],
    'news'         => ['primary',   'News'],
    'announcement' => ['warning',   'Announcements'],
  ];
  foreach ($tab_cfg as $k => [$color, $label]):
    $active_cls = $filter === $k ? "btn-$color" : "btn-outline-$color";
  ?>
    <a href="?cat=<?= $k ?>" class="btn btn-sm <?= $active_cls ?>">
      <?= $label ?>
      <span class="badge bg-white text-dark ms-1"><?= $counts[$k] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- News Table -->
<div class="cardx">
  <?php if ($total > 0): ?>
    <div class="table-wrap">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:36px">#</th>
            <th style="width:76px">Image</th>
            <th>Title</th>
            <th style="width:140px">Category</th>
            <th style="width:110px">Date</th>
            <th style="width:100px">Status</th>
            <th style="width:95px">Added</th>
            <th style="width:130px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($row = mysqli_fetch_assoc($items)): ?>
            <tr>
              <td class="text-muted small"><?= $i++ ?></td>

              <!-- Thumbnail -->
              <td>
                <?php
                  $img_file = $row['image'] ?? '';
                  $img_path = __DIR__ . '/../assets/uploads/news/' . $img_file;
                  $has_img  = $img_file !== '' && file_exists($img_path);
                ?>
                <?php if ($has_img): ?>
                  <img src="../assets/uploads/news/<?= htmlspecialchars($img_file) ?>"
                       alt="" class="news-thumb">
                <?php else: ?>
                  <div class="thumb-ph"><i class="fa-regular fa-image"></i></div>
                <?php endif; ?>
              </td>

              <!-- Title + excerpt -->
              <td>
                <strong><?= htmlspecialchars($row['title']) ?></strong>
                <?php if (!empty($row['content'])): ?>
                  <div class="text-muted small">
                    <?= htmlspecialchars(mb_substr(strip_tags($row['content']), 0, 60)) ?>
                    <?= mb_strlen($row['content']) > 60 ? '…' : '' ?>
                  </div>
                <?php endif; ?>
              </td>

              <!-- Category badge -->
              <td>
                <span class="status-badge cat-<?= $row['category'] ?>">
                  <?php if ($row['category'] === 'news'): ?>
                    <i class="fa-solid fa-newspaper me-1" style="font-size:.65rem"></i>News
                  <?php else: ?>
                    <i class="fa-solid fa-bullhorn me-1" style="font-size:.65rem"></i>Announcement
                  <?php endif; ?>
                </span>
              </td>

              <!-- Date -->
              <td class="small fw-semibold">
                <?= !empty($row['news_date'])
                    ? date('d M Y', strtotime($row['news_date']))
                    : '<span class="text-muted">—</span>' ?>
              </td>

              <!-- Status -->
              <td>
                <?php $st = $row['status'] ?? 'active'; ?>
                <span class="status-badge sb-<?= $st ?>">
                  <i class="fa-solid <?= $st === 'active' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-1" style="font-size:.65rem"></i>
                  <?= ucfirst($st) ?>
                </span>
              </td>

              <!-- Added -->
              <td class="text-muted small"><?= date('d M Y', strtotime($row['created_at'])) ?></td>

              <!-- Actions -->
              <td>
                <button class="btn btn-sm btn-outline-primary me-1 btn-edit"
                  data-id="<?= $row['id'] ?>"
                  data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>"
                  data-content="<?= htmlspecialchars($row['content'] ?? '', ENT_QUOTES) ?>"
                  data-category="<?= htmlspecialchars($row['category'] ?? 'news', ENT_QUOTES) ?>"
                  data-date="<?= htmlspecialchars($row['news_date'] ?? '', ENT_QUOTES) ?>"
                  data-status="<?= htmlspecialchars($row['status'] ?? 'active', ENT_QUOTES) ?>"
                  data-image="<?= htmlspecialchars($row['image'] ?? '', ENT_QUOTES) ?>"
                  title="Edit">
                  <i class="fa-solid fa-pen fa-sm"></i> Edit
                </button>
                <button class="btn btn-sm btn-outline-danger btn-delete"
                  data-id="<?= $row['id'] ?>"
                  data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>"
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
      <i class="fa-solid fa-newspaper"></i>
      <p>
        No <?= $filter !== 'all' ? "<strong>$filter</strong> entries" : 'news or announcements' ?> yet.<br>
        <?php if ($filter !== 'all'): ?>
          <a href="?cat=all">View all entries</a>
        <?php else: ?>
          Click <strong>Add New</strong> to publish your first entry.
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════
     Add / Edit Modal
     ═══════════════════════════════════════════════ -->
<div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">

      <form method="POST" action="news_action.php" enctype="multipart/form-data" id="newsForm">
        <input type="hidden" name="action"    id="nf_action"    value="create">
        <input type="hidden" name="news_id"   id="nf_news_id"   value="">
        <input type="hidden" name="old_image" id="nf_old_image" value="">

        <!-- Modal Header -->
        <div class="modal-header" style="background:var(--navy)">
          <h5 class="modal-title text-white" id="newsModalLabel">
            <i class="fa-solid fa-plus me-2"></i>Add News / Announcement
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
          <div class="row g-3">

            <!-- Category selector at the top so it drives intent -->
            <div class="col-md-6">
              <label class="form-label">Category <span class="text-danger">*</span></label>
              <select class="form-select" name="category" id="nf_category" required>
                <option value="news">📰 News</option>
                <option value="announcement">📢 Announcement</option>
              </select>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="nf_status">
                <option value="active">Active (visible on website)</option>
                <option value="inactive">Inactive (hidden)</option>
              </select>
            </div>

            <!-- Title -->
            <div class="col-12">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" id="nf_title" required
                placeholder="Enter a clear, descriptive title">
            </div>

            <!-- Content -->
            <div class="col-12">
              <label class="form-label">Content / Details</label>
              <textarea class="form-control" name="content" id="nf_content" rows="5"
                placeholder="Write the full news article or announcement details here…"></textarea>
            </div>

            <!-- Date -->
            <div class="col-md-6">
              <label class="form-label">Publish Date</label>
              <input type="date" class="form-control" name="news_date" id="nf_date">
            </div>

            <!-- Image -->
            <div class="col-12">
              <label class="form-label">Featured Image</label>
              <input type="file" class="form-control" name="image" id="nf_image"
                accept="image/jpeg,image/png,image/gif,image/webp">
              <div class="form-text">JPG, PNG, GIF or WEBP — max 3 MB. Leave blank to keep existing image.</div>

              <!-- New image live preview -->
              <img id="nf_preview" class="preview-box" alt="Preview">

              <!-- Current image in edit mode -->
              <div id="nf_cur_wrap" class="current-img-wrap d-none">
                <div class="text-muted small mb-1">Current image:</div>
                <img id="nf_cur_img" src="" alt="Current image">
              </div>
            </div>

          </div><!-- /.row -->
        </div><!-- /.modal-body -->

        <!-- Modal Footer -->
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="nf_submit_btn">
            <i class="fa-solid fa-plus me-1"></i>Publish
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     Delete Confirm Modal
     ═══════════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa-solid fa-trash me-2"></i>Delete Entry</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Delete <strong id="del_title"></strong>?</p>
        <small class="text-muted">This will also remove the image. Cannot be undone.</small>
      </div>
      <div class="modal-footer">
        <form method="POST" action="news_action.php">
          <input type="hidden" name="action"  value="delete">
          <input type="hidden" name="news_id" id="del_id">
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
     JavaScript (after Bootstrap via window.load)
     ═══════════════════════════════════════════════ -->
<script>
window.addEventListener('load', function () {

  /* Helper: get or create Bootstrap modal instance */
  function modal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
  }

  /* ── Reset form to Add/Create mode ── */
  function openAdd() {
    document.getElementById('newsModalLabel').innerHTML =
      '<i class="fa-solid fa-plus me-2"></i>Add News / Announcement';
    document.getElementById('nf_action').value    = 'create';
    document.getElementById('nf_news_id').value   = '';
    document.getElementById('nf_old_image').value = '';
    document.getElementById('nf_title').value     = '';
    document.getElementById('nf_content').value   = '';
    document.getElementById('nf_category').value  = 'news';
    document.getElementById('nf_date').value      = '';
    document.getElementById('nf_status').value    = 'active';
    document.getElementById('nf_image').value     = '';
    document.getElementById('nf_preview').style.display = 'none';
    document.getElementById('nf_cur_wrap').classList.add('d-none');
    document.getElementById('nf_submit_btn').innerHTML =
      '<i class="fa-solid fa-paper-plane me-1"></i>Publish';
    modal('newsModal').show();
  }

  document.getElementById('openAddBtn')?.addEventListener('click', openAdd);

  /* ── Edit buttons ── */
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var d = btn.dataset;

      document.getElementById('newsModalLabel').innerHTML =
        '<i class="fa-solid fa-pen me-2"></i>Edit Entry';
      document.getElementById('nf_action').value    = 'edit';
      document.getElementById('nf_news_id').value   = d.id;
      document.getElementById('nf_old_image').value = d.image;
      document.getElementById('nf_title').value     = d.title;
      document.getElementById('nf_content').value   = d.content;
      document.getElementById('nf_category').value  = d.category;
      document.getElementById('nf_date').value      = d.date;
      document.getElementById('nf_status').value    = d.status;
      document.getElementById('nf_image').value     = '';
      document.getElementById('nf_preview').style.display = 'none';
      document.getElementById('nf_submit_btn').innerHTML =
        '<i class="fa-solid fa-save me-1"></i>Save Changes';

      /* Show existing image if present */
      var wrap = document.getElementById('nf_cur_wrap');
      var cImg = document.getElementById('nf_cur_img');
      if (d.image) {
        cImg.src = '../assets/uploads/news/' + d.image;
        wrap.classList.remove('d-none');
      } else {
        wrap.classList.add('d-none');
      }

      modal('newsModal').show();
    });
  });

  /* ── Live image preview ── */
  document.getElementById('nf_image')?.addEventListener('change', function () {
    var file = this.files[0];
    var prev = document.getElementById('nf_preview');
    if (file) {
      var reader = new FileReader();
      reader.onload = function (e) {
        prev.src = e.target.result;
        prev.style.display = 'block';
      };
      reader.readAsDataURL(file);
      document.getElementById('nf_cur_wrap').classList.add('d-none');
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
