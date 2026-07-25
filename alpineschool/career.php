<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$jobs = $pdo->query('SELECT * FROM careers WHERE is_active = 1 ORDER BY created_at DESC')->fetchAll();
$email = get_setting($pdo, 'email');

$pageTitle = 'Career';
$breadcrumbs = [['label' => 'Career']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Careers at Alpine</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Join Our Team</span>
      <h2>Why Teach at Alpine?</h2>
      <p>We're always looking for passionate educators and staff who share our commitment to Perfection, Progress, and Prosperity.</p>
    </div>
    <div class="card-grid">
      <div class="info-card" data-anim="up">
        <div class="icon" aria-hidden="true">🌱</div>
        <h3>Grow Professionally</h3>
        <p>Regular teacher training, mentoring, and a leadership team that invests in your development.</p>
      </div>
      <div class="info-card" data-anim="up" data-anim-delay="90">
        <div class="icon" aria-hidden="true">🤝</div>
        <h3>Supportive Environment</h3>
        <p>A collaborative staff room, respectful culture, and administration that listens to its teachers.</p>
      </div>
      <div class="info-card" data-anim="up" data-anim-delay="180">
        <div class="icon" aria-hidden="true">🏫</div>
        <h3>Purpose-Built Campus</h3>
        <p>Modern classrooms and facilities at our Haroonabad campus, designed for effective teaching.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container" style="max-width:900px;">
    <div class="section-header">
      <span class="eyebrow">Openings</span>
      <h2>Current Openings</h2>
      <p>Apply by email with your CV — mention the position in the subject line.</p>
    </div>

    <?php if (empty($jobs)): ?>
      <p class="text-center" style="color:var(--text-light);">There are no open positions right now. Please check back later<?php if ($email): ?>, or send your CV to <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a> for future consideration<?php endif; ?>.</p>
    <?php else: ?>
      <div class="jobs-list">
        <?php foreach ($jobs as $i => $job): ?>
        <div class="job-card" data-anim="up" data-anim-delay="<?= min((int)$i, 5) * 90 ?>">
          <div class="job-card-head">
            <h3><?= e($job['title']) ?></h3>
            <span class="job-type"><?= e(ucwords(str_replace('-', ' ', $job['job_type']))) ?></span>
          </div>
          <?php if ($job['location']): ?>
          <p class="job-location"><span aria-hidden="true"><?= edu_icon('map-pin') ?></span> <?= e($job['location']) ?></p>
          <?php endif; ?>
          <?php if ($job['description']): ?><p class="job-desc"><?= nl2br(e($job['description'])) ?></p><?php endif; ?>
          <?php if ($email): ?>
            <a href="mailto:<?= e($email) ?>?subject=Application: <?= e($job['title']) ?>" class="btn btn-dark">Apply via Email</a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($email): ?>
<section class="section home-cta" style="background:linear-gradient(120deg, var(--primary), var(--primary-dark));text-align:center;">
  <div class="container">
    <h2 style="color:#fff;">Don't See the Right Role?</h2>
    <p style="color:#fff;opacity:.85;max-width:560px;margin:0 auto 28px;">Send us your CV anyway — we keep strong candidates on file and reach out when a matching position opens.</p>
    <div class="cta-actions">
      <a href="mailto:<?= e($email) ?>?subject=CV for future consideration" class="btn btn-primary">Email Your CV</a>
      <a href="contact.php" class="btn btn-outline">Contact Us</a>
    </div>
  </div>
</section>
<?php endif; ?>

<style>
.jobs-list { display:flex; flex-direction:column; gap:20px; }
.job-card {
  background:var(--white); border-radius:var(--radius); padding:28px;
  box-shadow:var(--shadow); border:1px solid var(--border); border-left:4px solid var(--accent);
  transition:var(--transition);
}
.job-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-lg); }
.job-card-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.job-card-head h3 { margin:0; font-size:19px; }
.job-type { background:rgba(126,211,33,.15); color:#4d8b0f; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:700; flex-shrink:0; }
.job-location { display:flex; align-items:center; gap:6px; color:var(--text-light); font-size:14px; margin:10px 0 12px; }
.job-location svg { width:15px; height:15px; color:var(--primary); }
.job-desc { color:var(--text-light); font-size:14.5px; line-height:1.7; margin-bottom:18px; }
@media (max-width:600px) {
  .job-card { padding:22px 18px; }
  .job-card .btn { width:100%; justify-content:center; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
