<?php
declare(strict_types=1);
require_once 'includes/config.php';

// Editable content (AdminCP → Website Pages → About Us)
$pc = fn(string $k, string $d): string => get_content($conn, 'about', $k, $d);

$current   = 'about.php';
$pageTitle = 'About Us';
$pageHero  = [
    'kicker' => 'About Us',
    'title'  => $pc('hero_title', 'About Malik Solution'),
    'lead'   => $pc('hero_lead', 'One of the fastest growing web development companies — crafting high-performance websites, mobile apps, software, and e-commerce solutions.'),
];
require_once 'includes/site-header.php';
?>

<!-- ══ 1. WHO WE ARE ══ -->
<section class="section" id="who-we-are">
  <div class="container">
    <div class="row align-items-center g-5">
      <?php $mainImg = $pc('main_image', 'Hero-About.png'); if ($mainImg !== '__none__'): ?>
      <div class="col-lg-6">
        <div class="position-relative mb-4 mb-lg-0">
          <img src="<?= htmlspecialchars($mainImg) ?>" alt="About Malik Solution" class="about-img" style="max-height:440px">
          <div class="exp-badge"><strong>150+</strong><span>Projects Delivered</span></div>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-lg-6">
        <div class="section-kicker">Who We Are</div>
        <h2 class="section-title"><?= htmlspecialchars($pc('who_title', 'One of the Fastest Growing Web Development Companies')) ?></h2>
        <div class="title-bar"></div>
        <p class="section-lead mb-3">
          <?= htmlspecialchars($pc('who_text1', 'Malik Solution Private Limited is one of the fastest growing web development companies. We craft high-performance Websites, Mobile Apps, Software, and E-Commerce platforms for the Healthcare, Education, and Business industries. Our development team always aims for satisfied customers — we work with a wide range of clients, from very small startups to established large corporations.')) ?>
        </p>
        <p class="section-lead mb-3">
          <?= htmlspecialchars($pc('who_text2', 'Over the years of experience, we have helped our clients build great web, mobile & software solutions. With endless possibilities, all you need is to let us know your requirement — and we will deliver a web, mobile, or software solution worthy of outstanding business success. Malik Solution Private Limited is the Market Leader in Managed IT Services and Solutions, IT Support, and Disaster Recovery Services.')) ?>
        </p>
        <?php
        // Extra paragraphs from the AdminCP editor — one per blank-line block
        $who_extra = trim($pc('who_extra', ''));
        if ($who_extra !== ''):
            foreach (preg_split('/\R{2,}/', $who_extra) as $para):
                $para = trim($para);
                if ($para === '') continue; ?>
          <p class="section-lead mb-3"><?= nl2br(htmlspecialchars($para)) ?></p>
        <?php endforeach; endif; ?>
        <div class="mb-1"></div>
        <a href="contact.php" class="btn-grad"><i class="fa-solid fa-paper-plane me-2"></i>Work With Us</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ EXPERIENCE BAND ══ -->
<section class="section pt-0">
  <div class="container">
    <div class="cta-band">
      <div class="row align-items-center g-4">
        <div class="col-lg-3 text-center">
          <div class="big-num"><?= htmlspecialchars($pc('exp_num', '30')) ?><span>+</span></div>
          <div class="big-num-label"><?= htmlspecialchars($pc('exp_title', 'Years of IT Excellence')) ?></div>
        </div>
        <div class="col-lg-9">
          <p class="mb-3" style="color:#e3edfb;font-size:1.02rem">
            <i class="fa-solid fa-server me-2" style="color:#22d3ee"></i>
            <?= htmlspecialchars($pc('exp_text1', 'Malik Solution Private Limited has spent nearly 30 years designing, configuring, and supporting business-critical IT platforms for our customers.')) ?>
          </p>
          <p class="mb-0" style="color:#c2d4ee">
            <i class="fa-solid fa-shield-halved me-2" style="color:#22d3ee"></i>
            <?= htmlspecialchars($pc('exp_text2', 'We help organizations of all sizes protect their data and reduce the costs of running their IT systems through our IT Support, Managed Services, and Disaster Recovery Services.')) ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ 2 & 3. MISSION & VISION ══ -->
<section class="section alt" id="mission-vision" style="padding-top:3rem">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Mission &amp; Vision</div>
      <h2 class="section-title">What Drives Us Forward</h2>
      <div class="title-bar"></div>
    </div>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="mv-card">
          <div class="mv-icon"><i class="fa-solid fa-bullseye"></i></div>
          <h3>Our Mission</h3>
          <p>
            <?= htmlspecialchars($pc('mission_text', 'By providing these services, our mission is to become the preeminent provider in the market. We are improving the quality of our services consistently — and have become capable of adding value for clients with the help of our hardworking and honest experts. We provide innovation and quality performance to our honored clients.')) ?>
          </p>
          <ul class="check-list mb-0">
            <li>Satisfied customers as our first measure of success</li>
            <li>Continuous improvement in every service we offer</li>
            <li>Honest, transparent communication at every step</li>
          </ul>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="mv-card">
          <div class="mv-icon"><i class="fa-solid fa-eye"></i></div>
          <h3>Our Vision</h3>
          <p>
            <?= htmlspecialchars($pc('vision_text', 'A better future for our clients and customers through the power of technology. We offer world-class IT and disaster recovery services and IT solutions that take businesses to the next level — and we are here to serve you with honor and honesty.')) ?>
          </p>
          <ul class="check-list mb-0">
            <li>Technology that takes businesses to the next level</li>
            <li>Long-term partnerships, not one-time projects</li>
            <li>Serving clients worldwide, large and small</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ OUR APPROACH ══ -->
