<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/admissions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/admission-settings.php');
    }

    $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

    $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_POST['admission_prefix'] ?? 'ALP'))) ?: 'ALP';
    $update->execute(['admission_prefix', mb_substr($prefix, 0, 6)]);
    $update->execute(['admission_session', trim((string)($_POST['admission_session'] ?? ''))]);
    $update->execute(['admission_notify_emails', trim((string)($_POST['admission_notify_emails'] ?? ''))]);
    $update->execute(['admission_instructions', trim((string)($_POST['admission_instructions'] ?? ''))]);
    $update->execute(['admission_open', isset($_POST['admission_open']) ? '1' : '0']);

    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'admission_settings', 'Updated admission settings');
    flash_set('success', 'Admission settings saved.');
    redirect(BASE_URL . '/admin/admission-settings.php');
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$s = fn(string $k, string $d = '') => $settings[$k] ?? $d;

$isOpen = $s('admission_open', '1') === '1';
$nextNumber = next_application_number($pdo);
$total = (int)$pdo->query('SELECT COUNT(*) c FROM applications')->fetch()['c'];
$thisYear = (int)$pdo->query('SELECT COUNT(*) c FROM applications WHERE YEAR(created_at) = YEAR(NOW())')->fetch()['c'];

$pageTitle = 'Admission Settings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="dash-stats">
  <div class="dash-stat">
    <strong><?= $isOpen ? 'Open' : 'Closed' ?></strong>
    <span>Admissions Status</span>
    <span class="stat-sub">Session <?= e($s('admission_session', '—')) ?></span>
  </div>
  <div class="dash-stat">
    <strong><?= $total ?></strong>
    <span>Total Applications</span>
    <span class="stat-sub"><?= $thisYear ?> this year</span>
  </div>
  <div class="dash-stat">
    <strong style="font-size:20px;"><?= e($nextNumber) ?></strong>
    <span>Next Application Number</span>
  </div>
  <div class="dash-stat">
    <strong><?= (int)$pdo->query("SELECT COUNT(*) c FROM applications WHERE status = 'submitted'")->fetch()['c'] ?></strong>
    <span>Awaiting Review</span>
    <span class="stat-sub"><a href="applications.php?status=submitted" style="color:var(--primary);">Review now →</a></span>
  </div>
</div>

<form method="post" action="admission-settings.php">
  <?= csrf_field() ?>

  <div class="grid-2" style="align-items:start;">
    <div class="card">
      <div class="card-header"><h2>🎓 Admission Window</h2></div>
      <div class="form-group">
        <label style="font-weight:400;"><input type="checkbox" name="admission_open" <?= $isOpen ? 'checked' : '' ?>> Admissions are open (accept online applications)</label>
        <p class="form-hint">When closed, the online form shows a friendly "admissions are closed" notice instead.</p>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="admission_session">Academic Session</label>
          <input type="text" id="admission_session" name="admission_session" placeholder="2026-27" value="<?= e($s('admission_session')) ?>">
        </div>
        <div class="form-group">
          <label for="admission_prefix">Application Number Prefix</label>
          <input type="text" id="admission_prefix" name="admission_prefix" maxlength="6" placeholder="ALP" value="<?= e($s('admission_prefix', 'ALP')) ?>">
          <p class="form-hint">Numbers look like <strong><?= e($nextNumber) ?></strong>.</p>
        </div>
      </div>
      <div class="form-group">
        <label for="admission_instructions">Instructions Shown on the Form</label>
        <textarea id="admission_instructions" name="admission_instructions" style="min-height:90px;"><?= e($s('admission_instructions')) ?></textarea>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2>✉️ Notifications</h2></div>
      <div class="form-group">
        <label for="admission_notify_emails">Notify These Emails on Each Application</label>
        <input type="text" id="admission_notify_emails" name="admission_notify_emails" placeholder="admissions@school.com, principal@school.com" value="<?= e($s('admission_notify_emails')) ?>">
        <p class="form-hint">Comma separated. Falls back to the site email (<?= e($s('email')) ?: 'not set' ?>). Applicants automatically get a confirmation email with their application number when they provide an address.</p>
      </div>
      <p class="form-hint">
        SMTP host: <strong><?= e($s('smtp_host')) ?: '— not configured —' ?></strong>.
        Configure it in <a href="integrations.php" style="color:var(--primary);">Integrations</a> and test it in <a href="contact-settings.php" style="color:var(--primary);">Contact Settings</a>.
      </p>

      <div class="card-header" style="margin-top:22px;"><h2>Public Links</h2></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>/admission-form.php" target="_blank" class="btn btn-outline btn-sm">Online Form ↗</a>
        <a href="<?= BASE_URL ?>/application-status.php" target="_blank" class="btn btn-outline btn-sm">Status Tracker ↗</a>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">💾 Save Admission Settings</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
