<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Accounts'; $active = 'accounts';
$db = ums_db(); $campus = (int)$user['campus_id'];

// Filters (validated → safe to inline in the UNION)
$fType = in_array($_GET['type'] ?? '', ['income', 'expense'], true) ? $_GET['type'] : '';
$from  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : '';
$to    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15; $offset = ($page - 1) * $perPage;

$esc = fn($s) => $db->real_escape_string($s);

// Manual transactions part
$mWhere = "campus_id = $campus";
if ($fType !== '') $mWhere .= " AND type = '$fType'";
if ($from !== '')  $mWhere .= " AND txn_date >= '" . $esc($from) . "'";
if ($to !== '')    $mWhere .= " AND txn_date <= '" . $esc($to) . "'";
$manual = "SELECT id, type, category, title, amount, method, txn_date, reference, 'manual' AS source
    FROM " . tbl('transactions') . " WHERE $mWhere";

$parts = [$manual];
// Fee collections part (income only) — included unless filtering to expenses
if ($fType !== 'expense' && acc_has_fees()) {
    $fWhere = "p.campus_id = $campus";
    if ($from !== '') $fWhere .= " AND p.paid_on >= '" . $esc($from) . "'";
    if ($to !== '')   $fWhere .= " AND p.paid_on <= '" . $esc($to) . "'";
    $parts[] = "SELECT p.id, 'income' AS type, 'Fee Collection' AS category,
        CONCAT('Fee · ', ch.challan_no) AS title, p.amount, p.method, p.paid_on AS txn_date, p.reference, 'fee' AS source
        FROM " . tbl('fee_payments') . " p JOIN " . tbl('fee_challans') . " ch ON ch.id = p.challan_id WHERE $fWhere";
}
// Library fines part (income only) — paid fines, included unless filtering to expenses
if ($fType !== 'expense' && acc_has_library()) {
    $lWhere = "bi.campus_id = $campus AND bi.fine > 0 AND bi.fine_paid = 1";
    if ($from !== '') $lWhere .= " AND bi.return_date >= '" . $esc($from) . "'";
    if ($to !== '')   $lWhere .= " AND bi.return_date <= '" . $esc($to) . "'";
    $parts[] = "SELECT bi.id, 'income' AS type, 'Library Fine' AS category,
        CONCAT('Fine · ', bk.title) AS title, bi.fine AS amount, 'cash' AS method, bi.return_date AS txn_date, '' AS reference, 'library' AS source
        FROM " . tbl('book_issues') . " bi JOIN " . tbl('books') . " bk ON bk.id = bi.book_id WHERE $lWhere";
}
// Hostel fee collections part (income only) — included unless filtering to expenses
if ($fType !== 'expense' && acc_has_hostel()) {
    $hWhere = "hp.campus_id = $campus";
    if ($from !== '') $hWhere .= " AND hp.paid_on >= '" . $esc($from) . "'";
    if ($to !== '')   $hWhere .= " AND hp.paid_on <= '" . $esc($to) . "'";
    $parts[] = "SELECT hp.id, 'income' AS type, 'Hostel Fee' AS category,
        CONCAT('Hostel · ', COALESCE(hs.name, 'Student')) AS title, hp.amount, hp.method, hp.paid_on AS txn_date, hp.reference, 'hostel' AS source
        FROM " . tbl('hostel_payments') . " hp LEFT JOIN " . tbl('students') . " hs ON hs.id = hp.student_id WHERE $hWhere";
}
// Transport fee collections part (income only) — included unless filtering to expenses
if ($fType !== 'expense' && acc_has_transport()) {
    $tWhere = "tp.campus_id = $campus";
    if ($from !== '') $tWhere .= " AND tp.paid_on >= '" . $esc($from) . "'";
    if ($to !== '')   $tWhere .= " AND tp.paid_on <= '" . $esc($to) . "'";
    $parts[] = "SELECT tp.id, 'income' AS type, 'Transport Fee' AS category,
        CONCAT('Transport · ', COALESCE(ts.name, 'Student')) AS title, tp.amount, tp.method, tp.paid_on AS txn_date, tp.reference, 'transport' AS source
        FROM " . tbl('transport_payments') . " tp LEFT JOIN " . tbl('students') . " ts ON ts.id = tp.student_id WHERE $tWhere";
}
// Paid salaries part (expense only) — included unless filtering to income
if ($fType !== 'income' && acc_has_payroll()) {
    $pWhere = "ps.campus_id = $campus AND ps.status = 'paid'";
    if ($from !== '') $pWhere .= " AND ps.paid_on >= '" . $esc($from) . "'";
    if ($to !== '')   $pWhere .= " AND ps.paid_on <= '" . $esc($to) . "'";
    $parts[] = "SELECT ps.id, 'expense' AS type, 'Salaries' AS category,
        CONCAT('Salary · ', t.name, ' · ', ps.month) AS title, ps.net_salary AS amount, ps.method, ps.paid_on AS txn_date, '' AS reference, 'payroll' AS source
        FROM " . tbl('payslips') . " ps JOIN " . tbl('teachers') . " t ON t.id = ps.teacher_id WHERE $pWhere";
}
$union = implode(' UNION ALL ', $parts);

