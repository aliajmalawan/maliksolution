<?php
declare(strict_types=1);
require_once 'includes/config.php';

// Editable content (AdminCP → Website Pages → Demonstration)
$pc = fn(string $k, string $d): string => get_content($conn, 'demonstration', $k, $d);

/** Render "*text*" markers as the gradient span. */
function demo_grad(string $raw): string {
    return preg_replace('/\*([^*]+)\*/', '<span class="grad-text">$1</span>', htmlspecialchars($raw));
}

/** Parse editor textarea rows: one item per line, "Part 1 | Part 2". */
function pc_rows(string $raw): array {
    $rows = [];
    foreach (preg_split('/\R/', trim($raw)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = array_map('trim', explode('|', $line, 2));
        $rows[] = [$parts[0], $parts[1] ?? ''];
    }
    return $rows;
}

$current   = 'demonstration.php';
$pageTitle = 'Demonstration';

// Ensure the contacts table exists (demo requests go to AdminCP → Contact Queries)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    subject VARCHAR(255) DEFAULT '',
    message TEXT,
    date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_demo'])) {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $company = trim($_POST['company'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $pdate   = trim($_POST['preferred_date'] ?? '');
    $notes   = trim($_POST['message'] ?? '');
    $date    = date('Y-m-d');

    $allowed_services = ['Website Development', 'Mobile Applications', 'ERP Software', 'Cloud Hosting', 'Custom Software', 'AI Solutions', 'Other'];
    if (!in_array($service, $allowed_services, true)) $service = 'Other';
    if ($pdate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pdate)) $pdate = '';

    if ($name === '' || $phone === '') {
        $error = 'Please enter your name and phone number.';
    } else {
        $subject = 'Demonstration Request — ' . $service;
        $message = "Company: " . ($company !== '' ? $company : '—') . "\n"
                 . "Preferred date: " . ($pdate !== '' ? $pdate : 'Any') . "\n"
                 . "Notes: " . ($notes !== '' ? $notes : '—');
        $stmt = mysqli_prepare($conn,
            "INSERT INTO contacts (name, email, phone, subject, message, date) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $phone, $subject, $message, $date);
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Your demonstration request has been received! Our team will contact you shortly to schedule it.';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}

require_once 'includes/site-header.php';

/** Reusable dark dashboard skeleton (inside laptop frames). */
function demo_screen(array $stats, string $url = 'maliksolution.com'): void { ?>
  <div class="hero-mock">
    <div class="mock-bar">
      <div class="mock-dots"><i></i><i></i><i></i></div>
      <div class="mock-url"><i class="fa-solid fa-lock"></i> <?= htmlspecialchars($url) ?></div>
    </div>
    <div class="mock-ui" role="img" aria-label="Software demonstration preview">
      <div class="mock-side">
        <span class="ms-brand"></span>
        <span class="ms-item active w1"></span>
        <span class="ms-item w2"></span>
        <span class="ms-item w3"></span>
        <span class="ms-item w4"></span>
        <span class="ms-item w2"></span>
      </div>
      <div class="mock-main">
        <div class="mock-top">
          <span class="mt-title"></span>
          <span class="mt-pill"></span>
          <span class="mt-avatar"></span>
        </div>
        <div class="mock-cards">
          <?php foreach ($stats as [$label, $value, $delta]): ?>
            <div class="mock-card"><small><?= htmlspecialchars($label) ?></small><strong><?= htmlspecialchars($value) ?></strong><em>&#9650; <?= htmlspecialchars($delta) ?></em></div>
          <?php endforeach; ?>
        </div>
        <div class="mock-chart">
          <svg viewBox="0 0 600 150" preserveAspectRatio="none" aria-hidden="true">
            <defs>
              <linearGradient id="demoArea<?= md5(implode('', array_column($stats, 0))) ?>" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#22d3ee" stop-opacity=".32"/>
                <stop offset="100%" stop-color="#22d3ee" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <g stroke="rgba(255,255,255,.06)" stroke-width="1">
              <line x1="0" y1="38" x2="600" y2="38"/><line x1="0" y1="76" x2="600" y2="76"/><line x1="0" y1="114" x2="600" y2="114"/>
            </g>
            <path d="M0,118 L60,104 L120,110 L180,86 L240,92 L300,64 L360,72 L420,46 L480,54 L540,30 L600,22 L600,150 L0,150 Z" fill="url(#demoArea<?= md5(implode('', array_column($stats, 0))) ?>)"/>
            <polyline points="0,118 60,104 120,110 180,86 240,92 300,64 360,72 420,46 480,54 540,30 600,22"
                      fill="none" stroke="#22d3ee" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="600" cy="22" r="5" fill="#22d3ee"/>
          </svg>
        </div>
        <div class="mock-rows">
          <div class="mock-row r1"><i></i><b></b><u></u></div>
          <div class="mock-row r2"><i></i><b></b><u></u></div>
          <div class="mock-row r3"><i></i><b></b><u></u></div>
        </div>
      </div>
    </div>
  </div>
<?php } ?>

<!-- ══ 1. HERO ══ -->
<header class="hero">
  <div class="hero-blob b1"></div>
  <div class="hero-blob b2"></div>
  <div class="container position-relative">
    <div class="hero-inner">
      <div class="hero-kicker"><span class="pulse-dot"></span> <?= htmlspecialchars($pc('hero_kicker', 'Live Product Demonstration')) ?></div>
      <h1><?= demo_grad($pc('hero_title', 'Experience Our *Solutions Live*')) ?></h1>
      <p class="hero-lead">
        <?= htmlspecialchars($pc('hero_lead', 'See our websites, ERP software, mobile apps, hosting solutions and custom software in action.')) ?>
      </p>
      <div class="hero-cta">
        <a href="#book-demo" class="btn-grad"><i class="fa-solid fa-calendar-check me-2"></i>Book Live Demo</a>
        <a href="contact.php" class="btn-ghost"><i class="fa-solid fa-envelope me-2"></i>Contact Us</a>
      </div>
    </div>

    <div class="hero-shot">
      <?php demo_screen([['Revenue', 'Rs 1.28M', '12.4%'], ['Projects', '152', '8.1%'], ['Uptime', '99.99%', 'stable']]); ?>
      <div class="phone-mock hero-mini-phone">
        <div class="p-notch"></div>
        <div class="p-screen">
          <span class="p-bar brand"></span>
          <span class="p-bar b2"></span>
          <div class="p-tile"><small>Orders</small><strong>1,248</strong></div>
          <div class="p-tile"><small>Sales</small><strong>Rs 96K</strong></div>
          <div class="p-cta">VIEW REPORT</div>
        </div>
      </div>
      <div class="tech-chip tc-1"><i class="fa-solid fa-globe"></i> Websites</div>
      <div class="tech-chip tc-2"><i class="fa-solid fa-table-columns"></i> ERP Dashboards</div>
      <div class="tech-chip tc-3"><i class="fa-solid fa-mobile-screen-button"></i> Mobile Apps</div>
    </div>
  </div>
</header>

<!-- ══ 2. CHOOSE A DEMONSTRATION ══ -->
<section class="section" id="choose-demo">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Choose a Demonstration</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('choose_title', 'What Would You Like to See?')) ?></h2>
      <div class="title-bar"></div>
      <p class="section-lead"><?= htmlspecialchars($pc('choose_lead', "Pick a solution — we'll walk you through a real, working example built by our team.")) ?></p>
    </div>
    <div class="row g-4">
      <?php
      // Icons + anchor links stay fixed by position; text comes from the editor
      $demo_icons = ['fa-solid fa-globe', 'fa-solid fa-mobile-screen-button', 'fa-solid fa-school', 'fa-solid fa-cloud', 'fa-solid fa-laptop-code', 'fa-solid fa-robot'];
      $demo_links = ['#demo-preview', '#mobile-apps', '#erp-modules', '#hosting-demo', '#demo-preview', '#book-demo'];
      $demo_cards = pc_rows($pc('choose_cards', "Website Development | Corporate sites, portfolios, and landing pages that convert visitors into customers.\nMobile Applications | Android & iOS apps with payments, maps, and push notifications — built to delight.\nERP Software | School, hospital, and business ERPs — attendance, fees, HR, accounts, and reports.\nCloud Hosting | SSD hosting with SSL, daily backups, and 24/7 monitoring — see the panel live.\nCustom Software | POS, inventory, and workflow systems tailored exactly to your business process.\nAI Solutions | Chatbots, automation, and smart analytics that put AI to work for your business."));
      foreach ($demo_cards as $i => [$title, $desc]): ?>
        <div class="col-md-6 col-xl-4">
          <div class="svc-card">
            <div class="svc-icon"><i class="<?= $demo_icons[$i] ?? 'fa-solid fa-cube' ?>"></i></div>
            <h3><?= htmlspecialchars($title) ?></h3>
            <p><?= htmlspecialchars($desc) ?></p>
            <a href="<?= $demo_links[$i] ?? '#book-demo' ?>" class="svc-link">View Demo <i class="fa-solid fa-arrow-right ms-1"></i></a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 3. INTERACTIVE DEMO PREVIEW ══ -->
<section class="section alt" id="demo-preview">
  <div class="container">
    <div class="text-center center mb-4">
      <div class="section-kicker">Interactive Preview</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('preview_title', 'One Platform, Many Solutions')) ?></h2>
      <div class="title-bar"></div>
      <p class="section-lead"><?= htmlspecialchars($pc('preview_lead', 'Switch between solution types to preview the kind of system we will demonstrate for you.')) ?></p>
    </div>

    <div class="demo-preview-wrap">
      <ul class="nav demo-tab-pills" id="demoTabs" role="tablist">
        <?php
        $tabs = [
            ['corporate', 'Corporate Website', true],
            ['school',    'School ERP',        false],
            ['hospital',  'Hospital System',   false],
            ['pos',       'Restaurant POS',    false],
            ['mobile',    'Mobile App',        false],
            ['inventory', 'Inventory Software', false],
        ];
        foreach ($tabs as [$id, $label, $active]): ?>
          <li class="nav-item" role="presentation">
            <button class="nav-link <?= $active ? 'active' : '' ?>" id="tab-<?= $id ?>" data-bs-toggle="pill"
                    data-bs-target="#pane-<?= $id ?>" type="button" role="tab"><?= htmlspecialchars($label) ?></button>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php
      /** Laptop frame opener/closer for the tab previews. */
      function frame_start(string $url): void { ?>
        <div class="hero-mock">
          <div class="mock-bar">
            <div class="mock-dots"><i></i><i></i><i></i></div>
            <div class="mock-url"><i class="fa-solid fa-lock"></i> <?= htmlspecialchars($url) ?></div>
          </div>
      <?php }
      function frame_end(): void { echo '</div>'; }

      /** Row of dark stat cards (reuses .mock-card styling). */
      function stat_cards(array $stats): void { ?>
        <div class="mock-cards">
          <?php foreach ($stats as [$label, $value, $delta]): ?>
            <div class="mock-card"><small><?= htmlspecialchars($label) ?></small><strong><?= htmlspecialchars($value) ?></strong><em>&#9650; <?= htmlspecialchars($delta) ?></em></div>
          <?php endforeach; ?>
        </div>
      <?php } ?>

      <div class="tab-content">

        <!-- Corporate Website: webpage wireframe -->
        <div class="tab-pane fade show active" id="pane-corporate" role="tabpanel">
          <?php frame_start('yourcompany.com'); ?>
          <div class="ui-screen">
            <div class="w-nav">
              <span class="w-logo"></span>
              <span class="w-link"></span><span class="w-link"></span><span class="w-link"></span><span class="w-link"></span>
              <span class="w-btn"></span>
            </div>
            <div class="w-hero">
              <span class="w-h1"></span>
              <span class="w-h1 l2"></span>
              <span class="w-sub"></span>
              <span class="w-cta"></span>
            </div>
            <div class="w-cards">
              <div class="w-card"><i class="ic"></i><b></b><u></u></div>
              <div class="w-card"><i class="ic"></i><b></b><u></u></div>
              <div class="w-card"><i class="ic"></i><b></b><u></u></div>
            </div>
          </div>
          <?php frame_end(); ?>
        </div>

        <!-- School ERP: sidebar + stats + attendance bar chart -->
        <div class="tab-pane fade" id="pane-school" role="tabpanel">
          <?php frame_start('school-erp.maliksolution.com'); ?>
          <div class="mock-ui">
            <div class="mock-side">
              <span class="ms-brand"></span>
              <span class="ms-item active w1"></span>
              <span class="ms-item w2"></span>
              <span class="ms-item w3"></span>
              <span class="ms-item w4"></span>
              <span class="ms-item w2"></span>
            </div>
            <div class="mock-main">
              <?php stat_cards([['Students', '1,842', '6%'], ['Fee Collected', 'Rs 4.6M', '11%'], ['Attendance', '94.2%', '2%']]); ?>
              <div class="erp-bars">
                <?php foreach ([62, 78, 55, 84, 70, 92, 66, 88, 74, 95, 81, 90] as $hgt): ?>
                  <i style="height:<?= $hgt ?>%"></i>
                <?php endforeach; ?>
              </div>
              <div class="mock-rows">
                <div class="mock-row r1"><i></i><b></b><u></u></div>
                <div class="mock-row r2"><i></i><b></b><u></u></div>
              </div>
            </div>
          </div>
          <?php frame_end(); ?>
        </div>

        <!-- Hospital System: appointments list + occupancy donut -->
        <div class="tab-pane fade" id="pane-hospital" role="tabpanel">
          <?php frame_start('hospital.maliksolution.com'); ?>
          <div class="ui-screen">
            <?php stat_cards([['Patients Today', '236', '8%'], ['Appointments', '184', '12%'], ['Doctors On Duty', '28', 'active']]); ?>
            <div class="ui-cols">
              <div class="h-list">
                <div class="h-row"><span class="h-av"></span><span class="h-name"></span><span class="h-sub"></span><span class="h-pill pill-ok">CHECKED IN</span></div>
                <div class="h-row"><span class="h-av"></span><span class="h-name"></span><span class="h-sub"></span><span class="h-pill pill-wait">WAITING</span></div>
                <div class="h-row"><span class="h-av"></span><span class="h-name"></span><span class="h-sub"></span><span class="h-pill pill-urgent">EMERGENCY</span></div>
                <div class="h-row"><span class="h-av"></span><span class="h-name"></span><span class="h-sub"></span><span class="h-pill pill-ok">CHECKED IN</span></div>
              </div>
              <div class="h-side">
                <svg viewBox="0 0 80 80" width="72" height="72" aria-hidden="true">
                  <circle cx="40" cy="40" r="30" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="10"></circle>
                  <circle cx="40" cy="40" r="30" fill="none" stroke="#22d3ee" stroke-width="10"
                          stroke-dasharray="152.7 35.8" stroke-dashoffset="0" transform="rotate(-90 40 40)" stroke-linecap="round"></circle>
                  <text x="40" y="45" text-anchor="middle" fill="#eaf3ff" font-size="15" font-weight="800">81%</text>
                </svg>
                <small>Bed Occupancy</small>
              </div>
            </div>
          </div>
          <?php frame_end(); ?>
        </div>

        <!-- Restaurant POS: menu grid + live receipt -->
        <div class="tab-pane fade" id="pane-pos" role="tabpanel">
          <?php frame_start('pos.maliksolution.com'); ?>
          <div class="ui-screen">
            <div class="ui-cols">
              <div class="pos-grid">
                <?php foreach (['fa-burger', 'fa-pizza-slice', 'fa-drumstick-bite', 'fa-bowl-food', 'fa-mug-hot', 'fa-ice-cream', 'fa-bottle-water', 'fa-cookie-bite'] as $food): ?>
                  <div class="pos-item"><i class="fa-solid <?= $food ?>"></i><b></b></div>
                <?php endforeach; ?>
              </div>
              <div class="pos-receipt">
                <div class="r-title">Order #412</div>
                <div class="r-line"><span>2 × Zinger Burger</span><span>Rs 760</span></div>
                <div class="r-line"><span>1 × Chicken Pizza</span><span>Rs 1,150</span></div>
                <div class="r-line"><span>3 × Soft Drink</span><span>Rs 270</span></div>
                <div class="r-line"><span>1 × Fries</span><span>Rs 250</span></div>
                <div class="r-total"><span>TOTAL</span><span>Rs 2,430</span></div>
                <div class="r-pay">PAY — CASH / CARD</div>
              </div>
            </div>
          </div>
          <?php frame_end(); ?>
        </div>

        <!-- Mobile App: phone inside the laptop + feature list -->
        <div class="tab-pane fade" id="pane-mobile" role="tabpanel">
          <?php frame_start('app.maliksolution.com'); ?>
          <div class="ui-screen ui-mobile-row">
            <div class="phone-mock">
              <div class="p-notch"></div>
              <div class="p-screen">
                <span class="p-bar brand"></span>
                <span class="p-bar b2"></span>
                <div class="p-tile"><small>Orders</small><strong>248</strong></div>
                <div class="p-tile"><small>Wallet</small><strong>Rs 12,400</strong></div>
                <div class="p-cta">CHECKOUT</div>
              </div>
            </div>
            <div class="mf-list">
              <div class="mf-item"><i class="fa-brands fa-android"></i> Android &amp; iOS</div>
              <div class="mf-item"><i class="fa-solid fa-credit-card"></i> Payment Gateway</div>
              <div class="mf-item"><i class="fa-solid fa-map-location-dot"></i> Live Order Tracking</div>
              <div class="mf-item"><i class="fa-solid fa-bell"></i> Push Notifications</div>
              <div class="mf-item"><i class="fa-solid fa-fire"></i> Firebase Backend</div>
            </div>
          </div>
          <?php frame_end(); ?>
        </div>

        <!-- Inventory: stock levels with low-stock alerts -->
        <div class="tab-pane fade" id="pane-inventory" role="tabpanel">
          <?php frame_start('inventory.maliksolution.com'); ?>
          <div class="ui-screen">
            <?php stat_cards([['Stock Items', '8,240', '3%'], ['Low Stock', '17', 'alerts'], ['Purchases', 'Rs 2.1M', '9%']]); ?>
            <div class="inv-rows">
              <div class="inv-row"><span class="inv-ic"></span><span class="inv-name"></span><div class="inv-track"><div class="inv-fill" style="width:86%"></div></div><span class="inv-pct">86%</span></div>
              <div class="inv-row"><span class="inv-ic"></span><span class="inv-name"></span><div class="inv-track"><div class="inv-fill" style="width:64%"></div></div><span class="inv-pct">64%</span></div>
              <div class="inv-row"><span class="inv-ic"></span><span class="inv-name"></span><div class="inv-track"><div class="inv-fill low" style="width:12%"></div></div><span class="inv-pct">12%</span></div>
              <div class="inv-row"><span class="inv-ic"></span><span class="inv-name"></span><div class="inv-track"><div class="inv-fill" style="width:73%"></div></div><span class="inv-pct">73%</span></div>
            </div>
          </div>
          <?php frame_end(); ?>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- ══ 4. ERP MODULES ══ -->
