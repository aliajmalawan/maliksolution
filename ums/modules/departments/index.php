<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Departments';
$active     = 'academic';
$db         = ums_db();
$campus     = (int)$user['campus_id'];

// Filters / search / paging
$q       = trim((string)($_GET['q'] ?? ''));
$fStatus = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where = ['campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '')       { $where[] = '(name LIKE ? OR code LIKE ? OR head_name LIKE ?)'; $l = "%$q%"; $types .= 'sss'; array_push($args, $l, $l, $l); }
if ($fStatus !== '') { $where[] = 'status = ?'; $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('departments') . " WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

// Does the courses table exist yet? (for the per-department course count)
$hasCourses = (bool)$db->query("SHOW TABLES LIKE '" . tbl('courses') . "'")->num_rows;
$countExpr  = $hasCourses ? '(SELECT COUNT(*) FROM ' . tbl('courses') . ' c WHERE c.department_id = d.id)' : '0';

$ls = $db->prepare("SELECT d.*, $countExpr AS course_count FROM " . tbl('departments') . " d WHERE $whereSql ORDER BY d.name LIMIT ? OFFSET ?");
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

// Stat chips
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$sr = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('departments') . ' WHERE campus_id = ? GROUP BY status');
$sr->bind_param('i', $campus); $sr->execute(); $r = $sr->get_result();
while ($x = $r->fetch_assoc()) { $stats[$x['status']] = (int)$x['c']; $stats['total'] += (int)$x['c']; }
$sr->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Departments</h1><p>Faculties and academic departments — the root of your academic structure</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= dept_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= dept_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> New Department</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-sitemap"></i></span><div><small>Total</small><strong><?= $stats['total'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= $stats['active'] ?></strong></div></div>
  <div class="u-chip"><span class="ci" style="background:var(--muted)"><i class="fa-solid fa-circle-pause"></i></span><div><small>Inactive</small><strong><?= $stats['inactive'] ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, code, or head of department…">
    </div>
    <select name="status" class="u-select" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="active" <?= $fStatus === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $fStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q !== '' || $fStatus): ?><a href="<?= dept_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head">
    <h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Departments</h2>
    <span class="hint"><?= $total ?> record<?= $total === 1 ? '' : 's' ?></span>
  </div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-sitemap"></i>
      <p>No departments found<?= ($q || $fStatus) ? ' for these filters' : ' yet' ?>.</p>
      <a href="<?= dept_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add the first department</a>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="u-table">
        <thead><tr><th>Department</th><th>Code</th><th>Head</th><th>Courses</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><span style="display:flex;align-items:center;gap:.6rem"><span class="u-mini-av"><?= e(ini2($r['name'])) ?></span><strong><?= e($r['name']) ?></strong></span></td>
              <td style="color:var(--muted);font-weight:700"><?= e($r['code'] ?: '—') ?></td>
              <td style="color:var(--muted)"><?= e($r['head_name'] ?: '—') ?></td>
              <td><span class="st" style="background:rgba(99,102,241,.1);color:var(--primary)"><?= (int)$r['course_count'] ?></span></td>
              <td><?= active_badge($r['status']) ?></td>
              <td style="text-align:right">
                <span class="u-act">
                  <a href="<?= dept_url('edit.php?id=' . (int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                  <form method="post" action="<?= dept_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this department?')">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q', 'status'], ['page' => $p])) ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
