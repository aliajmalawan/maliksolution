<?php
// Academic programs — editable from the Homepage Builder (cards: icon / title / text / link).
$defaultCards = [
    ['icon' => '🧸', 'title' => 'Montessori & Early Years', 'text' => 'Playgroup, Nursery and Prep — learning through play, phonics and activity in a warm, caring space.', 'link' => 'programs.php'],
    ['icon' => '📖', 'title' => 'Primary (Grade 1–5)', 'text' => 'Strong foundations in English, Urdu, Maths and Science with regular assessment and parent feedback.', 'link' => 'programs.php'],
    ['icon' => '🎓', 'title' => 'Secondary (Grade 6–10)', 'text' => 'Board-focused preparation, concept-based teaching and career guidance towards matriculation.', 'link' => 'programs.php'],
];
$cards = !empty($S['cards']) && is_array($S['cards']) ? $S['cards'] : $defaultCards;
?>
<?php hb_section_open($S, 'section'); ?>
  <div class="container">
    <?php hb_heading($S, 'Academics', 'Our Programs', 'A complete academic journey — from a child\'s first classroom to matriculation.'); ?>
    <div class="programs-grid">
      <?php foreach ($cards as $i => $card): ?>
        <?php if (trim((string)($card['title'] ?? '')) === '') continue; ?>
        <div class="program-card" data-anim="up" data-anim-delay="<?= min((int)$i, 5) * 90 ?>">
          <div class="program-card-head">
            <span class="program-icon" aria-hidden="true"><?= e($card['icon'] ?: '📘') ?></span>
          </div>
          <div class="program-card-body">
            <h3><?= e($card['title']) ?></h3>
            <p><?= e($card['text']) ?></p>
            <a href="<?= e($card['link'] ?: 'programs.php') ?>" class="read-more">Learn More →</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php hb_section_close(); ?>
