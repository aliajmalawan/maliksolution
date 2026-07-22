<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Assign / Collect'; $active = 'transport';
$db = ums_db(); $campus = (int)$user['campus_id'];

$routes   = transport_available_routes($campus);
$students = transport_student_options($campus);

// Active assignments (riders)
$riders = $db->query('SELECT a.*, r.route_name, r.vehicle_no, s.name AS student_name, s.registration_no,
    (SELECT COALESCE(SUM(p.amount),0) FROM ' . tbl('transport_payments') . ' p WHERE p.assignment_id=a.id) paid
    FROM ' . tbl('transport_assignments') . ' a
    JOIN ' . tbl('transport_routes') . ' r ON r.id=a.route_id
    LEFT JOIN ' . tbl('students') . ' s ON s.id=a.student_id
    WHERE a.campus_id=' . $campus . ' AND a.status="active"
    ORDER BY r.route_name, s.name')->fetch_all(MYSQLI_ASSOC);

$assignOpts = [];
foreach ($riders as $a) {
    $assignOpts[(int)$a['id']] = ($a['student_name'] ?? 'Student') . ' · ' . $a['route_name'] . ' (' . money((float)$a['monthly_fee']) . '/mo)';
}

$collected = (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('transport_payments') . ' WHERE campus_id=' . $campus)->fetch_assoc()['s'];

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Assign / Collect</h1><p>Assign routes &amp; record transport fees</p></div>
  <div><a href="<?= transport_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Routes</a></div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-users"></i></span><div><small>Riders</small><strong><?= count($riders) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-chair"></i></span><div><small>Routes w/ Seat</small><strong><?= count($routes) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-coins"></i></span><div><small>Fees Collected</small><strong><?= money($collected) ?></strong></div></div>
</div>

<div class="u-grid g-two">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-user-plus" style="color:var(--primary)"></i> Assign a Route</h2></div>
    <?php if (!$routes || !$students): ?>
      <div class="u-empty"><i class="fa-solid fa-circle-info"></i><p>
        <?= !$routes ? 'No routes with a free seat. ' : '' ?><?= !$students ? 'No unassigned active students.' : '' ?></p>
        <?php if (!$routes): ?><a href="<?= transport_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add a route</a><?php endif; ?></div>
    <?php else: ?>
      <form method="post" action="<?= transport_url('action.php') ?>" class="u-form-grid">
        <?= csrf_field() ?><input type="hidden" name="action" value="assign">
        <div class="u-fld col-full"><label>Route <span class="req">*</span></label>
          <select name="route_id" required><option value="">— Select route —</option>
            <?php foreach ($routes as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld col-full"><label>Student <span class="req">*</span></label>
          <select name="student_id" required><option value="">— Select student —</option>
            <?php foreach ($students as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld col-full"><label>Pickup Stop</label><input type="text" name="stop" placeholder="e.g. Main Gate"></div>
        <div class="u-form-actions col-full">
          <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-check"></i> Assign Route</button></div>
      </form>
    <?php endif; ?>
  </div>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-hand-holding-dollar" style="color:var(--primary)"></i> Collect Fee</h2></div>
    <?php if (!$assignOpts): ?>
      <div class="u-empty"><i class="fa-solid fa-circle-info"></i><p>No active riders to collect from.</p></div>
    <?php else: ?>
      <form method="post" action="<?= transport_url('action.php') ?>" class="u-form-grid">
        <?= csrf_field() ?><input type="hidden" name="action" value="collect">
        <div class="u-fld col-full"><label>Rider <span class="req">*</span></label>
          <select name="assignment_id" required><option value="">— Select rider —</option>
            <?php foreach ($assignOpts as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld"><label>Amount (Rs) <span class="req">*</span></label><input type="number" name="amount" min="1" step="0.01" required placeholder="0"></div>
        <div class="u-fld"><label>Month</label><input type="month" name="month" value="<?= date('Y-m') ?>"></div>
        <div class="u-fld"><label>Method</label>
          <select name="method"><?php foreach (TRANSPORT_METHODS as $k => $lbl): ?><option value="<?= $k ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld"><label>Reference</label><input type="text" name="reference" placeholder="Receipt / note"></div>
        <div class="u-form-actions col-full">
          <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-coins"></i> Record Payment</button></div>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="u-card" style="margin-top:1.1rem">
  <div class="u-card-head"><h2><i class="fa-solid fa-list-check" style="color:var(--primary)"></i> Current Riders</h2><span class="hint"><?= count($riders) ?></span></div>
  <?php if (!$riders): ?>
    <div class="u-empty"><i class="fa-solid fa-bus"></i><p>No riders assigned yet.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Student</th><th>Route</th><th>Stop</th><th>Since</th><th style="text-align:right">Monthly</th><th style="text-align:right">Paid</th><th style="text-align:right">Action</th></tr></thead>
      <tbody>
        <?php foreach ($riders as $a): ?>
          <tr>
            <td><strong><?= e($a['student_name'] ?? '—') ?></strong><?= $a['registration_no'] ? '<br><small style="color:var(--muted)">' . e($a['registration_no']) . '</small>' : '' ?></td>
            <td><?= e($a['route_name']) ?><?= $a['vehicle_no'] ? '<br><small style="color:var(--muted)">' . e($a['vehicle_no']) . '</small>' : '' ?></td>
            <td style="color:var(--muted)"><?= e($a['stop'] ?: '—') ?></td>
            <td style="color:var(--muted)"><?= date('d M Y', strtotime($a['assigned_on'])) ?></td>
            <td style="text-align:right"><?= money((float)$a['monthly_fee']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--success)"><?= money((float)$a['paid']) ?></td>
            <td style="text-align:right">
              <form method="post" action="<?= transport_url('action.php') ?>" style="display:inline" onsubmit="return confirm('End this assignment? The seat becomes free.')">
                <?= csrf_field() ?><input type="hidden" name="action" value="end"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="u-btn u-btn-soft u-btn-sm"><i class="fa-solid fa-right-from-bracket"></i> End</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