<section class="section" id="erp-modules">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">ERP Software</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('modules_title', 'Every Module Your Institution Needs')) ?></h2>
      <div class="title-bar"></div>
      <p class="section-lead"><?= htmlspecialchars($pc('modules_lead', 'Our ERP systems ship with complete, ready-to-use modules — see any of them live in your demo.')) ?></p>
    </div>
    <div class="row g-3">
      <?php
      $module_icons = ['fa-gauge', 'fa-user-graduate', 'fa-clipboard-user', 'fa-money-bill-wave', 'fa-users-gear', 'fa-file-invoice-dollar',
                       'fa-calculator', 'fa-book-open', 'fa-bus', 'fa-bed', 'fa-file-pen', 'fa-chart-pie'];
      $modules = pc_rows($pc('modules_list', "Dashboard\nStudents\nAttendance\nFee Collection\nHR\nPayroll\nAccounts\nLibrary\nTransport\nHostel\nExams\nReports"));
      foreach ($modules as $i => [$label, $unused]): ?>
        <div class="col-6 col-md-4 col-xl-2">
          <div class="module-card">
            <div class="m-ico"><i class="fa-solid <?= $module_icons[$i] ?? 'fa-cube' ?>"></i></div>
            <strong><?= htmlspecialchars($label) ?></strong>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 5. PORTFOLIO PREVIEW ══ -->
