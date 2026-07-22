<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Teachers'; $active = 'teachers';
$db = ums_db(); $campus = (int)$user['campus_id'];
$depts = dept_options($campus);

$q       = trim((string)($_GET['q'] ?? ''));
$fDept   = (int)($_GET['department'] ?? 0);
$fDesig  = in_array($_GET['designation'] ?? '', TCH_DESIGNATIONS, true) ? $_GET['designation'] : '';
$fStatus = array_key_exists($_GET['status'] ?? '', TCH_STATUS) ? $_GET['status'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10; $offset = ($page - 1) * $perPage;

$where = ['t.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '')      { $where[] = '(t.name LIKE ? OR t.employee_no LIKE ? OR t.email LIKE ? OR t.phone LIKE ?)'; $l = "%$q%"; $types .= 'ssss'; array_push($args, $l, $l, $l, $l); }
if ($fDept)         { $where[] = 't.department_id = ?'; $types .= 'i'; $args[] = $fDept; }
if ($fDesig !== '') { $where[] = 't.designation = ?';   $types .= 's'; $args[] = $fDesig; }
if ($fStatus !=='') { $where[] = 't.status = ?';        $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('teachers') . " t WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$ls = $db->prepare('SELECT t.*, d.name AS dept_name FROM ' . tbl('teachers') . ' t
    LEFT JOIN ' . tbl('departments') . ' d ON d.id = t.department_id
    WHERE ' . $whereSql . ' ORDER BY t.id DESC LIMIT ? OFFSET ?');
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

$stats = ['total' => 0, 'active' => 0, 'on_leave' => 0, 'inactive' => 0];
$sr = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('teachers') . ' WHERE campus_id = ? GROUP BY status');
$sr->bind_param('i', $campus); $sr->execute(); $r = $sr->get_result();
while ($x = $r->fetch_assoc()) { $stats[$x['status']] = (int)$x['c']; $stats['total'] += (int)$x['c']; }
$sr->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Teachers</h1><p>Faculty and staff records</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= tch_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= tch_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-user-plus"></i> Add Teacher</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-person-chalkboard"></i></span><div><small>Total Faculty</small><strong><?= $stats['total'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= $stats['active'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-plane-departure"></i></span><div><small>On Leave</small><strong><?= $stats['on_leave'] ?></strong></div></div>
  <div class="u-chip"><span class="ci" style="background:var(--muted)"><i class="fa-solid fa-circle-pause"></i></span><div><small>Inactive</small><strong><?= $stats['inactive'] ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, employee no, email, or phone…"></div>
    <select name="department" class="u-select" onchange="this.form.submit()"><option value="0">All Departments</option>
      <?php foreach ($depts as $id=>$name): ?><option value="<?= $id ?>" <?= $fDept===$id?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></select>
    <select name="designation" class="u-select" onchange="this.form.submit()"><option value="">All Designations</option>
      <?php foreach (TCH_DESIGNATIONS as $d): ?><option value="<?= e($d) ?>" <?= $fDesig===$d?'selected':'' ?>><?= e($d) ?></option><?php endforeach; ?></select>
    <select name="status" class="u-select" onchange="this.form.submit()"><option value="">All Status</option>
      <?php foreach (TCH_STATUS as $k=>$m): ?><option value="<?= $k ?>" <?= $fStatus===$k?'selected':'' ?>><?= e($m[0]) ?></option><?php endforeach; ?></select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q||$fDept||$fDesig||$fStatus): ?><a href="<?= tch_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Faculty</h2>
    <span class="hint"><?= $total ?> record<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-person-chalkboard"></i><p>No teachers found<?= ($q||$fDept||$fDesig||$fStatus)?' for these filters':' yet' ?>.</p>
      <a href="<?= tch_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-user-plus"></i> Add the first teacher</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Teacher</th><th>Employee No.</th><th>Department</th><th>Designation</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><span style="display:flex;align-items:center;gap:.6rem">
              <?php if ($r['photo'] !== ''): ?><img src="<?= UMS_URL.'/'.e($r['photo']) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover">
              <?php else: ?><span class="u-mini-av"><?= e(ini2($r['name'])) ?></span><?php endif; ?>
              <span><strong><?= e($r['name']) ?></strong><br><small style="color:var(--muted)"><?= e($r['email'] ?: $r['phone'] ?: '—') ?></small></span></span></td>
            <td style="color:var(--muted);font-weight:700"><?= e($r['employee_no']) ?></td>
            <td style="color:var(--muted)"><?= e($r['dept_name'] ?: '—') ?></td>
            <td style="color:var(--muted)"><?= e($r['designation']) ?></td>
            <td><?= status_badge($r['status'], TCH_STATUS) ?></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= tch_url('view.php?id='.(int)$r['id']) ?>" title="View"><i class="fa-solid fa-eye"></i></a>
              <a href="<?= tch_url('edit.php?id='.(int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= tch_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this teacher?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q','department','designation','status'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
