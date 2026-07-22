<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Issue / Return'; $active = 'library';
$db = ums_db(); $campus = (int)$user['campus_id'];
$today = date('Y-m-d');

$avBooks = lib_available_books($campus);
$students = lib_student_options($campus);

$filter = in_array($_GET['show'] ?? '', ['all', 'overdue'], true) ? $_GET['show'] : 'all';
$cond = 'i.campus_id = ' . $campus . ' AND i.status = "issued"';
if ($filter === 'overdue') $cond .= ' AND i.due_date < "' . $today . '"';

$sql = 'SELECT i.*, b.title, s.name AS student_name, s.registration_no
        FROM ' . tbl('book_issues') . ' i
        JOIN ' . tbl('books') . ' b ON b.id = i.book_id
        LEFT JOIN ' . tbl('students') . ' s ON s.id = i.student_id
        WHERE ' . $cond . ' ORDER BY i.due_date ASC';
$out = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

$totIssued = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('book_issues') . ' i WHERE i.campus_id=' . $campus . ' AND i.status="issued"')->fetch_assoc()['c'];
$totOver   = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('book_issues') . ' i WHERE i.campus_id=' . $campus . ' AND i.status="issued" AND i.due_date<"' . $today . '"')->fetch_assoc()['c'];
$unpaid    = (float)$db->query('SELECT COALESCE(SUM(fine),0) s FROM ' . tbl('book_issues') . ' i WHERE i.campus_id=' . $campus . ' AND i.fine>0 AND i.fine_paid=0')->fetch_assoc()['s'];

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Issue / Return</h1><p>Circulation desk · fine Rs <?= (int)lib_fine_rate() ?>/day overdue</p></div>
  <div><a href="<?= lib_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Catalog</a></div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-right-from-bracket"></i></span><div><small>Issued Out</small><strong><?= $totIssued ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-red"><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Overdue</small><strong><?= $totOver ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-coins"></i></span><div><small>Fines Unpaid</small><strong><?= money($unpaid) ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <div class="u-card-head"><h2><i class="fa-solid fa-hand-holding-hand" style="color:var(--primary)"></i> Issue a Book</h2></div>
  <?php if (!$avBooks || !$students): ?>
    <div class="u-empty"><i class="fa-solid fa-circle-info"></i><p>
      <?= !$avBooks ? 'No available books to issue. ' : '' ?><?= !$students ? 'No active students found.' : '' ?></p>
      <?php if (!$avBooks): ?><a href="<?= lib_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add a book</a><?php endif; ?></div>
  <?php else: ?>
    <form method="post" action="<?= lib_url('action.php') ?>" class="u-form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="issue">
      <div class="u-fld col-full"><label>Book <span class="req">*</span></label>
        <select name="book_id" required><option value="">— Select book —</option>
          <?php foreach ($avBooks as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Student <span class="req">*</span></label>
        <select name="student_id" required><option value="">— Select student —</option>
          <?php foreach ($students as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Loan Period (days)</label><input type="number" name="days" min="1" max="180" value="14"></div>
      <div class="u-form-actions col-full">
        <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-right-left"></i> Issue Book</button></div>
    </form>
  <?php endif; ?>
</div>

<div class="u-card">
  <div class="u-card-head">
    <h2><i class="fa-solid fa-list-check" style="color:var(--primary)"></i> Currently Issued</h2>
    <div class="u-seg">
      <a href="?show=all" class="<?= $filter==='all'?'on':'' ?>">All (<?= $totIssued ?>)</a>
      <a href="?show=overdue" class="<?= $filter==='overdue'?'on':'' ?>">Overdue (<?= $totOver ?>)</a>
    </div>
  </div>
  <?php if (!$out): ?>
    <div class="u-empty"><i class="fa-solid fa-check-double"></i><p>No <?= $filter==='overdue'?'overdue':'issued' ?> books right now.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Book</th><th>Student</th><th>Issued</th><th>Due</th><th style="text-align:right">Est. Fine</th><th style="text-align:right">Return</th></tr></thead>
      <tbody>
        <?php foreach ($out as $r):
          $over = strtotime($r['due_date']) < strtotime($today);
          $estFine = lib_fine($r['due_date'], $today); ?>
          <tr>
            <td><strong><?= e($r['title']) ?></strong></td>
            <td><?= e($r['student_name'] ?? '—') ?><?= $r['registration_no'] ? '<br><small style="color:var(--muted)">' . e($r['registration_no']) . '</small>' : '' ?></td>
            <td style="color:var(--muted)"><?= date('d M Y', strtotime($r['issue_date'])) ?></td>
            <td><?= $over ? '<span class="st st-danger">' . date('d M Y', strtotime($r['due_date'])) . '</span>' : date('d M Y', strtotime($r['due_date'])) ?></td>
            <td style="text-align:right;font-weight:700;color:<?= $estFine>0?'var(--danger)':'var(--muted)' ?>"><?= $estFine>0?money($estFine):'—' ?></td>
            <td style="text-align:right">
              <form method="post" action="<?= lib_url('action.php') ?>" style="display:inline-flex;gap:.4rem;align-items:center"
                    onsubmit="return confirm('Mark this book as returned<?= $estFine>0?' with a fine of '.addslashes(money($estFine)):'' ?>?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="return"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <?php if ($estFine > 0): ?>
                  <label style="font-size:.78rem;color:var(--muted);display:inline-flex;gap:.25rem;align-items:center">
                    <input type="checkbox" name="fine_paid" value="1" style="width:auto" checked> paid</label>
                <?php endif; ?>
                <button type="submit" class="u-btn u-btn-soft u-btn-sm"><i class="fa-solid fa-rotate-left"></i> Return</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
