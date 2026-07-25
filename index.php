<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$current   = 'index.php';
$pageTitle = 'Home';
require_once 'includes/site-header.php';

// Editable content (AdminCP → Website Pages → Home)
$pc = fn(string $k, string $d): string => get_content($conn, 'home', $k, $d);

/** Render "*text*" markers as the gradient span. */
function grad_title(string $raw): string {
    return preg_replace('/\*([^*]+)\*/', '<span class="grad-text">$1</span>', htmlspecialchars($raw));
}

/** Split "number | label" into [number, label]. */
function stat_pair(string $raw, string $defNum, string $defLabel): array {
    $parts = array_map('trim', explode('|', $raw, 2));
    return [$parts[0] !== '' ? $parts[0] : $defNum, $parts[1] ?? $defLabel];
}
?>

<!-- ══ HERO SLIDER ══ -->
<?php
$slides = [];
for ($i = 1; $i <= 3; $i++) {
    $defaults = [
        1 => ['Software Development Company', 'We Build *Web, Mobile & Software* Solutions That Grow Your Business',
              'Malik Solution is a professional, highly skilled development company delivering modern websites, mobile apps, custom software, managed IT services, and reliable hosting to clients worldwide.', 'slide-1.jpg'],
        2 => ['Web Development', 'Modern Websites That *Convert Visitors* Into Customers',
              'Interactive, robust, and easy-to-use web solutions that represent your business — from landing pages to complete e-commerce platforms.', 'Ser-bg1.jpg'],
        3 => ['Managed IT & Hosting', '24/7 Monitoring, *Secure Hosting* & Expert IT Support',
              'We keep your IT systems running optimally so you can focus on growing your business — with cloud backup, disaster recovery, and hosting from Rs.1,999/yr.', 'Hero-Hosting.png'],
    ][$i];
    $img = $pc("slide{$i}_image", $defaults[3]);
    $slides[] = [
        'kicker' => $pc("slide{$i}_kicker", $defaults[0]),
        'title'  => $pc("slide{$i}_title", $defaults[1]),
        'text'   => $pc("slide{$i}_text", $defaults[2]),
        'image'  => $img === '__none__' ? '' : $img,
        'style'  => $pc("slide{$i}_style", 'text'),
    ];
}
?>
<header class="hero-slider">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-indicators">
      <?php foreach ($slides as $i => $s): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $i ?>"
                class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"
                <?= $i === 0 ? 'aria-current="true"' : '' ?>></button>
      <?php endforeach; ?>
    </div>

    <div class="carousel-inner">
      <?php foreach ($slides as $i => $s): ?>
        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?> <?= $s['style'] === 'image' ? 'hs-image-only' : '' ?>" data-bs-interval="6000">
          <div class="hs-bg" <?= $s['image'] !== '' ? 'style="background-image:url(\'' . htmlspecialchars($s['image']) . '\')"' : '' ?>></div>
          <?php if ($s['style'] === 'image'): ?>
            <a href="contact.php" class="hs-banner-link" aria-label="<?= htmlspecialchars($s['kicker']) ?>"></a>
            <div class="container"></div>
          <?php else: ?>
            <div class="hs-overlay"></div>
            <div class="container">
              <div class="hs-content">
                <div class="hero-kicker"><span class="pulse-dot"></span> <?= htmlspecialchars($s['kicker']) ?></div>
                <h1><?= grad_title($s['title']) ?></h1>
                <p class="hs-lead"><?= htmlspecialchars($s['text']) ?></p>
                <div class="d-flex gap-3 flex-wrap">
                  <a href="contact.php" class="btn-grad"><i class="fa-solid fa-paper-plane me-2"></i>Start Your Project</a>
                  <a href="#services" class="btn-ghost"><i class="fa-solid fa-arrow-down me-2"></i>Our Services</a>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="hs-arrow"><i class="fa-solid fa-chevron-left"></i></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="hs-arrow"><i class="fa-solid fa-chevron-right"></i></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</header>

<!-- ══ TECH MARQUEE ══ -->
<div class="marquee-band">
  <div class="marquee-track">
    <?php for ($i = 0; $i < 2; $i++): ?>
      <span><i class="fa-brands fa-react"></i> React</span>
      <span><i class="fa-brands fa-laravel"></i> Laravel</span>
      <span><i class="fa-brands fa-php"></i> PHP</span>
      <span><i class="fa-brands fa-js"></i> JavaScript</span>
      <span><i class="fa-brands fa-flutter"></i> Flutter</span>
      <span><i class="fa-brands fa-android"></i> Android</span>
      <span><i class="fa-brands fa-apple"></i> iOS</span>
      <span><i class="fa-brands fa-wordpress"></i> WordPress</span>
      <span><i class="fa-brands fa-aws"></i> AWS</span>
      <span><i class="fa-brands fa-microsoft"></i> Azure</span>
      <span><i class="fa-solid fa-database"></i> MySQL</span>
      <span><i class="fa-brands fa-node-js"></i> Node.js</span>
    <?php endfor; ?>
  </div>
