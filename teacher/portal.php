<?php
declare(strict_types=1);
$page_title = 'Dashboard';
$active_nav = 'dashboard';
include 'header.php';

$total_subj   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teacher_subject_assignments WHERE teacher_id=$tid"))[0] ?? 0;
$total_quiz   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teacher_quizzes WHERE teacher_id=$tid"))[0] ?? 0;
$total_assign = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=$tid"))[0] ?? 0;
$total_tests  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teacher_tests WHERE teacher_id=$tid"))[0] ?? 0;
$upcoming     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teacher_tests WHERE teacher_id=$tid AND status='scheduled' AND test_date >= NOW()"))[0] ?? 0;
$active_quiz  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM teacher_quizzes WHERE teacher_id=$tid AND status='active'"))[0] ?? 0;

$recent_quizzes = mysqli_query($conn, "SELECT tq.*, c.name AS course_name, cs.name AS subject_name FROM teacher_quizzes tq LEFT JOIN courses c ON c.id=tq.course_id LEFT JOIN course_subjects cs ON cs.id=tq.subject_id WHERE tq.teacher_id=$tid ORDER BY tq.created_at DESC LIMIT 5");
$recent_assigns = mysqli_query($conn, "SELECT ta.*, c.name AS course_name, cs.name AS subject_name FROM teacher_assignments ta LEFT JOIN courses c ON c.id=ta.course_id LEFT JOIN course_subjects cs ON cs.id=ta.subject_id WHERE ta.teacher_id=$tid ORDER BY ta.created_at DESC LIMIT 5");
$upcoming_tests = mysqli_query($conn, "SELECT tt.*, c.name AS course_name, cs.name AS subject_name FROM teacher_tests tt LEFT JOIN courses c ON c.id=tt.course_id LEFT JOIN course_subjects cs ON cs.id=tt.subject_id WHERE tt.teacher_id=$tid AND tt.status='scheduled' ORDER BY tt.test_date ASC LIMIT 5");
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-gauge me-2 text-primary"></i>Welcome, <?= htmlspecialchars(explode(' ', $teacher['name'])[0]) ?>!</h1>
    <p><?= htmlspecialchars($teacher['designation'] ?: 'Teacher') ?> — <?= htmlspecialchars($teacher['department'] ?: 'Malik Solution') ?></p>
  </div>
  <a href="quizzes.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>New Quiz</a>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div><p>My Subjects</p><h3><?= $total_subj ?></h3></div>
      <div class="si si-b"><i class="fa-solid fa-book-open"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div><p>Active Quizzes</p><h3><?= $active_quiz ?></h3></div>
      <div class="si si-g"><i class="fa-solid fa-circle-question"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div><p>Assignments</p><h3><?= $total_assign ?></h3></div>
      <div class="si si-o"><i class="fa-solid fa-file-pen"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div><p>Upcoming Tests</p><h3><?= $upcoming ?></h3></div>
      <div class="si si-p"><i class="fa-solid fa-clipboard-list"></i></div>
    </div>
  </div>
  <div class="col-6 col-xl">
    <div class="cardx stat-card">
      <div><p>Total Tests</p><h3><?= $total_tests ?></h3></div>
      <div class="si si-t"><i class="fa-solid fa-calendar-check"></i></div>
    </div>
  </div>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="cardx">
      <div class="sec-hd"><h2><i class="fa-solid fa-bolt me-2" style="color:#f6b221"></i>Quick Actions</h2></div>
      <div class="d-flex flex-wrap gap-2">
        <a href="quizzes.php?add=1"       class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Quiz</a>
        <a href="assignments.php?add=1"   class="btn btn-outline-success btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Assignment</a>
        <a href="tests.php?add=1"         class="btn btn-outline-warning btn-sm"><i class="fa-solid fa-plus me-1"></i>Schedule Test</a>
        <a href="announcements.php?add=1" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-bullhorn me-1"></i>Post Announcement</a>
        <a href="subjects.php"            class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-book-open me-1"></i>View Subjects</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Recent Quizzes -->
  <div class="col-xl-4">
    <div class="cardx h-100">
      <div class="sec-hd">
        <h2><i class="fa-solid fa-circle-question me-2 text-primary"></i>Recent Quizzes</h2>
        <a href="quizzes.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <?php if (mysqli_num_rows($recent_quizzes) > 0): ?>
        <?php while ($q = mysqli_fetch_assoc($recent_quizzes)): ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div class="si si-g" style="width:34px;height:34px;font-size:.8rem;border-radius:8px;flex-shrink:0"><i class="fa-solid fa-circle-question"></i></div>
          <div style="min-width:0;flex:1">
            <div class="fw-bold" style="font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($q['title']) ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($q['subject_name'] ?? '—') ?></div>
          </div>
          <span class="bs bs-<?= $q['status'] ?>"><?= ucfirst($q['status']) ?></span>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-circle-question" style="font-size:1.8rem"></i><p class="small">No quizzes yet.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Assignments -->
  <div class="col-xl-4">
    <div class="cardx h-100">
      <div class="sec-hd">
        <h2><i class="fa-solid fa-file-pen me-2 text-success"></i>Recent Assignments</h2>
        <a href="assignments.php" class="btn btn-sm btn-outline-success">View All</a>
      </div>
      <?php if (mysqli_num_rows($recent_assigns) > 0): ?>
        <?php while ($a = mysqli_fetch_assoc($recent_assigns)): ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div class="si si-o" style="width:34px;height:34px;font-size:.8rem;border-radius:8px;flex-shrink:0"><i class="fa-solid fa-file-pen"></i></div>
          <div style="min-width:0;flex:1">
            <div class="fw-bold" style="font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($a['title']) ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($a['subject_name'] ?? '—') ?> <?= $a['due_date'] ? '· Due: '.date('d M',strtotime($a['due_date'])) : '' ?></div>
          </div>
          <span class="bs bs-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-file-pen" style="font-size:1.8rem"></i><p class="small">No assignments yet.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Upcoming Tests -->
  <div class="col-xl-4">
    <div class="cardx h-100">
      <div class="sec-hd">
        <h2><i class="fa-solid fa-clipboard-list me-2 text-warning"></i>Upcoming Tests</h2>
        <a href="tests.php" class="btn btn-sm btn-outline-warning">View All</a>
      </div>
      <?php if (mysqli_num_rows($upcoming_tests) > 0): ?>
        <?php while ($t = mysqli_fetch_assoc($upcoming_tests)): ?>
        <div class="d-flex align-items-center gap-2 py-2 border-bottom">
          <div class="si si-p" style="width:34px;height:34px;font-size:.8rem;border-radius:8px;flex-shrink:0"><i class="fa-solid fa-calendar-check"></i></div>
          <div style="min-width:0;flex:1">
            <div class="fw-bold" style="font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($t['title']) ?></div>
            <div class="text-muted" style="font-size:.7rem"><?= $t['test_date'] ? date('d M Y, h:i A', strtotime($t['test_date'])) : 'Date TBD' ?></div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-st" style="padding:1.5rem"><i class="fa-solid fa-clipboard-list" style="font-size:1.8rem"></i><p class="small">No upcoming tests.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
