<?php
// Quick-access cards right under the hero. Fully editable from the
// Homepage Builder (cards: icon / title / text / link).
$defaultCards = [
    ['icon' => '📝', 'title' => 'Admissions Open', 'text' => 'Session ' . e(get_setting($pdo, 'admission_session', '2026-27')) . ' — apply online in minutes.', 'link' => 'admission-form.php'],
    ['icon' => '📊', 'title' => 'Results', 'text' => 'Check the latest examination results.', 'link' => 'results.php'],
    ['icon' => '📥', 'title' => 'Downloads', 'text' => 'Syllabus, forms and study material.', 'link' => 'downloads.php'],
    ['icon' => '📅', 'title' => 'Events', 'text' => 'See what is happening on campus.', 'link' => 'events.php'],
];
$cards = !empty($S['cards']) && is_array($S['cards']) ? $S['cards'] : $defaultCards;
?>
<div class="quick-links">
  <div class="container">
    <div class="quick-links-grid">
      <?php foreach ($cards as $card): ?>
        <?php if (trim((string)($card['title'] ?? '')) === '') continue; ?>
        <a href="<?= e($card['link'] ?: '#') ?>" class="quick-link-card" data-anim="up">
          <span class="ql-icon" aria-hidden="true"><?= e($card['icon'] ?: '🔗') ?></span>
          <span class="ql-body">
            <strong><?= e($card['title']) ?></strong>
            <small><?= e($card['text']) ?></small>
          </span>
          <span class="ql-arrow" aria-hidden="true">→</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
