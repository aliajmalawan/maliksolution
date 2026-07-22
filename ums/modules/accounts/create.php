<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'New Transaction'; $active = 'accounts';
$type = ($_GET['type'] ?? 'expense') === 'income' ? 'income' : 'expense';
require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head"><div><h1>New Transaction</h1>
  <p><a href="<?= acc_url('index.php') ?>" style="color:var(--primary);font-weight:700"><i class="fa-solid fa-arrow-left"></i> Back to Ledger</a></p></div></div>

<form method="post" action="<?= acc_url('action.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="create">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-money-check-dollar" style="color:var(--primary)"></i> Transaction Details</h2></div>
    <div class="u-form-grid">
      <div class="u-fld"><label>Type <span class="req">*</span></label>
        <select name="type" id="txnType">
          <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Expense</option>
          <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>Income</option>
        </select></div>
      <div class="u-fld"><label>Category</label>
        <select name="category" id="txnCat"></select></div>
      <div class="u-fld"><label>Amount (Rs) <span class="req">*</span></label>
        <input type="number" name="amount" min="1" step="0.01" required placeholder="0"></div>
      <div class="u-fld"><label>Method</label>
        <select name="method"><?php foreach (ACC_METHODS as $k => $lbl): ?><option value="<?= $k ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
      <div class="u-fld"><label>Date</label><input type="date" name="txn_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
      <div class="u-fld"><label>Reference</label><input type="text" name="reference" placeholder="Voucher / invoice no (optional)"></div>
      <div class="u-fld col-full"><label>Description</label><input type="text" name="title" placeholder="e.g. Electricity bill — July"></div>
    </div>
    <div class="u-form-actions">
      <a href="<?= acc_url('index.php') ?>" class="u-btn u-btn-soft">Cancel</a>
      <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Transaction</button>
    </div>
  </div>
</form>

<script>
  var CATS = { income: <?= json_encode(ACC_INCOME_CATS) ?>, expense: <?= json_encode(ACC_EXPENSE_CATS) ?> };
  var typeEl = document.getElementById('txnType'), catEl = document.getElementById('txnCat');
  function fillCats(){ catEl.innerHTML=''; CATS[typeEl.value].forEach(function(c){ var o=document.createElement('option'); o.value=c; o.textContent=c; catEl.appendChild(o); }); }
  typeEl.addEventListener('change', fillCats); fillCats();
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
