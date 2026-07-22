<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Admissions';
$active     = 'admissions';
$db         = ums_db();

// ── Read filters / search / paging from the query string ──
$q       = trim((string)($_GET['q'] ?? ''));
$fStatus = (string)($_GET['status'] ?? '');
$fProg   = (string)($_GET['program'] ?? '');
$fSess   = (string)($_GET['session'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

if (!isset(ADM_STATUS[$fStatus])) $fStatus = '';
if (!in_array($fProg, ADM_PROGRAMS, true)) $fProg = '';
if (!in_array($fSess, ADM_SESSIONS, true)) $fSess = '';

// ── Build a parameterised WHERE clause (no string interpolation of input) ──
$where = ['campus_id = ?'];
$types = 'i';
$args  = [(int)$user['campus_id']];

if ($q !== '') {
    $where[] = '(student_name LIKE ? OR application_no LIKE ? OR cnic LIKE ? OR phone LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'sssss';
    array_push($args, $like, $like, $like, $like, $like);
}
if ($fStatus !== '') { $where[] = 'status = ?';     $types .= 's'; $args[] = $fStatus; }
if ($fProg   !== '') { $where[] = 'program = ?';    $types .= 's'; $args[] = $fProg; }
if ($fSess   !== '') { $where[] = 'session = ?';    $types .= 's'; $args[] = $fSess; }
$whereSql = implode(' AND ', $where);

// ── Total for pagination ──
$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('admissions') . " WHERE $whereSql");
$cs->bind_param($types, ...$args);
$cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c'];
$cs->close();
$pages = max(1, (int)ceil($total / $perPage));

// ── Page rows ──
$ls = $db->prepare('SELECT * FROM ' . tbl('admissions') . " WHERE $whereSql ORDER BY id DESC LIMIT ? OFFSET ?");
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC);
$ls->close();

// ── Stat chips (whole campus, unfiltered) ──
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'enrolled' => 0];
$sres = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('admissions') . ' WHERE campus_id = ? GROUP BY status');
$campus = (int)$user['campus_id'];
$sres->bind_param('i', $campus);
$sres->execute();
$sr = $sres->get_result();
while ($r = $sr->fetch_assoc()) { $stats[$r['status']] = (int)$r['c']; $stats['total'] += (int)$r['c']; }
$sres->close();

/** Preserve current filters when building links. */
function adm_qs(array $extra = []): string
{
    $base = array_filter([
        'q'       => $_GET['q'] ?? '',
        'status'  => $_GET['status'] ?? '',
        'program' => $_GET['program'] ?? '',
        'session' => $_GET['session'] ?? '',
    ], fn($v) => $v !== '');
    return http_build_query(array_merge($base, $extra));
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="u-page-head">
  <div>
    <h1>Admissions</h1>
    <p>Manage applications, review merit, and enroll students</p>
  </div>
  <div class="d-flex gap-2" style="display:flex;gap:.6rem">
    <a href="<?= adm_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Reports</a>
    <a href="<?= adm_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-user-plus"></i> New Application</a>
  </div>
</div>

<!-- Stat chips -->
<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-inbox"></i></span><div><small>Total</small><strong><?= $stats['total'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-clock"></i></span><div><small>Pending</small><strong><?= $stats['pending'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Approved</small><strong><?= $stats['approved'] ?></strong></div></div>
  <div class="u-chip"><span class="ci" style="background:linear-gradient(135deg,#ef4444,#f87171)"><i class="fa-solid fa-circle-xmark"></i></span><div><small>Rejected</small><strong><?= $stats['rejected'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-user-graduate"></i></span><div><small>Enrolled</small><strong><?= $stats['enrolled'] ?></strong></div></div>
</div>

<!-- Search + filters -->
<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, application no, CNIC, phone, or email…">
    </div>
    <select name="status" class="u-select" onchange="this.form.submit()">
      <option value="">All Status</option>
      <?php foreach (ADM_STATUS as $k => [$lbl]): ?>
        <option value="<?= e($k) ?>" <?= $fStatus === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="program" class="u-select" onchange="this.form.submit()">
      <option value="">All Programs</option>
      <?php foreach (ADM_PROGRAMS as $p): ?>
        <option value="<?= e($p) ?>" <?= $fProg === $p ? 'selected' : '' ?>><?= e($p) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="session" class="u-select" onchange="this.form.submit()">
      <option value="">All Sessions</option>
      <?php foreach (ADM_SESSIONS as $s): ?>
        <option value="<?= e($s) ?>" <?= $fSess === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q !== '' || $fStatus || $fProg || $fSess): ?>
      <a href="<?= adm_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Results -->
<div class="u-card">
  <div class="u-card-head">
    <h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Applications</h2>
    <span class="hint"><?= $total ?> record<?= $total === 1 ? '' : 's' ?><?= $q !== '' ? ' matching “' . e($q) . '”' : '' ?></span>
  </div>

  <?php if (!$rows): ?>
    <div class="u-empty">
      <i class="fa-solid fa-inbox"></i>
      <p>No applications found<?= ($q || $fStatus || $fProg || $fSess) ? ' for these filters' : ' yet' ?>.</p>
      <a href="<?= adm_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-user-plus"></i> Add the first application</a>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="u-table">
        <thead><tr><th>Applicant</th><th>App No.</th><th>Program</th><th>Session</th><th>Merit</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>
                <span style="display:flex;align-items:center;gap:.6rem">
                  <span class="u-mini-av"><?= e(adm_ini($r['student_name'])) ?></span>
                  <span><strong><?= e($r['student_name']) ?></strong><br><small style="color:var(--muted)"><?= e($r['phone'] ?: $r['email'] ?: '—') ?></small></span>
                </span>
              </td>
              <td style="color:var(--muted);font-weight:700"><?= e($r['application_no']) ?></td>
              <td style="color:var(--muted)"><?= e($r['program']) ?></td>
              <td style="color:var(--muted)"><?= e($r['session']) ?></td>
              <td style="font-weight:700"><?= $r['total_marks'] > 0 ? number_format((float)$r['merit_score'], 1) . '%' : '—' ?></td>
              <td><?= adm_badge($r['status']) ?></td>
              <td style="text-align:right">
                <span class="u-act">
                  <a href="<?= adm_url('view.php?id=' . (int)$r['id']) ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                  <a href="<?= adm_url('edit.php?id=' . (int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                  <?php if ($r['status'] === 'pending'): ?>
                    <form method="post" action="<?= adm_url('action.php') ?>" style="display:inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="set_status">
                      <input type="hidden" name="status" value="approved">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button type="submit" class="ok" title="Approve"><i class="fa-solid fa-check"></i></button>
                    </form>
                  <?php endif; ?>
                  <form method="post" action="<?= adm_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this application permanently?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <div class="u-pager">
        <a class="<?= $page <= 1 ? 'dis' : '' ?>" href="?<?= adm_qs(['page' => $page - 1]) ?>"><i class="fa-solid fa-chevron-left"></i></a>
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <?php if ($p == 1 || $p == $pages || abs($p - $page) <= 2): ?>
            <a class="<?= $p == $page ? 'cur' : '' ?>" href="?<?= adm_qs(['page' => $p]) ?>"><?= $p ?></a>
          <?php elseif (abs($p - $page) === 3): ?>
            <span class="dis">…</span>
          <?php endif; ?>
        <?php endfor; ?>
        <a class="<?= $page >= $pages ? 'dis' : '' ?>" href="?<?= adm_qs(['page' => $page + 1]) ?>"><i class="fa-solid fa-chevron-right"></i></a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
