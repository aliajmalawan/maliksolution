<?php
declare(strict_types=1);
require_once 'includes/config.php';

$current   = 'projects.php';
$pageTitle = 'Projects';

// ── Editable content (AdminCP → Website Pages → Projects) ──
// Defaults come straight from includes/page-fields.php, so the editor
// and the page can never drift apart.
$PF = require __DIR__ . '/includes/page-fields.php';
function pf_default(array $PF, string $page, string $key): string {
    foreach ($PF[$page]['sections'] as $fields) {
        if (isset($fields[$key])) return (string)$fields[$key][2];
    }
    return '';
}
$pc = fn(string $k): string => get_content($conn, 'projects', $k, pf_default($PF, 'projects', $k));

/** One item per line, "Part 1 | Part 2 | Part 3". */
function pj_rows(string $raw, int $parts = 2): array {
    $rows = [];
    foreach (preg_split('/\R/', trim($raw)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $rows[] = array_pad(array_map('trim', explode('|', $line, $parts)), $parts, '');
    }
    return $rows;
}

/**
 * Parse the editor's project blocks (blank-line separated, 6-7 lines each)
 * into the structure the cards + modal need. First 3 blocks are featured.
 */
function pj_parse_projects(string $raw): array {
    $out = [];
    foreach (preg_split('/\R{2,}/', trim($raw)) as $block) {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $block)), fn($l) => $l !== ''));
        if (count($lines) < 6) continue;
        [$cats, $catLabel, $year, $status] = array_pad(array_map('trim', explode('|', $lines[1], 4)), 4, '');
        [$industry, $duration, $platform]  = array_pad(array_map('trim', explode('|', $lines[5], 3)), 3, '');
        $p = [
            'name' => $lines[0], 'cats' => $cats, 'catLabel' => $catLabel,
            'year' => $year, 'status' => $status !== '' ? $status : 'Live',
            'tech' => $lines[2], 'desc' => $lines[3], 'overview' => $lines[3],
            'features' => $lines[4],
            'industry' => $industry, 'duration' => $duration, 'platform' => $platform,
        ];
        if (isset($lines[6])) {
            $chipIcons = ['fa-chart-line', 'fa-circle-check'];
            foreach (array_slice(array_map('trim', explode(';', $lines[6])), 0, 2) as $ci => $chip) {
                $p['chips'][] = [$chipIcons[$ci] ?? 'fa-circle-check', $chip];
            }
        }
        $out[] = $p;
    }
    return $out;
}

/** Icon per project position (extras get a generic icon). */
$proj_icons = ['fa-graduation-cap', 'fa-cloud', 'fa-building-columns', 'fa-school', 'fa-hospital', 'fa-utensils',
               'fa-boxes-stacked', 'fa-briefcase', 'fa-house-chimney', 'fa-cart-shopping', 'fa-hand-holding-heart', 'fa-building'];
// Build project list from the editor content; attach icons by position
$projects = pj_parse_projects($pc('projects_data'));
foreach ($projects as $i => $p) {
    $projects[$i]['icon'] = $proj_icons[$i] ?? 'fa-diagram-project';
}
$featured = array_slice($projects, 0, 3);

require_once 'includes/site-header.php';

/** Shared data-attributes for the project detail modal. */
/** ERP projects preview the live UMS; everything else routes to the demo page. */
function proj_preview(array $p): string {
    return stripos($p['catLabel'], 'ERP') !== false ? 'ums/index.php' : 'demonstration.php';
}

function proj_attrs(array $p): string {
    $map = [
        'name' => $p['name'], 'cat' => $p['catLabel'], 'icon' => $p['icon'],
        'preview' => proj_preview($p),
        'overview' => $p['overview'], 'industry' => $p['industry'], 'features' => $p['features'],
        'tech' => $p['tech'], 'duration' => $p['duration'], 'platform' => $p['platform'],
        'status' => $p['status'], 'year' => $p['year'],
    ];
    $out = '';
    foreach ($map as $k => $v) $out .= ' data-p' . $k . '="' . htmlspecialchars($v) . '"';
    return $out;
}
?>

