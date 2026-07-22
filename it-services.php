<?php
declare(strict_types=1);
require_once 'includes/config.php';

// Editable content (AdminCP → Website Pages → IT Services)
$pc = fn(string $k, string $d): string => get_content($conn, 'it-services', $k, $d);

$current   = 'it-services.php';
$pageTitle = 'IT Services';
$pageHero  = [
    'kicker' => 'Our Services',
    'title'  => $pc('hero_title', 'Managed IT Services'),
    'lead'   => $pc('hero_lead', 'Market leader in Managed IT Services and Solutions, IT Support, and Disaster Recovery — we keep your systems running so you can focus on growing your business.'),
];
require_once 'includes/site-header.php';
?>

<!-- ══ KEY FACTS ══ -->
<section class="section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <div class="section-kicker">Key Facts</div>
        <h2 class="section-title"><?= htmlspecialchars($pc('intro_title', 'Why Businesses Trust Our IT Services')) ?></h2>
        <p class="section-lead mb-4">
          <?= htmlspecialchars($pc('intro_text', 'Malik Solution provides an in-house helpdesk with escalation to level-3 consultants, available to all our customers — backed by engineers on staff and free monthly training.')) ?>
        </p>
        <ul class="check-list">
          <li>30+ engineers on staff</li>
          <li>24 × 7 system monitoring</li>
          <li>In-house helpdesk with level-3 escalation</li>
          <li>Free monthly training for all customers</li>
          <li>Cloud backup &amp; disaster recovery services</li>
          <li>Database administration &amp; hardware resolution</li>
        </ul>
      </div>
      <?php $introImg = $pc('intro_image', 'Hero-IT-Services.jpg'); if ($introImg !== '__none__'): ?>
      <div class="col-lg-6">
        <img src="<?= htmlspecialchars($introImg) ?>" alt="Managed IT services" class="img-rounded w-100">
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ══ SERVICE GRID ══ -->
<section class="section alt">
  <div class="container">
    <div class="text-center center mb-5">
      <div class="section-kicker">Full Portfolio</div>
      <h2 class="section-title">Everything Your IT Department Needs</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <h3>Security Services</h3>
          <p>Antivirus, anti-spam, phishing training, information security audits, penetration testing, and more.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
          <h3>Cloud &amp; Migrations</h3>
          <p>Virtualization, migrations to AWS and Azure, network and exchange migrations, OS migrations, and database upgrades.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-brands fa-windows"></i></div>
          <h3>Windows Administration</h3>
          <p>A complete portfolio of Microsoft Managed IT Services — Windows, Hyper-V, and VMware system administration, tailored to your needs.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-headset"></i></div>
          <h3>Helpdesk &amp; Outsourcing</h3>
          <p>Business IT outsourcing, helpdesk service contracts, and network &amp; IT system monitoring with fast troubleshooting.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-database"></i></div>
          <h3>Backup &amp; Disaster Recovery</h3>
          <p>Cloud backup and disaster recovery services that protect your data and keep your business running through any event.</p>
        </div>
      </div>
      <div class="col-md-6 col-xl-4">
        <div class="svc-card">
          <div class="svc-icon"><i class="fa-solid fa-network-wired"></i></div>
          <h3>Infrastructure &amp; Relocation</h3>
          <p>Desktop, server, and storage architecture and implementation, hardware issue resolution, and complete IT relocation services.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ WINDOWS MANAGED ══ -->
<section class="section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6 order-lg-2">
        <div class="section-kicker">Microsoft Specialists</div>
        <h2 class="section-title">Windows Managed IT Services at a Glance</h2>
        <p class="section-lead mb-4">
          You'll be provided a dedicated Microsoft specialist who takes the time to fully understand
          your environment, your applications, and the demands of your business — with 24 × 7 or
          business-hours coverage, and patching services to keep systems protected.
        </p>
        <div class="feat-item">
          <div class="feat-icon"><i class="fa-solid fa-user-gear"></i></div>
          <div>
            <h4>Dedicated Specialist</h4>
            <p>One expert who knows your systems — not a different voice every call.</p>
          </div>
        </div>
        <div class="feat-item">
          <div class="feat-icon"><i class="fa-solid fa-bolt"></i></div>
          <div>
            <h4>Fast Issue Resolution</h4>
            <p>Immediate access to expert engineers who diagnose and resolve issues with minimum impact on operations.</p>
          </div>
        </div>
        <div class="feat-item mb-0">
          <div class="feat-icon"><i class="fa-solid fa-arrows-rotate"></i></div>
          <div>
            <h4>Always Up to Date</h4>
            <p>Microsoft patching services keep your systems current and protected from security threats.</p>
          </div>
        </div>
      </div>
      <?php $winImg = $pc('windows_image', 'Managed-IT.png'); if ($winImg !== '__none__'): ?>
      <div class="col-lg-6 order-lg-1">
        <img src="<?= htmlspecialchars($winImg) ?>" alt="Windows managed IT services" class="img-rounded w-100">
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ══ CTA ══ -->
<section class="section pt-0">
  <div class="container">
    <div class="cta-band">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2>We keep your IT running — you focus on growing.</h2>
          <p>Managed hosting and IT services that keep your systems optimal, so you can serve your customers.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn-grad"><i class="fa-solid fa-headset me-2"></i>Talk to an Expert</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/site-footer.php'; ?>
