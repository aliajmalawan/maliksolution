<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Library Report'; $active = 'library';
$db = ums_db(); $campus = (int)$user['campus_id'];
$today = date('Y-m-d');

/* Circulation totals */
$tot = $db->query('SELECT
    COUNT(*) issues,
    SUM(status="issued") out_now,
    SUM(status="returned") returned,
    COALESCE(SUM(CASE WHEN fine_paid=1 THEN fine ELSE 0 END),0) collected,
    COALESCE(SUM(CASE WHEN fine>0 AND fine_paid=0 THEN fine ELSE 0 END),0) pending
    FROM ' . tbl('book_issues') . ' WHERE campus_id=' . $campus)->fetch_assoc();

/* Most issued books */
$popular = $db->query('SELECT b.title, b.author, COUNT(*) n
    FROM ' . tbl('book_issues') . ' i JOIN ' . tbl('books') . ' b ON b.id=i.book_id
    WHERE i.campus_id=' . $campus . '
    GROUP BY i.book_id ORDER BY n DESC, b.title LIMIT 10')->fetch_all(MYSQLI_ASSOC);

/* Overdue list */
$overdue = $db->query('SELECT i.*, b.title, s.name student_name, s.registration_no
    FROM ' . tbl('book_issues') . ' i JOIN ' . tbl('books') . ' b ON b.id=i.book_id
    LEFT JOIN ' . tbl('students') . ' s ON s.id=i.student_id
    WHERE i.campus_id=' . $campus . ' AND i.status="issued" AND i.due_date<"' . $today . '"
    ORDER BY i.due_date ASC LIMIT 25')->fetch_all(MYSQLI_ASSOC);

/* Category breakdown */
$byCat = $db->query('SELECT category, COUNT(*) titles, COALESCE(SUM(total_copies),0) copies
    FROM ' . tbl('books') . ' WHERE campus_id=' . $campus . '
    GROUP BY category ORDER BY copies DESC')->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Library Report</h1><p>Circulation, fines &amp; collection overview</p></div>
  <div><a href="<?= lib_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Catalog</a></div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-right-left"></i></span><div><small>Total Issues</small><strong><?= (int)$tot['issues'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-book-open-reader"></i></span><div><small>Out Now</small><strong><?= (int)$tot['out_now'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-coins"></i></span><div><small>Fines Collected</small><strong><?= money((float)$tot['collected']) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-red"><i class="fa-solid fa-hourglass-half"></i></span><div><small>Fines Pending</small><strong><?= money((float)$tot['pending']) ?></strong></div></div>
</div>

<div class="u-grid g-two">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-fire" style="color:var(--primary)"></i> Most Issued Books</h2></div>
    <?php if (!$popular): ?><div class="u-empty"><i class="fa-solid fa-book"></i><p>No issue history yet.</p></div>
    <?php else: $max = max(array_map(fn($r)=>(int)$r['n'], $popular)); ?>
      <table class="u-table"><thead><tr><th>Book</th><th style="width:45%">Times Issued</th></tr></thead><tbody>
        <?php foreach ($popular as $r): ?>
          <tr><td><strong><?= e($r['title']) ?></strong><?= $r['author']?'<br><small style="color:var(--muted)">'.e($r['author']).'</small>':'' ?></td>
            <td><div style="display:flex;align-items:center;gap:.6rem">
              <div class="u-prog" style="flex:1"><span style="width:<?= (int)round((int)$r['n']/$max*100) ?>%"></span></div>
              <strong style="min-width:1.5rem;text-align:right"><?= (int)$r['n'] ?></strong></div></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-layer-group" style="color:var(--primary)"></i> By Category</h2></div>
    <?php if (!$byCat): ?><div class="u-empty"><i class="fa-solid fa-book"></i><p>No books yet.</p></div>
    <?php else: ?>
      <table class="u-table"><thead><tr><th>Category</th><th style="text-align:center">Titles</th><th style="text-align:center">Copies</th></tr></thead><tbody>
        <?php foreach ($byCat as $r): ?>
          <tr><td><span class="st" style="background:rgba(99,102,241,.1);color:var(--primary)"><?= e($r['category']) ?></span></td>
            <td style="text-align:center"><?= (int)$r['titles'] ?></td><td style="text-align:center;font-weight:700"><?= (int)$r['copies'] ?></td></tr>
        <?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
</div>

<div class="u-card" style="margin-top:1.1rem">
  <div class="u-card-head"><h2><i class="fa-solid fa-triangle-exclamation" style="color:var(--danger)"></i> Overdue Books</h2>
    <span class="hint"><?= count($overdue) ?> overdue</span></div>
  <?php if (!$overdue): ?><div class="u-empty"><i class="fa-solid fa-check-double"></i><p>Nothing overdue. All caught up.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Book</th><th>Student</th><th>Due</th><th style="text-align:center">Days Late</th><th style="text-align:right">Est. Fine</th></tr></thead>
      <tbody>
        <?php foreach ($overdue as $r):
          $late = (int)ceil((strtotime($today) - strtotime($r['due_date'])) / 86400);
          $fine = lib_fine($r['due_date'], $today); ?>
          <tr><td><strong><?= e($r['title']) ?></strong></td>
            <td><?= e($r['student_name'] ?? '—') ?><?= $r['registration_no']?'<br><small style="color:var(--muted)">'.e($r['registration_no']).'</small>':'' ?></td>
            <td><span class="st st-danger"><?= date('d M Y', strtotime($r['due_date'])) ?></span></td>
            <td style="text-align:center;font-weight:700;color:var(--danger)"><?= $late ?></td>
            <td style="text-align:right;font-weight:700"><?= money($fine) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