<!-- ══ 1. HERO ══ -->
<header class="hero">
  <div class="hero-blob b1"></div>
  <div class="hero-blob b2"></div>
  <div class="container position-relative">
    <div class="hero-inner">
      <div class="hero-kicker"><span class="pulse-dot"></span> <?= htmlspecialchars($pc('hero_kicker')) ?></div>
      <h1><?= preg_replace('/\*([^*]+)\*/', '<span class="grad-text">$1</span>', htmlspecialchars($pc('hero_title'))) ?></h1>
      <p class="hero-lead">
        <?= htmlspecialchars($pc('hero_lead')) ?>
      </p>
      <div class="hero-cta">
        <a href="#projects-gallery" class="btn-grad"><i class="fa-solid fa-layer-group me-2"></i>View Projects</a>
        <a href="contact.php?subject=<?= urlencode('Similar Project Request') ?>" class="btn-ghost"><i class="fa-solid fa-paper-plane me-2"></i>Request Similar Project</a>
      </div>
    </div>

    <div class="hero-shot">
      <div class="hero-mock">
        <div class="mock-bar">
          <div class="mock-dots"><i></i><i></i><i></i></div>
          <div class="mock-url"><i class="fa-solid fa-lock"></i> maliksolution.com/projects</div>
        </div>
        <div class="mock-ui" role="img" aria-label="Project portfolio dashboard preview">
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
              <div class="mock-card"><small>Projects</small><strong>500+</strong><em>&#9650; growing</em></div>
              <div class="mock-card"><small>Clients</small><strong>250+</strong><em>&#9650; happy</em></div>
              <div class="mock-card"><small>Satisfaction</small><strong>99%</strong><em>&#9650; rated</em></div>
            </div>
            <div class="mock-chart">
              <svg viewBox="0 0 600 150" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                  <linearGradient id="projArea" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#22d3ee" stop-opacity=".32"/>
                    <stop offset="100%" stop-color="#22d3ee" stop-opacity="0"/>
                  </linearGradient>
                </defs>
                <g stroke="rgba(255,255,255,.06)" stroke-width="1">
                  <line x1="0" y1="38" x2="600" y2="38"/><line x1="0" y1="76" x2="600" y2="76"/><line x1="0" y1="114" x2="600" y2="114"/>
                </g>
                <path d="M0,118 L60,104 L120,110 L180,86 L240,92 L300,64 L360,72 L420,46 L480,54 L540,30 L600,22 L600,150 L0,150 Z" fill="url(#projArea)"/>
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
      <div class="phone-mock hero-mini-phone">
        <div class="p-notch"></div>
        <div class="p-screen">
          <span class="p-bar brand"></span>
          <span class="p-bar b2"></span>
          <div class="p-tile"><small>Live Projects</small><strong>128</strong></div>
          <div class="p-tile"><small>This Year</small><strong>64</strong></div>
          <div class="p-cta">VIEW PORTFOLIO</div>
        </div>
      </div>
      <div class="tech-chip tc-1"><i class="fa-solid fa-globe"></i> Websites</div>
      <div class="tech-chip tc-2"><i class="fa-solid fa-table-columns"></i> ERP Systems</div>
      <div class="tech-chip tc-3"><i class="fa-solid fa-mobile-screen-button"></i> Mobile Apps</div>
    </div>
  </div>
</header>

