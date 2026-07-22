<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Classes & Sections'; $active = 'classes';
$db = ums_db(); $campus = (int)$user['campus_id'];

$q       = trim((string)($_GET['q'] ?? ''));
$fProg   = in_array($_GET['program'] ?? '', program_list(), true) ? $_GET['program'] : '';
$fSem    = (int)($_GET['semester'] ?? 0);
$fStatus = in_array($_GET['status'] ?? '', ['active', 'inactive'], true) ? $_GET['status'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10; $offset = ($page - 1) * $perPage;

$where = ['s.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '')      { $where[] = '(s.program LIKE ? OR s.name LIKE ?)'; $l = "%$q%"; $types .= 'ss'; array_push($args, $l, $l); }
if ($fProg !== '')  { $where[] = 's.program = ?';  $types .= 's'; $args[] = $fProg; }
if ($fSem)          { $where[] = 's.semester = ?'; $types .= 'i'; $args[] = $fSem; }
if ($fStatus !=='') { $where[] = 's.status = ?';   $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('sections') . " s WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$hasStudents = (bool)$db->query("SHOW TABLES LIKE '" . tbl('students') . "'")->num_rows;
$enrolExpr = $hasStudents ? '(SELECT COUNT(*) FROM ' . tbl('students') . ' st WHERE st.section_id = s.id)' : '0';

$ls = $db->prepare("SELECT s.*, t.name AS teacher_name, $enrolExpr AS enrolled
    FROM " . tbl('sections') . " s
    LEFT JOIN " . tbl('teachers') . " t ON t.id = s.class_teacher_id
    WHERE $whereSql ORDER BY s.program, s.semester, s.name LIMIT ? OFFSET ?");
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

$agg = $db->prepare('SELECT COUNT(*) n, COALESCE(SUM(capacity),0) cap, SUM(status="active") act FROM ' . tbl('sections') . ' WHERE campus_id=?');
$agg->bind_param('i', $campus); $agg->execute(); $a = $agg->get_result()->fetch_assoc(); $agg->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Classes &amp; Sections</h1><p>Group students into classes by program and semester</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= sec_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= sec_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> New Section</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-chalkboard"></i></span><div><small>Total Sections</small><strong><?= (int)$a['n'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= (int)$a['act'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-users"></i></span><div><small>Total Capacity</small><strong><?= (int)$a['cap'] ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search program or section name…"></div>
    <select name="program" class="u-select" onchange="this.form.submit()"><option value="">All Programs</option>
      <?php foreach (program_list() as $p): ?><option value="<?= e($p) ?>" <?= $fProg===$p?'selected':'' ?>><?= e($p) ?></option><?php endforeach; ?></select>
    <select name="semester" class="u-select" onchange="this.form.submit()"><option value="0">All Semesters</option>
      <?php for ($i=1;$i<=8;$i++): ?><option value="<?= $i ?>" <?= $fSem===$i?'selected':'' ?>>Semester <?= $i ?></option><?php endfor; ?></select>
    <select name="status" class="u-select" onchange="this.form.submit()"><option value="">All Status</option>
      <option value="active" <?= $fStatus==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $fStatus==='inactive'?'selected':'' ?>>Inactive</option></select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q||$fProg||$fSem||$fStatus): ?><a href="<?= sec_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Sections</h2>
    <span class="hint"><?= $total ?> record<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-chalkboard"></i><p>No sections found<?= ($q||$fProg||$fSem||$fStatus)?' for these filters':' yet' ?>.</p>
      <a href="<?= sec_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Create the first section</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Section</th><th>Session</th><th>Class Teacher</th><th>Enrolled</th><th>Room</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $fill = (int)$r['capacity'] > 0 ? min(100, (int)round((int)$r['enrolled'] / (int)$r['capacity'] * 100)) : 0; ?>
          <tr>
            <td><strong><?= e($r['program']) ?></strong><br><small style="color:var(--muted)">Sem <?= (int)$r['semester'] ?> · Section <?= e($r['name']) ?></small></td>
            <td style="color:var(--muted)"><?= e($r['session'] ?: '—') ?></td>
            <td style="color:var(--muted)"><?= e($r['teacher_name'] ?: '—') ?></td>
            <td style="min-width:130px">
              <div style="display:flex;align-items:center;gap:.5rem">
                <div class="u-prog-track" style="flex:1"><div class="u-prog-fill" style="width:<?= $fill ?>%"></div></div>
                <span style="font-weight:700;font-size:.78rem"><?= (int)$r['enrolled'] ?>/<?= (int)$r['capacity'] ?></span>
              </div>
            </td>
            <td style="color:var(--muted)"><?= e($r['room'] ?: '—') ?></td>
            <td><?= active_badge($r['status']) ?></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= sec_url('view.php?id='.(int)$r['id']) ?>" title="Manage students"><i class="fa-solid fa-users"></i></a>
              <a href="<?= sec_url('edit.php?id='.(int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= sec_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this section? Students will be unassigned.')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q','program','semester','status'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
