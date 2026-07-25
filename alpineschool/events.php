<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$upcoming = $pdo->query('SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC')->fetchAll();
$past = $pdo->query('SELECT * FROM events WHERE event_date < CURDATE() ORDER BY event_date DESC')->fetchAll();

$pageTitle = 'Events';
$breadcrumbs = [['label' => 'Events']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>School Events</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">What's Next</span>
      <h2>Upcoming Events</h2>
    </div>
    <?php if (empty($upcoming)): ?>
      <p class="text-center" style="color:var(--text-light);">No upcoming events at the moment. Please check back soon.</p>
    <?php else: ?>
    <div class="events-grid">
      <?php foreach ($upcoming as $event): ?>
      <div class="event-card">
        <div class="event-date-block">
          <strong><?= date('d', strtotime($event['event_date'])) ?></strong>
          <span><?= date('M', strtotime($event['event_date'])) ?></span>
        </div>
        <div>
          <h3 style="font-size:16px;margin-bottom:6px;"><?= e($event['title']) ?></h3>
          <p style="font-size:14px;color:var(--text-light);margin-bottom:4px;"><?= e($event['description']) ?></p>
          <p style="font-size:13px;color:var(--accent2);font-weight:600;margin:0;">🕐 <?= e($event['event_time']) ?> · 📍 <?= e($event['location']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($past)): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <span class="eyebrow">Look Back</span>
      <h2>Past Events</h2>
    </div>
    <div class="events-grid">
      <?php foreach ($past as $event): ?>
      <div class="event-card" style="opacity:.75;">
        <div class="event-date-block" style="background:var(--secondary);">
          <strong><?= date('d', strtotime($event['event_date'])) ?></strong>
          <span><?= date('M', strtotime($event['event_date'])) ?></span>
        </div>
        <div>
          <h3 style="font-size:16px;margin-bottom:6px;"><?= e($event['title']) ?></h3>
          <p style="font-size:14px;color:var(--text-light);margin:0;"><?= e($event['location']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
