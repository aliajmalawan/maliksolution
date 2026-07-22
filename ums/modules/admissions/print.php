<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/**
 * Printable admission application form — a clean, A4-style document
 * (no ERP shell). Opened from the application view via the Print button.
 */

$id  = (int)($_GET['id'] ?? 0);
$adm = adm_find($id);
if (!$adm) { flash_set('error', 'Application not found.'); redirect(adm_url('index.php')); }

/** Read a UMS setting with fallback to the website CMS settings, then a default. */
function inst(string $key, string $siteKey, string $default): string
{
    static $ums = null, $site = null;
    $db = ums_db();
    if ($ums === null) {
        $ums = [];
        try { $r = $db->query('SELECT name, value FROM ' . tbl('settings')); while ($x = $r->fetch_assoc()) $ums[$x['name']] = $x['value']; } catch (Throwable $t) {}
    }
    if ($site === null) {
        $site = [];
        try { $r = $db->query("SELECT name, value FROM settings"); while ($x = $r->fetch_assoc()) $site[$x['name']] = $x['value']; } catch (Throwable $t) {}
    }
    if (!empty($ums[$key]))     return $ums[$key];
    if (!empty($site[$siteKey])) return $site[$siteKey];
    return $default;
}

$instName    = inst('institute_name', 'site_name', 'Malik University');
$instCampus  = inst('campus_name', '', 'Main Campus');
$instAddr    = inst('institute_address', 'contact_address', 'Kehkashan Society, Malir Halt, Karachi');
$instPhone   = inst('institute_phone', 'contact_phone', '0346-4890875');
$instEmail   = inst('institute_email', 'contact_email', 'info@maliksolution.com');
$logoRel     = inst('institute_logo', 'logo_path', '');
$logoUrl     = $logoRel !== '' ? dirname(UMS_URL) . '/' . $logoRel : '';

$statusLabel = ADM_STATUS[$adm['status']][0] ?? ucfirst($adm['status']);
$marks = ((int)$adm['total_marks'] > 0) ? (int)$adm['obtained_marks'] . ' / ' . (int)$adm['total_marks'] . '  (' . number_format((float)$adm['merit_score'], 2) . '%)' : '—';

// Approval date is stamped automatically when the application is approved.
$approvedDate = !empty($adm['approved_at']) ? date('d M Y', strtotime($adm['approved_at'])) : '';

// Status badge colours
$statusColors = [
    'pending'  => ['#fef3c7', '#92400e'],
    'approved' => ['#dcfce7', '#166534'],
    'rejected' => ['#fee2e2', '#991b1b'],
    'enrolled' => ['#e0e7ff', '#3730a3'],
];
[$stBg, $stFg] = $statusColors[$adm['status']] ?? ['#dcfce7', '#166534'];

