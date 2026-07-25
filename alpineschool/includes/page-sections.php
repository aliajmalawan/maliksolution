<?php
declare(strict_types=1);
// Generic page sections: type registry + front-end renderer.
// Used by includes/content-page.php (rendering) and admin/pages.php (builder UI).

/** Section types available in the page builder. */
function page_section_types(): array
{
    return [
        'content' => [
            'label' => 'Text Content',
            'hint' => 'A heading and rich text.',
        ],
        'image-text' => [
            'label' => 'Image + Text',
            'hint' => 'A photo beside a heading and text, with an optional button. Image size: 1200 × 900 px (4:3).',
        ],
        'story' => [
            'label' => 'Story (framed photo + badge)',
            'hint' => 'Framed photo with a floating badge (e.g. "8+ Years"), text, and a green check-list. Image size: 1200 × 900 px (4:3).',
        ],
        'cards' => [
            'label' => 'Icon Cards',
            'hint' => 'Up to 6 cards with an icon, title and short text.',
        ],
        'gallery' => [
            'label' => 'Gallery Preview',
            'hint' => 'Latest photos from Admin → Gallery, with a button to the full gallery.',
        ],
        'stats' => [
            'label' => 'Statistics Bar',
            'hint' => 'Students / Faculty / Years / Results — numbers come from Admin → Settings.',
        ],
        'cta' => [
            'label' => 'Call-to-Action Band',
            'hint' => 'A colored band with a heading, text and a button.',
        ],
    ];
}

/** Fetch a page's active sections in display order. */
function page_sections_for(PDO $pdo, int $pageId): array
{
    $stmt = $pdo->prepare('SELECT * FROM page_sections WHERE page_id = ? AND is_active = 1 ORDER BY sort_order, id');
    $stmt->execute([$pageId]);
    return $stmt->fetchAll();
}

