<?php if (empty($partners)) { return; } ?>
<?php hb_section_open($S, 'section section-alt'); ?>
  <div class="container">
    <?php hb_heading($S, 'Our Network', 'Partners & Affiliations'); ?>
    <div class="partners-row">
      <?php foreach ($partners as $partner): ?>
        <?php if ($partner['url']): ?><a href="<?= e($partner['url']) ?>" target="_blank" rel="noopener" title="<?= e($partner['name']) ?>"><?php endif; ?>
        <img src="<?= BASE_URL ?>/<?= e($partner['logo_path']) ?>" alt="<?= e($partner['name']) ?>" class="partner-logo" loading="lazy">
        <?php if ($partner['url']): ?></a><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
<?php hb_section_close(); ?>
