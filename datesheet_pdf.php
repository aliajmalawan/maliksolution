<?php
declare(strict_types=1);
require_once 'includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); die('Not found.'); }

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `datesheet_subjects` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `datesheet_id` INT NOT NULL,
    `subject_name` VARCHAR(255) NOT NULL DEFAULT '',
    `exam_date`    DATE DEFAULT NULL,
    `start_time`   TIME DEFAULT NULL,
    `end_time`     TIME DEFAULT NULL,
    `venue`        VARCHAR(255) DEFAULT '',
    `sort_order`   INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$ds = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM datesheets WHERE id = $id LIMIT 1"));
if (!$ds) { http_response_code(404); die('Date sheet not found.'); }

$subj_res = mysqli_query($conn,
    "SELECT * FROM datesheet_subjects WHERE datesheet_id = $id ORDER BY sort_order ASC, exam_date ASC, id ASC");
$subjects = [];
if ($subj_res) while ($s = mysqli_fetch_assoc($subj_res)) $subjects[] = $s;

function fmt_t(string $t): string {
    if (!$t || $t === '00:00:00') return '';
    return date('g:i A', strtotime($t));
}

$title = $ds['title']     ?? 'Date Sheet';
$prog  = $ds['program']   ?? '';
$etype = $ds['exam_type'] ?? '';
$sess  = $ds['session']   ?? '';