/** Render one section. $alt toggles the alternating (white) background. */
function render_page_section(PDO $pdo, array $sec, bool $alt): void
{
    $S = json_decode((string)($sec['settings'] ?? ''), true) ?: [];
    $cls = 'section' . ($alt ? ' section-alt' : '');

    switch ($sec['section_key']) {
        case 'content': ?>
<section class="<?= $cls ?>">
  <div class="container" style="max-width:860px;">
    <?php if (!empty($S['heading'])): ?><div class="section-header"><h2><?= e($S['heading']) ?></h2></div><?php endif; ?>
    <?= $S['body'] ?? '' ?>
  </div>
</section>
<?php
            break;

        case 'image-text':
            $img = (string)($S['image'] ?? '');
            if ($img !== '' && !is_file(dirname(__DIR__) . '/' . $img)) {
                $img = '';
            }
            $side = ($S['side'] ?? 'left') === 'right' ? 'right' : 'left';
            $imgTag = $img !== ''
                ? '<img src="' . BASE_URL . '/' . e($img) . '" alt="' . e($S['heading'] ?? 'Photo') . '" loading="lazy" decoding="async"' . img_dimensions($img) . '>'
                : '';
            $btnLabel = trim((string)($S['btn_label'] ?? ''));
            ?>
<section class="<?= $cls ?>">
  <div class="container <?= $imgTag ? 'split' : '' ?>" <?= $imgTag ? '' : 'style="max-width:860px;"' ?>>
    <?php if ($imgTag && $side === 'left') echo $imgTag; ?>
    <div>
      <?php if (!empty($S['heading'])): ?><h2><?= e($S['heading']) ?></h2><?php endif; ?>
      <?= $S['body'] ?? '' ?>
      <?php if ($btnLabel !== ''): ?>
      <p style="margin-top:18px;"><a href="<?= e(trim((string)($S['btn_link'] ?? '')) ?: 'index.php') ?>" class="btn btn-dark"><?= e($btnLabel) ?></a></p>
      <?php endif; ?>
    </div>
    <?php if ($imgTag && $side === 'right') echo $imgTag; ?>
  </div>
</section>
<?php
            break;

        case 'story':
            $img = (string)($S['image'] ?? '');
            if ($img !== '' && !is_file(dirname(__DIR__) . '/' . $img)) {
                $img = '';
            }
            $checks = array_values(array_filter(array_map('trim', (array)($S['checks'] ?? [])), fn($c) => $c !== ''));
            ?>
<section class="<?= $cls ?>">
  <div class="container split">
    <div class="about-media" data-anim="right">
      <?php if ($img !== ''): ?>
        <img src="<?= BASE_URL ?>/<?= e($img) ?>" alt="<?= e($S['heading'] ?? 'Photo') ?>" loading="lazy" decoding="async"<?= img_dimensions($img) ?>>
      <?php endif; ?>
      <?php if (trim((string)($S['badge_number'] ?? '')) !== ''): ?>
      <div class="about-badge">
        <strong><?= e($S['badge_number']) ?></strong>
        <span><?= nl2br(e((string)($S['badge_text'] ?? ''))) ?></span>
      </div>
      <?php endif; ?>
    </div>
    <div data-anim="left">
      <?php if (!empty($S['eyebrow'])): ?><span class="eyebrow"><?= e($S['eyebrow']) ?></span><?php endif; ?>
      <?php if (!empty($S['heading'])): ?><h2><?= e($S['heading']) ?></h2><?php endif; ?>
      <?= $S['body'] ?? '' ?>
      <?php if ($checks): ?>
      <ul class="check-list">
        <?php foreach ($checks as $check): ?><li><?= e($check) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php
            break;

        case 'cards':
            $cards = is_array($S['cards'] ?? null) ? $S['cards'] : [];
            $cards = array_values(array_filter($cards, fn($c) => trim((string)($c['title'] ?? '')) !== ''));
            if (!$cards) {
                break;
            } ?>
<section class="<?= $cls ?>">
  <div class="container">
    <?php if (!empty($S['heading']) || !empty($S['eyebrow']) || !empty($S['subheading'])): ?>
    <div class="section-header">
      <?php if (!empty($S['eyebrow'])): ?><span class="eyebrow"><?= e($S['eyebrow']) ?></span><?php endif; ?>
      <?php if (!empty($S['heading'])): ?><h2><?= e($S['heading']) ?></h2><?php endif; ?>
      <?php if (!empty($S['subheading'])): ?><p><?= e($S['subheading']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="card-grid">
      <?php foreach ($cards as $card): ?>
      <div class="info-card">
        <div class="icon" aria-hidden="true"><?= e($card['icon'] ?: '⭐') ?></div>
        <h3><?= e($card['title']) ?></h3>
        <p><?= e($card['text'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
            break;

        case 'gallery':
            $count = max(4, min(12, (int)($S['count'] ?? 8)));
            $photos = $pdo->query("SELECT * FROM gallery_images ORDER BY id DESC LIMIT $count")->fetchAll();
            if (!$photos) {
                break;
            } ?>
<section class="<?= $cls ?>">
  <div class="container">
    <div class="section-header">
      <h2><?= e(($S['heading'] ?? '') !== '' ? $S['heading'] : 'Photo Gallery') ?></h2>
      <?php if (!empty($S['subheading'])): ?><p><?= e($S['subheading']) ?></p><?php endif; ?>
    </div>
    <div class="gallery-grid home-gallery-grid">
      <?php foreach ($photos as $photo): ?>
      <a href="gallery.php" class="gallery-item">
        <img src="<?= BASE_URL ?>/<?= e($photo['image_path']) ?>" alt="<?= e($photo['caption'] ?: 'Gallery photo') ?>" loading="lazy" decoding="async">
      </a>
      <?php endforeach; ?>
    </div>
    <p class="text-center" style="margin-top:30px;"><a href="gallery.php" class="btn btn-dark">View Full Gallery</a></p>
  </div>
</section>
<?php
            break;

        case 'stats': ?>
<div class="stats-bar">
  <div class="container stats-grid">
    <div class="stat-item"><strong data-count-to="<?= (int)get_setting($pdo, 'stats_students', '600') ?>" data-count-suffix="+"><?= e(get_setting($pdo, 'stats_students', '600')) ?>+</strong><span>Students</span></div>
    <div class="stat-item"><strong data-count-to="<?= (int)get_setting($pdo, 'stats_faculty', '35') ?>" data-count-suffix="+"><?= e(get_setting($pdo, 'stats_faculty', '35')) ?>+</strong><span>Faculty Members</span></div>
    <div class="stat-item"><strong data-count-to="<?= (int)get_setting($pdo, 'stats_years', '8') ?>" data-count-suffix="+"><?= e(get_setting($pdo, 'stats_years', '8')) ?>+</strong><span>Years of Excellence</span></div>
    <div class="stat-item"><strong data-count-to="<?= (int)get_setting($pdo, 'stats_results', '98') ?>" data-count-suffix="%"><?= e(get_setting($pdo, 'stats_results', '98')) ?>%</strong><span>Result Rate</span></div>
  </div>
</div>
<?php
            break;

        case 'cta':
            $btnLabel = trim((string)($S['btn_label'] ?? '')) ?: 'Apply for Admission';
            $btnLink = trim((string)($S['btn_link'] ?? '')) ?: 'admissions.php'; ?>
<section class="section home-cta" style="background:linear-gradient(120deg, var(--primary), var(--primary-dark));text-align:center;">
  <div class="container">
    <?php if (!empty($S['heading'])): ?><h2 style="color:#fff;"><?= e($S['heading']) ?></h2><?php endif; ?>
    <?php if (!empty($S['text'])): ?><p style="color:#fff;opacity:.85;max-width:560px;margin:0 auto 28px;"><?= e($S['text']) ?></p><?php endif; ?>
    <div class="cta-actions">
      <a href="<?= e($btnLink) ?>" class="btn btn-primary"><?= e($btnLabel) ?></a>
    </div>
  </div>
</section>
<?php
            break;
    }
}
