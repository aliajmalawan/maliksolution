<?php
// Feature cards — editable from the Homepage Builder (cards: icon / title / text).
$defaultCards = [
    ['icon' => '📚', 'title' => 'Quality Academics', 'text' => 'A structured curriculum from Montessori through Secondary level, taught by dedicated educators.'],
    ['icon' => '🕌', 'title' => 'Values & Character', 'text' => 'Islamic studies and character-building activities are woven into everyday school life.'],
    ['icon' => '🏆', 'title' => 'Co-curricular Growth', 'text' => 'Sports days, cultural events, and award ceremonies that celebrate every student\'s talents.'],
    ['icon' => '🧑‍🏫', 'title' => 'Caring Faculty', 'text' => 'Experienced teachers who know every child by name and nurture individual potential.'],
    ['icon' => '🏫', 'title' => 'Safe Campus', 'text' => 'A secure, purpose-built environment where parents can leave their children with confidence.'],
    ['icon' => '💻', 'title' => 'Modern Skills', 'text' => 'Computer literacy, spoken English, and presentation skills built into the timetable.'],
];
$cards = !empty($S['cards']) && is_array($S['cards']) ? $S['cards'] : $defaultCards;
?>
<?php hb_section_open($S, 'section section-alt'); ?>
  <div class="container">
    <?php hb_heading($S, 'Why Choose Us', 'The Alpine Advantage', 'We are committed to Perfection, Progress, and Prosperity in everything we do.'); ?>
    <div class="card-grid">
      <?php foreach ($cards as $card): ?>
        <?php if (trim((string)($card['title'] ?? '')) === '') continue; ?>
        <div class="info-card">
          <div class="icon" aria-hidden="true"><?= e($card['icon'] ?: '⭐') ?></div>
          <h3><?= e($card['title']) ?></h3>
          <p><?= e($card['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php hb_section_close(); ?>