$total = (int)$db->query("SELECT COUNT(*) c FROM ( $union ) t")->fetch_assoc()['c'];
$pages = max(1, (int)ceil($total / $perPage));
$rows = $db->query("SELECT * FROM ( $union ) t ORDER BY txn_date DESC, source LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);

[$income, $expense, $net] = acc_totals($db, $campus, $from, $to);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Accounts</h1><p>Income &amp; expense ledger — fee collections included automatically</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= acc_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= acc_url('create.php?type=expense') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-minus"></i> Expense</a>
    <a href="<?= acc_url('create.php?type=income') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> Income</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-arrow-down"></i></span><div><small>Total Income</small><strong style="color:var(--success)"><?= money($income) ?></strong></div></div>
  <div class="u-chip"><span class="ci" style="background:linear-gradient(135deg,#ef4444,#f87171)"><i class="fa-solid fa-arrow-up"></i></span><div><small>Total Expense</small><strong style="color:var(--danger)"><?= money($expense) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-scale-balanced"></i></span><div><small>Net Balance</small><strong style="color:<?= $net>=0?'var(--success)':'var(--danger)' ?>"><?= money($net) ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <select name="type" class="u-select grow" onchange="this.form.submit()"><option value="">All Transactions</option>
      <option value="income" <?= $fType==='income'?'selected':'' ?>>Income only</option>
      <option value="expense" <?= $fType==='expense'?'selected':'' ?>>Expense only</option></select>
    <div class="u-fld" style="gap:.2rem"><label style="font-size:.7rem;color:var(--muted)">From</label><input type="date" name="from" class="u-input" value="<?= e($from) ?>"></div>
    <div class="u-fld" style="gap:.2rem"><label style="font-size:.7rem;color:var(--muted)">To</label><input type="date" name="to" class="u-input" value="<?= e($to) ?>"></div>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($fType||$from||$to): ?><a href="<?= acc_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-book" style="color:var(--primary)"></i> Ledger</h2><span class="hint"><?= $total ?> entries</span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-book"></i><p>No transactions<?= ($fType||$from||$to)?' for these filters':' yet' ?>.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Method</th><th style="text-align:right">Amount</th><th style="text-align:right"></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $inc = $r['type'] === 'income'; ?>
          <tr>
            <td><?= e(date('d M Y', strtotime($r['txn_date']))) ?></td>
            <td><span class="st <?= $inc?'st-approved':'st-rejected' ?>"><?= $inc?'Income':'Expense' ?></span> <span style="color:var(--muted);font-size:.8rem"><?= e($r['category']) ?></span></td>
            <td><?= e($r['title'] ?: '—') ?><?= $r['reference'] ? ' <small style="color:var(--muted)">· ' . e($r['reference']) . '</small>' : '' ?></td>
            <td style="color:var(--muted)"><?= e(ACC_METHODS[$r['method']] ?? $r['method']) ?></td>
            <td style="text-align:right;font-weight:700;color:<?= $inc?'var(--success)':'var(--danger)' ?>"><?= ($inc?'+':'−') . ' ' . money((float)$r['amount']) ?></td>
            <td style="text-align:right">
              <?php if ($r['source'] === 'manual'): ?>
                <form method="post" action="<?= acc_url('action.php') ?>" onsubmit="return confirm('Delete this transaction?')">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="del" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--line);background:var(--surface);cursor:pointer" title="Delete"><i class="fa-solid fa-xmark"></i></button>
                </form>
              <?php else: ?>
                <span title="Auto-linked from the <?= e($r['source']) ?> module" style="color:var(--muted);font-size:.7rem"><i class="fa-solid fa-link"></i> <?= e($r['source']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['type','from','to'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
