<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Courses';
$active     = 'courses';
$db         = ums_db();
$campus     = (int)$user['campus_id'];
$depts      = dept_options($campus);

// Filters / search / paging
$q       = trim((string)($_GET['q'] ?? ''));
$fDept   = (int)($_GET['department'] ?? 0);
$fSem    = (int)($_GET['semester'] ?? 0);
$fType   = array_key_exists($_GET['type'] ?? '', CRS_TYPES) ? $_GET['type'] : '';
$fStatus = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where = ['c.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '')     { $where[] = '(c.title LIKE ? OR c.code LIKE ?)'; $l = "%$q%"; $types .= 'ss'; array_push($args, $l, $l); }
if ($fDept)        { $where[] = 'c.department_id = ?'; $types .= 'i'; $args[] = $fDept; }
if ($fSem)         { $where[] = 'c.semester = ?';      $types .= 'i'; $args[] = $fSem; }
if ($fType !== '') { $where[] = 'c.type = ?';          $types .= 's'; $args[] = $fType; }
if ($fStatus !=='') { $where[] = 'c.status = ?';       $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('courses') . " c WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$sql = 'SELECT c.*, d.name AS dept_name, p.title AS prereq_title
        FROM ' . tbl('courses') . ' c
        LEFT JOIN ' . tbl('departments') . ' d ON d.id = c.department_id
        LEFT JOIN ' . tbl('courses') . ' p ON p.id = c.prerequisite_id
        WHERE ' . $whereSql . ' ORDER BY c.semester, c.title LIMIT ? OFFSET ?';
$ls = $db->prepare($sql);
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

// Stat chips
$agg = $db->prepare('SELECT COUNT(*) n, COALESCE(SUM(credit_hours),0) cr,
    SUM(status="active") act FROM ' . tbl('courses') . ' WHERE campus_id = ?');
$agg->bind_param('i', $campus); $agg->execute();
$a = $agg->get_result()->fetch_assoc(); $agg->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Courses</h1><p>Semester-wise courses, credit hours, and prerequisites</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= crs_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= crs_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> New Course</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-book-open"></i></span><div><small>Total Courses</small><strong><?= (int)$a['n'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= (int)$a['act'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-clock"></i></span><div><small>Total Credit Hours</small><strong><?= (int)$a['cr'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-sitemap"></i></span><div><small>Departments</small><strong><?= count($depts) ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search course title or code…">
    </div>
    <select name="department" class="u-select" onchange="this.form.submit()">
      <option value="0">All Departments</option>
      <?php foreach ($depts as $id => $name): ?><option value="<?= $id ?>" <?= $fDept === $id ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?>
    </select>
    <select name="semester" class="u-select" onchange="this.form.submit()">
      <option value="0">All Semesters</option>
      <?php for ($s = 1; $s <= CRS_SEMESTERS; $s++): ?><option value="<?= $s ?>" <?= $fSem === $s ? 'selected' : '' ?>>Semester <?= $s ?></option><?php endfor; ?>
    </select>
    <select name="type" class="u-select" onchange="this.form.submit()">
      <option value="">All Types</option>
      <?php foreach (CRS_TYPES as $k => $lbl): ?><option value="<?= $k ?>" <?= $fType === $k ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q || $fDept || $fSem || $fType || $fStatus): ?><a href="<?= crs_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head">
    <h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Courses</h2>
    <span class="hint"><?= $total ?> record<?= $total === 1 ? '' : 's' ?></span>
  </div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-book-open"></i>
      <p>No courses found<?= ($q || $fDept || $fSem || $fType) ? ' for these filters' : ' yet' ?>.</p>
      <a href="<?= crs_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add the first course</a>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="u-table">
        <thead><tr><th>Course</th><th>Code</th><th>Department</th><th>Sem</th><th>Cr.</th><th>Type</th><th>Prerequisite</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><strong><?= e($r['title']) ?></strong></td>
              <td style="color:var(--muted);font-weight:700"><?= e($r['code'] ?: '—') ?></td>
              <td style="color:var(--muted)"><?= e($r['dept_name'] ?: '—') ?></td>
              <td style="text-align:center"><?= (int)$r['semester'] ?></td>
              <td style="text-align:center;font-weight:700"><?= (int)$r['credit_hours'] ?></td>
              <td><span class="st" style="background:rgba(6,182,212,.1);color:var(--info)"><?= e(CRS_TYPES[$r['type']] ?? $r['type']) ?></span></td>
              <td style="color:var(--muted);font-size:.8rem"><?= e($r['prereq_title'] ?: '—') ?></td>
              <td><?= active_badge($r['status']) ?></td>
              <td style="text-align:right">
                <span class="u-act">
                  <a href="<?= crs_url('edit.php?id=' . (int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                  <form method="post" action="<?= crs_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this course?')">
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
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q', 'department', 'semester', 'type', 'status'], ['page' => $p])) ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
