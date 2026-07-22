<?php
declare(strict_types=1);
require_once 'includes/config.php';

// Editable content (AdminCP → Website Pages → Web Development)
$pc = fn(string $k, string $d): string => get_content($conn, 'web-development', $k, $d);

$current   = 'web-development.php';
$pageTitle = 'Web Development';
$pageHero  = [
    'kicker' => 'Our Services',
    'title'  => $pc('hero_title', 'Web Development Services'),
    'lead'   => $pc('hero_lead', 'Interactive, robust, and easy-to-use web solutions that represent your business — built with the latest technologies for the education, health, and business sectors.'),
];
require_once 'includes/site-header.php';
?>

<!-- ══ INTRO ══ -->
<section class="section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="section-kicker">Websites That Work</div>
        <h2 class="section-title"><?= htmlspecialchars($pc('intro_title', 'A Professional, Highly Skilled Web Design & Development Company')) ?></h2>
        <p class="section-lead mb-4">
          <?= htmlspecialchars($pc('intro_text', 'Malik Solution provides web design and development services worldwide. A website marks the online presence of any business and opens the way into global markets — we make sure yours is fast, modern, and built to convert visitors into customers.')) ?>
        </p>
        <ul class="check-list">
          <li>Business websites, portals &amp; e-commerce stores</li>
          <li>Custom web applications &amp; dashboards</li>
          <li>Responsive design for every screen size</li>
          <li>SEO-friendly structure &amp; fast load times</li>
          <li>Ongoing maintenance, hosting &amp; support</li>
        </ul>
      </div>
      <?php $introImg = $pc('intro_image', 'Website-Development-Company.jpg'); if ($introImg !== '__none__'): ?>
      <div class="col-lg-6">
        <img src="<?= htmlspecialchars($introImg) ?>" alt="Web development company" class="img-rounded w-100">
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ══ WHAT WE BUILD ══ -->
<section class="section alt">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">What We Build</div>
      <h2 class="section-title">Web Design &amp; Development, End to End</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-3">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-pen-ruler"></i></div>
          <h3>Web Design</h3>
          <p>Design plays a vital role in holding the visitor to your website. We craft clean, modern interfaces that keep users engaged.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-code"></i></div>
          <h3>Web Development</h3>
          <p>Robust, secure, standards-compliant development that puts your business on the World Wide Web and into global markets.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-cart-shopping"></i></div>
          <h3>E-Commerce</h3>
          <p>High-performance online stores with secure payments, inventory management, and a checkout that converts.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-laptop-code"></i></div>
          <h3>Custom Software</h3>
          <p>Tailor-made software for your workflow — from admin panels and CRMs to complete business management platforms.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ TECH ══ -->
<section class="section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <div class="section-kicker">Our Toolbox</div>
        <h2 class="section-title">Latest Technologies, Proven Results</h2>
        <p class="section-lead">We choose the right stack for each project — never the other way around.</p>
      </div>
      <div class="col-lg-7">
        <div class="tech-pills">
          <span><i class="fa-brands fa-html5"></i> HTML5</span>
          <span><i class="fa-brands fa-css3-alt"></i> CSS3</span>
          <span><i class="fa-brands fa-js"></i> JavaScript</span>
          <span><i class="fa-brands fa-php"></i> PHP</span>
          <span><i class="fa-brands fa-laravel"></i> Laravel</span>
          <span><i class="fa-brands fa-react"></i> React</span>
          <span><i class="fa-brands fa-bootstrap"></i> Bootstrap</span>
          <span><i class="fa-solid fa-database"></i> MySQL</span>
          <span><i class="fa-brands fa-wordpress"></i> WordPress</span>
          <span><i class="fa-brands fa-node-js"></i> Node.js</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ CTA ══ -->
<section class="section pt-0">
  <div class="container">
    <div class="cta-band">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2>Ready for a website that represents your business?</h2>
          <p>Get an interactive, easy-to-use, robust web solution — tell us what you need.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn-grad"><i class="fa-solid fa-paper-plane me-2"></i>Get a Free Quote</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
