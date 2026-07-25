<?php if (empty($testimonials)) { return; } ?>
<?php hb_section_open($S, 'section'); ?>
  <div class="container">
    <?php hb_heading($S, 'What Families Say', 'Testimonials'); ?>
    <div class="testimonials-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testimonial-card">
        <p class="testimonial-quote">"<?= e($t['quote']) ?>"</p>
        <div class="testimonial-author">
          <?php if ($t['photo_path']): ?>
            <img src="<?= BASE_URL ?>/<?= e($t['photo_path']) ?>" alt="<?= e($t['name']) ?>" loading="lazy">
          <?php else: ?>
            <span class="testimonial-avatar"><?= e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?></span>
          <?php endif; ?>
          <div>
            <strong><?= e($t['name']) ?></strong>
            <?php if ($t['role']): ?><small><?= e($t['role']) ?></small><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php hb_section_close(); ?>
