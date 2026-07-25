<?php
// Latest gallery photos (auto-updates when photos are added in Admin → Gallery).
if (empty($galleryPreview)) {
    return;
}
?>
<?php hb_section_open($S, 'section'); ?>
  <div class="container">
    <?php hb_heading($S, 'Campus Moments', 'Life at ' . $site_name, 'A glimpse of learning, play and celebration on our campus.'); ?>
    <div class="gallery-grid home-gallery-grid">
      <?php foreach ($galleryPreview as $img): ?>
      <a href="gallery.php" class="gallery-item" data-anim="zoom">
        <img src="<?= BASE_URL ?>/<?= e($img['image_path']) ?>" alt="<?= e(media_alt($pdo, $img['image_path'], (string)$img['caption'])) ?>" loading="lazy" decoding="async"<?= img_dimensions($img['image_path']) ?>>
        <?php if ($img['caption']): ?><div class="caption"><?= e($img['caption']) ?></div><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:34px;">
      <a href="gallery.php" class="btn btn-dark">View Full Gallery</a>
    </div>
  </div>
<?php hb_section_close(); ?>