<section class="section alt" id="portfolio">
  <div class="container">
    <div class="text-center center mb-4">
      <div class="section-kicker">Portfolio Preview</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('folio_title', 'Built by Malik Solution')) ?></h2>
      <div class="title-bar"></div>
      <p class="section-lead"><?= htmlspecialchars($pc('folio_lead', 'A taste of what we can demonstrate — swipe or scroll to explore.')) ?></p>
    </div>
    <div class="folio-scroll">
      <?php
      $folio_icons = ['fa-building', 'fa-school', 'fa-graduation-cap', 'fa-hospital', 'fa-cart-shopping', 'fa-briefcase'];
      $folio = pc_rows($pc('folio_list', "Corporate Website | PHP, Bootstrap, MySQL\nSchool Website + ERP | Laravel, MySQL, Bootstrap\nCollege Website | PHP, JavaScript, MySQL\nHospital Website | Laravel, React, MySQL\nE-Commerce Website | WordPress, WooCommerce\nBusiness Website | PHP, Bootstrap, AJAX"));
      foreach ($folio as $i => [$title, $techRaw]): ?>
        <div class="folio-card">
          <div class="folio-thumb"><i class="fa-solid <?= $folio_icons[$i] ?? 'fa-globe' ?>"></i></div>
          <div class="folio-body">
            <h4><?= htmlspecialchars($title) ?></h4>
            <div class="folio-tech">
              <?php foreach (array_filter(array_map('trim', explode(',', $techRaw))) as $t): ?><span><?= htmlspecialchars($t) ?></span><?php endforeach; ?>
            </div>
            <a href="#book-demo" class="svc-link">View Demo <i class="fa-solid fa-arrow-right ms-1"></i></a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 6. MOBILE APPLICATIONS ══ -->
