<?php
declare(strict_types=1);
require_once 'includes/config.php';

// Editable content (AdminCP → Website Pages → Hosting)
$pc = fn(string $k, string $d): string => get_content($conn, 'hosting', $k, $d);

$current   = 'hosting.php';
$pageTitle = 'Web Hosting';
$pageHero  = [
    'kicker' => 'Malik Hosting',
    'title'  => $pc('hero_title', 'Reliable, Scalable & Secure Web Hosting'),
    'lead'   => $pc('hero_lead', 'Hosting and maintenance solutions made easy and affordable — with packages to suit your unique needs and budget.'),
];
require_once 'includes/site-header.php';

$plan_defaults = [
    1 => ['tag' => 'Top',     'name' => 'Starter Plan',  'price' => '1,999', 'popular' => false,
          'features' => "Host 1 Domain\n1 GB Disk Space\n20 GB Bandwidth\n5 Free Email Accounts\n2 Free Databases\n2 Free FTP Accounts\n2 Free Sub Domains"],
    2 => ['tag' => 'Popular', 'name' => 'Economy Plan',  'price' => '2,999', 'popular' => true,
          'features' => "Host 2 Domains\n3 GB Disk Space\n40 GB Bandwidth\n10 Free Email Accounts\n5 Free Databases\n5 Free FTP Accounts\n5 Free Sub Domains"],
    3 => ['tag' => 'Hot',     'name' => 'Business Plan', 'price' => '3,999', 'popular' => false,
          'features' => "Host 3 Domains\n6 GB Disk Space\n60 GB Bandwidth\n15 Free Email Accounts\n10 Free Databases\n10 Free FTP Accounts\n10 Free Sub Domains"],
    4 => ['tag' => 'New',     'name' => 'Deluxe Plan',   'price' => '4,999', 'popular' => false,
          'features' => "Host 5 Domains\n10 GB Disk Space\n100 GB Bandwidth\n50 Free Email Accounts\n25 Free Databases\n25 Free FTP Accounts\n10 Free Sub Domains"],
];

$plans = [];
foreach ($plan_defaults as $i => $d) {
    $features = preg_split('/\r\n|\r|\n/', $pc("plan{$i}_features", $d['features']));
    $plans[] = [
        'tag'      => $d['tag'],
        'name'     => $pc("plan{$i}_name", $d['name']),
        'price'    => $pc("plan{$i}_price", $d['price']),
        'features' => array_values(array_filter(array_map('trim', $features))),
        'popular'  => $d['popular'],
    ];
}
?>

<!-- ══ PLANS ══ -->
<section class="section">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Shared Web Hosting</div>
      <h2 class="section-title">Simple, Honest Hosting Plans</h2>
      <p class="section-lead">Every plan includes a free SSL certificate, 99.99% service uptime, and 24/7 best-in-class support.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($plans as $plan): ?>
        <div class="col-md-6 col-xl-3">
          <div class="price-card <?= $plan['popular'] ? 'popular' : '' ?>">
            <?php if ($plan['popular']): ?><div class="price-badge">Most Popular</div><?php endif; ?>
            <div class="plan-name"><?= htmlspecialchars($plan['tag']) ?> · <?= htmlspecialchars($plan['name']) ?></div>
            <div class="plan-price">Rs.<?= htmlspecialchars($plan['price']) ?><small>/yr</small></div>
            <div class="plan-sub">Shared Web Hosting</div>
            <ul>
              <?php foreach ($plan['features'] as $f): ?>
                <li><?= htmlspecialchars($f) ?></li>
              <?php endforeach; ?>
              <li>Free SSL Certificate</li>
              <li>99.99% Service Uptime</li>
              <li>24/7 Best Support</li>
            </ul>
            <a href="contact.php?subject=<?= urlencode('Hosting Order — ' . $plan['name']) ?>" class="btn-grad w-100 text-center d-block">Order Now</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ FEATURES ══ -->
<section class="section alt">
  <div class="container">
    <div class="row g-5 align-items-center">
      <?php $featImg = $pc('feature_image', 'Hero-Hosting.png'); if ($featImg !== '__none__'): ?>
      <div class="col-lg-6">
        <img src="<?= htmlspecialchars($featImg) ?>" alt="Web hosting" class="img-rounded w-100">
      </div>
      <?php endif; ?>
      <div class="col-lg-6">
        <div class="section-kicker">Why Malik Hosting</div>
        <h2 class="section-title">Hosting &amp; Maintenance Made Easy and Affordable</h2>
        <div class="feat-item">
          <div class="feat-icon"><i class="fa-solid fa-lock"></i></div>
          <div>
            <h4>Free SSL on Every Plan</h4>
            <p>Every website gets HTTPS out of the box — good for trust and for search rankings.</p>
          </div>
        </div>
        <div class="feat-item">
          <div class="feat-icon"><i class="fa-solid fa-gauge-high"></i></div>
          <div>
            <h4>99.99% Uptime</h4>
            <p>Your site stays online — monitored 24 × 7 by our own engineers.</p>
          </div>
        </div>
        <div class="feat-item">
          <div class="feat-icon"><i class="fa-solid fa-headset"></i></div>
          <div>
            <h4>24/7 Best Support</h4>
            <p>Real people, real answers — whenever you need help with your hosting.</p>
          </div>
        </div>
        <div class="feat-item mb-0">
          <div class="feat-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
          <div>
            <h4>Backups &amp; Recovery</h4>
            <p>Cloud backup and disaster recovery options keep your data safe.</p>
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
          <h2>Not sure which plan fits?</h2>
          <p>Tell us about your website and we'll recommend the right package for your needs and budget.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn-grad"><i class="fa-solid fa-comments me-2"></i>Ask Our Team</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
