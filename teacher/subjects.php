<?php
declare(strict_types=1);
$page_title = 'My Subjects';
$active_nav = 'subjects';
include 'header.php';

$rows = mysqli_query($conn, "
    SELECT cs.id AS subject_id, cs.name AS subject_name, c.id AS course_id, c.name AS course_name,
           (SELECT COUNT(*) FROM teacher_quizzes   WHERE teacher_id=$tid AND subject_id=cs.id) AS quiz_cnt,
           (SELECT COUNT(*) FROM teacher_assignments WHERE teacher_id=$tid AND subject_id=cs.id) AS assign_cnt,
           (SELECT COUNT(*) FROM teacher_tests       WHERE teacher_id=$tid AND subject_id=cs.id) AS test_cnt
    FROM teacher_subject_assignments tsa
    JOIN course_subjects cs ON cs.id = tsa.subject_id
    JOIN courses c          ON c.id  = cs.course_id
    WHERE tsa.teacher_id = $tid
    ORDER BY c.id ASC, cs.sort_order ASC, cs.id ASC
");

// Group by course
$grouped = [];
if ($rows) while ($r = mysqli_fetch_assoc($rows)) {
    $grouped[$r['course_id']] = $grouped[$r['course_id']] ?? ['name' => $r['course_name'], 'subjects' => []];
    $grouped[$r['course_id']]['subjects'][] = $r;
}
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-book-open me-2 text-primary"></i>My Subjects</h1>
    <p>All subjects you are assigned to teach, grouped by course.</p>
  </div>
</div>

<?php if (empty($grouped)): ?>
<div class="cardx">
  <div class="empty-st">
    <i class="fa-solid fa-book-open"></i>
    <p>No subjects assigned yet.<br>Contact the administrator to assign subjects to your profile.</p>
  </div>
</div>
<?php else: ?>
  <?php foreach ($grouped as $cid => $cdata): ?>
  <div class="cardx mb-3">
    <div class="sec-hd mb-3">
      <h2><i class="fa-solid fa-graduation-cap me-2 text-primary"></i><?= htmlspecialchars($cdata['name']) ?></h2>
      <span style="font-size:.75rem;color:var(--muted)"><?= count($cdata['subjects']) ?> subject<?= count($cdata['subjects']) !== 1 ? 's' : '' ?></span>
    </div>
    <div class="row g-3">
      <?php foreach ($cdata['subjects'] as $s): ?>
      <div class="col-sm-6 col-lg-4">
        <div style="border:1.5px solid var(--line);border-radius:11px;padding:1rem;transition:box-shadow .2s;background:#fafcff">
          <div class="fw-bold mb-1" style="font-size:.9rem;color:var(--navy)"><?= htmlspecialchars($s['subject_name']) ?></div>
          <div class="text-muted mb-2" style="font-size:.72rem"><?= htmlspecialchars($cdata['name']) ?></div>
          <div class="d-flex gap-3" style="font-size:.72rem;color:var(--muted)">
            <span><i class="fa-solid fa-circle-question me-1 text-primary"></i><?= $s['quiz_cnt'] ?> quiz<?= $s['quiz_cnt'] != 1 ? 'zes' : '' ?></span>
            <span><i class="fa-solid fa-file-pen me-1 text-success"></i><?= $s['assign_cnt'] ?> assignment<?= $s['assign_cnt'] != 1 ? 's' : '' ?></span>
            <span><i class="fa-solid fa-clipboard-list me-1 text-warning"></i><?= $s['test_cnt'] ?> test<?= $s['test_cnt'] != 1 ? 's' : '' ?></span>
          </div>
          <div class="d-flex gap-1 mt-2">
            <a href="quizzes.php?subject_id=<?= $s['subject_id'] ?>"     class="btn btn-xs btn-outline-primary">Quiz</a>
            <a href="assignments.php?subject_id=<?= $s['subject_id'] ?>" class="btn btn-xs btn-outline-success">Assignment</a>
            <a href="tests.php?subject_id=<?= $s['subject_id'] ?>"       class="btn btn-xs btn-outline-warning">Test</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<style>.btn-xs{font-size:.7rem;padding:.2rem .55rem;border-radius:6px}</style>
<?php include 'footer.php'; ?>
