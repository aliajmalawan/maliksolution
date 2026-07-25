<?php hb_section_open($S, 'section'); ?>
  <div class="container split">
    <?php
    // Prefer the admin-set side image; otherwise use the first real gallery photo
    // (paths change when images are optimized to WebP), falling back to the logo.
    $aboutImg = trim((string)($S['side_image'] ?? ''));
    if ($aboutImg === '' || !is_file(dirname(__DIR__, 2) . '/' . $aboutImg)) {
        $galleryPick = $pdo->query('SELECT image_path FROM gallery_images ORDER BY id LIMIT 1')->fetch();
        $aboutImg = $galleryPick['image_path'] ?? $logo_path;
    }
    ?>
    <div class="about-media" data-anim="fade">
      <img src="<?= BASE_URL ?>/<?= e($aboutImg) ?>" alt="Students at <?= e($site_name) ?>" loading="lazy" decoding="async" data-parallax="0.12"<?= img_dimensions($aboutImg) ?>>
      <div class="about-badge">
        <strong><?= e($statsYears ?? get_setting($pdo, 'stats_years', '8')) ?>+</strong>
        <span>Years of<br>Excellence</span>
      </div>
    </div>
    <div>
      <span class="eyebrow"><?= e(trim((string)($S['eyebrow'] ?? '')) ?: 'Welcome to ' . $site_name) ?></span>
      <h2><?= e(trim((string)($S['heading'] ?? '')) ?: ($aboutPage['title'] ?? 'About Us')) ?></h2>
      <?= $aboutPage['body'] ?? '' ?>
      <ul class="check-list">
        <li>Experienced &amp; caring faculty</li>
        <li>Values-based, modern curriculum</li>
        <li>Safe and nurturing campus environment</li>
        <li>Strong focus on character building</li>
      </ul>
      <a href="about.php" class="btn btn-dark">Read More About Us</a>
    </div>
  </div>
<?php hb_section_close(); ?>
