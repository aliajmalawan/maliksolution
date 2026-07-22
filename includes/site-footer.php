<?php
declare(strict_types=1);
// Company footer — content driven by AdminCP → Site Settings

$fSiteName  = get_setting($conn, 'site_name', 'Malik Solution');
$fTagline   = get_setting($conn, 'tagline', 'Web · Mobile · Software Solutions');
$fDesc      = get_setting($conn, 'footer_description', 'A professional software development company crafting high-performance websites, mobile apps, and IT solutions for businesses worldwide.');
$fPhone     = get_setting($conn, 'contact_phone', '+92 333 2150925');
$fEmail     = get_setting($conn, 'contact_email', 'support@maliksolution.com');
$fAddress   = get_setting($conn, 'contact_address', 'Kehkashan Society, Malir Halt, Karachi, Pakistan');
$fCopyright = get_setting($conn, 'footer_copyright', '© ' . date('Y') . ' Malik Solution Private Limited. All rights reserved.');
?>

<!-- ── Footer ── -->
<footer class="company-footer">
  <div class="container">
    <div class="row g-5">

      <!-- Brand -->
      <div class="col-lg-4">
        <h3 class="footer-brand"><?= htmlspecialchars($fSiteName) ?></h3>
        <p class="footer-tagline"><?= htmlspecialchars($fTagline) ?></p>
        <p class="footer-desc"><?= htmlspecialchars($fDesc) ?></p>
        <div class="footer-socials">
          <a href="<?= htmlspecialchars(get_setting($conn, 'facebook_url', '#')) ?>" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="<?= htmlspecialchars(get_setting($conn, 'instagram_url', '#')) ?>" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="<?= htmlspecialchars(get_setting($conn, 'youtube_url', '#')) ?>" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $fPhone)) ?>" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>

      <!-- Quick links -->
      <div class="col-sm-6 col-lg-2">
        <h4 class="footer-heading">Company</h4>
        <ul class="footer-links">
          <li><a href="index.php"><i class="fa-solid fa-chevron-right"></i>Home</a></li>
          <li><a href="about.php"><i class="fa-solid fa-chevron-right"></i>About Us</a></li>
          <li><a href="projects.php"><i class="fa-solid fa-chevron-right"></i>Projects</a></li>
          <li><a href="contact.php"><i class="fa-solid fa-chevron-right"></i>Contact</a></li>
          <li><a href="demonstration.php"><i class="fa-solid fa-chevron-right"></i>Request a Demonstration</a></li>
          <li><a href="contact.php"><i class="fa-solid fa-chevron-right"></i>Get a Quote</a></li>
        </ul>
      </div>

      <!-- Services -->
      <div class="col-sm-6 col-lg-3">
        <h4 class="footer-heading">Services</h4>
        <ul class="footer-links">
          <li><a href="web-development.php"><i class="fa-solid fa-chevron-right"></i>Web Development</a></li>
          <li><a href="mobile-app.php"><i class="fa-solid fa-chevron-right"></i>Mobile App Development</a></li>
          <li><a href="web-development.php"><i class="fa-solid fa-chevron-right"></i>Software Development</a></li>
          <li><a href="it-services.php"><i class="fa-solid fa-chevron-right"></i>Managed IT Services</a></li>
          <li><a href="hosting.php"><i class="fa-solid fa-chevron-right"></i>Web Hosting</a></li>
          <li><a href="it-services.php"><i class="fa-solid fa-chevron-right"></i>Cloud &amp; Security</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="col-lg-3">
        <h4 class="footer-heading">Contact Us</h4>
        <ul class="footer-contact">
          <li>
            <div class="fc-icon"><i class="fa-solid fa-phone"></i></div>
            <div class="fc-text">
              <strong>Phone</strong>
              <span><?= htmlspecialchars($fPhone) ?></span>
            </div>
          </li>
          <li>
            <div class="fc-icon"><i class="fa-solid fa-envelope"></i></div>
            <div class="fc-text">
              <strong>Email</strong>
              <span><?= htmlspecialchars($fEmail) ?></span>
            </div>
          </li>
          <li>
            <div class="fc-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div class="fc-text">
              <strong>Office</strong>
              <span><?= nl2br(htmlspecialchars($fAddress)) ?></span>
            </div>
          </li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <span><?= htmlspecialchars($fCopyright) ?></span>
      <div>
        <a href="<?= htmlspecialchars(get_setting($conn, 'privacy_url', '#')) ?>">Privacy Policy</a>
        <a href="<?= htmlspecialchars(get_setting($conn, 'terms_url', '#')) ?>">Terms of Use</a>
      </div>
    </div>
  </div>
</footer>

<!-- ── Floating buttons ── -->
<a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $fPhone)) ?>" class="wa-float" target="_blank"
   rel="noopener" aria-label="Chat on WhatsApp" title="Chat on WhatsApp">
  <i class="fa-brands fa-whatsapp"></i>
</a>
<button type="button" class="top-float" id="backToTop" aria-label="Back to top" title="Back to top">
  <i class="fa-solid fa-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  'use strict';

  /* ── Scroll reveal: auto-tag common blocks, stagger siblings ── */
  var selectors = ['.svc-card', '.step-card', '.team-card', '.price-card', '.testi-card',
                   '.industry-chip', '.feat-item', '.contact-card', '.cta-band', '.req-tile', '.mv-card',
                   '.module-card', '.folio-card', '.phone-mock', '.case-card',
                   '.section-kicker', '.section-title', '.section-lead', '.check-list',
                   '.about-img', '.img-rounded', '.stat-x'];
  var els = document.querySelectorAll(selectors.join(','));
  els.forEach(function (el) {
    el.classList.add('reveal');
    var col = el.closest('[class*="col-"]');
    if (col && col.parentElement) {
      var idx = Array.prototype.indexOf.call(col.parentElement.children, col);
      if (idx > 0) el.classList.add('d' + Math.min(idx, 4));
    }
  });
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: .12, rootMargin: '0px 0px -30px 0px' });
    els.forEach(function (el) { io.observe(el); });
  } else {
    els.forEach(function (el) { el.classList.add('in'); });
  }

  /* ── Animated counters (hero trust + stats band) ── */
  var counters = document.querySelectorAll('.trust-item strong, .stat-x strong');
  function animateCounter(el) {
    var raw = el.textContent.trim();
    var m = raw.match(/^([0-9][0-9.,]*)(.*)$/);
    if (!m) return;
    var target = parseFloat(m[1].replace(/,/g, ''));
    var suffix = m[2] || '';
    var isFloat = m[1].indexOf('.') !== -1;
    var start = null, dur = 1400;
    function tick(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      var val = target * eased;
      el.textContent = (isFloat ? val.toFixed(2) : Math.round(val).toLocaleString()) + suffix;
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  if ('IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { animateCounter(e.target); cio.unobserve(e.target); }
      });
    }, { threshold: .6 });
    counters.forEach(function (el) { cio.observe(el); });
  }

  /* ── Navbar shadow + back-to-top visibility ── */
  var nav = document.querySelector('.company-nav');
  var topBtn = document.getElementById('backToTop');
  function onScroll() {
    var y = window.scrollY || document.documentElement.scrollTop;
    if (nav) nav.classList.toggle('scrolled', y > 10);
    if (topBtn) topBtn.classList.toggle('show', y > 500);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  topBtn && topBtn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();
</script>
</body>
</html>
