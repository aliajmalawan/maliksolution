<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Dashboard'; $active_nav = 'dashboard';
$db = ums_db();

$total_subj   = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('teacher_subjects') . " WHERE teacher_id=$tid")->fetch_assoc()['c'];
$total_quiz   = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('teacher_quizzes') . " WHERE teacher_id=$tid")->fetch_assoc()['c'];
$total_assign = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('teacher_assignments') . " WHERE teacher_id=$tid")->fetch_assoc()['c'];
$total_tests  = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('teacher_tests') . " WHERE teacher_id=$tid")->fetch_assoc()['c'];
$upcoming     = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('teacher_tests') . " WHERE teacher_id=$tid AND status='scheduled' AND test_date >= NOW()")->fetch_assoc()['c'];
$active_quiz  = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('teacher_quizzes') . " WHERE teacher_id=$tid AND status='active'")->fetch_assoc()['c'];

$recent_quizzes = $db->query('SELECT tq.*, c.name AS course_name, cs.name AS subject_name FROM ' . tbl('teacher_quizzes') . ' tq LEFT JOIN courses c ON c.id=tq.course_id LEFT JOIN course_subjects cs ON cs.id=tq.subject_id WHERE tq.teacher_id=' . $tid . ' ORDER BY tq.created_at DESC LIMIT 5');
$recent_assigns = $db->query('SELECT ta.*, c.name AS course_name, cs.name AS subject_name FROM ' . tbl('teacher_assignments') . ' ta LEFT JOIN courses c ON c.id=ta.course_id LEFT JOIN course_subjects cs ON cs.id=ta.subject_id WHERE ta.teacher_id=' . $tid . ' ORDER BY ta.created_at DESC LIMIT 5');
$upcoming_tests = $db->query('SELECT tt.*, c.name AS course_name, cs.name AS subject_name FROM ' . tbl('teacher_tests') . ' tt LEFT JOIN courses c ON c.id=tt.course_id LEFT JOIN course_subjects cs ON cs.id=tt.subject_id WHERE tt.teacher_id=' . $tid . ' AND tt.status="scheduled" ORDER BY tt.test_date ASC LIMIT 5');

require __DIR__ . '/header.php';
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-gauge me-2 text-primary"></i>Welcome, <?= e(explode(' ', $teacher['name'])[0]) ?>!</h1>
    <p><?= e($teacher['designation'] ?: 'Teacher') ?> — <?= e(UMS_NAME) ?></p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl">
    <div class="cardx stat-card"><div><p>My Subjects</p><h3><?= $total_subj ?></h3></div><div class="si si-b"><i class="fa-solid fa-book-open"></i></div></div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card"><div><p>Active Quizzes</p><h3><?= $active_quiz ?></h3></div><div class="si si-g"><i class="fa-solid fa-circle-question"></i></div></div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card"><div><p>Assignments</p><h3><?= $total_assign ?></h3></div><div class="si si-o"><i class="fa-solid fa-file-pen"></i></div></div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card"><div><p>Upcoming Tests</p><h3><?= $upcoming ?></h3></div><div class="si si-p"><i class="fa-solid fa-clipboard-list"></i></div></div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card"><div><p>Total Tests</p><h3><?= $total_tests ?></h3></div><div class="si si-t"><i class="fa-solid fa-calendar-check"></i></div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="cardx h-100">
      <div class="sec-hd"><h2><i class="fa-solid fa-circle-question me-2 text-primary"></i>Recent Quizzes</h2></div>
      <?php if ($recent_quizzes->num_rows > 0): ?>
        <?php while ($q = $recent_quizzes->fetch_assoc()): ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div class="si si-g" style="width:34px;height:34px;font-size:.8rem;border-radius:8px;flex-shrink:0"><i class="fa-solid fa-circle-question"></i></div>
          <div style="min-width:0;flex:1">
            <div class="fw-bold" style="font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($q['title']) ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= e($q['subject_name'] ?? '—') ?></div>
          </div>
          <span class="bs bs-<?= e($q['status']) ?>"><?= e(ucfirst($q['status'])) ?></span>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-circle-question"></i><p class="small">No quizzes yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="cardx h-100">
      <div class="sec-hd"><h2><i class="fa-solid fa-file-pen me-2 text-success"></i>Recent Assignments</h2></div>
      <?php if ($recent_assigns->num_rows > 0): ?>
        <?php while ($a = $recent_assigns->fetch_assoc()): ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div class="si si-o" style="width:34px;height:34px;font-size:.8rem;border-radius:8px;flex-shrink:0"><i class="fa-solid fa-file-pen"></i></div>
          <div style="min-width:0;flex:1">
            <div class="fw-bold" style="font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($a['title']) ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= e($a['subject_name'] ?? '—') ?><?= $a['due_date'] ? ' · Due: ' . e(date('d M', strtotime($a['due_date']))) : '' ?></div>
          </div>
          <span class="bs bs-<?= e($a['status']) ?>"><?= e(ucfirst($a['status'])) ?></span>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-file-pen"></i><p class="small">No assignments yet.</p></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="cardx h-100">
      <div class="sec-hd"><h2><i class="fa-solid fa-clipboard-list me-2 text-warning"></i>Upcoming Tests</h2></div>
      <?php if ($upcoming_tests->num_rows > 0): ?>
        <?php while ($t = $upcoming_tests->fetch_assoc()): ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div class="si si-p" style="width:34px;height:34px;font-size:.8rem;border-radius:8px;flex-shrink:0"><i class="fa-solid fa-calendar-check"></i></div>
          <div style="min-width:0;flex:1">
            <div class="fw-bold" style="font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($t['title']) ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= $t['test_date'] ? e(date('d M Y, h:i A', strtotime($t['test_date']))) : 'Date TBD' ?></div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-clipboard-list"></i><p class="small">No upcoming tests.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