/** One label/value row. */
function frow(string $label, ?string $value): void {
    echo '<tr><td class="lbl">' . e($label) . '</td><td class="val">' . e(($value !== null && $value !== '') ? $value : '—') . '</td></tr>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Application <?= e($adm['application_no']) ?> — <?= e($instName) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #eef2f7; font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; }
    .bar { text-align: center; padding: 1rem; }
    .bar .btn { border: none; border-radius: 8px; font-weight: 700; font-size: .85rem; padding: .55rem 1.2rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: .45rem; }
    .bar .print { background: #4f46e5; color: #fff; }
    .bar .back { background: #fff; color: #334155; border: 1px solid #cbd5e1; }

    /* A4 sheet */
    .sheet {
      width: 210mm; min-height: 297mm; margin: 0 auto 2rem; background: #fff; padding: 16mm 15mm;
      box-shadow: 0 10px 40px rgba(15,23,42,.15); position: relative;
    }
    .doc-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2.5px solid #0d6efd; padding-bottom: 14px; }
    .doc-brand { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
    .doc-brand .doc-logo { height: 54px; width: auto; max-width: 220px; object-fit: contain; }
    .doc-brand .logo-fallback { width: 54px; height: 54px; border-radius: 12px; background: linear-gradient(135deg,#0d6efd,#22d3ee); color: #fff; display: grid; place-items: center; font-size: 1.5rem; }
    .doc-brand .brand-sub { font-size: .82rem; color: #334155; font-weight: 600; letter-spacing: .01em; }
    .doc-meta { text-align: right; }
    .doc-meta .appno { font-size: 1.15rem; font-weight: 800; color: #0d6efd; letter-spacing: .02em; }
    .doc-meta .sess { font-size: .72rem; color: #64748b; margin-top: 1px; }
    .doc-meta .status { display: inline-block; margin-top: 6px; font-size: .64rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: .18rem .6rem; border-radius: 20px; }
    .doc-meta .approved { margin-top: 5px; font-size: .68rem; color: #166534; font-weight: 700; }

    .form-title { text-align: center; font-size: .9rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: #0f172a; margin: 16px 0 2px; }
    .form-title-line { width: 46px; height: 3px; background: linear-gradient(90deg,#0d6efd,#22d3ee); margin: 6px auto 0; border-radius: 3px; }

    .sec-title { font-size: .74rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #0d6efd; margin: 18px 0 6px; padding-left: 9px; border-left: 3px solid #0d6efd; }
    table.form { width: 100%; border-collapse: collapse; }
    table.form td { border: 1px solid #e2e8f0; padding: 7px 10px; font-size: .82rem; vertical-align: top; }
    table.form td.lbl { width: 32%; color: #475569; font-weight: 600; background: #f8fafc; }
    table.form td.val { color: #0f172a; }

    .with-photo { display: flex; gap: 14px; }
    .with-photo table.form { flex: 1; }
    .photo-box { width: 32mm; height: 40mm; border: 1px dashed #94a3b8; border-radius: 6px; display: grid; place-items: center; text-align: center; color: #94a3b8; font-size: .62rem; padding: 6px; flex-shrink: 0; overflow: hidden; }
    .photo-box img { width: 100%; height: 100%; object-fit: cover; }

    .docs { width: 100%; border-collapse: collapse; }
    .docs td { padding: 6px 10px; font-size: .8rem; border: 1px solid #e2e8f0; }
    .docs .box { font-family: monospace; color: #64748b; margin-right: 6px; }

    .declaration { margin-top: 16px; font-size: .74rem; color: #475569; line-height: 1.5; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; background: #f8fafc; }
    .signs { display: flex; justify-content: space-between; gap: 24px; margin-top: 40px; }
    .signs .s { flex: 1; text-align: center; }
    .signs .fill { display: block; height: 20px; font-size: .82rem; font-weight: 600; color: #0f172a; }
    .signs .line { border-top: 1px solid #334155; padding-top: 5px; font-size: .72rem; color: #475569; }
    .doc-foot { border-top: 1px solid #e2e8f0; margin-top: 26px; padding-top: 8px; display: flex; justify-content: space-between; font-size: .66rem; color: #94a3b8; }

    @media print {
      body { background: #fff; }
      .bar { display: none; }
      .sheet { width: auto; min-height: auto; margin: 0; padding: 6mm 4mm; box-shadow: none; }
      @page { size: A4; margin: 10mm; }
    }
  </style>
</head>
<body>

<div class="bar">
  <button class="btn print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save as PDF</button>
  <a class="btn back" href="<?= adm_url('view.php?id=' . $id) ?>"><i class="fa-solid fa-arrow-left"></i> Back to Application</a>
</div>

<div class="sheet">
  <!-- Header -->
  <div class="doc-head">
    <div class="doc-brand">
      <?php if ($logoUrl !== ''): ?>
        <img class="doc-logo" src="<?= e($logoUrl) ?>" alt="Logo">
      <?php else: ?>
        <span class="logo-fallback"><i class="fa-solid fa-graduation-cap"></i></span>
      <?php endif; ?>
      <div class="brand-sub"><?= e($instCampus) ?> &middot; Admission Application</div>
    </div>
    <div class="doc-meta">
      <div class="appno"><?= e($adm['application_no']) ?></div>
      <div class="sess">Session <?= e($adm['session'] ?: '—') ?></div>
      <span class="status" style="background:<?= $stBg ?>;color:<?= $stFg ?>"><?= e($statusLabel) ?></span>
      <?php if ($approvedDate !== ''): ?>
        <div class="approved">Approved: <?= e($approvedDate) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="form-title">Admission Application Form</div>
  <div class="form-title-line"></div>

  <!-- Applicant details + photo -->
  <div class="sec-title">Applicant Details</div>
  <div class="with-photo">
    <table class="form">
      <?php
      frow("Student's Name", $adm['student_name']);
      frow('Father / Guardian', $adm['father_name']);
      frow('Gender', ucfirst($adm['gender']));
      frow('Date of Birth', $adm['dob'] ? date('d M Y', strtotime($adm['dob'])) : '');
      frow('CNIC / B-Form', $adm['cnic']);
      ?>
    </table>
    <div class="photo-box">
      <?php if ($adm['photo'] !== ''): ?>
        <img src="<?= UMS_URL . '/' . e($adm['photo']) ?>" alt="Photo">
      <?php else: ?>
        Affix recent<br>photograph
      <?php endif; ?>
    </div>
  </div>

  <!-- Contact -->
  <div class="sec-title">Contact Details</div>
  <table class="form">
    <?php
    frow('Contact Phone', $adm['phone']);
    frow('Email', $adm['email']);
    frow('Home Address', $adm['address']);
    ?>
  </table>

  <!-- Program & academics -->
  <div class="sec-title">Program &amp; Academic Details</div>
  <table class="form">
    <?php
    frow('Program Applied For', $adm['program']);
    frow('Academic Session', $adm['session']);
    frow('Last Qualification', $adm['last_qualification']);
    frow('Board / University', $adm['board_university']);
    frow('Marks Obtained', $marks);
    ?>
  </table>

  <!-- Declaration -->
  <div class="declaration">
    <strong>Declaration:</strong> I hereby declare that the information provided above is true and correct to the
    best of my knowledge. I understand that any false statement may result in the cancellation of my admission.
  </div>

  <!-- Signatures -->
  <div class="signs">
    <div class="s"><span class="fill">&nbsp;</span><div class="line">Applicant / Guardian Signature</div></div>
    <div class="s"><span class="fill">&nbsp;</span><div class="line">Admissions Officer</div></div>
    <div class="s"><span class="fill"><?= e($approvedDate) ?></span><div class="line">Date</div></div>
  </div>

  <!-- Footer -->
  <div class="doc-foot">
    <span><?= e($instAddr) ?> · <?= e($instPhone) ?> · <?= e($instEmail) ?></span>
    <span>Applied <?= e(date('d M Y', strtotime($adm['applied_at']))) ?> · Printed <?= e(date('d M Y')) ?></span>
  </div>
</div>

<script>window.addEventListener('load', function(){ if (location.hash === '#auto') window.print(); });</script>
</body>
</html>