<!-- ══ 2 & 3. CATEGORIES + FEATURED ══ -->
<section class="section" id="featured">
  <div class="container">
    <div class="text-center center mb-4">
      <div class="section-kicker">Featured Projects</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('feat_title')) ?></h2>
      <div class="title-bar"></div>
      <p class="section-lead"><?= htmlspecialchars($pc('feat_lead')) ?></p>
    </div>

    <!-- Category filter (also filters the gallery, no page reload) -->
    <ul class="nav demo-tab-pills mb-5" id="projFilter">
      <?php
      $cats = ['all' => 'All', 'web' => 'Web Development', 'erp' => 'ERP Software', 'mobile' => 'Mobile Applications',
               'hosting' => 'Hosting', 'custom' => 'Custom Software', 'ai' => 'AI Solutions'];
      foreach ($cats as $key => $label): ?>
        <li class="nav-item">
          <button type="button" class="nav-link filter-btn <?= $key === 'all' ? 'active' : '' ?>" data-filter="<?= $key ?>"><?= htmlspecialchars($label) ?></button>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="d-flex flex-column gap-4">
      <?php foreach ($featured as $fi => $p): ?>
        <div class="proj-item" data-cats="<?= htmlspecialchars($p['cats']) ?>">
          <div class="case-card <?= $fi % 2 === 1 ? 'rev' : '' ?>">

            <!-- screenshot side -->
            <div class="case-shot">
              <div class="case-grid-bg"></div>
              <div class="case-ico"><i class="fa-solid <?= $p['icon'] ?>"></i></div>
              <?php foreach (($p['chips'] ?? []) as $ci => [$cIcon, $cText]): ?>
                <div class="case-chip cc-<?= $ci + 1 ?>"><i class="fa-solid <?= $cIcon ?>"></i> <?= htmlspecialchars($cText) ?></div>
              <?php endforeach; ?>
            </div>

            <!-- details side -->
            <div class="case-body">
              <span class="pf-cat"><?= htmlspecialchars($p['catLabel']) ?></span>
              <h3><?= htmlspecialchars($p['name']) ?></h3>
              <p><?= htmlspecialchars($p['desc']) ?></p>
              <ul class="case-feats">
                <?php foreach (array_slice(explode(';', $p['features']), 0, 4) as $feat): ?>
                  <li><?= htmlspecialchars(trim($feat)) ?></li>
                <?php endforeach; ?>
              </ul>
              <div class="folio-tech mt-1">
                <?php foreach (array_map('trim', explode(',', $p['tech'])) as $t): ?>
                  <span><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
              </div>
              <div class="case-meta">
                <span><i class="fa-solid fa-calendar"></i>Completed <?= htmlspecialchars($p['year']) ?></span>
                <span><i class="fa-solid fa-display"></i><?= htmlspecialchars($p['platform']) ?></span>
                <span><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($p['status']) ?></span>
              </div>
              <div class="pf-actions">
                <a href="<?= proj_preview($p) ?>" class="btn-grad btn-sm-x"><i class="fa-solid fa-eye me-1"></i>Live Preview</a>
                <button type="button" class="btn-outline-navy btn-sm-x border-2" data-bs-toggle="modal" data-bs-target="#projectModal" <?= proj_attrs($p) ?>>
                  View Details
                </button>
              </div>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 4. PROJECTS GALLERY ══ -->
<section class="section alt" id="projects-gallery">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Projects Gallery</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('gal_title')) ?></h2>
      <div class="title-bar"></div>
      <p class="section-lead"><?= htmlspecialchars($pc('gal_lead')) ?></p>
    </div>
    <div class="row g-4" id="projGrid">
      <?php foreach ($projects as $p): ?>
        <div class="col-md-6 col-xl-3 proj-item" data-cats="<?= htmlspecialchars($p['cats']) ?>">
          <div class="folio-card h-100">
            <div class="folio-thumb"><i class="fa-solid <?= $p['icon'] ?>"></i></div>
            <div class="folio-body">
              <span class="pf-cat mb-2 d-inline-block"><?= htmlspecialchars($p['catLabel']) ?></span>
              <h4><?= htmlspecialchars($p['name']) ?></h4>
              <div class="folio-tech">
                <?php foreach (array_slice(array_map('trim', explode(',', $p['tech'])), 0, 3) as $t): ?>
                  <span><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
              </div>
              <button type="button" class="svc-link border-0 bg-transparent p-0" data-bs-toggle="modal" data-bs-target="#projectModal" <?= proj_attrs($p) ?>>
                Quick View <i class="fa-solid fa-arrow-right ms-1"></i>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center text-muted small d-none" id="noProjects">
      <i class="fa-solid fa-magnifying-glass-minus me-1"></i>No projects in this category yet — <a href="contact.php">ask us to build the first one</a>.
    </div>
  </div>
</section>