</div>

<!-- ══ SERVICES ══ -->
<section class="section" id="services">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">What We Do</div>
      <h2 class="section-title">Web Development | Mobile App | Software Development</h2>
      <div class="title-bar"></div>
      <p class="section-lead">From a single landing page to a complete business platform — we design, build, host, and maintain it all under one roof.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-code"></i></div>
          <h3>Web Development</h3>
          <p>Interactive, robust, easy-to-use websites and web apps built with the latest technologies — solutions that truly represent your business online.</p>
          <a href="web-development.php" class="svc-link">Learn More <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
          <h3>Mobile App Development</h3>
          <p>Innovative Android and iOS applications focused on user experience — apps that complement your workflow and delight your customers.</p>
          <a href="mobile-app.php" class="svc-link">Learn More <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-laptop-code"></i></div>
          <h3>Software Development</h3>
          <p>Custom software tailored to your business — we don't just build solutions, we give you a powerful online presence to match.</p>
          <a href="web-development.php" class="svc-link">Learn More <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-server"></i></div>
          <h3>Managed IT Services</h3>
          <p>24/7 system monitoring, helpdesk support, Windows administration, migrations to AWS &amp; Azure, and complete IT outsourcing.</p>
          <a href="it-services.php" class="svc-link">Learn More <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-cloud"></i></div>
          <h3>Web Hosting</h3>
          <p>Reliable, scalable, and secure hosting with free SSL, 99.99% uptime, and packages for every budget — starting at Rs.1,999/yr.</p>
          <a href="hosting.php" class="svc-link">View Plans <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <h3>Security &amp; Cloud Backup</h3>
          <p>Antivirus, anti-spam, penetration testing, security audits, cloud backup, and disaster recovery — your data protected end to end.</p>
          <a href="it-services.php" class="svc-link">Learn More <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ STATS ══ -->
<section class="stats-band">
  <div class="container">
    <div class="row g-4">
      <?php
      $stats = [
          stat_pair($pc('stat1', ''), '150+', 'Projects Completed'),
          stat_pair($pc('stat2', ''), '30+', 'Engineers On Staff'),
          stat_pair($pc('stat3', ''), '99.99%', 'Hosting Uptime'),
          stat_pair($pc('stat4', ''), '24/7', 'Monitoring & Support'),
      ];
      foreach ($stats as [$num, $label]): ?>
        <div class="col-6 col-md-3"><div class="stat-x"><strong><?= htmlspecialchars($num) ?></strong><span><?= htmlspecialchars($label) ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ ABOUT PREVIEW ══ -->
<section class="section alt">
  <div class="container">
    <div class="row align-items-center g-5">
      <?php $aboutImg = $pc('about_image', 'Hero-About.png'); if ($aboutImg !== '__none__'): ?>
      <div class="col-lg-6">
        <div class="position-relative mb-4 mb-lg-0">
          <img src="<?= htmlspecialchars($aboutImg) ?>" alt="About Malik Solution" class="about-img" style="max-height:420px">
          <div class="exp-badge"><strong>10+</strong><span>Years of Excellence</span></div>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-lg-6">
        <div class="section-kicker">About Malik Solution</div>
        <h2 class="section-title"><?= htmlspecialchars($pc('about_title', 'One of the Fastest Growing Web & Software Development Companies')) ?></h2>
        <p class="section-lead mb-4">
          <?= htmlspecialchars($pc('about_text', 'We craft high-performance websites, mobile apps, software, and e-commerce platforms for the healthcare, education, and business industries. From small startups to established corporations, our team delivers solutions worthy of outstanding business success.')) ?>
        </p>
        <ul class="check-list mb-4">
          <li>Expert teams dedicated to every service we offer</li>
          <li>Latest technologies with an innovative approach</li>
          <li>Managed IT, disaster recovery &amp; data protection</li>
          <li>Serving clients worldwide with honor and honesty</li>
        </ul>
        <a href="about.php" class="btn-outline-navy"><i class="fa-solid fa-circle-info me-2"></i>More About Us</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ PROCESS ══ -->
<section class="section">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">How We Work</div>
      <h2 class="section-title">From Idea to Launch in 4 Steps</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">1</div>
          <h4>Discover</h4>
          <p>We listen to your requirements and understand your business goals in detail.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">2</div>
          <h4>Design</h4>
          <p>Modern, user-focused designs that hold visitors and represent your brand.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">3</div>
          <h4>Develop</h4>
          <p>Clean, secure code built with the latest technologies and best practices.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="step-card">
          <div class="step-num">4</div>
          <h4>Deliver &amp; Support</h4>
          <p>Launch, hosting, 24/7 monitoring, and ongoing maintenance — we stay with you.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ INDUSTRIES ══ -->
