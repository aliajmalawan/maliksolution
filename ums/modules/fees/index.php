<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Fee Management'; $active = 'fees';
$db = ums_db(); $campus = (int)$user['campus_id'];

$q       = trim((string)($_GET['q'] ?? ''));
$fSess   = in_array($_GET['session'] ?? '', session_list(), true) ? $_GET['session'] : '';
$fStatus = array_key_exists($_GET['status'] ?? '', FEE_STATUS) ? $_GET['status'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12; $offset = ($page - 1) * $perPage;

$where = ['ch.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '') { $where[] = '(s.name LIKE ? OR s.registration_no LIKE ? OR ch.challan_no LIKE ?)'; $l = "%$q%"; $types .= 'sss'; array_push($args, $l, $l, $l); }
if ($fSess !== '')   { $where[] = 'ch.session = ?'; $types .= 's'; $args[] = $fSess; }
if ($fStatus !== '') { $where[] = 'ch.status = ?';  $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('fee_challans') . ' ch JOIN ' . tbl('students') . " s ON s.id = ch.student_id WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$ls = $db->prepare("SELECT ch.*, s.name AS sname, s.registration_no AS sreg
    FROM " . tbl('fee_challans') . " ch JOIN " . tbl('students') . " s ON s.id = ch.student_id
    WHERE $whereSql ORDER BY ch.id DESC LIMIT ? OFFSET ?");
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

// Totals across the whole campus
$agg = $db->prepare('SELECT
        COALESCE(SUM(total_amount - discount + fine),0) billed,
        COALESCE(SUM(paid_amount),0) collected
    FROM ' . tbl('fee_challans') . ' WHERE campus_id = ?');
$agg->bind_param('i', $campus); $agg->execute(); $a = $agg->get_result()->fetch_assoc(); $agg->close();
$outstanding = (float)$a['billed'] - (float)$a['collected'];

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Fee Management</h1><p>Issue challans and record student payments</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= fee_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= fee_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> New Challan</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><small>Total Billed</small><strong><?= money((float)$a['billed']) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-hand-holding-dollar"></i></span><div><small>Collected</small><strong><?= money((float)$a['collected']) ?></strong></div></div>
  <div class="u-chip"><span class="ci" style="background:linear-gradient(135deg,#ef4444,#f87171)"><i class="fa-solid fa-triangle-exclamation"></i></span><div><small>Outstanding</small><strong><?= money($outstanding) ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search student, reg no, or challan no…"></div>
    <select name="session" class="u-select" onchange="this.form.submit()"><option value="">All Sessions</option>
      <?php foreach (session_list() as $s): ?><option value="<?= e($s) ?>" <?= $fSess===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    <select name="status" class="u-select" onchange="this.form.submit()"><option value="">All Status</option>
      <?php foreach (FEE_STATUS as $k=>[$lbl]): ?><option value="<?= $k ?>" <?= $fStatus===$k?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q||$fSess||$fStatus): ?><a href="<?= fee_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Challans</h2>
    <span class="hint"><?= $total ?> record<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-file-invoice-dollar"></i><p>No challans found<?= ($q||$fSess||$fStatus)?' for these filters':' yet' ?>.</p>
      <a href="<?= fee_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Create the first challan</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Challan</th><th>Student</th><th style="text-align:right">Payable</th><th style="text-align:right">Paid</th><th style="text-align:right">Balance</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $net = fee_net($r); $bal = fee_balance($r); ?>
          <tr>
            <td><strong><?= e($r['challan_no']) ?></strong><br><small style="color:var(--muted)"><?= e($r['title'] ?: ('Sem ' . (int)$r['semester'] . ' · ' . $r['session'])) ?></small></td>
            <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($r['sname'])) ?></span><span><strong><?= e($r['sname']) ?></strong><br><small style="color:var(--muted)"><?= e($r['sreg']) ?></small></span></span></td>
            <td style="text-align:right;font-weight:700"><?= money($net) ?></td>
            <td style="text-align:right;color:var(--success)"><?= money((float)$r['paid_amount']) ?></td>
            <td style="text-align:right;font-weight:700;color:<?= $bal>0?'var(--danger)':'var(--muted)' ?>"><?= money($bal) ?></td>
            <td><?= status_badge($r['status'], FEE_STATUS) ?></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= fee_url('view.php?id='.(int)$r['id']) ?>" title="View / Pay"><i class="fa-solid fa-eye"></i></a>
              <a href="<?= fee_url('edit.php?id='.(int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= fee_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this challan and its payments?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q','session','status'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