<section class="section" id="mobile-apps">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <div class="phone-wrap">
          <div class="phone-mock tilt-l">
            <div class="p-notch"></div>
            <div class="p-screen">
              <span class="p-bar brand"></span>
              <span class="p-bar b2"></span>
              <div class="p-tile"><small>Today's Orders</small><strong>248</strong></div>
              <div class="p-tile"><small>Revenue</small><strong>Rs 84,200</strong></div>
              <div class="p-tile"><small>New Customers</small><strong>36</strong></div>
              <div class="p-cta">TRACK DELIVERY</div>
            </div>
          </div>
          <div class="phone-mock tilt-r">
            <div class="p-notch"></div>
            <div class="p-screen">
              <span class="p-bar brand"></span>
              <span class="p-bar b1"></span>
              <div class="p-tile"><small>Attendance</small><strong>94.2%</strong></div>
              <div class="p-tile"><small>Fee Due</small><strong>Rs 12,500</strong></div>
              <div class="p-tile"><small>Next Exam</small><strong>24 Aug</strong></div>
              <div class="p-cta">PAY NOW</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="section-kicker">Mobile Applications</div>
        <h2 class="section-title"><?= htmlspecialchars($pc('mob_title', 'Native Feel, Real Business Power')) ?></h2>
        <div class="title-bar"></div>
        <p class="section-lead mb-4"><?= htmlspecialchars($pc('mob_lead', 'Every mobile demo shows a real, installable app — complete with the integrations your business needs.')) ?></p>
        <div class="row g-3">
          <?php
          $mobtech = [
              ['fa-brands fa-android', 'Android'], ['fa-brands fa-apple', 'iPhone (iOS)'],
              ['fa-brands fa-flutter', 'Flutter'], ['fa-brands fa-react', 'React Native'],
              ['fa-solid fa-map-location-dot', 'Google Maps'], ['fa-solid fa-credit-card', 'Payment Gateway'],
              ['fa-solid fa-fire', 'Firebase'], ['fa-solid fa-bell', 'Push Notifications'],
          ];
          foreach ($mobtech as [$icon, $label]): ?>
            <div class="col-6 col-md-3 col-lg-6 col-xl-3">
              <div class="module-card">
                <div class="m-ico"><i class="<?= $icon ?>"></i></div>
                <strong><?= htmlspecialchars($label) ?></strong>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ 7. HOSTING DEMONSTRATION ══ -->
