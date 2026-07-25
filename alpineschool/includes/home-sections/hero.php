<?php /* Hero slider — slides managed on the Hero Slider admin page. */ ?>
<section class="hero-slider">
  <?php foreach ($slides as $i => $slide): ?>
  <?php // Only the first slide loads eagerly (it's the LCP). The rest are fetched by JS after load. ?>
  <div class="hero-slide<?= $i === 0 ? ' active' : '' ?>"
       <?php if ($i === 0): ?>style="background-image:url('<?= BASE_URL ?>/<?= e($slide['image_path']) ?>')"<?php else: ?>data-bg="<?= BASE_URL ?>/<?= e($slide['image_path']) ?>"<?php endif; ?>>
    <div class="container">
      <div class="hero-content">
        <span class="eyebrow"><?= e(trim((string)($S['eyebrow'] ?? '')) ?: 'Welcome to ' . $campus_name) ?></span>
        <h1><?= e($slide['title']) ?></h1>
        <p><?= e($slide['subtitle']) ?></p>
        <div class="hero-actions">
          <?php if ($slide['button_text']): ?>
          <a href="<?= e($slide['button_link']) ?>" class="btn btn-primary"><?= e($slide['button_text']) ?></a>
          <?php endif; ?>
          <a href="contact.php" class="btn btn-outline">Contact Us</a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="hero-dots" id="heroDots"></div>
</section>
