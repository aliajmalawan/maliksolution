<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Allot / Collect'; $active = 'hostel';
$db = ums_db(); $campus = (int)$user['campus_id'];

$rooms    = hostel_available_rooms($campus);
$students = hostel_student_options($campus);

// Active allotments (residents)
$residents = $db->query('SELECT a.*, r.block, r.room_no, s.name AS student_name, s.registration_no,
    (SELECT COALESCE(SUM(p.amount),0) FROM ' . tbl('hostel_payments') . ' p WHERE p.allotment_id=a.id) paid
    FROM ' . tbl('hostel_allotments') . ' a
    JOIN ' . tbl('hostel_rooms') . ' r ON r.id=a.room_id
    LEFT JOIN ' . tbl('students') . ' s ON s.id=a.student_id
    WHERE a.campus_id=' . $campus . ' AND a.status="active"
    ORDER BY r.block, r.room_no')->fetch_all(MYSQLI_ASSOC);

// Options for the collect form
$allotOpts = [];
foreach ($residents as $a) {
    $allotOpts[(int)$a['id']] = ($a['student_name'] ?? 'Student') . ' · ' . ($a['block'] ? $a['block'] . ' ' : '') . 'Room ' . $a['room_no'] . ' (' . money((float)$a['monthly_fee']) . '/mo)';
}

$collected = (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('hostel_payments') . ' WHERE campus_id=' . $campus)->fetch_assoc()['s'];

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Allot / Collect</h1><p>Assign rooms &amp; record hostel fees</p></div>
  <div><a href="<?= hostel_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Rooms</a></div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-user-group"></i></span><div><small>Residents</small><strong><?= count($residents) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-bed-pulse"></i></span><div><small>Rooms w/ Vacancy</small><strong><?= count($rooms) ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-coins"></i></span><div><small>Fees Collected</small><strong><?= money($collected) ?></strong></div></div>
</div>

<div class="u-grid g-two">
  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-user-plus" style="color:var(--primary)"></i> Allot a Room</h2></div>
    <?php if (!$rooms || !$students): ?>
      <div class="u-empty"><i class="fa-solid fa-circle-info"></i><p>
        <?= !$rooms ? 'No rooms with a vacant bed. ' : '' ?><?= !$students ? 'No unallotted active students.' : '' ?></p>
        <?php if (!$rooms): ?><a href="<?= hostel_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add a room</a><?php endif; ?></div>
    <?php else: ?>
      <form method="post" action="<?= hostel_url('action.php') ?>" class="u-form-grid">
        <?= csrf_field() ?><input type="hidden" name="action" value="allot">
        <div class="u-fld col-full"><label>Room <span class="req">*</span></label>
          <select name="room_id" required><option value="">— Select room —</option>
            <?php foreach ($rooms as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld col-full"><label>Student <span class="req">*</span></label>
          <select name="student_id" required><option value="">— Select student —</option>
            <?php foreach ($students as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-form-actions col-full">
          <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-check"></i> Allot Room</button></div>
      </form>
    <?php endif; ?>
  </div>

  <div class="u-card">
    <div class="u-card-head"><h2><i class="fa-solid fa-hand-holding-dollar" style="color:var(--primary)"></i> Collect Fee</h2></div>
    <?php if (!$allotOpts): ?>
      <div class="u-empty"><i class="fa-solid fa-circle-info"></i><p>No active residents to collect from.</p></div>
    <?php else: ?>
      <form method="post" action="<?= hostel_url('action.php') ?>" class="u-form-grid">
        <?= csrf_field() ?><input type="hidden" name="action" value="collect">
        <div class="u-fld col-full"><label>Resident <span class="req">*</span></label>
          <select name="allotment_id" required><option value="">— Select resident —</option>
            <?php foreach ($allotOpts as $id => $lbl): ?><option value="<?= $id ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld"><label>Amount (Rs) <span class="req">*</span></label><input type="number" name="amount" min="1" step="0.01" required placeholder="0"></div>
        <div class="u-fld"><label>Month</label><input type="month" name="month" value="<?= date('Y-m') ?>"></div>
        <div class="u-fld"><label>Method</label>
          <select name="method"><?php foreach (HOSTEL_METHODS as $k => $lbl): ?><option value="<?= $k ?>"><?= e($lbl) ?></option><?php endforeach; ?></select></div>
        <div class="u-fld"><label>Reference</label><input type="text" name="reference" placeholder="Receipt / note"></div>
        <div class="u-form-actions col-full">
          <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-coins"></i> Record Payment</button></div>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="u-card" style="margin-top:1.1rem">
  <div class="u-card-head"><h2><i class="fa-solid fa-list-check" style="color:var(--primary)"></i> Current Residents</h2><span class="hint"><?= count($residents) ?></span></div>
  <?php if (!$residents): ?>
    <div class="u-empty"><i class="fa-solid fa-bed"></i><p>No residents allotted yet.</p></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Student</th><th>Room</th><th>Since</th><th style="text-align:right">Monthly</th><th style="text-align:right">Paid</th><th style="text-align:right">Action</th></tr></thead>
      <tbody>
        <?php foreach ($residents as $a): ?>
          <tr>
            <td><strong><?= e($a['student_name'] ?? '—') ?></strong><?= $a['registration_no'] ? '<br><small style="color:var(--muted)">' . e($a['registration_no']) . '</small>' : '' ?></td>
            <td><?= e(($a['block']?$a['block'].' · ':'').'Room '.$a['room_no']) ?></td>
            <td style="color:var(--muted)"><?= date('d M Y', strtotime($a['allotted_on'])) ?></td>
            <td style="text-align:right"><?= money((float)$a['monthly_fee']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--success)"><?= money((float)$a['paid']) ?></td>
            <td style="text-align:right">
              <form method="post" action="<?= hostel_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Vacate this room? The bed becomes available.')">
                <?= csrf_field() ?><input type="hidden" name="action" value="vacate"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="u-btn u-btn-soft u-btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Vacate</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
