<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$p  = pay_find($id);
if (!$p) { flash_set('error', 'Payslip not found.'); redirect(pay_url('index.php')); }

$db = ums_db(); $campus = (int)$user['campus_id'];
$t = $db->query('SELECT name, employee_no, designation, email FROM ' . tbl('teachers') . ' WHERE id = ' . (int)$p['teacher_id'])->fetch_assoc() ?: ['name' => '—', 'employee_no' => '', 'designation' => '', 'email' => ''];
$inst = ums_inst_name($db);

$page_title = 'Payslip'; $active = 'payroll';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Salary Slip <?= status_badge($p['status'], PAY_STATUS) ?></h1>
    <p><?= e($t['name']) ?> · <?= e($t['employee_no'] ?: $t['designation']) ?> · <?= e(pay_month_label($p['month'])) ?></p></div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a href="<?= pay_url('index.php?month=' . e($p['month'])) ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print</button>
  </div>
</div>

<div class="u-grid g-main">
  <div>
    <!-- Slip -->
    <div class="u-card">
      <div style="display:flex;justify-content:space-between;border-bottom:2px solid var(--line);padding-bottom:.9rem;margin-bottom:1rem">
        <div><h2 style="margin:0;font-size:1.1rem;font-weight:800"><?= e($inst) ?></h2><p style="margin:.15rem 0 0;color:var(--muted);font-size:.8rem">Salary Slip · <?= e(pay_month_label($p['month'])) ?></p></div>
        <div style="text-align:right"><div style="font-weight:800"><?= e($t['name']) ?></div><div style="color:var(--muted);font-size:.8rem"><?= e($t['employee_no']) ?> · <?= e($t['designation']) ?></div></div>
      </div>
      <table class="u-table">
        <tbody>
          <tr><td>Basic Salary</td><td style="text-align:right"><?= money((float)$p['basic_salary']) ?></td></tr>
          <tr><td>Allowances</td><td style="text-align:right;color:var(--success)">+ <?= money((float)$p['allowances']) ?></td></tr>
          <tr><td>Deductions</td><td style="text-align:right;color:var(--danger)">− <?= money((float)$p['deductions']) ?></td></tr>
          <tr><td style="font-weight:800;font-size:1rem">Net Salary</td><td style="text-align:right;font-weight:800;font-size:1rem;color:var(--primary)"><?= money((float)$p['net_salary']) ?></td></tr>
        </tbody>
      </table>
      <?php if ($p['status'] === 'paid'): ?>
        <p style="margin:1rem 0 0;color:var(--muted);font-size:.82rem"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i> Paid on <?= e(date('d M Y', strtotime($p['paid_on']))) ?> via <?= e(PAY_METHODS[$p['method']] ?? $p['method']) ?>.</p>
      <?php endif; ?>
      <?php if (trim((string)$p['remarks']) !== ''): ?><p style="margin:.6rem 0 0;color:var(--muted);font-size:.82rem"><strong>Remarks:</strong> <?= e($p['remarks']) ?></p><?php endif; ?>
      <div style="display:flex;justify-content:space-between;margin-top:2.5rem;color:var(--muted);font-size:.75rem">
        <div style="border-top:1px solid var(--line);padding-top:.3rem;width:150px;text-align:center">Prepared By</div>
        <div style="border-top:1px solid var(--line);padding-top:.3rem;width:150px;text-align:center">Received By</div>
      </div>
    </div>
  </div>

  <div class="no-print">
    <!-- Adjust -->
    <div class="u-card" style="margin-bottom:1.1rem;height:fit-content">
      <div class="u-card-head"><h2><i class="fa-solid fa-sliders" style="color:var(--primary)"></i> Adjust</h2></div>
      <form method="post" action="<?= pay_url('action.php') ?>">
        <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $id ?>">
        <div class="u-fld" style="margin-bottom:.8rem"><label>Basic Salary</label><input type="number" name="basic_salary" min="0" step="0.01" value="<?= e((string)(float)$p['basic_salary']) ?>"></div>
        <div class="u-fld" style="margin-bottom:.8rem"><label>Allowances</label><input type="number" name="allowances" min="0" step="0.01" value="<?= e((string)(float)$p['allowances']) ?>"></div>
        <div class="u-fld" style="margin-bottom:.8rem"><label>Deductions</label><input type="number" name="deductions" min="0" step="0.01" value="<?= e((string)(float)$p['deductions']) ?>"></div>
        <div class="u-fld" style="margin-bottom:1rem"><label>Remarks</label><input type="text" name="remarks" value="<?= e($p['remarks']) ?>" placeholder="Optional"></div>
        <button type="submit" class="u-btn u-btn-soft" style="width:100%"><i class="fa-solid fa-floppy-disk"></i> Save Adjustments</button>
      </form>
    </div>

    <!-- Pay -->
    <div class="u-card" style="height:fit-content">
      <div class="u-card-head"><h2><i class="fa-solid fa-hand-holding-dollar" style="color:var(--primary)"></i> Payment</h2></div>
      <?php if ($p['status'] === 'paid'): ?>
        <p style="color:var(--muted);font-size:.82rem;margin:0 0 .9rem"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i> This salary has been paid.</p>
        <form method="post" action="<?= pay_url('action.php') ?>" onsubmit="return confirm('Revert to unpaid?')">
          <?= csrf_field() ?><input type="hidden" name="action" value="mark_unpaid"><input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="u-btn u-btn-soft" style="width:100%"><i class="fa-solid fa-rotate-left"></i> Revert to Unpaid</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= pay_url('action.php') ?>">
          <?= csrf_field() ?><input type="hidden" name="action" value="mark_paid"><input type="hidden" name="id" value="<?= $id ?>">
          <div class="u-fld" style="margin-bottom:.8rem"><label>Method</label>
            <select name="method"><?php foreach (PAY_METHODS as $k=>$lbl): ?><option value="<?= $k ?>" <?= $k==='bank'?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select></div>
          <div class="u-fld" style="margin-bottom:1rem"><label>Payment Date</label><input type="date" name="paid_on" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
          <button type="submit" class="u-btn u-btn-primary" style="width:100%"><i class="fa-solid fa-check"></i> Mark as Paid (<?= money((float)$p['net_salary']) ?>)</button>
        </form>
        <p style="color:var(--muted);font-size:.72rem;margin:.7rem 0 0;text-align:center">Paid salaries appear as expenses in Accounts.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.no-print,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}.g-main{grid-template-columns:1fr!important}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