<section class="section alt" id="hosting-demo">
  <div class="container">
    <div class="row g-5 align-items-center">
      <?php $hostImg = $pc('hosting_image', 'Hero-Hosting.png'); if ($hostImg !== '__none__'): ?>
      <div class="col-lg-6">
        <div class="position-relative">
          <img src="<?= htmlspecialchars($hostImg) ?>" alt="Cloud hosting infrastructure" class="img-rounded w-100">
          <div class="tech-chip" style="position:absolute;top:8%;left:-14px"><i class="fa-solid fa-lock" style="color:#42d392"></i> Free SSL</div>
          <div class="tech-chip" style="position:absolute;bottom:10%;right:-10px"><i class="fa-solid fa-rotate" style="color:#61dafb"></i> Daily Backup</div>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-lg-6">
        <div class="section-kicker">Hosting Demonstration</div>
        <h2 class="section-title"><?= htmlspecialchars($pc('host_title', "See Malik Hosting's Control Panel Live")) ?></h2>
        <div class="title-bar"></div>
        <p class="section-lead mb-4"><?= htmlspecialchars($pc('host_lead', "In your demo we'll create a live hosting account in front of you — domain, SSL, email, and backups configured in minutes.")) ?></p>
        <?php
        $host_feats = pc_rows($pc('host_list', "SSD Hosting\nDaily Backup\nCloud Servers\nFree SSL Certificate\nDomain Management\nAdvanced Security\n24/7 Monitoring\n99.99% Uptime"));
        $host_cols  = array_chunk(array_column($host_feats, 0), (int)ceil(max(1, count($host_feats)) / 2));
        ?>
        <div class="row">
          <?php foreach ($host_cols as $col): ?>
            <div class="col-sm-6">
              <ul class="check-list">
                <?php foreach ($col as $feat): ?><li><?= htmlspecialchars($feat) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
        </div>
        <a href="hosting.php" class="btn-outline-navy mt-3"><i class="fa-solid fa-cloud me-2"></i>View Hosting Plans</a>
      </div>
    </div>
  </div>
