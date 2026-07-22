<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Hostel'; $active = 'hostel';
$db = ums_db(); $campus = (int)$user['campus_id'];

$q     = trim((string)($_GET['q'] ?? ''));
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12; $offset = ($page - 1) * $perPage;

$where = ['r.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '') { $where[] = '(r.block LIKE ? OR r.room_no LIKE ?)'; $l = "%$q%"; $types .= 'ss'; array_push($args, $l, $l); }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('hostel_rooms') . " r WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$sql = 'SELECT r.*, (SELECT COUNT(*) FROM ' . tbl('hostel_allotments') . ' a WHERE a.room_id=r.id AND a.status="active") occ
        FROM ' . tbl('hostel_rooms') . " r WHERE $whereSql ORDER BY r.block, r.room_no LIMIT ? OFFSET ?";
$ls = $db->prepare($sql);
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

$agg = $db->query('SELECT COUNT(*) rooms, COALESCE(SUM(capacity),0) beds FROM ' . tbl('hostel_rooms') . ' WHERE campus_id=' . $campus . ' AND status="active"')->fetch_assoc();
$occTotal = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('hostel_allotments') . ' WHERE campus_id=' . $campus . ' AND status="active"')->fetch_assoc()['c'];
$beds = (int)$agg['beds']; $vacant = max(0, $beds - $occTotal);
$occRate = $beds > 0 ? round($occTotal / $beds * 100) : 0;

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Hostel</h1><p>Rooms, allotments &amp; fees</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= hostel_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= hostel_url('allot.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-user-plus"></i> Allot / Collect</a>
    <a href="<?= hostel_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> Add Room</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-door-closed"></i></span><div><small>Active Rooms</small><strong><?= (int)$agg['rooms'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-bed"></i></span><div><small>Total Beds</small><strong><?= $beds ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-user-group"></i></span><div><small>Occupied</small><strong><?= $occTotal ?> <span style="font-weight:600;color:var(--muted);font-size:.8rem">(<?= $occRate ?>%)</span></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-bed-pulse"></i></span><div><small>Vacant Beds</small><strong><?= $vacant ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search block or room number…"></div>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Search</button>
    <?php if ($q): ?><a href="<?= hostel_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-building" style="color:var(--primary)"></i> Rooms</h2><span class="hint"><?= $total ?> room<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-door-open"></i><p>No rooms found<?= $q?' for this search':' yet' ?>.</p>
      <a href="<?= hostel_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add the first room</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Room</th><th>Type</th><th style="text-align:center">Occupancy</th><th style="text-align:right">Monthly Fee</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $occ = (int)$r['occ']; $cap = (int)$r['capacity']; $full = $occ >= $cap; ?>
          <tr>
            <td><strong><?= e(($r['block']?e($r['block']).' · ':'').'Room '.e($r['room_no'])) ?></strong></td>
            <td><span class="st" style="background:rgba(99,102,241,.1);color:var(--primary)"><?= e(HOSTEL_ROOM_TYPES[$r['room_type']] ?? $r['room_type']) ?></span></td>
            <td style="text-align:center;font-weight:700"><span style="color:<?= $full?'var(--danger)':'var(--success)' ?>"><?= $occ ?></span> / <?= $cap ?></td>
            <td style="text-align:right;font-weight:700"><?= money((float)$r['monthly_fee']) ?></td>
            <td><?= active_badge($r['status']) ?></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= hostel_url('edit.php?id='.(int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= hostel_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this room?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
