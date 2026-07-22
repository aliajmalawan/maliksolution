<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Hostel Report'; $active = 'hostel';
$db = ums_db(); $campus = (int)$user['campus_id'];

/* Occupancy by block */
$byBlock = $db->query('SELECT
    CASE WHEN r.block="" THEN "(No block)" ELSE r.block END blk,
    COUNT(*) rooms, COALESCE(SUM(r.capacity),0) beds,
    COALESCE(SUM((SELECT COUNT(*) FROM ' . tbl('hostel_allotments') . ' a WHERE a.room_id=r.id AND a.status="active")),0) occ
    FROM ' . tbl('hostel_rooms') . ' r WHERE r.campus_id=' . $campus . ' AND r.status="active"
    GROUP BY blk ORDER BY blk')->fetch_all(MYSQLI_ASSOC);

/* Totals */
$beds = 0; $occ = 0; foreach ($byBlock as $b) { $beds += (int)$b['beds']; $occ += (int)$b['occ']; }
$vacant = max(0, $beds - $occ);
$collected = (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('hostel_payments') . ' WHERE campus_id=' . $campus)->fetch_assoc()['s'];
$residents = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('hostel_allotments') . ' WHERE campus_id=' . $campus . ' AND status="active"')->fetch_assoc()['c'];

/* Recent payments */
$payments = $db->query('SELECT p.*, s.name student_name, r.block, r.room_no
    FROM ' . tbl('hostel_payments') . ' p
    LEFT JOIN ' . tbl('students') . ' s ON s.id=p.student_id
    LEFT JOIN ' . tbl('hostel_allotments') . ' a ON a.id=p.allotment_id
    LEFT JOIN ' . tbl('hostel_rooms') . ' r ON r.id=a.room_id
    WHERE p.campus_id=' . $campus . ' ORDER BY p.paid_on DESC, p.id DESC LIMIT 15')->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Hostel Report</h1><p>Occupancy &amp; fee collection · <?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= hostel_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-bed"></i></span><div><small>Total Beds</small><strong><?= $beds ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-user-group"></i></span><div><small>Residents</small><strong><?= $residents ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-bed-pulse"></i></span><div><small>Vacant Beds</small><strong><?= $vacant ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-coins"></i></span><div><small>Fees Collected</small><strong><?= money($collected) ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <div class="u-card-head"><h2><i class="fa-solid fa-building" style="color:var(--primary)"></i> Occupancy by Block</h2></div>
  <?php if (!$byBlock): ?><div class="u-empty"><i class="fa-solid fa-door-open"></i><p>No active rooms yet.</p></div>
  <?php else: ?>
    <table class="u-table"><thead><tr><th>Block</th><th style="text-align:center">Rooms</th><th style="text-align:center">Beds</th><th style="width:40%">Occupancy</th></tr></thead><tbody>
      <?php foreach ($byBlock as $b): $bd=(int)$b['beds']; $oc=(int)$b['occ']; $pct=$bd>0?round($oc/$bd*100):0; ?>
        <tr><td><strong><?= e($b['blk']) ?></strong></td>
          <td style="text-align:center"><?= (int)$b['rooms'] ?></td>
          <td style="text-align:center"><?= $bd ?></td>
          <td><div style="display:flex;align-items:center;gap:.6rem">
            <div class="u-prog" style="flex:1"><span style="width:<?= $pct ?>%"></span></div>
            <strong style="min-width:3.4rem;text-align:right"><?= $oc ?>/<?= $bd ?></strong></div></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  <?php endif; ?>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-receipt" style="color:var(--primary)"></i> Recent Fee Payments</h2></div>
  <?php if (!$payments): ?><div class="u-empty"><i class="fa-solid fa-coins"></i><p>No hostel fees collected yet.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Date</th><th>Student</th><th>Room</th><th>Month</th><th>Method</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr><td><?= date('d M Y', strtotime($p['paid_on'])) ?></td>
            <td><?= e($p['student_name'] ?? '—') ?></td>
            <td style="color:var(--muted)"><?= e(($p['block']?$p['block'].' · ':'').'Room '.($p['room_no']??'—')) ?></td>
            <td><?= e($p['month'] ? date('M Y', strtotime($p['month'].'-01')) : '—') ?></td>
            <td style="color:var(--muted)"><?= e(HOSTEL_METHODS[$p['method']] ?? $p['method']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--success)"><?= money((float)$p['amount']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