</section>

<!-- ══ 8. HOW DEMONSTRATION WORKS ══ -->
<section class="section" id="how-it-works">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">How It Works</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('how_title', 'From Demo to Delivery in 5 Steps')) ?></h2>
      <div class="title-bar"></div>
    </div>
    <div class="step-flow">
      <?php
      $steps = pc_rows($pc('how_steps', "Book Demo | Fill the form below or WhatsApp us — pick a day that suits you.\nRequirement Discussion | We listen first: your business, your goals, your budget.\nLive Demonstration | A real working system, demonstrated live — ask anything.\nQuotation | A clear written quote with price, timeline, and deliverables.\nProject Starts | Approve, and our team gets to work the same week."));
      $last = count($steps) - 1;
      foreach ($steps as $i => [$title, $desc]): ?>
        <div class="step-card">
          <div class="step-num"><?= $i + 1 ?></div>
          <h4><?= htmlspecialchars($title) ?></h4>
          <p><?= htmlspecialchars($desc) ?></p>
        </div>
        <?php if ($i < $last): ?><div class="step-arrow"><i class="fa-solid fa-arrow-right-long"></i></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 9. WHY CLIENTS CHOOSE US ══ -->
<section class="section alt" id="why-us">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Why Malik Solution</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('why_title', 'Why Clients Choose Malik Solution')) ?></h2>
      <div class="title-bar"></div>
    </div>
    <div class="row g-4">
      <?php
      $why_icons = ['fa-people-group', 'fa-bolt', 'fa-wallet', 'fa-microchip', 'fa-shield-halved', 'fa-headset'];
      $whys = pc_rows($pc('why_cards', "Experienced Team | 30+ engineers with expert teams dedicated to each service.\nFast Delivery | Agile process — most demos become live projects within weeks.\nAffordable Pricing | The right solution for your budget, from Rs.1,999/yr hosting up.\nLatest Technologies | React, Laravel, Flutter, AWS, Azure — always current, never outdated.\nSecure Development | Security audits, penetration testing, and safe coding standards.\n24/7 Support | Round-the-clock monitoring and a helpdesk that actually answers."));
      foreach ($whys as $i => [$title, $desc]): ?>
        <div class="col-md-6 col-xl-4">
          <div class="svc-card text-center">
            <div class="svc-icon mx-auto"><i class="fa-solid <?= $why_icons[$i] ?? 'fa-star' ?>"></i></div>
            <h3><?= htmlspecialchars($title) ?></h3>
            <p class="mb-0"><?= htmlspecialchars($desc) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 10. BOOK FREE DEMONSTRATION ══ -->
