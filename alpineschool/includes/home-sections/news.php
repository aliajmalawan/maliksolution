<?php hb_section_open($S, 'section'); ?>
  <div class="container">
    <?php hb_heading($S, 'Stay Updated', 'Latest News'); ?>
    <div class="news-grid">
      <?php foreach ($news as $item): ?>
      <div class="news-card">
        <img src="<?= BASE_URL ?>/<?= e($item['image_path']) ?>" alt="<?= e(media_alt($pdo, $item['image_path'], $item['title'])) ?>" loading="lazy" decoding="async"<?= img_dimensions($item['image_path']) ?>>
        <div class="news-card-body">
          <span class="news-date"><?= format_date($item['published_at']) ?></span>
          <h3><?= e($item['title']) ?></h3>
          <p><?= e($item['excerpt']) ?></p>
          <a href="news-detail.php?slug=<?= e($item['slug']) ?>" class="read-more">Read More →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php hb_section_close(); ?>
