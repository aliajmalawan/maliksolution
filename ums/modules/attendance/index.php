<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Attendance Register'; $active = 'attendance';
$db = ums_db(); $campus = (int)$user['campus_id'];
$sections = att_section_options($campus);
$courses  = att_course_options($campus);

$fSec  = (int)($_GET['section'] ?? 0);
$from  = (string)($_GET['from'] ?? '');
$to    = (string)($_GET['to'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12; $offset = ($page - 1) * $perPage;

$where = ['campus_id = ?']; $types = 'i'; $args = [$campus];
if ($fSec && isset($sections[$fSec])) { $where[] = 'section_id = ?'; $types .= 'i'; $args[] = $fSec; }
if ($from !== '') { $where[] = 'a_date >= ?'; $types .= 's'; $args[] = $from; }
if ($to !== '')   { $where[] = 'a_date <= ?'; $types .= 's'; $args[] = $to; }
$whereSql = implode(' AND ', $where);

// total distinct sessions
$cs = $db->prepare("SELECT COUNT(*) c FROM (SELECT 1 FROM " . tbl('attendance') . " WHERE $whereSql GROUP BY section_id, course_id, a_date) t");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$ls = $db->prepare("SELECT section_id, course_id, a_date, COUNT(*) total, SUM(status IN ('present','late')) present
    FROM " . tbl('attendance') . " WHERE $whereSql
    GROUP BY section_id, course_id, a_date ORDER BY a_date DESC, section_id LIMIT ? OFFSET ?");
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

// summary chips
$sum = $db->prepare("SELECT COUNT(*) total, SUM(status IN ('present','late')) present FROM " . tbl('attendance') . " WHERE $whereSql");
$sum->bind_param($types, ...$args); $sum->execute();
$s = $sum->get_result()->fetch_assoc(); $sum->close();
$overallPct = (int)$s['total'] > 0 ? round((int)$s['present'] / (int)$s['total'] * 100, 1) : 0;

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Attendance Register</h1><p>Marked attendance sessions</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= att_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= att_url('mark.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-clipboard-user"></i> Mark Attendance</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-calendar-check"></i></span><div><small>Sessions</small><strong><?= $total ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-user-check"></i></span><div><small>Records Marked</small><strong><?= (int)$s['total'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-percent"></i></span><div><small>Overall Attendance</small><strong><?= $overallPct ?>%</strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <select name="section" class="u-select grow" onchange="this.form.submit()"><option value="0">All Sections</option>
      <?php foreach ($sections as $id => $label): ?><option value="<?= $id ?>" <?= $fSec === $id ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
    <div class="u-fld" style="gap:.2rem"><label style="font-size:.7rem;color:var(--muted)">From</label><input type="date" name="from" class="u-input" value="<?= e($from) ?>"></div>
    <div class="u-fld" style="gap:.2rem"><label style="font-size:.7rem;color:var(--muted)">To</label><input type="date" name="to" class="u-input" value="<?= e($to) ?>"></div>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($fSec || $from || $to): ?><a href="<?= att_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-table-list" style="color:var(--primary)"></i> Sessions</h2>
    <span class="hint"><?= $total ?> session<?= $total === 1 ? '' : 's' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-clipboard-user"></i><p>No attendance marked<?= ($fSec||$from||$to) ? ' for these filters' : ' yet' ?>.</p>
      <a href="<?= att_url('mark.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-clipboard-user"></i> Mark attendance</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Date</th><th>Section</th><th>Subject</th><th>Present</th><th>%</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $pct = (int)$r['total'] > 0 ? round((int)$r['present'] / (int)$r['total'] * 100) : 0; ?>
          <tr>
            <td><strong><?= e(date('d M Y', strtotime($r['a_date']))) ?></strong></td>
            <td style="color:var(--muted)"><?= e($sections[(int)$r['section_id']] ?? ('Section #' . (int)$r['section_id'])) ?></td>
            <td style="color:var(--muted)"><?= (int)$r['course_id'] > 0 ? e($courses[(int)$r['course_id']] ?? '—') : '<span style="color:var(--muted)">Daily</span>' ?></td>
            <td style="font-weight:700"><?= (int)$r['present'] ?>/<?= (int)$r['total'] ?></td>
            <td><span class="st <?= $pct >= 75 ? 'st-approved' : ($pct >= 50 ? 'st-pending' : 'st-rejected') ?>"><?= $pct ?>%</span></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= att_url('mark.php?section=' . (int)$r['section_id'] . '&date=' . e($r['a_date']) . '&course=' . (int)$r['course_id']) ?>" title="View / Edit"><i class="fa-solid fa-pen"></i></a>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['section','from','to'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