$logo_path = __DIR__ . '/assets/images/Logo.png';
$logo_src  = file_exists($logo_path) ? 'assets/images/Logo.png' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?> — Malik Solution</title>
<style>
*  { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; color:#071f40; background:#f0f4f8; }

/* ── Toolbar (hidden on print) ── */
.toolbar {
  position:sticky; top:0; z-index:99;
  background:#071f40; padding:.7rem 1.5rem;
  display:flex; align-items:center; justify-content:space-between;
  box-shadow:0 2px 12px rgba(0,0,0,.3);
}
.toolbar-title { color:#fff; font-size:.85rem; opacity:.75; }
.toolbar-right  { display:flex; gap:.6rem; }
.btn-print {
  background:#e9b44c; color:#071f40; border:none; border-radius:7px;
  padding:.48rem 1.2rem; font-weight:800; font-size:.85rem; cursor:pointer;
  display:inline-flex; align-items:center; gap:.4rem; transition:background .2s;
}
.btn-print:hover { background:#d4a33a; }
.btn-back {
  background:rgba(255,255,255,.12); color:#fff; border:1.5px solid rgba(255,255,255,.25);
  border-radius:7px; padding:.45rem 1rem; font-size:.82rem; text-decoration:none;
  display:inline-flex; align-items:center; gap:.35rem; transition:all .2s;
}
.btn-back:hover { background:rgba(255,255,255,.2); color:#fff; }

/* ── Page wrapper ── */
.page {
  max-width:900px; margin:2rem auto; background:#fff;
  border-radius:14px; overflow:hidden;
  box-shadow:0 8px 32px rgba(7,31,64,.12);
}

/* ── College header ── */
.college-hd {
  background:#071f40; color:#fff;
  padding:1.5rem 2rem; display:flex; align-items:center; gap:1.25rem;
}
.college-hd img { height:64px; width:64px; object-fit:contain; }
.college-hd-text { flex:1; }
.college-hd-name {
  font-size:1.45rem; font-weight:900; text-transform:uppercase;
  letter-spacing:.03em; line-height:1.2;
}
.college-hd-sub {
  font-size:.78rem; opacity:.65; margin-top:.2rem; text-transform:uppercase;
  letter-spacing:.08em;
}
.college-hd-right { text-align:right; font-size:.75rem; opacity:.6; white-space:nowrap; }
.college-hd-right strong { display:block; font-size:.85rem; opacity:1; }

/* ── Date sheet title band ── */
.ds-band {
  background:#f4f7fb; border-bottom:1px solid #dfe7f1;
  padding:1.25rem 2rem;
}
.ds-band-title {
  font-size:1.3rem; font-weight:800; color:#071f40; margin-bottom:.8rem;
}
.ds-meta-strip {
  display:flex; flex-wrap:wrap; gap:1rem;
}
.ds-meta-chip {
  display:flex; flex-direction:column; gap:.1rem;
}
.ds-meta-chip-label {
  font-size:.64rem; font-weight:800; text-transform:uppercase;
  letter-spacing:.08em; color:#94a3b8;
}
.ds-meta-chip-val {
  font-size:.88rem; font-weight:700; color:#071f40;
}
.ds-badge {
  display:inline-block; font-size:.7rem; font-weight:800;
  padding:.22rem .65rem; border-radius:20px; color:#fff; white-space:nowrap;
}

/* ── Schedule table ── */
.sched-wrap { padding:1.5rem 2rem; }
.sched-section-title {
  font-size:.72rem; font-weight:800; text-transform:uppercase;
  letter-spacing:.08em; color:#94a3b8; margin-bottom:.75rem;
}

table.sched-tbl {
  width:100%; border-collapse:collapse;
}
.sched-tbl thead tr th {
  background:#071f40; color:#fff; padding:.6rem 1rem;
  text-align:left; font-size:.72rem; text-transform:uppercase;
  letter-spacing:.06em; font-weight:700;
}
.sched-tbl thead tr th:first-child { border-radius:8px 0 0 0; }
.sched-tbl thead tr th:last-child  { border-radius:0 8px 0 0; }
.sched-tbl tbody tr:nth-child(even) td { background:#f8fafc; }
.sched-tbl tbody tr td {
  padding:.7rem 1rem; border-bottom:1px solid #e8edf5; vertical-align:middle;
}
.sched-tbl tbody tr:last-child td { border-bottom:none; }
.td-sn   { color:#94a3b8; font-size:.72rem; width:38px; }
.td-subj { font-weight:700; color:#071f40; }
.td-date { color:#0d6efd; font-weight:600; font-size:.85rem; white-space:nowrap; }
.td-time { color:#198754; font-size:.82rem; white-space:nowrap; }
.td-venue{ color:#6c757d; font-size:.82rem; }

.no-subjects {
  text-align:center; padding:2.5rem 1rem; color:#94a3b8;
}
.no-subjects svg { margin-bottom:.75rem; }

/* ── Footer note ── */
.pdf-foot {
  background:#f8fafc; border-top:1px solid #e8edf5;
  padding:1rem 2rem; text-align:center;
  font-size:.72rem; color:#94a3b8; line-height:1.6;
}

/* ── Print ── */
@media print {
  body { background:#fff !important; }
  .toolbar { display:none !important; }
  .page {
    max-width:100%; margin:0; border-radius:0;
    box-shadow:none;
  }
  .sched-tbl thead tr th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .college-hd            { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .sched-tbl tbody tr { page-break-inside:avoid; }
}
@page { size:A4; margin:1.2cm 1.5cm; }
</style>
</head>
<body>

<!-- Toolbar -->
<div class="toolbar">
  <span class="toolbar-title">
    &#128196; Exam Date Sheet — Malik Solution
  </span>
  <div class="toolbar-right">
    <a href="datesheet.php" class="btn-back">&#8592; Back</a>
    <button class="btn-print" onclick="window.print()">
      &#128438; Print / Save as PDF
    </button>
  </div>
</div>

<!-- Page -->
<div class="page">

  <!-- College header -->
  <div class="college-hd">
    <?php if ($logo_src): ?>
      <img src="<?= htmlspecialchars($logo_src) ?>" alt="Malik Solution Logo">
    <?php endif; ?>
    <div class="college-hd-text">
      <div class="college-hd-name">Malik Solution</div>
      <div class="college-hd-sub">Excellence in Education &mdash; Examination Department</div>
    </div>
    <div class="college-hd-right">
      <strong><?= date('d M Y') ?></strong>
      Official Date Sheet
    </div>
  </div>

  <!-- Title + meta -->
  <div class="ds-band">
    <div class="ds-band-title"><?= htmlspecialchars($title) ?></div>
    <div class="ds-meta-strip">
      <?php if ($prog): ?>
        <div class="ds-meta-chip">
          <span class="ds-meta-chip-label">Program</span>
          <span class="ds-meta-chip-val"><?= htmlspecialchars($prog) ?></span>
        </div>
      <?php endif; ?>
      <?php if ($etype): ?>
        <div class="ds-meta-chip">
          <span class="ds-meta-chip-label">Exam Type</span>
          <span class="ds-meta-chip-val"><?= htmlspecialchars($etype) ?></span>
        </div>
      <?php endif; ?>
      <?php if ($sess): ?>
        <div class="ds-meta-chip">
          <span class="ds-meta-chip-label">Session</span>
          <span class="ds-meta-chip-val"><?= htmlspecialchars($sess) ?></span>
        </div>
      <?php endif; ?>
      <?php if (!empty($subjects)): ?>
        <div class="ds-meta-chip">
          <span class="ds-meta-chip-label">Total Subjects</span>
          <span class="ds-meta-chip-val"><?= count($subjects) ?></span>
        </div>
        <?php
          $dates = array_filter(array_column($subjects, 'exam_date'));
          sort($dates);
          if (count($dates) >= 2):
        ?>
        <div class="ds-meta-chip">
          <span class="ds-meta-chip-label">Exam Period</span>
          <span class="ds-meta-chip-val">
            <?= date('d M', strtotime(reset($dates))) ?> &ndash; <?= date('d M Y', strtotime(end($dates))) ?>
          </span>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Schedule -->
  <div class="sched-wrap">
    <div class="sched-section-title">&#128197; Subject-wise Exam Schedule</div>

    <?php if (!empty($subjects)): ?>
    <table class="sched-tbl">
      <thead>
        <tr>
          <th>#</th>
          <th>Subject / Paper</th>
          <th>Exam Date</th>
          <th>Time</th>
          <th>Venue / Room</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subjects as $i => $s):
          $t_start = fmt_t($s['start_time'] ?? '');
          $t_end   = fmt_t($s['end_time']   ?? '');
          $time    = $t_start ? ($t_end ? "$t_start &ndash; $t_end" : $t_start) : '&mdash;';
        ?>
        <tr>
          <td class="td-sn"><?= $i + 1 ?></td>
          <td class="td-subj"><?= htmlspecialchars($s['subject_name']) ?></td>
          <td class="td-date">
            <?= $s['exam_date'] ? date('D, d M Y', strtotime($s['exam_date'])) : '&mdash;' ?>
          </td>
          <td class="td-time"><?= $time ?></td>
          <td class="td-venue"><?= htmlspecialchars($s['venue'] ?? '') ?: '&mdash;' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="no-subjects">
        <p>Subject schedule has not been added yet.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Footer note -->
  <div class="pdf-foot">
    This date sheet is subject to change without prior notice. Students are advised to confirm
    with the college administration for any updates.<br>
    Generated on <?= date('d M Y \a\t h:i A') ?> &mdash; Malik Solution
  </div>

</div><!-- /page -->

</body>
</html>
