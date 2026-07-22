<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Examinations'; $active = 'exams';
$db = ums_db(); $campus = (int)$user['campus_id'];
$sections = exam_section_options($campus);
$courses  = exam_course_options($campus);

$q       = trim((string)($_GET['q'] ?? ''));
$fSec    = (int)($_GET['section'] ?? 0);
$fType   = array_key_exists($_GET['type'] ?? '', EXAM_TYPES) ? $_GET['type'] : '';
$fStatus = in_array($_GET['status'] ?? '', ['scheduled', 'completed'], true) ? $_GET['status'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10; $offset = ($page - 1) * $perPage;

$where = ['e.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '')      { $where[] = 'e.title LIKE ?'; $types .= 's'; $args[] = "%$q%"; }
if ($fSec)          { $where[] = 'e.section_id = ?'; $types .= 'i'; $args[] = $fSec; }
if ($fType !== '')  { $where[] = 'e.exam_type = ?'; $types .= 's'; $args[] = $fType; }
if ($fStatus !=='') { $where[] = 'e.status = ?'; $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('exams') . " e WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$ls = $db->prepare("SELECT e.*, (SELECT COUNT(*) FROM " . tbl('exam_marks') . " m WHERE m.exam_id = e.id) AS marked
    FROM " . tbl('exams') . " e WHERE $whereSql ORDER BY e.id DESC LIMIT ? OFFSET ?");
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

$st = ['total' => 0, 'scheduled' => 0, 'completed' => 0];
$sr = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('exams') . ' WHERE campus_id=? GROUP BY status');
$sr->bind_param('i', $campus); $sr->execute(); $r = $sr->get_result();
while ($x = $r->fetch_assoc()) { $st[$x['status']] = (int)$x['c']; $st['total'] += (int)$x['c']; }
$sr->close();

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Examinations</h1><p>Schedule exams and enter student marks</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= exam_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= exam_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> New Exam</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-file-pen"></i></span><div><small>Total Exams</small><strong><?= $st['total'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-clock"></i></span><div><small>Scheduled</small><strong><?= $st['scheduled'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Completed</small><strong><?= $st['completed'] ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search exam title…"></div>
    <select name="section" class="u-select" onchange="this.form.submit()"><option value="0">All Sections</option>
      <?php foreach ($sections as $id=>$label): ?><option value="<?= $id ?>" <?= $fSec===$id?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select>
    <select name="type" class="u-select" onchange="this.form.submit()"><option value="">All Types</option>
      <?php foreach (EXAM_TYPES as $k=>$lbl): ?><option value="<?= $k ?>" <?= $fType===$k?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select>
    <select name="status" class="u-select" onchange="this.form.submit()"><option value="">All Status</option>
      <option value="scheduled" <?= $fStatus==='scheduled'?'selected':'' ?>>Scheduled</option><option value="completed" <?= $fStatus==='completed'?'selected':'' ?>>Completed</option></select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q||$fSec||$fType||$fStatus): ?><a href="<?= exam_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Exams</h2>
    <span class="hint"><?= $total ?> record<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-file-pen"></i><p>No exams found<?= ($q||$fSec||$fType||$fStatus)?' for these filters':' yet' ?>.</p>
      <a href="<?= exam_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Create the first exam</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Exam</th><th>Type</th><th>Section</th><th>Marks</th><th>Marked</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= e($r['title']) ?></strong><?= $r['exam_date'] ? '<br><small style="color:var(--muted)">' . e(date('d M Y', strtotime($r['exam_date']))) . '</small>' : '' ?></td>
            <td><span class="st" style="background:rgba(99,102,241,.1);color:var(--primary)"><?= e(EXAM_TYPES[$r['exam_type']] ?? $r['exam_type']) ?></span></td>
            <td style="color:var(--muted)"><?= e($sections[(int)$r['section_id']] ?? '—') ?></td>
            <td style="color:var(--muted)"><?= (int)$r['total_marks'] ?> <small>(pass <?= (int)$r['passing_marks'] ?>)</small></td>
            <td><span class="st <?= (int)$r['marked']>0?'st-approved':'st-pending' ?>"><?= (int)$r['marked'] ?></span></td>
            <td><span class="st <?= $r['status']==='completed'?'st-approved':'st-pending' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= exam_url('marks.php?exam='.(int)$r['id']) ?>" title="Enter marks"><i class="fa-solid fa-pen-to-square"></i></a>
              <a href="<?= exam_url('edit.php?id='.(int)$r['id']) ?>" title="Edit exam"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= exam_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this exam and its marks?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q','section','type','status'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