<section class="section alt">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Industries We Serve</div>
      <h2 class="section-title">Solutions for Every Sector</h2>
      <div class="title-bar"></div>
      <p class="section-lead">From startups to established corporations — healthcare, education, and business industries trust Malik Solution.</p>
    </div>
    <div class="row g-3 justify-content-center">
      <div class="col-6 col-md-4 col-xl-2"><div class="industry-chip"><i class="fa-solid fa-heart-pulse"></i><span>Healthcare</span></div></div>
      <div class="col-6 col-md-4 col-xl-2"><div class="industry-chip"><i class="fa-solid fa-graduation-cap"></i><span>Education</span></div></div>
      <div class="col-6 col-md-4 col-xl-2"><div class="industry-chip"><i class="fa-solid fa-cart-shopping"></i><span>E-Commerce</span></div></div>
      <div class="col-6 col-md-4 col-xl-2"><div class="industry-chip"><i class="fa-solid fa-building"></i><span>Corporate</span></div></div>
      <div class="col-6 col-md-4 col-xl-2"><div class="industry-chip"><i class="fa-solid fa-truck-fast"></i><span>Delivery</span></div></div>
      <div class="col-6 col-md-4 col-xl-2"><div class="industry-chip"><i class="fa-solid fa-rocket"></i><span>Startups</span></div></div>
    </div>
  </div>
</section>

<!-- ══ TESTIMONIALS ══ -->
<section class="section">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Testimonials</div>
      <h2 class="section-title">What Our Clients Say</h2>
      <div class="title-bar"></div>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-4">
        <div class="testi-card">
          <div class="quote-ico"><i class="fa-solid fa-quote-left"></i></div>
          <div class="t-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
          <p>"Malik Solution built our business website exactly the way we imagined — fast, modern, and easy to manage. Their team stayed with us even after launch."</p>
          <div class="t-who">
            <div class="t-avatar">AR</div>
            <div><strong>Ahmed Raza</strong><span>Business Owner — E-Commerce Store</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="testi-card">
          <div class="quote-ico"><i class="fa-solid fa-quote-left"></i></div>
          <div class="t-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
          <p>"Their mobile app team understood our delivery workflow perfectly. The app is smooth, our customers love it, and support is always one call away."</p>
          <div class="t-who">
            <div class="t-avatar">SK</div>
            <div><strong>Sana Khan</strong><span>Operations Manager — Delivery Service</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="testi-card">
          <div class="quote-ico"><i class="fa-solid fa-quote-left"></i></div>
          <div class="t-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
          <p>"We moved our hosting and IT support to Malik Solution. Zero downtime since, and their 24/7 monitoring gives us complete peace of mind."</p>
          <div class="t-who">
            <div class="t-avatar">UF</div>
            <div><strong>Usman Farooq</strong><span>IT Manager — Healthcare Group</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ TEAM ══ -->
<section class="section alt">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Our Team</div>
      <h2 class="section-title">The People Behind Malik Solution</h2>
      <div class="title-bar"></div>
      <p class="section-lead">Hardworking, honest experts continuously working for improvement, innovation, and the best solutions to your problems.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-sm-6 col-lg-4">
        <div class="team-card">
          <?php $m1p = get_content($conn, 'about', 'member1_photo', 'Shahid.jpeg'); if ($m1p !== '__none__'): ?>
          <img src="<?= htmlspecialchars($m1p) ?>" alt="<?= htmlspecialchars(get_content($conn, 'about', 'member1_name', 'Shahid Malik')) ?>">
          <?php endif; ?>
          <div class="tc-body">
            <h4><?= htmlspecialchars(get_content($conn, 'about', 'member1_name', 'Shahid Malik')) ?></h4>
            <span><?= htmlspecialchars(get_content($conn, 'about', 'member1_role', 'Chief Executive Officer')) ?></span>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="team-card">
          <?php $m2p = get_content($conn, 'about', 'member2_photo', 'Zahid.png'); if ($m2p !== '__none__'): ?>
          <img src="<?= htmlspecialchars($m2p) ?>" alt="<?= htmlspecialchars(get_content($conn, 'about', 'member2_name', 'Muhammad Zahid')) ?>">
          <?php endif; ?>
          <div class="tc-body">
            <h4><?= htmlspecialchars(get_content($conn, 'about', 'member2_name', 'Muhammad Zahid')) ?></h4>
            <span><?= htmlspecialchars(get_content($conn, 'about', 'member2_role', 'Director')) ?></span>
          </div>
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
          <h2><?= htmlspecialchars($pc('cta_title', "Have a project in mind? Let's build it together.")) ?></h2>
          <p><?= htmlspecialchars($pc('cta_text', "Tell us your requirements and we'll deliver a web, mobile, or software solution worthy of outstanding business success.")) ?></p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn-grad"><i class="fa-solid fa-paper-plane me-2"></i>Get a Free Quote</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
