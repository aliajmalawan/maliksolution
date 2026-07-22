<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'HR & Payroll'; $active = 'payroll';
$db = ums_db(); $campus = (int)$user['campus_id'];

$month = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
$q       = trim((string)($_GET['q'] ?? ''));
$fStatus = array_key_exists($_GET['status'] ?? '', PAY_STATUS) ? $_GET['status'] : '';

$where = ['p.campus_id = ?', 'p.month = ?']; $types = 'is'; $args = [$campus, $month];
if ($q !== '')      { $where[] = '(t.name LIKE ? OR t.employee_no LIKE ?)'; $l = "%$q%"; $types .= 'ss'; array_push($args, $l, $l); }
if ($fStatus !== '') { $where[] = 'p.status = ?'; $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$ls = $db->prepare("SELECT p.*, t.name AS tname, t.employee_no, t.designation
    FROM " . tbl('payslips') . " p JOIN " . tbl('teachers') . " t ON t.id = p.teacher_id
    WHERE $whereSql ORDER BY t.name");
$ls->bind_param($types, ...$args); $ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

// Month totals
$mt = $db->prepare('SELECT COALESCE(SUM(net_salary),0) net, SUM(status="paid") paid, SUM(status="unpaid") unpaid,
    COALESCE(SUM(CASE WHEN status="paid" THEN net_salary END),0) disbursed
    FROM ' . tbl('payslips') . ' WHERE campus_id=? AND month=?');
$mt->bind_param('is', $campus, $month); $mt->execute(); $m = $mt->get_result()->fetch_assoc(); $mt->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>HR &amp; Payroll</h1><p>Monthly salary slips for staff</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= pay_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><small>Month Payroll</small><strong><?= money((float)$m['net']) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Disbursed</small><strong><?= money((float)$m['disbursed']) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-clock"></i></span><div><small>Unpaid Slips</small><strong><?= (int)$m['unpaid'] ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end;justify-content:space-between">
    <form method="get" class="att-picker" style="margin:0">
      <div class="u-fld"><label>Month</label><input type="month" name="month" class="u-input" value="<?= e($month) ?>" onchange="this.form.submit()"></div>
      <div class="u-fld"><label>Status</label>
        <select name="status" class="u-select" onchange="this.form.submit()"><option value="">All</option>
          <?php foreach (PAY_STATUS as $k=>[$lbl]): ?><option value="<?= $k ?>" <?= $fStatus===$k?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld u-search-box" style="min-width:220px"><label>Search</label>
        <div style="position:relative"><i class="fa-solid fa-magnifying-glass" style="top:60%"></i><input type="search" name="q" value="<?= e($q) ?>" placeholder="Name or employee no…" style="padding-left:2.3rem"></div></div>
      <div class="u-fld"><button type="submit" class="u-btn u-btn-soft"><i class="fa-solid fa-filter"></i> Filter</button></div>
    </form>
    <form method="post" action="<?= pay_url('action.php') ?>" onsubmit="return confirm('Generate payslips for all active teachers for <?= e(pay_month_label($month)) ?>?')">
      <?= csrf_field() ?><input type="hidden" name="action" value="generate"><input type="hidden" name="month" value="<?= e($month) ?>">
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate for <?= e(pay_month_label($month)) ?></button>
    </form>
  </div>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-money-check-dollar" style="color:var(--primary)"></i> Payslips — <?= e(pay_month_label($month)) ?></h2>
    <span class="hint"><?= count($rows) ?> staff</span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-money-check-dollar"></i>
      <p>No payslips for <?= e(pay_month_label($month)) ?> yet. Click <strong>Generate</strong> to create them from staff salaries.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Staff</th><th style="text-align:right">Basic</th><th style="text-align:right">Allow.</th><th style="text-align:right">Deduct.</th><th style="text-align:right">Net</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($r['tname'])) ?></span><span><strong><?= e($r['tname']) ?></strong><br><small style="color:var(--muted)"><?= e($r['employee_no'] ?: $r['designation']) ?></small></span></span></td>
            <td style="text-align:right;color:var(--muted)"><?= money((float)$r['basic_salary']) ?></td>
            <td style="text-align:right;color:var(--success)"><?= money((float)$r['allowances']) ?></td>
            <td style="text-align:right;color:var(--danger)"><?= money((float)$r['deductions']) ?></td>
            <td style="text-align:right;font-weight:800"><?= money((float)$r['net_salary']) ?></td>
            <td><?= status_badge($r['status'], PAY_STATUS) ?></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= pay_url('view.php?id='.(int)$r['id']) ?>" title="View / Pay"><i class="fa-solid fa-eye"></i></a>
              <?php if ($r['status'] === 'unpaid'): ?>
                <form method="post" action="<?= pay_url('action.php') ?>" style="display:inline">
                  <?= csrf_field() ?><input type="hidden" name="action" value="mark_paid"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="paid_on" value="<?= date('Y-m-d') ?>">
                  <button type="submit" class="ok" title="Mark paid"><i class="fa-solid fa-check"></i></button>
                </form>
              <?php endif; ?>
              <form method="post" action="<?= pay_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this payslip?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
