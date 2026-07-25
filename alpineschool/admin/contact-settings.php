<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/contact-settings.php');
    }

    $update = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

    if (($_POST['action'] ?? '') === 'test_email') {
        $to = trim((string)($_POST['test_to'] ?? '')) ?: get_setting($pdo, 'email');
        $result = send_mail(
            $pdo,
            $to,
            'Test email from ' . get_setting($pdo, 'site_name'),
            submission_email_html($pdo, 'Test Notification', ['Status' => 'If you are reading this, email notifications are working.', 'Sent at' => date('d M Y H:i')])
        );
        flash_set($result['ok'] ? 'success' : 'error', ($result['ok'] ? '✅ Test email sent. ' : '❌ Test email failed. ') . $result['message']);
        redirect(BASE_URL . '/admin/contact-settings.php');
    }

    foreach (['map_embed', 'contact_map_lat', 'contact_map_lng', 'contact_notify_emails', 'spam_keywords'] as $key) {
        $update->execute([$key, trim((string)($_POST[$key] ?? ''))]);
    }
    $update->execute(['spam_min_seconds', (string)max(0, min(60, (int)($_POST['spam_min_seconds'] ?? 3)))]);
    $update->execute(['spam_max_per_hour', (string)max(1, min(100, (int)($_POST['spam_max_per_hour'] ?? 5)))]);
    $update->execute(['contact_float_whatsapp', isset($_POST['contact_float_whatsapp']) ? '1' : '0']);
    $update->execute(['contact_float_call', isset($_POST['contact_float_call']) ? '1' : '0']);

    log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'contact_settings', 'Updated contact settings');
    flash_set('success', 'Contact settings saved.');
    redirect(BASE_URL . '/admin/contact-settings.php');
}

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$s = fn(string $k, string $d = '') => $settings[$k] ?? $d;

$spamBlocked = (int)$pdo->query('SELECT COUNT(*) c FROM form_submissions WHERE created_at >= NOW() - INTERVAL 30 DAY')->fetch()['c'];

$pageTitle = 'Contact Settings';
require_once __DIR__ . '/includes/header.php';
?>