<section class="section" id="book-demo">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Book Now</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('intro_title', 'Book Your Free Demonstration')) ?></h2>
      <div class="title-bar"></div>
      <p class="section-lead">
        <?= htmlspecialchars($pc('intro_text', 'No cost, no obligation — a 30-minute live session on WhatsApp, Zoom, or at our office.')) ?>
      </p>
    </div>

    <?php
    $bPhone = get_setting($conn, 'contact_phone', '0346-4890875');
    $bEmail = get_setting($conn, 'contact_email', 'info@maliksolution.com');
    $bWa    = preg_replace('/[^0-9]/', '', $bPhone);
    ?>
    <div class="row justify-content-center">
      <div class="col-xl-10">
        <div class="book-wrap">

          <!-- Left — info panel -->
          <div class="book-side">
            <h3><i class="fa-solid fa-circle-play me-2" style="color:#7dd3fc"></i>What Happens Next?</h3>
            <p class="bs-sub">Submit the form and here's exactly what to expect:</p>
            <div class="bs-step"><span class="n">1</span><span>Our team calls you within a few working hours to confirm your slot.</span></div>
            <div class="bs-step"><span class="n">2</span><span>You get a 30-minute live demo — WhatsApp, Zoom, or at our office.</span></div>
            <div class="bs-step"><span class="n">3</span><span>We send a clear written quotation. No pressure, no obligation.</span></div>
            <div class="bs-divider"></div>
            <div class="bs-contact">
              <i class="fa-solid fa-phone"></i>
              <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $bPhone)) ?>"><?= htmlspecialchars($bPhone) ?></a>
            </div>
            <div class="bs-contact">
              <i class="fa-solid fa-envelope"></i>
              <a href="mailto:<?= htmlspecialchars($bEmail) ?>"><?= htmlspecialchars($bEmail) ?></a>
            </div>
            <div class="bs-contact">
              <i class="fa-solid fa-clock"></i>
              <span>Monday – Saturday, 9 AM – 6 PM</span>
            </div>
            <a class="bs-wa" href="https://wa.me/<?= htmlspecialchars($bWa) ?>?text=<?= urlencode('Assalam o Alaikum! I would like to book a free demonstration.') ?>">
              <i class="fa-brands fa-whatsapp me-2"></i>Book Instantly on WhatsApp
            </a>
          </div>

          <!-- Right — form -->
          <div class="book-form">
            <?php if ($success !== ''): ?>
              <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?></div>
            <?php elseif ($error !== ''): ?>
              <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="d-name"><i class="fa-solid fa-user"></i>Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="d-name" name="name" required placeholder="Full name">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="d-phone"><i class="fa-brands fa-whatsapp"></i>Phone / WhatsApp <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="d-phone" name="phone" required placeholder="+92 3xx xxxxxxx">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="d-email"><i class="fa-solid fa-envelope"></i>Email</label>
                  <input type="email" class="form-control" id="d-email" name="email" placeholder="you@example.com">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="d-company"><i class="fa-solid fa-building"></i>Company</label>
                  <input type="text" class="form-control" id="d-company" name="company" placeholder="Business name (optional)">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="d-service"><i class="fa-solid fa-layer-group"></i>Interested Service <span class="text-danger">*</span></label>
                  <select class="form-select" id="d-service" name="service" required>
                    <option value="Website Development">Website Development</option>
                    <option value="Mobile Applications">Mobile Applications</option>
                    <option value="ERP Software">ERP Software</option>
                    <option value="Cloud Hosting">Cloud Hosting</option>
                    <option value="Custom Software">Custom Software</option>
                    <option value="AI Solutions">AI Solutions</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="d-date"><i class="fa-solid fa-calendar-day"></i>Preferred Date</label>
                  <input type="date" class="form-control" id="d-date" name="preferred_date" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12">
                  <label class="form-label" for="d-message"><i class="fa-solid fa-message"></i>Message</label>
                  <textarea class="form-control" id="d-message" name="message" rows="4"
                            placeholder="Tell us about your business or what you'd like to see…"></textarea>
                </div>
                <div class="col-12">
                  <button type="submit" name="submit_demo" value="1" class="book-submit">
                    <i class="fa-solid fa-calendar-check me-2"></i>Book Free Demonstration
                  </button>
                  <p class="book-note"><i class="fa-solid fa-shield-halved"></i>Your information is safe — we never share your details.</p>
                </div>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
