<?php hb_section_open($S, 'section section-alt'); ?>
  <div class="container">
    <?php hb_heading($S, 'Mark Your Calendar', 'Upcoming Events'); ?>
    <div class="events-grid">
      <?php foreach ($events as $event): ?>
      <div class="event-card">
        <div class="event-date-block">
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
<?php hb_section_close(); ?>