<!-- ══ 5. PROJECT DETAIL MODAL (shared) ══ -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px;overflow:hidden;border:none">
      <div class="pf-shot" style="min-height:190px"><i class="fa-solid fa-building" id="pm-icon"></i></div>
      <div class="modal-body p-4">
        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
          <div>
            <span class="pf-cat" id="pm-cat">Category</span>
            <h3 class="mt-2 mb-0" style="font-weight:800;color:var(--navy)" id="pm-name">Project</h3>
          </div>
          <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <p class="text-muted" style="font-size:.9rem" id="pm-overview">Overview</p>

        <div class="row g-3 mb-3">
          <div class="col-sm-6"><div class="small"><strong class="text-navy"><i class="fa-solid fa-industry me-1 text-primary"></i>Client Industry:</strong> <span id="pm-industry"></span></div></div>
          <div class="col-sm-6"><div class="small"><strong class="text-navy"><i class="fa-solid fa-clock me-1 text-primary"></i>Duration:</strong> <span id="pm-duration"></span></div></div>
          <div class="col-sm-6"><div class="small"><strong class="text-navy"><i class="fa-solid fa-display me-1 text-primary"></i>Platform:</strong> <span id="pm-platform"></span></div></div>
          <div class="col-sm-6"><div class="small"><strong class="text-navy"><i class="fa-solid fa-circle-check me-1 text-success"></i>Status:</strong> <span id="pm-status"></span> · <span id="pm-year"></span></div></div>
        </div>

        <strong class="text-navy d-block mb-2" style="font-size:.9rem"><i class="fa-solid fa-list-check me-1 text-primary"></i>Key Features</strong>
        <ul class="check-list mb-3" id="pm-features"></ul>

        <strong class="text-navy d-block mb-2" style="font-size:.9rem"><i class="fa-solid fa-code me-1 text-primary"></i>Technology Used</strong>
        <div class="folio-tech mb-4" id="pm-tech"></div>

        <div class="d-flex gap-2 flex-wrap">
          <a href="demonstration.php" id="pm-visit" class="btn-grad btn-sm-x"><i class="fa-solid fa-globe me-1"></i>Visit Website</a>
          <a href="#" id="pm-similar" class="btn-outline-navy btn-sm-x border-2">Request Similar Project</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══ 6. TECHNOLOGIES ══ -->
<section class="section" id="technologies">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Technologies We Use</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('tech_title')) ?></h2>
      <div class="title-bar"></div>
    </div>
    <div class="row g-3">
      <?php
      $tech_icon_map = [
          'php' => 'fa-brands fa-php', 'laravel' => 'fa-brands fa-laravel', 'bootstrap' => 'fa-brands fa-bootstrap',
          'mysql' => 'fa-solid fa-database', 'flutter' => 'fa-brands fa-flutter', 'react' => 'fa-brands fa-react',
          'node.js' => 'fa-brands fa-node-js', 'javascript' => 'fa-brands fa-js', 'html5' => 'fa-brands fa-html5',
          'css3' => 'fa-brands fa-css3-alt', 'tailwind css' => 'fa-solid fa-wind', 'rest api' => 'fa-solid fa-plug',
          'wordpress' => 'fa-brands fa-wordpress', 'python' => 'fa-brands fa-python', 'vue' => 'fa-brands fa-vuejs',
      ];
      $techs = [];
      foreach (pj_rows($pc('tech_list'), 1) as [$t]) {
          $techs[] = [$tech_icon_map[strtolower($t)] ?? 'fa-solid fa-code', $t];
      }
      foreach ($techs as [$icon, $label]): ?>
        <div class="col-6 col-md-4 col-xl-2">
          <div class="module-card">
            <div class="m-ico"><i class="<?= $icon ?>"></i></div>
            <strong><?= htmlspecialchars($label) ?></strong>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 7. DEVELOPMENT PROCESS ══ -->
<section class="section alt" id="process">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Our Development Process</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('proc_title')) ?></h2>
      <div class="title-bar"></div>
    </div>
    <div class="step-flow">
      <?php
      $steps = pj_rows($pc('proc_steps'));
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

