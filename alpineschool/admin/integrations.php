<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$fields = [
    'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_from_email',
    'sms_api_key', 'sms_sender_id',
    'whatsapp_api_key',
    'api_access_key',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        if (($_POST['action'] ?? '') === 'generate_api_key') {
            $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
                ->execute(['api_access_key', bin2hex(random_bytes(24))]);
            flash_set('success', 'A new API key has been generated.');
            redirect(BASE_URL . '/admin/integrations.php');
        }
        $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($fields as $field) {
            // Don't blank out saved secrets when the field is left empty.
            if (in_array($field, ['smtp_password', 'sms_api_key', 'whatsapp_api_key'], true) && trim((string)($_POST[$field] ?? '')) === '') {
                continue;
            }
            $update->execute([$field, trim((string)($_POST[$field] ?? ''))]);
        }
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'integrations_update', 'Updated integration settings');
        flash_set('success', 'Integration settings saved.');
        redirect(BASE_URL . '/admin/integrations.php');
    }
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Integrations';
require_once __DIR__ . '/includes/header.php';
?>

<form method="post" action="integrations.php">
  <?= csrf_field() ?>

  <div class="card">
    <div class="card-header"><h2>✉️ Email (SMTP) Settings</h2></div>
    <p class="form-hint" style="margin-bottom:16px;">Used for sending email notifications. Ask your hosting provider for these values.</p>
    <div class="form-row">
      <div class="form-group">
        <label for="smtp_host">SMTP Host</label>
        <input type="text" id="smtp_host" name="smtp_host" placeholder="smtp.example.com" value="<?= e($settings['smtp_host'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="smtp_port">SMTP Port</label>
        <input type="text" id="smtp_port" name="smtp_port" value="<?= e($settings['smtp_port'] ?? '587') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="smtp_username">SMTP Username</label>
        <input type="text" id="smtp_username" name="smtp_username" value="<?= e($settings['smtp_username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="smtp_password">SMTP Password</label>
        <input type="password" id="smtp_password" name="smtp_password" placeholder="<?= !empty($settings['smtp_password']) ? '•••••••• (saved — leave empty to keep)' : '' ?>" autocomplete="new-password">
      </div>
    </div>
    <div class="form-group" style="max-width:calc(50% - 8px);">
      <label for="smtp_from_email">From Email Address</label>
      <input type="email" id="smtp_from_email" name="smtp_from_email" placeholder="noreply@thealpineschoolhn.com" value="<?= e($settings['smtp_from_email'] ?? '') ?>">
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>📱 SMS Settings</h2></div>
    <div class="form-row">
      <div class="form-group">
        <label for="sms_api_key">SMS Gateway API Key</label>
        <input type="password" id="sms_api_key" name="sms_api_key" placeholder="<?= !empty($settings['sms_api_key']) ? '•••••••• (saved — leave empty to keep)' : '' ?>" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label for="sms_sender_id">Sender ID / Masking</label>
        <input type="text" id="sms_sender_id" name="sms_sender_id" placeholder="ALPINE" value="<?= e($settings['sms_sender_id'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>💬 WhatsApp Settings</h2></div>
    <div class="form-row">
      <div class="form-group">
        <label>WhatsApp Number</label>
        <input type="text" value="<?= e($settings['whatsapp'] ?? '') ?>" disabled>
        <p class="form-hint">The public chat number is set on the Site Settings page.</p>
      </div>
      <div class="form-group">
        <label for="whatsapp_api_key">WhatsApp Business API Key</label>
        <input type="password" id="whatsapp_api_key" name="whatsapp_api_key" placeholder="<?= !empty($settings['whatsapp_api_key']) ? '•••••••• (saved — leave empty to keep)' : '' ?>" autocomplete="new-password">
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h2>🔑 API Access</h2></div>
    <div class="form-group">
      <label>Current API Key</label>
      <input type="text" value="<?= e($settings['api_access_key'] ?? '') ?: '— not generated —' ?>" readonly onclick="this.select()">
      <p class="form-hint">For future external integrations. Keep it secret; regenerate if it leaks.</p>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Save Integration Settings</button>
</form>

<form method="post" action="integrations.php" style="margin-top:10px;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="generate_api_key">
  <button type="submit" class="btn btn-outline" data-confirm="Generate a new API key? The old key stops working immediately.">🔄 Regenerate API Key</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
