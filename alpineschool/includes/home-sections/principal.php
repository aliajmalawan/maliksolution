<?php
// Principal's message — text comes from the "principal-message" page (editable in
// Admin → Pages); the photo and headings come from the Homepage Builder.
$principalName = trim((string)($S['heading'] ?? '')) ?: 'Message from the Principal';
$excerpt = '';
if (!empty($principalPage['body'])) {
    $plain = trim(strip_tags((string)$principalPage['body']));
    $excerpt = mb_strlen($plain) > 260 ? mb_substr($plain, 0, 257) . '…' : $plain;
}
$photo = trim((string)($S['side_image'] ?? ''));
?>
<?php hb_section_open($S, 'section section-alt'); ?>
  <div class="container principal-split">
    <div class="principal-photo" data-anim="right">
      <?php if ($photo !== '' && is_file(dirname(__DIR__, 2) . '/' . $photo)): ?>
        <img src="<?= BASE_URL ?>/<?= e($photo) ?>" alt="Principal, <?= e($site_name) ?>" loading="lazy" decoding="async"<?= img_dimensions($photo) ?>>
      <?php else: ?>
        <div class="principal-photo-empty" aria-hidden="true">🎓</div>
      <?php endif; ?>
    </div>
    <div data-anim="left">
      <span class="eyebrow"><?= e(trim((string)($S['eyebrow'] ?? '')) ?: 'From the Principal\'s Desk') ?></span>
      <h2><?= e($principalName) ?></h2>
      <blockquote class="principal-quote">
        <span class="principal-quote-mark" aria-hidden="true">“</span>
        <p><?= e($excerpt ?: 'Welcome to our campus — every child who walks through our gates is met with an education rooted in discipline, curiosity, and care.') ?></p>
      </blockquote>
      <a href="principal-message.php" class="btn btn-dark">Read Full Message</a>
    </div>
  </div>
<?php hb_section_close(); ?>
