<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/admissions.php';

$appNumber = strtoupper(trim((string)($_GET['app'] ?? $_POST['app'] ?? '')));
$application = null;
$documents = [];
$notFound = false;

if ($appNumber !== '') {
    $stmt = $pdo->prepare('SELECT * FROM applications WHERE app_number = ?');
    $stmt->execute([$appNumber]);
    $application = $stmt->fetch() ?: null;

    if ($application) {
        $docStmt = $pdo->prepare('SELECT * FROM application_documents WHERE application_id = ? ORDER BY id');
        $docStmt->execute([(int)$application['id']]);
        $documents = $docStmt->fetchAll();
    } else {
        $notFound = true;
    }
}

$timeline = ['submitted', 'under_review', 'shortlisted', 'approved', 'enrolled'];
$docTypes = admission_doc_types();

$pageTitle = 'Application Status';
$breadcrumbs = [['label' => 'Admissions', 'url' => 'admissions.php'], ['label' => 'Application Status']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Track Your Application</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:760px;">

    <form method="get" action="application-status.php" class="search-form" style="display:flex;gap:12px;margin-bottom:28px;">
      <input type="text" name="app" value="<?= e($appNumber) ?>" placeholder="Enter your application number (e.g. ALP-2026-0001)" required
             style="flex:1;padding:14px 18px;border:1.5px solid #ddd;border-radius:10px;font-size:16px;font-family:inherit;">
      <button type="submit" class="btn btn-dark">Check Status</button>
    </form>

    <?php if ($notFound): ?>
      <div role="alert" class="alert alert-error">No application found with the number "<strong><?= e($appNumber) ?></strong>". Please check the number and try again, or <a href="contact.php">contact the office</a>.</div>
    <?php endif; ?>

    <?php if ($application): ?>
      <?php $isRejected = $application['status'] === 'rejected'; ?>
      <div class="form-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
          <div>
            <small style="color:var(--text-light);">Application Number</small>
            <h2 style="margin:2px 0 6px;"><?= e($application['app_number']) ?></h2>
            <p style="margin:0;color:var(--text-light);">
              <strong><?= e($application['student_name']) ?></strong> · <?= e($application['class_applying']) ?> ·
              submitted <?= format_date($application['created_at']) ?>
            </p>
          </div>
          <span class="badge <?= admission_status_badge($application['status']) ?>" style="font-size:14px;padding:8px 18px;">
            <?= e(admission_status_label($application['status'])) ?>
          </span>
        </div>

        <?php if (!$isRejected): ?>
        <div class="app-timeline">
          <?php
          $currentIndex = array_search($application['status'], $timeline, true);
          $currentIndex = $currentIndex === false ? 0 : (int)$currentIndex;
          foreach ($timeline as $i => $step):
              $done = $i <= $currentIndex;
          ?>
            <div class="app-step<?= $done ? ' done' : '' ?>">
              <span class="dot"><?= $done ? '✓' : $i + 1 ?></span>
              <small><?= e(admission_status_label($step)) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
          <div role="alert" class="alert alert-error" style="margin-top:20px;">
            We're sorry — this application was not successful for the current session. Please contact the admissions office if you'd like to discuss it.
          </div>
        <?php endif; ?>

        <?php if (!empty($application['admin_remarks'])): ?>
          <div role="status" class="alert alert-success" style="margin-top:18px;">
            <strong>Message from the admissions office:</strong><br><?= nl2br(e($application['admin_remarks'])) ?>
          </div>
        <?php endif; ?>

        <?php if ($documents): ?>
          <h3 style="margin-top:26px;font-size:17px;">Documents on File (<?= count($documents) ?>)</h3>
          <ul style="color:var(--text-light);">
            <?php foreach ($documents as $doc): ?>
              <li><?= e($docTypes[$doc['doc_type']] ?? $doc['doc_type']) ?> — <?= e($doc['original_name']) ?> (<?= human_filesize((int)$doc['file_size']) ?>)</li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="form-hint" style="margin-top:22px;">No documents were uploaded with this application. Please bring the originals when you visit the campus.</p>
        <?php endif; ?>

        <div style="display:flex;gap:10px;margin-top:24px;flex-wrap:wrap;">
          <a href="application-print.php?app=<?= e($application['app_number']) ?>" target="_blank" class="btn btn-dark">🖨 Print Application</a>
          <a href="contact.php" class="btn btn-outline">Contact the Office</a>
        </div>
      </div>
    <?php elseif ($appNumber === ''): ?>
      <p style="color:var(--text-light);text-align:center;">Enter the application number you received when you applied. Haven't applied yet?
        <a href="admission-form.php" style="color:var(--primary);font-weight:600;">Apply online →</a></p>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