<section class="section" id="our-approach">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Our Approach</div>
      <h2 class="section-title">How We Work With Every Client</h2>
      <div class="title-bar"></div>
    </div>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="mv-card">
          <div class="mv-icon"><i class="fa-solid fa-handshake"></i></div>
          <h3>Client-Focused, Always</h3>
          <p class="mb-0">
            <?= htmlspecialchars($pc('approach_text', 'As an IT service company, we work separately with every client so that you can find the best technology solutions according to your demands. It does not matter which service you choose — you always get an innovative approach and the most current technology. We have expert teams for each service, and our team members are continuously working on improvement, innovation, and the best solutions to your problems — helping the company maintain a record of success.')) ?>
          </p>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="mv-card">
          <div class="mv-icon"><i class="fa-solid fa-earth-asia"></i></div>
          <h3>Serving Clients Worldwide</h3>
          <p class="mb-0">
            <?= htmlspecialchars($pc('global_text', 'We service customers large and small — commercial and public sector — across a wide range of industries, and we deliver our services worldwide through our unique approach to partnering with clients. Our clients take full advantage of our technology and disaster recovery services and solutions, for a better future powered by technology.')) ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ 4. WHY CHOOSE US ══ -->
<section class="section alt" id="why-choose-us">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Why Choose Us?</div>
      <h2 class="section-title">The Malik Solution Advantage</h2>
      <div class="title-bar"></div>
      <p class="section-lead">It doesn't matter which service you choose — you always get an innovative approach and the most current technology.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-user-check"></i></div>
          <h3>Client-First Approach</h3>
          <p>We work separately with every client to find the best technology solution for your exact demands and budget.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-people-group"></i></div>
          <h3>Expert Teams</h3>
          <p>A dedicated team for each service — 30+ engineers continuously working on improvement and innovation.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-lightbulb"></i></div>
          <h3>Latest Technology</h3>
          <p>Modern stacks — React, Laravel, Flutter, AWS, Azure — chosen to fit your project, never the other way around.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-headset"></i></div>
          <h3>24/7 Support</h3>
          <p>Round-the-clock monitoring and an in-house helpdesk with level-3 escalation — help is always one call away.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-shield-heart"></i></div>
          <h3>Data Protection</h3>
          <p>Market leader in Managed IT, IT Support, and Disaster Recovery — protecting your data while reducing IT costs.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-wallet"></i></div>
          <h3>Honest Pricing</h3>
          <p>The right solution for your needs and budget — from hosting at Rs.1,999/yr to complete enterprise platforms.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ TEAM ══ -->
<section class="section">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Leadership</div>
      <h2 class="section-title">Our Team</h2>
      <div class="title-bar"></div>
      <p class="section-lead">Serving customers large and small, commercial and public sector, across a wide range of industries.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-sm-6 col-lg-4">
        <div class="team-card">
          <?php $m1p = $pc('member1_photo', 'Shahid.jpeg'); if ($m1p !== '__none__'): ?>
          <img src="<?= htmlspecialchars($m1p) ?>" alt="<?= htmlspecialchars($pc('member1_name', 'Shahid Malik')) ?>">
          <?php endif; ?>
          <div class="tc-body">
            <h4><?= htmlspecialchars($pc('member1_name', 'Shahid Malik')) ?></h4>
            <span><?= htmlspecialchars($pc('member1_role', 'Chief Executive Officer')) ?></span>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-4">
        <div class="team-card">
          <?php $m2p = $pc('member2_photo', 'Zahid.png'); if ($m2p !== '__none__'): ?>
          <img src="<?= htmlspecialchars($m2p) ?>" alt="<?= htmlspecialchars($pc('member2_name', 'Muhammad Zahid')) ?>">
          <?php endif; ?>
          <div class="tc-body">
            <h4><?= htmlspecialchars($pc('member2_name', 'Muhammad Zahid')) ?></h4>
            <span><?= htmlspecialchars($pc('member2_role', 'Director')) ?></span>
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
          <h2>A better future for your business, powered by technology.</h2>
          <p>We are here to serve you with honor and honesty — worldwide, through our unique approach to partnering with clients.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn-grad"><i class="fa-solid fa-paper-plane me-2"></i>Contact Us</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
