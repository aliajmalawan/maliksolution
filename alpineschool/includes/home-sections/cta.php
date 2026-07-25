<?php
$ctaDefault = 'background:linear-gradient(120deg, var(--primary), var(--primary-dark));text-align:center;';
$hasCustomBg = in_array($S['bg_type'] ?? 'default', ['color', 'image', 'video'], true);
hb_section_open($S, 'section home-cta', $hasCustomBg ? 'text-align:center;' : $ctaDefault);
$txt = !empty($S['text_color']) ? e($S['text_color']) : '#fff';
?>
  <div class="container">
    <h2 style="color:<?= $txt ?>;" data-text-reveal><?= e(trim((string)($S['heading'] ?? '')) ?: 'Ready to Join The Alpine School Family?') ?></h2>
    <p style="color:<?= $txt ?>;opacity:.85;max-width:560px;margin:0 auto 28px;"><?= e(trim((string)($S['subheading'] ?? '')) ?: 'Admissions are now open for the new academic session at our Haroonabad Campus.') ?></p>
    <div class="cta-actions">
      <a href="admissions.php" class="btn btn-primary">Apply for Admission</a>
      <a href="contact.php" class="btn btn-outline">Contact Us</a>
    </div>
  </div>
<?php hb_section_close(); ?>
