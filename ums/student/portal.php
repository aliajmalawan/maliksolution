<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Dashboard'; $active_nav = 'dashboard';
$db = ums_db();

// "My Subjects" — matched via program name against the website's public course catalog (read-only reference).
$my_subjects = [];
if ($student['program'] !== '') {
    try {
        $prog = $db->real_escape_string($student['program']);
        $cr = $db->query("SELECT id FROM courses WHERE name='$prog' AND status='active' LIMIT 1")->fetch_assoc();
        if ($cr) {
            $sr = $db->query('SELECT name, description FROM course_subjects WHERE course_id=' . (int)$cr['id'] . ' ORDER BY sort_order ASC, id ASC');
            while ($s = $sr->fetch_assoc()) $my_subjects[] = $s;
        }
    } catch (Throwable $t) {}
}

$downloads = [];
try {
    $r = $db->query("SELECT * FROM downloads WHERE status='active' ORDER BY created_at DESC LIMIT 6");
    while ($d = $r->fetch_assoc()) $downloads[] = $d;
} catch (Throwable $t) {}

$datesheets = [];
try {
    $r = $db->query("SELECT * FROM datesheets WHERE status='active' ORDER BY created_at DESC LIMIT 5");
    while ($d = $r->fetch_assoc()) $datesheets[] = $d;
} catch (Throwable $t) {}

require __DIR__ . '/header.php';
$site = dirname(UMS_URL); // company website root, one level up
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-gauge me-2 text-primary"></i>Welcome, <?= e(explode(' ', $student['name'])[0]) ?>!</h1>
    <p><?= e($student['registration_no']) ?> · <?= e($student['program'] ?: 'Student') ?></p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card"><div><p>My Subjects</p><h3><?= count($my_subjects) ?></h3></div><div class="si si-b"><i class="fa-solid fa-book-open"></i></div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card"><div><p>Date Sheets</p><h3><?= count($datesheets) ?></h3></div><div class="si si-o"><i class="fa-solid fa-calendar-check"></i></div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card"><div><p>Downloads</p><h3><?= count($downloads) ?></h3></div><div class="si si-g"><i class="fa-solid fa-download"></i></div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="cardx stat-card"><div><p>Semester</p><h3><?= (int)$student['current_semester'] ?></h3></div><div class="si si-p"><i class="fa-solid fa-layer-group"></i></div></div>
  </div>
</div>

<?php if ($my_subjects): ?>
<div id="sec-subjects" style="scroll-margin-top:70px" class="mb-4">
  <div class="sec-hd"><h2><i class="fa-solid fa-book-open me-2 text-primary"></i>My Subjects</h2>
    <span class="text-muted" style="font-size:.72rem"><?= e($student['program']) ?> — <?= count($my_subjects) ?> subject<?= count($my_subjects) !== 1 ? 's' : '' ?></span></div>
  <div class="cardx" style="padding:0">
    <div class="tbl-wrap"><table class="table align-middle mb-0">
      <thead><tr><th style="width:42px">#</th><th>Subject</th><th>Description</th></tr></thead>
      <tbody>
        <?php foreach ($my_subjects as $i => $s): ?>
        <tr><td class="text-muted"><?= $i + 1 ?></td><td class="fw-bold" style="color:var(--navy)"><?= e($s['name']) ?></td>
          <td class="text-muted small"><?= e($s['description'] ?: '—') ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="sec-hd"><h2><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Exam Date Sheets</h2></div>
    <div class="cardx">
      <?php if ($datesheets): foreach ($datesheets as $ds): ?>
        <div class="ds-item">
          <div class="si si-o" style="width:36px;height:36px;font-size:.8rem"><i class="fa-solid fa-calendar-days"></i></div>
          <div style="flex:1;min-width:0">
            <div class="fw-bold" style="font-size:.82rem"><?= e($ds['title']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= e($ds['program'] ?: '') ?> <?= $ds['exam_type'] ? '· ' . e($ds['exam_type']) : '' ?></div>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-calendar-xmark"></i><p class="small">No date sheets available yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="sec-hd"><h2><i class="fa-solid fa-download me-2 text-success"></i>Study Resources</h2></div>
    <div class="cardx">
      <?php if ($downloads): foreach ($downloads as $dl): ?>
        <a href="<?= e($site) ?>/assets/uploads/downloads/<?= rawurlencode($dl['file'] ?? '') ?>" download class="ds-item" style="color:inherit">
          <div class="si si-g" style="width:36px;height:36px;font-size:.8rem"><i class="fa-solid fa-file-arrow-down"></i></div>
          <div style="flex:1;min-width:0">
            <div class="fw-bold" style="font-size:.82rem"><?= e($dl['title']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= e(strtoupper($dl['file_type'] ?? '')) ?> · <?= e(date('d M Y', strtotime($dl['created_at']))) ?></div>
          </div>
        </a>
      <?php endforeach; else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-folder-open"></i><p class="small">No downloads available yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
