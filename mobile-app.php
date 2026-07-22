<?php
declare(strict_types=1);
require_once 'includes/config.php';

// Editable content (AdminCP → Website Pages → Mobile Apps)
$pc = fn(string $k, string $d): string => get_content($conn, 'mobile-app', $k, $d);

$current   = 'mobile-app.php';
$pageTitle = 'Mobile App Development';
$pageHero  = [
    'kicker' => 'Our Services',
    'title'  => $pc('hero_title', 'Mobile App Development Services'),
    'lead'   => $pc('hero_lead', 'Years of experience delivering outstanding Android and iOS applications — innovative solutions that complement your workflow and needs.'),
];
require_once 'includes/site-header.php';
?>

<!-- ══ INTRO ══ -->
<section class="section">
  <div class="container">
    <div class="row align-items-center g-5">
      <?php $introImg = $pc('intro_image', 'Hero-Mobile-App.png'); if ($introImg !== '__none__'): ?>
      <div class="col-lg-6">
        <img src="<?= htmlspecialchars($introImg) ?>" alt="Mobile app development" class="img-rounded w-100">
      </div>
      <?php endif; ?>
      <div class="col-lg-6">
        <div class="section-kicker">Apps Users Love</div>
        <h2 class="section-title"><?= htmlspecialchars($pc('intro_title', 'Experienced in Every Aspect of Mobile Development')) ?></h2>
        <p class="section-lead mb-4">
          <?= htmlspecialchars($pc('intro_text', 'Our expert teams come up with innovative solutions for delivering outstanding mobile applications. We focus on the user experience to deliver an app that satisfies the customer — and we always make sure you get the right solution for your needs and budget.')) ?>
        </p>
        <ul class="check-list">
          <li>Native &amp; cross-platform apps for Android and iOS</li>
          <li>UI/UX design focused on real users</li>
          <li>Delivery, e-commerce &amp; business workflow apps</li>
          <li>API &amp; backend development included</li>
          <li>App Store &amp; Play Store publishing support</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ══ PLATFORMS ══ -->
<section class="section alt">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Platforms</div>
      <h2 class="section-title">One Codebase or Fully Native — Your Choice</h2>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-md-6 col-xl-3">
        <div class="svc-card text-center">
          <div class="svc-icon mx-auto"><i class="fa-brands fa-android"></i></div>
          <h3>Android</h3>
          <p>Modern Android apps built for performance across phones and tablets.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="svc-card text-center">
          <div class="svc-icon mx-auto"><i class="fa-brands fa-apple"></i></div>
          <h3>iOS</h3>
          <p>Polished iPhone and iPad apps that follow Apple's design standards.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="svc-card text-center">
          <div class="svc-icon mx-auto"><i class="fa-brands fa-flutter"></i></div>
          <h3>Cross-Platform</h3>
          <p>Flutter &amp; React Native — one codebase, both stores, faster delivery.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="svc-card text-center">
          <div class="svc-icon mx-auto"><i class="fa-solid fa-gears"></i></div>
          <h3>Backend &amp; APIs</h3>
          <p>Secure APIs, admin panels, and cloud infrastructure powering your app.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ PROCESS ══ -->
<section class="section">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Our Approach</div>
      <h2 class="section-title">Navigating the Maze of Choices, For You</h2>
      <p class="section-lead">Our reliable team delivers professional results while navigating ever-increasing platform choices and opportunities.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">1</div>
          <h4>Idea &amp; Strategy</h4>
          <p>We define the right app solution for your needs and your budget.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">2</div>
          <h4>UX &amp; Design</h4>
          <p>User-experience-first design that satisfies customers and keeps them coming back.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">3</div>
          <h4>Build &amp; Test</h4>
          <p>Agile development with continuous testing on real devices.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">4</div>
          <h4>Launch &amp; Grow</h4>
          <p>Store publishing, analytics, updates, and ongoing support after launch.</p>
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
          <h2>Have an app idea? Let's make it real.</h2>
          <p>Get the right mobile app solution for your needs and budget — talk to our team today.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn-grad"><i class="fa-solid fa-mobile-screen-button me-2"></i>Discuss Your App</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
