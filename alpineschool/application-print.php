<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/admissions.php';

// Printable application. Reachable by application number (the applicant's own copy),
// or by id when an admin is logged in (from the review screen).
$application = null;

if (!empty($_GET['app'])) {
    $stmt = $pdo->prepare('SELECT * FROM applications WHERE app_number = ?');
    $stmt->execute([strtoupper(trim((string)$_GET['app']))]);
    $application = $stmt->fetch() ?: null;
} elseif (!empty($_GET['id']) && !empty($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    $application = $stmt->fetch() ?: null;
}

if (!$application) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:40px;">Application not found. <a href="application-status.php">Try again</a>.</p>';
    exit;
}

$docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = ? ORDER BY id');
$docStmt->execute([(int)$application['id']]);
$documents = $docStmt->fetchAll();
$docTypes = admission_doc_types();

$siteName = get_setting($pdo, 'site_name');
$campusName = get_setting($pdo, 'campus_name');
$logo = get_setting($pdo, 'logo_path');
$address = get_setting($pdo, 'address');
$phone = get_setting($pdo, 'phone');
$email = get_setting($pdo, 'email');
$session = get_setting($pdo, 'admission_session', '2026-27');

$photo = null;
foreach ($documents as $doc) {
    if ($doc['doc_type'] === 'student_photo' && preg_match('/\.(jpg|jpeg|png|webp)$/i', $doc['file_path'])) {
        $photo = $doc['file_path'];
    }
}

/** One label/value row in the print sheet. */
function pr(string $label, ?string $value): void
{
    echo '<tr><th>' . e($label) . '</th><td>' . ($value !== null && $value !== '' ? e($value) : '<span class="muted">—</span>') . '</td></tr>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application <?= e($application['app_number']) ?> — <?= e($siteName) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?= BASE_URL ?>/<?= e($logo) ?>">
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2430; margin: 0; background: #f1f2f8; }
  .sheet { max-width: 820px; margin: 24px auto; background: #fff; padding: 36px 40px; box-shadow: 0 6px 24px rgba(0,0,0,.1); }
  .head { display: flex; align-items: center; gap: 16px; border-bottom: 3px solid #2E1B6B; padding-bottom: 16px; }
  .head img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; }
  .head h1 { margin: 0; font-size: 22px; color: #161233; }
  .head small { color: #6b7280; }
  .head .right { margin-left: auto; text-align: right; }
  .appno { font-size: 18px; font-weight: 700; color: #2E1B6B; letter-spacing: .5px; }
  .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; background: #eef0f6; font-size: 12px; font-weight: 600; }
  h2 { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin: 26px 0 8px; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7ef; vertical-align: top; }
  th { width: 34%; color: #52514e; font-weight: 600; background: #f8f9fc; }
  .muted { color: #9ca3af; }
  .top { display: flex; gap: 22px; align-items: flex-start; }
  .top .fields { flex: 1; }
  .photo { width: 110px; height: 130px; object-fit: cover; border: 1px solid #ddd; }
  .photo-box { width: 110px; height: 130px; border: 1px dashed #bbb; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 11px; text-align: center; padding: 6px; }
  .sign-row { display: flex; gap: 40px; margin-top: 46px; }
  .sign { flex: 1; border-top: 1px solid #333; padding-top: 6px; font-size: 12px; color: #52514e; }
  .foot { margin-top: 28px; border-top: 1px solid #e5e7ef; padding-top: 12px; font-size: 11px; color: #9ca3af; display: flex; justify-content: space-between; }
  .actions { max-width: 820px; margin: 16px auto 0; display: flex; gap: 10px; }
  .btn { padding: 10px 20px; border-radius: 8px; border: none; background: #2E1B6B; color: #fff; font-weight: 600; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
  .btn.alt { background: #fff; color: #2E1B6B; border: 1.5px solid #ddd; }
  @media print {
    body { background: #fff; }
    .actions { display: none; }
    .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0; }
    @page { margin: 16mm; }
  }
</style>
</head>
<body>

<div class="actions">
  <button class="btn" onclick="window.print()">🖨 Print / Save as PDF</button>
  <a class="btn alt" href="application-status.php?app=<?= e($application['app_number']) ?>">← Back to Status</a>
</div>

<div class="sheet">
  <div class="head">
    <img src="<?= BASE_URL ?>/<?= e($logo) ?>" alt="">
    <div>
      <h1><?= e($siteName) ?></h1>
      <small><?= e($campusName) ?> · Admission Application</small>
    </div>
    <div class="right">
      <div class="appno"><?= e($application['app_number']) ?></div>
      <small>Session <?= e($session) ?></small><br>
      <span class="badge"><?= e(admission_status_label($application['status'])) ?></span>
    </div>
  </div>

  <div class="top">
    <div class="fields">
      <h2>Student Details</h2>
      <table>
        <?php
        pr("Student's Name", $application['student_name']);
        pr('Date of Birth', $application['date_of_birth'] ? format_date($application['date_of_birth']) : '');
        pr('Gender', $application['gender'] ? ucfirst($application['gender']) : '');
        pr('Class Applied For', $application['class_applying']);
        pr('Previous School', $application['previous_school']);
        ?>
      </table>
    </div>
    <?php if ($photo): ?>
      <img class="photo" src="<?= BASE_URL ?>/<?= e($photo) ?>" alt="Student photograph">
    <?php else: ?>
      <div class="photo-box">Affix recent photograph</div>
    <?php endif; ?>
  </div>

  <h2>Parent / Guardian Details</h2>
  <table>
    <?php
    pr("Father's / Guardian's Name", $application['father_name']);
    pr('Occupation', $application['father_occupation']);
    pr('CNIC', $application['father_cnic']);
    pr("Mother's Name", $application['mother_name']);
    pr('Contact Phone', $application['guardian_phone']);
    pr('Email', $application['guardian_email']);
    pr('Home Address', $application['address']);
    ?>
  </table>

  <?php if (!empty($application['notes'])): ?>
    <h2>Additional Notes</h2>
    <table><tr><td><?= nl2br(e($application['notes'])) ?></td></tr></table>
  <?php endif; ?>

  <h2>Documents Submitted</h2>
  <table>
    <?php foreach ($docTypes as $key => $label): ?>
      <?php
      $found = null;
      foreach ($documents as $doc) {
          if ($doc['doc_type'] === $key) {
              $found = $doc;
          }
      }
      ?>
      <tr>
        <th><?= e($label) ?></th>
        <td><?= $found ? '☑ Received — ' . e((string)$found['original_name']) : '☐ Not submitted' ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if (!empty($application['admin_remarks'])): ?>
    <h2>Office Remarks</h2>
    <table><tr><td><?= nl2br(e($application['admin_remarks'])) ?></td></tr></table>
  <?php endif; ?>

  <div class="sign-row">
    <div class="sign">Parent / Guardian Signature</div>
    <div class="sign">Admissions Officer</div>
    <div class="sign">Date</div>
  </div>

  <div class="foot">
    <span><?= e($address) ?> · <?= e($phone) ?> · <?= e($email) ?></span>
    <span>Submitted <?= format_date($application['created_at']) ?> · Printed <?= date('d M Y') ?></span>
  </div>
</div>

</body>
</html>
