<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/form-render.php';

$contactForm = load_form($pdo, 'contact');
$result = $contactForm ? handle_form_submit($pdo, $contactForm) : ['status' => 'none', 'message' => '', 'data' => []];

$address = get_setting($pdo, 'address');
$phoneSetting = get_setting($pdo, 'phone');
$emailSetting = get_setting($pdo, 'email');
$whatsapp = get_setting($pdo, 'whatsapp');
$map_embed = get_setting($pdo, 'map_embed');
$mapLat = get_setting($pdo, 'contact_map_lat');
$mapLng = get_setting($pdo, 'contact_map_lng');

// Google Maps: a pasted <iframe> wins; otherwise build the map from the best location we have —
// a plain-text location in the map field, then coordinates, then the postal address.
$mapText = stripos($map_embed, '<iframe') === false ? trim($map_embed) : '';
if ($mapText !== '') {
    $mapQuery = $mapText;
} elseif ($mapLat !== '' && $mapLng !== '') {
    $mapQuery = $mapLat . ',' . $mapLng;
} else {
    $mapQuery = $address;
}
$directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($mapQuery);
$autoMapSrc = 'https://maps.google.com/maps?q=' . urlencode($mapQuery) . '&z=16&output=embed';

$pageTitle = 'Contact Us';
$breadcrumbs = [['label' => 'Contact']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Contact Us</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container contact-grid">
    <div class="contact-info-card">
      <h3>Get In Touch</h3>
      <p style="opacity:.85;font-size:14.5px;margin-bottom:24px;">Questions about admissions, fees, or a campus visit? Reach us any way you like — we respond quickly.</p>

      <?php if ($address): ?>
      <div class="contact-info-item">
        <div class="icon" aria-hidden="true"><?= edu_icon('map-pin') ?></div>
        <div><strong>Address</strong><p style="margin:4px 0 0;color:#d9d9ef;"><?= e($address) ?></p></div>
      </div>
      <?php endif; ?>
      <?php if ($phoneSetting): ?>
      <div class="contact-info-item">
        <div class="icon" aria-hidden="true"><?= edu_icon('phone') ?></div>
        <div><strong>Phone</strong><p style="margin:4px 0 0;"><a href="tel:<?= e($phoneSetting) ?>" style="color:#d9d9ef;"><?= e($phoneSetting) ?></a></p></div>
      </div>
      <?php endif; ?>
      <?php if ($emailSetting): ?>
      <div class="contact-info-item">
        <div class="icon" aria-hidden="true"><?= edu_icon('mail') ?></div>
        <div><strong>Email</strong><p style="margin:4px 0 0;"><a href="mailto:<?= e($emailSetting) ?>" style="color:#d9d9ef;"><?= e($emailSetting) ?></a></p></div>
      </div>
      <?php endif; ?>

      <div class="contact-actions">
        <?php if ($whatsapp): ?>
          <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-primary"><?= edu_icon('whatsapp') ?> WhatsApp</a>
        <?php endif; ?>
        <?php if ($phoneSetting): ?>
          <a href="tel:<?= e($phoneSetting) ?>" class="btn btn-accent"><?= edu_icon('phone') ?> Call Now</a>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <h2 style="font-size:24px;margin-bottom:6px;">Send Us a Message</h2>
      <p style="color:var(--text-light);margin-bottom:20px;">Fill in the form and our team will get back to you within one working day.</p>
      <?= form_alert_html($result) ?>

      <?php if ($contactForm): ?>
      <form class="form-card" method="post" action="contact.php" aria-label="Contact form"
            data-ajax data-ajax-action="<?= BASE_URL ?>/form-submit.php" data-loading-text="Sending…">
        <?php render_form_fields($contactForm, $result['data'], $result['errors'] ?? []); ?>
        <button type="submit" class="btn btn-primary">Send Message</button>
      </form>
      <?php else: ?>
        <div role="alert" class="alert alert-error">The contact form is currently unavailable. Please call or WhatsApp us instead.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Find Us</span>
      <h2>Visit Our Campus</h2>
      <?php if ($address): ?><p><?= e($address) ?></p><?php endif; ?>
    </div>
    <div class="map-embed map-embed-lg" data-anim="up">
      <?php if (stripos($map_embed, '<iframe') !== false): ?>
        <?= $map_embed ?>
      <?php else: ?>
        <iframe src="<?= e($autoMapSrc) ?>" width="100%" height="440" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map to <?= e(get_setting($pdo, 'site_name')) ?>"></iframe>
      <?php endif; ?>
    </div>
    <p style="text-align:center;margin-top:22px;">
      <a href="<?= e($directionsUrl) ?>" target="_blank" rel="noopener" class="btn btn-dark">Get Directions to Campus</a>
    </p>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