<form method="post" action="contact-settings.php">
  <?= csrf_field() ?>

  <div class="grid-2" style="align-items:start;">
    <div>
      <div class="card">
        <div class="card-header"><h2>🗺️ Google Maps</h2></div>
        <div class="form-row">
          <div class="form-group">
            <label for="contact_map_lat">Latitude</label>
            <input type="text" id="contact_map_lat" name="contact_map_lat" placeholder="29.6103" value="<?= e($s('contact_map_lat')) ?>">
          </div>
          <div class="form-group">
            <label for="contact_map_lng">Longitude</label>
            <input type="text" id="contact_map_lng" name="contact_map_lng" placeholder="73.1385" value="<?= e($s('contact_map_lng')) ?>">
          </div>
        </div>
        <p class="form-hint" style="margin-bottom:14px;">Right-click your campus in Google Maps → the first item is "latitude, longitude". The map and the "Get Directions" button are built from this automatically — no API key needed.</p>
        <div class="form-group">
          <label for="map_embed">Map Location / Embed Code (optional)</label>
          <textarea id="map_embed" name="map_embed" style="min-height:90px;" placeholder="Paste a Google Maps &lt;iframe&gt;, or just type the campus address."><?= e($s('map_embed')) ?></textarea>
          <p class="form-hint">Paste a full Google Maps <code>&lt;iframe&gt;</code> to use it exactly, or type a plain address here and the map will be centred on it (this takes priority over the coordinates above).</p>
        </div>
        <?php if ($s('contact_map_lat') && $s('contact_map_lng')): ?>
          <a href="https://www.google.com/maps/dir/?api=1&destination=<?= e($s('contact_map_lat') . ',' . $s('contact_map_lng')) ?>" target="_blank" class="btn btn-outline btn-sm">Preview Directions ↗</a>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header"><h2>📱 Floating Buttons</h2></div>
        <div class="form-group">
          <label style="font-weight:400;"><input type="checkbox" name="contact_float_whatsapp" <?= $s('contact_float_whatsapp', '1') === '1' ? 'checked' : '' ?>> Show floating WhatsApp button on every page</label>
          <label style="font-weight:400;"><input type="checkbox" name="contact_float_call" <?= $s('contact_float_call', '1') === '1' ? 'checked' : '' ?>> Show floating Call button on every page</label>
        </div>
        <p class="form-hint">They use the WhatsApp number and phone from <a href="settings.php" style="color:var(--primary);">Site Settings</a>. Current: WhatsApp <strong><?= e($s('whatsapp')) ?: 'not set' ?></strong>, Phone <strong><?= e($s('phone')) ?: 'not set' ?></strong>.</p>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-header"><h2>✉️ Email Notifications</h2></div>
        <div class="form-group">
          <label for="contact_notify_emails">Default Notification Recipients</label>
          <input type="text" id="contact_notify_emails" name="contact_notify_emails" placeholder="office@school.com, principal@school.com" value="<?= e($s('contact_notify_emails')) ?>">
          <p class="form-hint">Comma separated. Used for any form that doesn't set its own recipients in the <a href="forms.php" style="color:var(--primary);">Form Builder</a>. Falls back to the site email (<?= e($s('email')) ?: 'not set' ?>).</p>
        </div>
        <p class="form-hint">
          SMTP host: <strong><?= e($s('smtp_host')) ?: '— not configured —' ?></strong>
          <?php if (!$s('smtp_host')): ?><br>Without SMTP the site falls back to PHP's <code>mail()</code>, which usually fails on local XAMPP. Add credentials in <a href="integrations.php" style="color:var(--primary);">Integrations</a>.<?php endif; ?>
        </p>
      </div>

      <div class="card">
        <div class="card-header"><h2>🛡️ Spam Protection</h2></div>
        <p class="form-hint" style="margin-bottom:14px;">Every public form is protected by a hidden honeypot field and a signed timestamp — no CAPTCHA needed. These settings tune the rest.</p>
        <div class="form-row">
          <div class="form-group">
            <label for="spam_min_seconds">Minimum Fill Time (seconds)</label>
            <input type="number" id="spam_min_seconds" name="spam_min_seconds" min="0" max="60" value="<?= (int)$s('spam_min_seconds', '3') ?>">
            <p class="form-hint">Submissions faster than this are treated as bots.</p>
          </div>
          <div class="form-group">
            <label for="spam_max_per_hour">Max Submissions per IP / hour</label>
            <input type="number" id="spam_max_per_hour" name="spam_max_per_hour" min="1" max="100" value="<?= (int)$s('spam_max_per_hour', '5') ?>">
          </div>
        </div>
        <div class="form-group">
          <label for="spam_keywords">Blocked Keywords (comma separated)</label>
          <input type="text" id="spam_keywords" name="spam_keywords" value="<?= e($s('spam_keywords')) ?>">
          <p class="form-hint">Submissions containing these words are silently discarded. Messages with more than 3 links are also blocked.</p>
        </div>
        <p class="form-hint"><strong><?= $spamBlocked ?></strong> legitimate submissions stored in the last 30 days.</p>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">💾 Save Contact Settings</button>
</form>

<div class="card" style="margin-top:22px;">
  <div class="card-header"><h2>🧪 Send a Test Email</h2></div>
  <form method="post" action="contact-settings.php" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="test_email">
    <div class="form-group" style="margin-bottom:0;flex:1;min-width:240px;max-width:380px;">
      <label for="test_to">Send test notification to</label>
      <input type="email" id="test_to" name="test_to" placeholder="<?= e($s('email')) ?: 'you@example.com' ?>" value="<?= e($s('contact_notify_emails') ?: $s('email')) ?>">
    </div>
    <button type="submit" class="btn btn-outline">Send Test Email</button>
  </form>
  <p class="form-hint" style="margin-top:10px;">Confirms your SMTP settings work before you rely on form notifications.</p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
