<?php
declare(strict_types=1);
// Homepage-builder section wrapper. Each section template calls
// hb_section_open($S, $defaultClasses) / hb_section_close($S) so the
// admin-configured background (color / image / video), text color, and
// overlay apply uniformly.

/** Opens the <section> wrapper, applying builder settings. */
function hb_section_open(array $S, string $classes = 'section', string $extraStyle = ''): void
{
    $style = $extraStyle;
    $bgType = $S['bg_type'] ?? 'default';

    if ($bgType === 'color' && !empty($S['bg_color'])) {
        $style .= 'background:' . e($S['bg_color']) . ';';
    } elseif ($bgType === 'image' && !empty($S['bg_image'])) {
        $style .= "background-image:url('" . BASE_URL . '/' . e($S['bg_image']) . "');background-size:cover;background-position:center;";
    }
    if (!empty($S['text_color'])) {
        $style .= 'color:' . e($S['text_color']) . ';';
    }

    echo '<section class="' . e($classes) . ' hb-sec" style="' . $style . '">';

    if ($bgType === 'video' && !empty($S['bg_video'])) {
        echo '<video class="hb-bg-video" autoplay muted loop playsinline><source src="' . BASE_URL . '/' . e($S['bg_video']) . '"></video>';
    }
    $overlay = (int)($S['overlay'] ?? 0);
    if ($overlay > 0 && in_array($bgType, ['image', 'video'], true)) {
        echo '<div class="hb-overlay" style="background:rgba(15,13,40,' . ($overlay / 100) . ');"></div>';
    }
}

function hb_section_close(): void
{
    echo '</section>';
}

/** Heading block with builder overrides (eyebrow + heading + optional sub-text). */
function hb_heading(array $S, string $defaultEyebrow, string $defaultHeading, string $defaultSub = ''): void
{
    $eyebrow = trim((string)($S['eyebrow'] ?? '')) ?: $defaultEyebrow;
    $heading = trim((string)($S['heading'] ?? '')) ?: $defaultHeading;
    $sub = trim((string)($S['subheading'] ?? '')) ?: $defaultSub;
    $headingStyle = !empty($S['text_color']) ? ' style="color:' . e($S['text_color']) . ';"' : '';

    echo '<div class="section-header">';
    if ($eyebrow !== '') {
        echo '<span class="eyebrow">' . e($eyebrow) . '</span>';
    }
    echo '<h2' . $headingStyle . '>' . e($heading) . '</h2>';
    if ($sub !== '') {
        echo '<p>' . e($sub) . '</p>';
    }
    echo '</div>';
}
