<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Transport Report'; $active = 'transport';
$db = ums_db(); $campus = (int)$user['campus_id'];

/* Load / occupancy by route */
$byRoute = $db->query('SELECT r.route_name, r.vehicle_no, r.capacity,
    (SELECT COUNT(*) FROM ' . tbl('transport_assignments') . ' a WHERE a.route_id=r.id AND a.status="active") occ
    FROM ' . tbl('transport_routes') . ' r WHERE r.campus_id=' . $campus . ' AND r.status="active"
    ORDER BY r.route_name')->fetch_all(MYSQLI_ASSOC);

$seats = 0; $occ = 0; foreach ($byRoute as $b) { $seats += (int)$b['capacity']; $occ += (int)$b['occ']; }
$free = max(0, $seats - $occ);
$collected = (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('transport_payments') . ' WHERE campus_id=' . $campus)->fetch_assoc()['s'];
$riders = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('transport_assignments') . ' WHERE campus_id=' . $campus . ' AND status="active"')->fetch_assoc()['c'];

$payments = $db->query('SELECT p.*, s.name student_name, r.route_name
    FROM ' . tbl('transport_payments') . ' p
    LEFT JOIN ' . tbl('students') . ' s ON s.id=p.student_id
    LEFT JOIN ' . tbl('transport_assignments') . ' a ON a.id=p.assignment_id
    LEFT JOIN ' . tbl('transport_routes') . ' r ON r.id=a.route_id
    WHERE p.campus_id=' . $campus . ' ORDER BY p.paid_on DESC, p.id DESC LIMIT 15')->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Transport Report</h1><p>Route load &amp; fee collection · <?= e(date('d M Y')) ?></p></div>
  <div style="display:flex;gap:.5rem">
    <a href="<?= transport_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print / PDF</button>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-chair"></i></span><div><small>Total Seats</small><strong><?= $seats ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-users"></i></span><div><small>Riders</small><strong><?= $riders ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-chair"></i></span><div><small>Free Seats</small><strong><?= $free ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-coins"></i></span><div><small>Fees Collected</small><strong><?= money($collected) ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <div class="u-card-head"><h2><i class="fa-solid fa-route" style="color:var(--primary)"></i> Load by Route</h2></div>
  <?php if (!$byRoute): ?><div class="u-empty"><i class="fa-solid fa-route"></i><p>No active routes yet.</p></div>
  <?php else: ?>
    <table class="u-table"><thead><tr><th>Route</th><th>Vehicle</th><th style="width:40%">Occupancy</th></tr></thead><tbody>
      <?php foreach ($byRoute as $b): $cap=(int)$b['capacity']; $oc=(int)$b['occ']; $pct=$cap>0?round($oc/$cap*100):0; ?>
        <tr><td><strong><?= e($b['route_name']) ?></strong></td>
          <td style="color:var(--muted)"><?= e($b['vehicle_no'] ?: '—') ?></td>
          <td><div style="display:flex;align-items:center;gap:.6rem">
            <div class="u-prog" style="flex:1"><span style="width:<?= $pct ?>%"></span></div>
            <strong style="min-width:3.4rem;text-align:right"><?= $oc ?>/<?= $cap ?></strong></div></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  <?php endif; ?>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-receipt" style="color:var(--primary)"></i> Recent Fee Payments</h2></div>
  <?php if (!$payments): ?><div class="u-empty"><i class="fa-solid fa-coins"></i><p>No transport fees collected yet.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Date</th><th>Student</th><th>Route</th><th>Month</th><th>Method</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr><td><?= date('d M Y', strtotime($p['paid_on'])) ?></td>
            <td><?= e($p['student_name'] ?? '—') ?></td>
            <td style="color:var(--muted)"><?= e($p['route_name'] ?? '—') ?></td>
            <td><?= e($p['month'] ? date('M Y', strtotime($p['month'].'-01')) : '—') ?></td>
            <td style="color:var(--muted)"><?= e(TRANSPORT_METHODS[$p['method']] ?? $p['method']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--success)"><?= money((float)$p['amount']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<style>@media print{.u-side,.u-top,.u-page-head .u-btn,.u-side-backdrop{display:none!important}.u-main{margin:0!important}body{background:#fff}}</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
