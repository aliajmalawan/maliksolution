<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$c  = fee_find($id);
if (!$c) { flash_set('error', 'Challan not found.'); redirect(fee_url('index.php')); }

$db = ums_db(); $campus = (int)$user['campus_id'];
$stu = $db->query('SELECT name, registration_no, program FROM ' . tbl('students') . ' WHERE id = ' . (int)$c['student_id'])->fetch_assoc() ?: ['name' => '—', 'registration_no' => '', 'program' => ''];

$pays = [];
$ps = $db->prepare('SELECT * FROM ' . tbl('fee_payments') . ' WHERE challan_id = ? ORDER BY id DESC');
$ps->bind_param('i', $id); $ps->execute();
$pays = $ps->get_result()->fetch_all(MYSQLI_ASSOC); $ps->close();

$net = fee_net($c); $bal = fee_balance($c);
$inst = ums_inst_name($db);

$page_title = 'Challan ' . $c['challan_no']; $active = 'fees';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1><?= e($c['challan_no']) ?> <?= status_badge($c['status'], FEE_STATUS) ?></h1>
    <p><?= e($stu['name']) ?> · <?= e($stu['registration_no']) ?> · <?= e($c['title'] ?: ('Semester ' . (int)$c['semester'])) ?></p></div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a href="<?= fee_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <a href="<?= fee_url('edit.php?id='.$id) ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-pen"></i> Edit</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print</button>
  </div>
</div>

<div class="u-grid g-main">
  <div>
    <!-- Challan summary -->
    <div class="u-card" style="margin-bottom:1.1rem">
      <div style="display:flex;justify-content:space-between;border-bottom:2px solid var(--line);padding-bottom:.9rem;margin-bottom:1rem">
        <div><h2 style="margin:0;font-size:1.1rem;font-weight:800"><?= e($inst) ?></h2><p style="margin:.15rem 0 0;color:var(--muted);font-size:.8rem">Fee Challan · <?= e($c['session']) ?></p></div>
        <div style="text-align:right"><div style="font-weight:800"><?= e($c['challan_no']) ?></div><div style="color:var(--muted);font-size:.8rem"><?= $c['due_date'] ? 'Due ' . e(date('d M Y', strtotime($c['due_date']))) : '' ?></div></div>
      </div>
      <div class="u-detail">
        <div class="row-x"><span class="k">Student</span><span class="v"><?= e($stu['name']) ?></span></div>
        <div class="row-x"><span class="k">Registration</span><span class="v"><?= e($stu['registration_no']) ?></span></div>
        <div class="row-x"><span class="k">Program</span><span class="v"><?= e($stu['program'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Semester</span><span class="v"><?= (int)$c['semester'] ?></span></div>
      </div>
      <table class="u-table" style="margin-top:1rem">
        <tbody>
          <tr><td>Total Amount</td><td style="text-align:right"><?= money((float)$c['total_amount']) ?></td></tr>
          <?php if ((float)$c['discount'] > 0): ?><tr><td>Discount</td><td style="text-align:right;color:var(--success)">− <?= money((float)$c['discount']) ?></td></tr><?php endif; ?>
          <?php if ((float)$c['fine'] > 0): ?><tr><td>Fine / Late Fee</td><td style="text-align:right;color:var(--danger)">+ <?= money((float)$c['fine']) ?></td></tr><?php endif; ?>
          <tr><td style="font-weight:800">Net Payable</td><td style="text-align:right;font-weight:800"><?= money($net) ?></td></tr>
          <tr><td>Paid</td><td style="text-align:right;color:var(--success)"><?= money((float)$c['paid_amount']) ?></td></tr>
          <tr><td style="font-weight:800">Balance</td><td style="text-align:right;font-weight:800;color:<?= $bal>0?'var(--danger)':'var(--success)' ?>"><?= money($bal) ?></td></tr>
        </tbody>
      </table>
    </div>

    <!-- Payment history -->
    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-receipt" style="color:var(--primary)"></i> Payment History</h2><span class="hint"><?= count($pays) ?></span></div>
      <?php if (!$pays): ?>
        <div class="u-empty" style="padding:1.5rem"><i class="fa-solid fa-receipt"></i><p>No payments recorded yet.</p></div>
      <?php else: ?>
        <div style="overflow-x:auto"><table class="u-table">
          <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th style="text-align:right">Amount</th><th class="no-print"></th></tr></thead>
          <tbody>
            <?php foreach ($pays as $p): ?>
              <tr>
                <td><?= e(date('d M Y', strtotime($p['paid_on']))) ?></td>
                <td><span class="st" style="background:rgba(99,102,241,.1);color:var(--primary)"><?= e(FEE_METHODS[$p['method']] ?? $p['method']) ?></span></td>
                <td style="color:var(--muted)"><?= e($p['reference'] ?: '—') ?></td>
                <td style="text-align:right;font-weight:700;color:var(--success)"><?= money((float)$p['amount']) ?></td>
                <td class="no-print" style="text-align:right">
                  <form method="post" action="<?= fee_url('action.php') ?>" onsubmit="return confirm('Remove this payment?')">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_payment"><input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="challan_id" value="<?= $id ?>">
                    <button type="submit" class="del" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--line);background:var(--surface);cursor:pointer" title="Remove"><i class="fa-solid fa-xmark"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Record payment -->
  <div class="no-print">
    <div class="u-card" style="height:fit-content">
      <div class="u-card-head"><h2><i class="fa-solid fa-hand-holding-dollar" style="color:var(--primary)"></i> Record Payment</h2></div>
      <?php if ($bal <= 0): ?>
        <div class="u-empty" style="padding:1.5rem"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i><p>This challan is fully paid.</p></div>
      <?php else: ?>
        <p style="color:var(--muted);font-size:.82rem;margin:0 0 1rem">Outstanding balance: <strong style="color:var(--danger)"><?= money($bal) ?></strong></p>
        <form method="post" action="<?= fee_url('action.php') ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="add_payment"><input type="hidden" name="challan_id" value="<?= $id ?>">
          <div class="u-fld" style="margin-bottom:.9rem"><label>Amount (Rs) <span class="req">*</span></label>
            <input type="number" name="amount" min="1" max="<?= $bal ?>" step="0.01" required value="<?= $bal ?>"></div>
          <div class="u-fld" style="margin-bottom:.9rem"><label>Method</label>
            <select name="method"><?php foreach (FEE_METHODS as $k=>$lbl): ?><option value="<?= $k ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
          <div class="u-fld" style="margin-bottom:.9rem"><label>Payment Date</label><input type="date" name="paid_on" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
          <div class="u-fld" style="margin-bottom:1.1rem"><label>Reference (optional)</label><input type="text" name="reference" placeholder="Transaction / cheque no"></div>
          <button type="submit" class="u-btn u-btn-primary" style="width:100%"><i class="fa-solid fa-check"></i> Record Payment</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.no-print,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}.g-main{grid-template-columns:1fr!important}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