<!-- ══ 8. STATISTICS (animated counters) ══ -->
<section class="stats-band" id="stats">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <?php
      $stats = [];
      foreach (pj_rows($pc('stats_list')) as [$numRaw, $label]) {
          preg_match('/^([\d,\.]+)\s*(.*)$/', $numRaw, $m);
          $stats[] = [preg_replace('/[^\d]/', '', $m[1] ?? '0'), $m[2] ?? '', $label];
      }
      foreach ($stats as [$num, $suffix, $label]): ?>
        <div class="col-6 col-md">
          <div class="stat-x">
            <strong><span class="counter" data-count="<?= $num ?>">0</span><?= $suffix ?></strong>
            <span><?= htmlspecialchars($label) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 9. INDUSTRIES ══ -->
<section class="section" id="industries">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Industries We Serve</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('ind_title')) ?></h2>
      <div class="title-bar"></div>
    </div>
    <div class="row g-3 justify-content-center">
      <?php
      $ind_icon_map = [
          'education' => 'fa-graduation-cap', 'healthcare' => 'fa-heart-pulse', 'business' => 'fa-briefcase',
          'retail' => 'fa-store', 'restaurants' => 'fa-utensils', 'construction' => 'fa-helmet-safety',
          'ngos' => 'fa-hand-holding-heart', 'real estate' => 'fa-house-chimney', 'manufacturing' => 'fa-industry',
          'logistics' => 'fa-truck-fast', 'agriculture' => 'fa-wheat-awn', 'travel' => 'fa-plane',
      ];
      $industries = [];
      foreach (pj_rows($pc('ind_list'), 1) as [$label]) {
          $industries[] = [$ind_icon_map[strtolower($label)] ?? 'fa-building', $label];
      }
      foreach ($industries as [$icon, $label]): ?>
        <div class="col-6 col-md-4 col-xl-2">
          <div class="module-card">
            <div class="m-ico"><i class="fa-solid <?= $icon ?>"></i></div>
            <strong><?= htmlspecialchars($label) ?></strong>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══ 10. TESTIMONIALS ══ -->
<section class="section alt" id="testimonials">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Client Testimonials</div>
      <h2 class="section-title"><?= htmlspecialchars($pc('testi_title')) ?></h2>
      <div class="title-bar"></div>
    </div>
    <div id="testiCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4500" data-bs-wrap="true">
      <div class="carousel-inner">
        <?php
        $testis = [];
        foreach (pj_rows($pc('testi_list'), 3) as [$name, $role, $review]) {
            $words = preg_split('/\s+/', trim($name));
            $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[count($words) - 1] ?? '', 0, 1));
            $testis[] = [$initials, $name, $role, $review];
        }
        foreach ($testis as $i => [$initials, $name, $role, $review]): ?>
          <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" data-bs-interval="4500">
            <div class="row justify-content-center">
              <div class="col-lg-8">
                <div class="testi-card text-center">
                  <div class="quote-ico mx-auto"><i class="fa-solid fa-quote-left"></i></div>
                  <div class="t-stars">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                  </div>
                  <p>&ldquo;<?= htmlspecialchars($review) ?>&rdquo;</p>
                  <div class="t-who justify-content-center">
                    <div class="t-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="text-start">
                      <strong><?= htmlspecialchars($name) ?></strong>
                      <span><?= htmlspecialchars($role) ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="carousel-indicators position-static mt-3">
        <?php foreach ($testis as $i => $t): ?>
          <button type="button" data-bs-target="#testiCarousel" data-bs-slide-to="<?= $i ?>"
                  class="<?= $i === 0 ? 'active' : '' ?>" style="width:30px;height:5px;border-radius:4px;background:<?= $i === 0 ? 'var(--blue)' : '#c6d4e6' ?>;border:none"
                  aria-label="Testimonial <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ══ 11. CTA ══ -->
<section class="section pt-0" style="padding-top:3rem !important">
  <div class="container">
    <div class="cta-band">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2><?= htmlspecialchars($pc('cta_title')) ?></h2>
          <p><?= htmlspecialchars($pc('cta_text')) ?></p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
            <a href="contact.php" class="btn-grad"><i class="fa-solid fa-paper-plane me-2"></i>Start Your Project</a>
            <a href="demonstration.php" class="btn-ghost">Book Free Consultation</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ Page scripts: category filter, detail modal, counters ══ -->
