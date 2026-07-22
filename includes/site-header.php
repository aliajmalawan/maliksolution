<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$current   = $current   ?? '';
$pageTitle = $pageTitle ?? 'Malik Solution';

$siteName  = get_setting($conn, 'site_name', 'Malik Solution');
$sitePhone = get_setting($conn, 'contact_phone', '+92 333 2150925');
$siteEmail = get_setting($conn, 'contact_email', 'support@maliksolution.com');
$siteLogo  = get_setting($conn, 'logo_path', 'maliksolution.png');
// White monochrome variant for the dark navbar (falls back to the colored logo)
$siteLogoNav = get_setting($conn, 'logo_white_path', $siteLogo);
$fbUrl     = get_setting($conn, 'facebook_url', '#');
$igUrl     = get_setting($conn, 'instagram_url', '#');
$ytUrl     = get_setting($conn, 'youtube_url', '#');

// Services grouped in a dropdown keeps the navbar clean and professional
$navServices = [
    'web-development.php' => ['fa-solid fa-code', 'Web Development'],
    'mobile-app.php'      => ['fa-solid fa-mobile-screen-button', 'Mobile Apps'],
    'it-services.php'     => ['fa-solid fa-server', 'IT Services'],
    'hosting.php'         => ['fa-solid fa-cloud', 'Web Hosting'],
];
$navItems = [
    'index.php'    => 'Home',
    '__services__' => 'Services',
    'projects.php' => 'Projects',
    'about.php'    => 'About Us',
    'contact.php'  => 'Contact',
];
$servicesActive = array_key_exists($current, $navServices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($siteName) ?></title>
  <meta name="description" content="<?= htmlspecialchars($siteName) ?> — professional web development, mobile app development, software development, managed IT services, and web hosting.">
  <?php $siteFavicon = get_setting($conn, 'favicon_path', ''); if ($siteFavicon !== ''): ?>
  <link rel="icon" href="<?= htmlspecialchars($siteFavicon) ?>">
  <?php endif; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/company.css">
</head>
<body>

<!-- ── Top bar ── -->
<div class="top-bar d-none d-md-block">
  <div class="container d-flex align-items-center justify-content-between">
    <div>
      <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $sitePhone)) ?>"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($sitePhone) ?></a>
      <span class="tb-sep">|</span>
      <a href="mailto:<?= htmlspecialchars($siteEmail) ?>"><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($siteEmail) ?></a>
    </div>
    <div class="tb-social">
      <span class="me-1" style="font-size:.78rem">Web · Mobile · Software · IT Services</span>
      <a href="<?= htmlspecialchars($fbUrl) ?>" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="<?= htmlspecialchars($igUrl) ?>" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
      <a href="<?= htmlspecialchars($ytUrl) ?>" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
    </div>
  </div>
</div>

<!-- ── Navbar ── -->
<nav class="navbar navbar-expand-lg navbar-dark company-nav sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="<?= htmlspecialchars($siteLogoNav) ?>" alt="<?= htmlspecialchars($siteName) ?> Logo" class="logo">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu"
            aria-controls="mainMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php foreach ($navItems as $file => $label): ?>
          <?php if ($file === '__services__'): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle <?= $servicesActive ? 'active' : '' ?>" href="#" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false"><?= htmlspecialchars($label) ?></a>
              <ul class="dropdown-menu nav-dd shadow">
                <?php foreach ($navServices as $sFile => [$sIcon, $sLabel]): ?>
                  <li>
                    <a class="dropdown-item <?= $current === $sFile ? 'active' : '' ?>" href="<?= $sFile ?>">
                      <span class="dd-ico"><i class="<?= $sIcon ?>"></i></span><?= htmlspecialchars($sLabel) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link <?= $current === $file ? 'active' : '' ?>" href="<?= $file ?>"><?= htmlspecialchars($label) ?></a>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
          <a class="nav-link btn-quote" href="demonstration.php"><i class="fa-solid fa-circle-play me-1"></i>Demonstration</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<?php if (!empty($pageHero)): ?>
<!-- ── Page hero ── -->
<header class="page-hero">
  <div class="container">
    <div class="ph-kicker"><?= htmlspecialchars($pageHero['kicker'] ?? '') ?></div>
    <h1><?= htmlspecialchars($pageHero['title'] ?? $pageTitle) ?></h1>
    <?php if (!empty($pageHero['lead'])): ?><p><?= htmlspecialchars($pageHero['lead']) ?></p><?php endif; ?>
    <div class="breadcrumb-x">
      <a href="index.php">Home</a> <i class="fa-solid fa-angle-right mx-1" style="font-size:.65rem"></i>
      <?= htmlspecialchars($pageHero['title'] ?? $pageTitle) ?>
    </div>
  </div>
</header>
<?php endif; ?>
