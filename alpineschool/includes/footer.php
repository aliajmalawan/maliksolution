<?php
declare(strict_types=1);
$address     = get_setting($pdo, 'address');
$phone       = get_setting($pdo, 'phone');
$whatsapp    = get_setting($pdo, 'whatsapp');
$email       = get_setting($pdo, 'email');
$facebook    = get_setting($pdo, 'facebook');
$instagram   = get_setting($pdo, 'instagram');
$tiktok      = get_setting($pdo, 'tiktok');
$footer_text = get_setting($pdo, 'footer_text');
$motto       = get_setting($pdo, 'motto');
$logo_path   = get_setting($pdo, 'logo_path');
$site_name   = get_setting($pdo, 'site_name');
$campus_name = get_setting($pdo, 'campus_name');
?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-brand-col">
      <a href="<?= BASE_URL ?>/index.php" class="footer-brand">
        <img src="<?= BASE_URL ?>/<?= e($logo_path) ?>" alt="" width="52" height="52">
        <span class="footer-brand-text">
          <strong><?= e($site_name) ?></strong>
          <small><?= e($campus_name) ?></small>
        </span>
      </a>
      <?php if ($motto): ?><p class="footer-motto">"<?= e($motto) ?>"</p><?php endif; ?>
      <nav class="footer-social" aria-label="Social media">
        <?php if ($facebook): ?><a href="<?= e($facebook) ?>" target="_blank" rel="noopener" class="edu-soc-fb" aria-label="Facebook"><?= edu_icon('facebook') ?></a><?php endif; ?>
        <?php if ($instagram): ?><a href="<?= e($instagram) ?>" target="_blank" rel="noopener" class="edu-soc-ig" aria-label="Instagram"><?= edu_icon('instagram') ?></a><?php endif; ?>
        <?php if ($tiktok): ?><a href="<?= e($tiktok) ?>" target="_blank" rel="noopener" class="edu-soc-tt" aria-label="TikTok"><?= edu_icon('tiktok') ?></a><?php endif; ?>
        <?php if ($whatsapp): ?><a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="edu-soc-wa" aria-label="WhatsApp"><?= edu_icon('whatsapp') ?></a><?php endif; ?>
      </nav>
    </div>
    <?php
    // Footer link columns — managed in Admin → Menu Builder (cached).
    $footerColumns = cached_footer_menus($pdo);
    ?>
    <?php foreach ($footerColumns as $columnName => $links): ?>
    <nav class="footer-col footer-links" aria-label="<?= e($columnName) ?>">
      <h4><?= e($columnName) ?></h4>
      <?php foreach ($links as $link): ?>
        <a href="<?= e($link['url']) ?>"<?= $link['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($link['label']) ?><?= $link['new_tab'] ? '<span class="sr-only"> (opens in a new tab)</span>' : '' ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endforeach; ?>
    <div class="footer-col">
      <h4>Contact Us</h4>
      <ul class="footer-contact">
        <?php if ($address): ?>
        <li><span class="fc-icon" aria-hidden="true"><?= edu_icon('map-pin') ?></span><span><span class="sr-only">Address: </span><?= e($address) ?></span></li>
        <?php endif; ?>
        <?php if ($phone): ?>
        <li><span class="fc-icon" aria-hidden="true"><?= edu_icon('phone') ?></span><a href="tel:<?= e($phone) ?>"><span class="sr-only">Phone: </span><?= e($phone) ?></a></li>
        <?php endif; ?>
        <?php if ($email): ?>
        <li><span class="fc-icon" aria-hidden="true"><?= edu_icon('mail') ?></span><a href="mailto:<?= e($email) ?>"><span class="sr-only">Email: </span><?= e($email) ?></a></li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Newsletter</h4>
      <p class="footer-news-hint">Get updates about admissions, events and results in your inbox.</p>
      <?php $newsletterFlash = flash_get(); ?>
      <?php if ($newsletterFlash): ?>
        <p role="status" style="font-size:13px;color:<?= $newsletterFlash['type'] === 'success' ? '#9BE85C' : '#f2a3a3' ?>;"><?= e($newsletterFlash['message']) ?></p>
      <?php endif; ?>
      <form method="post" action="<?= BASE_URL ?>/newsletter-subscribe.php" class="newsletter-form">
        <?= csrf_field() ?>
        <label for="newsletter_email" class="sr-only">Your email address</label>
        <input type="email" id="newsletter_email" name="newsletter_email" placeholder="Your email address" required aria-required="true">
        <button type="submit" class="btn btn-accent btn-sm">Subscribe</button>
      </form>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container footer-bottom-inner">
      <p><?= e($footer_text) ?></p>
      <nav class="footer-legal" aria-label="Legal">
        <a href="<?= BASE_URL ?>/privacy-policy.php">Privacy Policy</a>
        <a href="<?= BASE_URL ?>/terms.php">Terms &amp; Conditions</a>
      </nav>
    </div>
  </div>
</footer>
<?php
// Floating action buttons (toggleable in Admin → Contact Settings)
$floatWhatsapp = get_setting($pdo, 'contact_float_whatsapp', '1') === '1' && $whatsapp !== '';
$floatCall = get_setting($pdo, 'contact_float_call', '1') === '1' && $phone !== '';
?>
<?php if ($floatWhatsapp || $floatCall): ?>
<div class="float-actions">
  <?php if ($floatWhatsapp): ?>
    <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="float-btn float-whatsapp" aria-label="Chat on WhatsApp" title="Chat on WhatsApp">
      <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 016.988 2.896 9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.885-9.885 9.885M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.36.101 11.945c0 2.096.549 4.142 1.595 5.945L0 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.585 0 11.946-5.36 11.949-11.945a11.86 11.86 0 00-3.487-8.4"/></svg>
    </a>
  <?php endif; ?>
  <?php if ($floatCall): ?>
    <a href="tel:<?= e($phone) ?>" class="float-btn float-call" aria-label="Call us" title="Call <?= e($phone) ?>">
      <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.01l-2.2 2.2z"/></svg>
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
$popupEnabled = get_setting($pdo, 'popup_enabled') === '1';
$popupTitle = get_setting($pdo, 'popup_title');
$popupText = get_setting($pdo, 'popup_text');
$popupImage = get_setting($pdo, 'popup_image');
$popupLink = get_setting($pdo, 'popup_link');
?>
<?php if ($popupEnabled && ($popupTitle || $popupImage)): ?>
<div class="site-popup" id="sitePopup" hidden role="dialog" aria-modal="true"
     <?= $popupTitle ? 'aria-labelledby="sitePopupTitle"' : 'aria-label="Announcement"' ?>>
  <div class="site-popup-box">
    <button type="button" class="site-popup-close" id="sitePopupClose" aria-label="Close announcement"><span aria-hidden="true">✕</span></button>
    <?php if ($popupImage): ?>
      <?php if ($popupLink): ?><a href="<?= e($popupLink) ?>"><?php endif; ?>
      <img src="<?= BASE_URL ?>/<?= e($popupImage) ?>" alt="<?= e($popupTitle) ?>">
      <?php if ($popupLink): ?></a><?php endif; ?>
    <?php endif; ?>
    <?php if ($popupTitle): ?><h3 id="sitePopupTitle"><?= e($popupTitle) ?></h3><?php endif; ?>
    <?php if ($popupText): ?><p><?= e($popupText) ?></p><?php endif; ?>
    <?php if ($popupLink): ?><a href="<?= e($popupLink) ?>" class="btn btn-dark">Learn More</a><?php endif; ?>
  </div>
</div>
<script>
(function () {
  if (sessionStorage.getItem('popupSeen')) return;
  var popup = document.getElementById('sitePopup');
  if (!popup) return;
  var closeBtn = document.getElementById('sitePopupClose');
  var opener = null;

  function dismiss() {
    popup.hidden = true;
    sessionStorage.setItem('popupSeen', '1');
    if (opener && opener.focus) opener.focus();
  }

  setTimeout(function () {
    opener = document.activeElement;
    popup.hidden = false;
    closeBtn.focus();
  }, 1200);

  closeBtn.addEventListener('click', dismiss);
  popup.addEventListener('click', function (e) {
    if (e.target === popup) dismiss();
  });
  document.addEventListener('keydown', function (e) {
    if (popup.hidden) return;
    if (e.key === 'Escape') {
      dismiss();
    } else if (e.key === 'Tab') {
      var f = popup.querySelectorAll('button, [href]');
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });
})();
</script>
<?php endif; ?>
<script src="<?= e(asset_url($pdo, 'assets/js/main.js')) ?>" defer></script>
<script src="<?= e(asset_url($pdo, 'assets/js/forms.js')) ?>" defer></script>
<?php if (get_setting($pdo, 'theme_animations', '1') === '1'): ?>
<script src="<?= e(asset_url($pdo, 'assets/js/animations.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