<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── Category filter (no reload) ── */
  var buttons = document.querySelectorAll('#projFilter .filter-btn');
  var items   = document.querySelectorAll('.proj-item');
  var empty   = document.getElementById('noProjects');
  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      buttons.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      var f = btn.dataset.filter, visible = 0;
      items.forEach(function (item) {
        var show = f === 'all' || (' ' + item.dataset.cats + ' ').indexOf(' ' + f + ' ') !== -1;
        item.classList.toggle('filtered-out', !show);
        if (show) visible++;
      });
      if (empty) empty.classList.toggle('d-none', visible > 0);
    });
  });

  /* ── Project detail modal ── */
  var modal = document.getElementById('projectModal');
  if (modal) {
    modal.addEventListener('show.bs.modal', function (ev) {
      var b = ev.relatedTarget; if (!b) return;
      var d = b.dataset;
      modal.querySelector('#pm-name').textContent     = d.pname;
      modal.querySelector('#pm-cat').textContent      = d.pcat;
      modal.querySelector('#pm-overview').textContent = d.poverview;
      modal.querySelector('#pm-industry').textContent = d.pindustry;
      modal.querySelector('#pm-duration').textContent = d.pduration;
      modal.querySelector('#pm-platform').textContent = d.pplatform;
      modal.querySelector('#pm-status').textContent   = d.pstatus;
      modal.querySelector('#pm-year').textContent     = d.pyear;
      modal.querySelector('#pm-icon').className       = 'fa-solid ' + d.picon;
      modal.querySelector('#pm-similar').href = 'contact.php?subject=' + encodeURIComponent('Similar Project — ' + d.pname);
      modal.querySelector('#pm-visit').href = d.ppreview || 'demonstration.php';
      var feats = modal.querySelector('#pm-features');
      feats.innerHTML = '';
      d.pfeatures.split(';').forEach(function (f) {
        var li = document.createElement('li'); li.textContent = f.trim(); feats.appendChild(li);
      });
      var tech = modal.querySelector('#pm-tech');
      tech.innerHTML = '';
      d.ptech.split(',').forEach(function (t) {
        var s = document.createElement('span'); s.textContent = t.trim(); tech.appendChild(s);
      });
    });
  }

  /* ── Animated counters (run once when visible) ── */
  var counters = document.querySelectorAll('.counter');
  if ('IntersectionObserver' in window && counters.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target, target = parseInt(el.dataset.count, 10) || 0;
        var start = null, dur = 1600;
        function tick(ts) {
          if (!start) start = ts;
          var p = Math.min((ts - start) / dur, 1);
          el.textContent = Math.floor((1 - Math.pow(1 - p, 3)) * target).toLocaleString();
          if (p < 1) requestAnimationFrame(tick); else el.textContent = target.toLocaleString();
        }
        requestAnimationFrame(tick);
        io.unobserve(el);
      });
    }, { threshold: .5 });
    counters.forEach(function (c) { io.observe(c); });
  }

  /* ── Testimonials: guaranteed auto-slide every 4.5s ── */
  var testiEl = document.getElementById('testiCarousel');
  if (testiEl && window.bootstrap) {
    var testi = bootstrap.Carousel.getOrCreateInstance(testiEl, {
      interval: 4500, ride: 'carousel', wrap: true, pause: false, touch: true
    });
    testi.cycle();
    /* indicators: keep the active bar highlighted in blue */
    var dots = document.querySelectorAll('#testiCarousel .carousel-indicators button');
    testiEl.addEventListener('slid.bs.carousel', function (e) {
      dots.forEach(function (d, i) { d.style.background = i === e.to ? 'var(--blue)' : '#c6d4e6'; });
    });
  }

  /* Safety net: if the counter animation was interrupted, show final values */
  setTimeout(function () {
    counters.forEach(function (el) {
      if (el.textContent === '0') el.textContent = parseInt(el.dataset.count, 10).toLocaleString();
    });
  }, 4000);

});
</script>

<?php require_once 'includes/site-footer.php'; ?>
