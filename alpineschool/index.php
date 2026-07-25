<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/home-sections/_helpers.php';

// Section layout is managed in Admin → Homepage Builder.
$sections = $pdo->query('SELECT * FROM homepage_sections WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
$activeKeys = array_column($sections, 'section_key');

// Shared data, loaded only when the section that needs it is active.
$slides = in_array('hero', $activeKeys, true)
    ? $pdo->query('SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order')->fetchAll() : [];
$aboutPage = in_array('about', $activeKeys, true)
    ? $pdo->query("SELECT * FROM pages WHERE slug = 'about'")->fetch() : null;
$news = in_array('news', $activeKeys, true)
    ? $pdo->query('SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3')->fetchAll() : [];
$events = [];
if (in_array('events', $activeKeys, true)) {
    $events = $pdo->query('SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3')->fetchAll();
    if (count($events) < 3) {
        $events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC LIMIT 3')->fetchAll();
    }
}
$testimonials = in_array('testimonials', $activeKeys, true)
    ? $pdo->query('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id LIMIT 3')->fetchAll() : [];
$partners = in_array('partners', $activeKeys, true)
    ? $pdo->query('SELECT * FROM partners ORDER BY sort_order, id')->fetchAll() : [];
$principalPage = in_array('principal', $activeKeys, true)
    ? $pdo->query("SELECT * FROM pages WHERE slug = 'principal-message'")->fetch() : null;
$galleryPreview = in_array('gallery-preview', $activeKeys, true)
    ? $pdo->query('SELECT * FROM gallery_images ORDER BY id DESC LIMIT 8')->fetchAll() : [];

$statsStudents = get_setting($pdo, 'stats_students', '600');
$statsFaculty  = get_setting($pdo, 'stats_faculty', '35');
$statsYears    = get_setting($pdo, 'stats_years', '8');
$statsResults  = get_setting($pdo, 'stats_results', '98');
$campus_name   = get_setting($pdo, 'campus_name');
$site_name     = get_setting($pdo, 'site_name');

require_once __DIR__ . '/includes/header.php';

foreach ($sections as $section) {
    $template = __DIR__ . '/includes/home-sections/' . basename($section['section_key']) . '.php';
    if (!is_file($template)) {
        continue;
    }
    $S = json_decode((string)$section['settings'], true) ?: [];
    include $template;
}

require_once __DIR__ . '/includes/footer.php';
