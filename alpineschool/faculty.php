<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$faculty = $pdo->query('SELECT * FROM faculty ORDER BY sort_order, name')->fetchAll();

$pageTitle = 'Faculty';
$breadcrumbs = [['label' => 'Faculty']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Our Faculty</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Meet The Team</span>
      <h2>Dedicated Educators</h2>
      <p>Our experienced and caring faculty are at the heart of everything we do at The Alpine School.</p>
    </div>

    <?php if (empty($faculty)): ?>
      <p class="text-center" style="color:var(--text-light);">Faculty profiles will be published here soon. Please check back shortly.</p>
    <?php else: ?>
    <div class="faculty-grid">
      <?php foreach ($faculty as $i => $member): ?>
      <?php
        // Initials for the no-photo avatar (first letter of the first two name words, skipping titles).
        $nameWords = preg_split('/\s+/', trim((string)$member['name'])) ?: [];
        $nameWords = array_values(array_filter($nameWords, fn($w) => !in_array(rtrim($w, '.'), ['Mr', 'Mrs', 'Miss', 'Ms', 'Dr', 'Hafiz', 'Prof'], true)));
        $initials = mb_strtoupper(mb_substr($nameWords[0] ?? 'A', 0, 1) . mb_substr($nameWords[1] ?? '', 0, 1));
      ?>
      <div class="faculty-card" data-anim="up" data-anim-delay="<?= min($i % 4, 5) * 80 ?>">
        <div class="faculty-card-head">
          <div class="faculty-avatar">
            <?php if ($member['photo_path']): ?>
              <img src="<?= BASE_URL ?>/<?= e($member['photo_path']) ?>" alt="<?= e($member['name']) ?>" loading="lazy" decoding="async">
            <?php else: ?>
              <span aria-hidden="true"><?= e($initials) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="faculty-card-body">
          <h3><?= e($member['name']) ?></h3>
          <p class="role"><?= e($member['designation']) ?></p>
          <?php if ($member['department']): ?><span class="faculty-dept"><?= e($member['department']) ?></span><?php endif; ?>
          <?php if (!empty($member['bio'])): ?><p class="faculty-bio"><?= e($member['bio']) ?></p><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section home-cta" style="background:linear-gradient(120deg, var(--primary), var(--primary-dark));text-align:center;">
  <div class="container">
    <h2 style="color:#fff;">Want to Join Our Team?</h2>
    <p style="color:#fff;opacity:.85;max-width:560px;margin:0 auto 28px;">We're always looking for passionate educators who want to shape young minds. See our current openings.</p>
    <div class="cta-actions">
      <a href="career.php" class="btn btn-primary">View Career Opportunities</a>
      <a href="contact.php" class="btn btn-outline">Contact Us</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
