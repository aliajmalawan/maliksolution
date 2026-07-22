<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Transport'; $active = 'transport';
$db = ums_db(); $campus = (int)$user['campus_id'];

$q     = trim((string)($_GET['q'] ?? ''));
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12; $offset = ($page - 1) * $perPage;

$where = ['r.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '') { $where[] = '(r.route_name LIKE ? OR r.vehicle_no LIKE ? OR r.driver_name LIKE ?)'; $l = "%$q%"; $types .= 'sss'; array_push($args, $l, $l, $l); }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('transport_routes') . " r WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$sql = 'SELECT r.*, (SELECT COUNT(*) FROM ' . tbl('transport_assignments') . ' a WHERE a.route_id=r.id AND a.status="active") occ
        FROM ' . tbl('transport_routes') . " r WHERE $whereSql ORDER BY r.route_name LIMIT ? OFFSET ?";
$ls = $db->prepare($sql);
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

$agg = $db->query('SELECT COUNT(*) routes, COALESCE(SUM(capacity),0) seats FROM ' . tbl('transport_routes') . ' WHERE campus_id=' . $campus . ' AND status="active"')->fetch_assoc();
$riders = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('transport_assignments') . ' WHERE campus_id=' . $campus . ' AND status="active"')->fetch_assoc()['c'];
$seats = (int)$agg['seats']; $free = max(0, $seats - $riders);

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Transport</h1><p>Routes, assignments &amp; fees</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= transport_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= transport_url('assign.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-user-plus"></i> Assign / Collect</a>
    <a href="<?= transport_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> Add Route</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-route"></i></span><div><small>Active Routes</small><strong><?= (int)$agg['routes'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-chair"></i></span><div><small>Total Seats</small><strong><?= $seats ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-users"></i></span><div><small>Riders</small><strong><?= $riders ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-chair"></i></span><div><small>Free Seats</small><strong><?= $free ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search route, vehicle, or driver…"></div>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Search</button>
    <?php if ($q): ?><a href="<?= transport_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-bus" style="color:var(--primary)"></i> Routes</h2><span class="hint"><?= $total ?> route<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-route"></i><p>No routes found<?= $q?' for this search':' yet' ?>.</p>
      <a href="<?= transport_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add the first route</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Route</th><th>Vehicle</th><th>Driver</th><th style="text-align:center">Seats</th><th style="text-align:right">Monthly Fee</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $occ = (int)$r['occ']; $cap = (int)$r['capacity']; $full = $occ >= $cap; ?>
          <tr>
            <td><strong><?= e($r['route_name']) ?></strong><?= $r['stops'] ? '<br><small style="color:var(--muted)">' . e($r['stops']) . '</small>' : '' ?></td>
            <td style="color:var(--muted)"><?= e($r['vehicle_no'] ?: '—') ?></td>
            <td><?= e($r['driver_name'] ?: '—') ?><?= $r['driver_phone'] ? '<br><small style="color:var(--muted)">' . e($r['driver_phone']) . '</small>' : '' ?></td>
            <td style="text-align:center;font-weight:700"><span style="color:<?= $full?'var(--danger)':'var(--success)' ?>"><?= $occ ?></span> / <?= $cap ?></td>
            <td style="text-align:right;font-weight:700"><?= money((float)$r['monthly_fee']) ?></td>
            <td><?= active_badge($r['status']) ?></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= transport_url('edit.php?id='.(int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= transport_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this route?')">
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
