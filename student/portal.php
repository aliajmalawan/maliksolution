<?php
declare(strict_types=1);
session_start();
require_once '../includes/config.php';

if (empty($_SESSION['student_id'])) { header('Location: login.php'); exit; }

$sid = (int)$_SESSION['student_id'];
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admissions WHERE id=$sid AND status='approved'"));
if (!$row) { session_destroy(); header('Location: login.php?msg=session_expired'); exit; }

$_SESSION['student_name'] = $row['student_name'];
$_SESSION['student_sid']  = $row['student_id'];
$_SESSION['student_prog'] = $row['program'];

$name          = htmlspecialchars($row['student_name']);
$student_id    = htmlspecialchars($row['student_id']);
$program       = htmlspecialchars($row['program']);
$first_name    = htmlspecialchars(explode(' ', $row['student_name'])[0]);
$student_photo = $row['student_photo'] ?? '';

/* ── My Subjects ── */
$my_subjects = [];
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS course_subjects (id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, name VARCHAR(255) NOT NULL DEFAULT '', description VARCHAR(500) DEFAULT '', sort_order INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$_prog_esc = mysqli_real_escape_string($conn, $row['program'] ?? '');
if ($_prog_esc !== '') {
    $_cr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM courses WHERE name='$_prog_esc' AND status='active' LIMIT 1"));
    if ($_cr) {
        $_sr = mysqli_query($conn, "SELECT name, description FROM course_subjects WHERE course_id=".(int)$_cr['id']." ORDER BY sort_order ASC, id ASC");
        if ($_sr) while ($s = mysqli_fetch_assoc($_sr)) $my_subjects[] = $s;
    }
}

$downloads_res = mysqli_query($conn, "SELECT * FROM downloads WHERE status='active' ORDER BY created_at DESC LIMIT 6");
$downloads = [];
if ($downloads_res) while ($dl = mysqli_fetch_assoc($downloads_res)) $downloads[] = $dl;

$ds_res = mysqli_query($conn, "SELECT * FROM datesheets WHERE status='active' ORDER BY created_at DESC LIMIT 5");
$datesheets = [];
if ($ds_res) while ($ds = mysqli_fetch_assoc($ds_res)) $datesheets[] = $ds;

$page_title = 'Dashboard';
$active_nav = 'dashboard';
include '_inc_head.php';
?>

<!-- Welcome Banner -->
<div class="welcome-banner mb-4">
  <div>
    <div class="wb-greeting">Welcome back, <?= $name ?></div>
    <div class="wb-meta">
      <span class="wb-pill wb-pill-prog"><i class="fa-solid fa-graduation-cap"></i><?= $program ?></span>
      <?php if ($student_id): ?>
      <span class="wb-pill wb-pill-id"><i class="fa-solid fa-id-card"></i><?= $student_id ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div class="wb-avatar">
    <?php if (!empty($student_photo)): ?>
      <img src="../<?= htmlspecialchars($student_photo) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
    <?php else: ?>
      <i class="fa-solid fa-user-graduate"></i>
    <?php endif; ?>
  </div>
</div>

<!-- Stats -->
<div class="stats-row mb-4">
  <div class="stat-card">
    <div class="stat-icon" style="background:#edf4ff;color:#0d6efd"><i class="fa-solid fa-book-open"></i></div>
    <div><div class="stat-info-val"><?= count($my_subjects) ?></div><div class="stat-info-lbl">My Subjects</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#fff7ed;color:#ea580c"><i class="fa-solid fa-calendar-check"></i></div>
    <div><div class="stat-info-val"><?= count($datesheets) ?></div><div class="stat-info-lbl">Date Sheets</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f0fdf4;color:#16a34a"><i class="fa-solid fa-download"></i></div>
    <div><div class="stat-info-val"><?= count($downloads) ?></div><div class="stat-info-lbl">Downloads</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#dcfce7;color:#15803d"><i class="fa-solid fa-circle-check"></i></div>
    <div><div class="stat-info-val" style="font-size:1rem">Active</div><div class="stat-info-lbl">Status</div></div>
  </div>
</div>

<!-- My Subjects -->
<?php if (!empty($my_subjects)): ?>
<div id="sec-subjects" style="scroll-margin-top:70px">
  <div class="sec-hdr">
    <span class="sec-title"><i class="fa-solid fa-book-open"></i> My Subjects</span>
    <span style="font-size:.7rem;color:var(--muted)"><?= $program ?> &mdash; <?= count($my_subjects) ?> subject<?= count($my_subjects)!==1?'s':'' ?></span>
  </div>
  <div class="panel">
    <div class="panel-head"><div class="panel-head-title"><i class="fa-solid fa-list-check"></i> Enrolled Subjects</div></div>
    <div class="panel-body-flush">
      <table class="subj-table">
        <thead><tr><th class="subj-sn">#</th><th>Subject Name</th><th>Description</th></tr></thead>
        <tbody>
          <?php foreach ($my_subjects as $i => $s): ?>
          <tr>
            <td class="subj-sn"><?= $i+1 ?></td>
            <td style="font-weight:700;color:var(--navy)"><?= htmlspecialchars($s['name']) ?></td>
            <td style="color:var(--muted);font-size:.76rem"><?= htmlspecialchars($s['description']) ?: '<span style="color:#cbd5e1">—</span>' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Date Sheets + Downloads -->
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="sec-hdr"><span class="sec-title"><i class="fa-solid fa-calendar-days"></i> Upcoming Exams</span><a href="../datesheet.php" class="sec-link">View all <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="panel">
      <div class="panel-head"><div class="panel-head-title"><i class="fa-solid fa-calendar-check"></i> Exam Date Sheets</div></div>
      <div class="panel-body-flush">
        <?php if (!empty($datesheets)): foreach ($datesheets as $ds): $etype=$ds['exam_type']??''; $prog=$ds['program']??''; ?>
        <a href="../datesheet.php" class="ds-item">
          <div class="ds-icon"><i class="fa-solid fa-calendar-days"></i></div>
          <div style="flex:1;min-width:0">
            <div class="ds-title"><?= htmlspecialchars($ds['title']) ?></div>
            <div class="ds-meta">
              <?php if ($prog): ?><span><?= htmlspecialchars($prog) ?></span><?php endif; ?>
              <?php if ($etype): ?><span class="ds-badge" style="background:#fff0f0;color:#dc2626"><?= htmlspecialchars($etype) ?></span><?php endif; ?>
            </div>
          </div>
          <i class="fa-solid fa-chevron-right" style="font-size:.72rem;color:#c5d0e0;flex-shrink:0"></i>
        </a>
        <?php endforeach; else: ?>
        <div class="subj-no-data"><i class="fa-solid fa-calendar-xmark"></i>No date sheets available yet</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="sec-hdr"><span class="sec-title"><i class="fa-solid fa-download"></i> Study Resources</span><a href="../downloads.php" class="sec-link">View all <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="panel">
      <div class="panel-head"><div class="panel-head-title"><i class="fa-solid fa-file-arrow-down"></i> Recent Downloads</div></div>
      <div class="panel-body-flush">
        <?php if (!empty($downloads)):
          $dlc = ['PDF'=>'#dc2626','DOCX'=>'#2563eb','DOC'=>'#2563eb','XLSX'=>'#16a34a','XLS'=>'#16a34a','PPTX'=>'#ea580c','PPT'=>'#ea580c'];
          foreach ($downloads as $dl): $ft=strtoupper($dl['file_type']??''); $dc=$dlc[$ft]??'#64748b'; ?>
        <a href="../assets/uploads/downloads/<?= rawurlencode($dl['file']??'') ?>" download class="dl-item">
          <div class="dl-icon" style="background:<?= $dc ?>"><i class="fa-solid fa-file-arrow-down"></i></div>
          <div style="flex:1;min-width:0">
            <div class="dl-title"><?= htmlspecialchars($dl['title']) ?></div>
            <div class="dl-meta"><?= $ft ?> &middot; <?= date('d M Y',strtotime($dl['created_at'])) ?></div>
          </div>
          <i class="fa-solid fa-download" style="margin-left:auto;color:#c5d0e0;font-size:.8rem;flex-shrink:0"></i>
        </a>
        <?php endforeach; else: ?>
        <div class="subj-no-data"><i class="fa-solid fa-folder-open"></i>No downloads available yet</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$extra_js = <<<JS
/* smooth scroll to My Subjects on same page */
var subjLink = document.querySelector('a[href="portal.php#sec-subjects"]');
if (subjLink && window.location.pathname.endsWith('portal.php')) {
  subjLink.setAttribute('href','#sec-subjects');
  subjLink.addEventListener('click', function(e) {
    var t = document.getElementById('sec-subjects');
    if (!t) return;
    e.preventDefault();
    if (window.innerWidth < 992) closeSidebar();
    setTimeout(function(){ t.scrollIntoView({behavior:'smooth',block:'start'}); }, window.innerWidth < 992 ? 320 : 0);
  });
}
JS;
include '_inc_foot.php';
?>
