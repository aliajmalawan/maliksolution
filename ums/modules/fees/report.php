<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Fee Report'; $active = 'fees';
$db = ums_db(); $campus = (int)$user['campus_id'];

$a = $db->query('SELECT
    COALESCE(SUM(total_amount - discount + fine),0) billed,
    COALESCE(SUM(paid_amount),0) collected,
    COUNT(*) challans,
    SUM(status = "paid") paid,
    SUM(status = "partial") partial,
    SUM(status = "unpaid") unpaid
    FROM ' . tbl('fee_challans') . ' WHERE campus_id = ' . $campus)->fetch_assoc();
$outstanding = (float)$a['billed'] - (float)$a['collected'];
$collectRate = (float)$a['billed'] > 0 ? round((float)$a['collected'] / (float)$a['billed'] * 100, 1) : 0;

// Collection by method
$byMethod = [];
$res = $db->query('SELECT method, COALESCE(SUM(amount),0) amt FROM ' . tbl('fee_payments') . ' WHERE campus_id = ' . $campus . ' GROUP BY method');
while ($x = $res->fetch_assoc()) $byMethod[$x['method']] = (float)$x['amt'];

// Defaulters: unpaid/partial with a due date in the past (top 10 by balance)
$defaulters = [];
$res = $db->query('SELECT ch.challan_no, ch.total_amount, ch.discount, ch.fine, ch.paid_amount, ch.due_date, s.name, s.registration_no
    FROM ' . tbl('fee_challans') . ' ch JOIN ' . tbl('students') . ' s ON s.id = ch.student_id
    WHERE ch.campus_id = ' . $campus . ' AND ch.status <> "paid"
    ORDER BY (ch.total_amount - ch.discount + ch.fine - ch.paid_amount) DESC LIMIT 10');
while ($x = $res->fetch_assoc()) {
    $bal = (float)$x['total_amount'] - (float)$x['discount'] + (float)$x['fine'] - (float)$x['paid_amount'];
    $overdue = $x['due_date'] && strtotime($x['due_date']) < strtotime('today');
    $defaulters[] = ['name' => $x['name'], 'reg' => $x['registration_no'], 'no' => $x['challan_no'], 'bal' => $bal, 'overdue' => $overdue];
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Fee Report</h1><p><?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= fee_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-grid g-kpi" style="margin-bottom:1.1rem">
  <div class="u-card u-kpi"><span class="ic ic-indigo"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><small>Total Billed</small><strong><?= money((float)$a['billed']) ?></strong></div></div>
  <div class="u-card u-kpi"><span class="ic ic-green"><i class="fa-solid fa-hand-holding-dollar"></i></span><div><small>Collected</small><strong><?= money((float)$a['collected']) ?></strong><span class="delta up"><?= $collectRate ?>%</span></div></div>
  <div class="u-card u-kpi"><span class="ic ic-amber"><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Outstanding</small><strong><?= money($outstanding) ?></strong></div></div>
  <div class="u-card u-kpi"><span class="ic ic-cyan"><i class="fa-solid fa-file-lines"></i></span><div><small>Challans</small><strong><?= (int)$a['challans'] ?></strong><span class="stat-sub"><?= (int)$a['paid'] ?> paid · <?= (int)$a['unpaid'] ?> unpaid</span></div></div>
</div>

<div class="u-grid g-two">
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-wallet" style="color:var(--primary)"></i> Collection by Method</h2></div>
    <?php if (!array_sum($byMethod)): ?><div class="u-empty"><i class="fa-solid fa-wallet"></i><p>No payments recorded yet.</p></div>
    <?php else: $mx = max($byMethod); ?><div class="u-prog">
      <?php foreach (FEE_METHODS as $k=>$lbl): $v = $byMethod[$k] ?? 0; if ($v<=0) continue; ?>
        <div><div class="u-prog-row"><span class="lbl" style="width:auto"><?= e($lbl) ?></span><span class="val"><?= money($v) ?></span></div>
          <div class="u-prog-track"><div class="u-prog-fill g-green" style="width:<?= (int)round($v/$mx*100) ?>%"></div></div></div>
      <?php endforeach; ?>
    </div><?php endif; ?>
  </div>
  <div class="u-card"><div class="u-card-head"><h2><i class="fa-solid fa-user-clock" style="color:var(--danger)"></i> Top Defaulters</h2></div>
    <?php if (!$defaulters): ?><div class="u-empty"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i><p>No outstanding balances.</p></div>
    <?php else: ?><table class="u-table">
      <thead><tr><th>Student</th><th>Challan</th><th style="text-align:right">Balance</th></tr></thead>
      <tbody>
        <?php foreach ($defaulters as $d): ?>
          <tr><td><strong><?= e($d['name']) ?></strong><br><small style="color:var(--muted)"><?= e($d['reg']) ?></small></td>
            <td style="color:var(--muted)"><?= e($d['no']) ?><?= $d['overdue'] ? ' <span class="st st-rejected" style="font-size:.58rem">OVERDUE</span>' : '' ?></td>
            <td style="text-align:right;font-weight:700;color:var(--danger)"><?= money($d['bal']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table><?php endif; ?>
  </div>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
