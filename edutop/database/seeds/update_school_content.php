<?php

/**
 * One-time content update: rewrites Home/About/Careers/Contact/FAQ/
 * Testimonials/Why-EduTop with real branding for Edutop Pakistan School
 * Network (Quaidabad, Khushab) instead of the generic "school management
 * SaaS" placeholder copy seeded in Phase 2. Confirmed real facts only:
 * name + location. Everything else (phone/email/WhatsApp/stats) is a clear
 * placeholder for the school to fill in via the admin dashboard.
 * Safe to re-run — replaces sections on these 7 pages each time.
 * Usage: php database/seeds/update_school_content.php
 */

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoMeta;
use App\Models\Setting;

function replaceSections(string $slug, array $sections): void
{
    $page = Page::findBySlug($slug);
    if (!$page) {
        echo "SKIP (page not found): {$slug}\n";
        return;
    }

    foreach (PageSection::forPage((int) $page['id']) as $existing) {
        PageSection::delete((int) $existing['id']);
    }

    foreach ($sections as [$type, $content]) {
        PageSection::create((int) $page['id'], $type, $content);
    }

    echo "Updated sections: {$slug}\n";
}

function setSeo(string $slug, string $title, string $description): void
{
    $page = Page::findBySlug($slug);
    if (!$page) {
        return;
    }
    SeoMeta::upsert((int) $page['id'], [
        'seo_title' => $title,
        'meta_description' => $description,
    ]);
}

// ---- Settings ----
Setting::set('site_name', 'Edutop Pakistan School Network', 'company', 'text');
Setting::set('tagline', 'Pre-School to Intermediate — Nurturing Excellence in Quaidabad, Khushab', 'company', 'text');
Setting::set('address', 'Quaidabad Colony, Quaidabad, Khushab, Punjab, Pakistan', 'contact', 'text');
Setting::set('phone', '+92-XXX-XXXXXXX', 'contact', 'text');
Setting::set('email', 'info@edutop.pk', 'contact', 'text');
Setting::set('whatsapp', '+92-XXX-XXXXXXX', 'contact', 'text');
echo "Settings updated (contact fields are placeholders — edit via Admin > Settings > General).\n";

// ---- Home ----
replaceSections('home', [
    ['hero', [
        'heading' => 'Welcome to Edutop Pakistan School Network',
        'subheading' => 'Nurturing young minds from Pre-School to Intermediate in Quaidabad, Khushab — where quality education meets strong values.',
        'button_text' => 'Schedule a Campus Visit',
        'button_url' => '#demo-modal',
    ]],
    ['icon_grid', [
        'heading' => 'Our Academic Programs',
        'subheading' => 'A complete educational journey, all on one campus.',
        'items' => [
            ['icon' => '🎈', 'title' => 'Pre-School', 'description' => 'A nurturing early-years environment that builds curiosity and confidence.'],
            ['icon' => '📘', 'title' => 'Primary (Class 1–5)', 'description' => 'Strong foundations in core subjects through engaging, student-centered teaching.'],
            ['icon' => '📗', 'title' => 'Middle & Secondary (Class 6–10)', 'description' => 'Comprehensive preparation for the Matriculation examinations.'],
            ['icon' => '🎓', 'title' => 'Intermediate / College (FA/FSc)', 'description' => 'Higher-secondary education preparing students for university and beyond.'],
        ],
    ]],
    ['icon_grid', [
        'heading' => 'Why Families Choose Edutop',
        'subheading' => '',
        'items' => [
            ['icon' => '👩‍🏫', 'title' => 'Qualified & Caring Faculty', 'description' => "Experienced teachers dedicated to every student's growth."],
            ['icon' => '🛡️', 'title' => 'Safe & Disciplined Campus', 'description' => 'A secure, well-managed environment for focused learning.'],
            ['icon' => '📚', 'title' => 'Modern Curriculum', 'description' => 'A balanced academic program aligned with national standards.'],
            ['icon' => '⚽', 'title' => 'Co-Curricular Activities', 'description' => 'Sports, arts, and events that build well-rounded students.'],
        ],
    ]],
    ['stats', [
        'heading' => 'Edutop at a Glance',
        'items' => [
            ['value' => 'XX', 'label' => 'Years Serving Quaidabad'],
            ['value' => 'XX+', 'label' => 'Students Enrolled'],
            ['value' => 'XX+', 'label' => 'Qualified Teachers'],
            ['value' => 'XX%', 'label' => 'Matriculation Pass Rate'],
        ],
    ]],
    ['testimonials', [
        'heading' => 'What Our Families Say',
        'items' => [
            ['name' => 'A Parent', 'role' => 'Parent of a Class 5 Student', 'quote' => "Edutop has given my child a strong foundation and a genuine love for learning. The teachers are attentive and truly care.", 'rating' => 5],
            ['name' => 'A Student', 'role' => 'Intermediate Student', 'quote' => 'The teachers push us to do our best, and the campus feels like a second home.', 'rating' => 5],
        ],
    ]],
    ['cta', [
        'heading' => 'Admissions Are Open',
        'subtext' => 'Give your child the foundation they deserve. Schedule a visit or get in touch with our admissions team today.',
        'button_text' => 'Schedule a Campus Visit',
        'button_url' => '#demo-modal',
    ]],
]);

// ---- About ----
replaceSections('about', [
    ['hero', [
        'heading' => 'About Edutop Pakistan School Network',
        'subheading' => 'A trusted name in education, based in Quaidabad, Khushab.',
    ]],
    ['rich_text', [
        'heading' => 'Our Story',
        'content' => '<p>Edutop Pakistan School Network is an educational institution based in Quaidabad Colony, Khushab, Punjab, offering quality education from Pre-School through Intermediate/College level.</p><p>We are committed to nurturing every student\'s academic, moral, and personal growth in a safe and supportive environment.</p><p><em>Add your school\'s founding story, history, and achievements here from the admin Sections editor.</em></p>',
    ]],
    ['icon_grid', [
        'heading' => 'Our Mission &amp; Values',
        'subheading' => '',
        'items' => [
            ['icon' => '🎯', 'title' => 'Our Mission', 'description' => 'To provide accessible, high-quality education that empowers students to achieve their full potential.'],
            ['icon' => '🌟', 'title' => 'Our Vision', 'description' => 'To be a leading educational institution in Khushab, known for academic excellence and strong character.'],
            ['icon' => '🤝', 'title' => 'Our Values', 'description' => "Integrity, discipline, respect, and a genuine commitment to every student's success."],
        ],
    ]],
]);

// ---- Careers ----
replaceSections('careers', [
    ['hero', [
        'heading' => 'Careers at Edutop',
        'subheading' => 'Join a team dedicated to shaping the future of education in Khushab.',
    ]],
    ['rich_text', [
        'heading' => 'Work With Us',
        'content' => '<p>Edutop Pakistan School Network is always looking for passionate, qualified educators and staff who share our commitment to student success. We offer a supportive work environment and opportunities for professional growth.</p><p><em>List current openings here, or note that there are no vacancies at this time — editable from the admin Sections editor.</em></p>',
    ]],
    ['contact_form', [
        'heading' => 'Interested in Joining Us?',
        'subtext' => 'Send us a message with your background and interest, and our team will get in touch.',
    ]],
]);

// ---- Contact ----
$contactPage = Page::findBySlug('contact');
if ($contactPage) {
    $existingSections = PageSection::forPage((int) $contactPage['id']);
    $hero = null;
    foreach ($existingSections as $s) {
        if ($s['section_type'] === 'hero') {
            $hero = $s;
        }
    }
    if ($hero) {
        PageSection::updateContent((int) $hero['id'], [
            'heading' => 'Contact Edutop Pakistan School Network',
            'subheading' => "We'd love to hear from you. Reach out for admissions, inquiries, or a campus visit.",
            'image' => null,
            'button_text' => '',
            'button_url' => '',
        ]);
        echo "Updated hero: contact\n";
    }

    $hasMap = array_filter($existingSections, fn($s) => $s['section_type'] === 'map');
    if (!$hasMap) {
        PageSection::create((int) $contactPage['id'], 'map', [
            'heading' => 'Find Us',
            'embed_url' => '',
        ]);
        echo "Added empty map section: contact (add your Google Maps embed URL via the Sections editor)\n";
    }
}

// ---- FAQ ----
replaceSections('faq', [
    ['hero', [
        'heading' => 'Frequently Asked Questions',
        'subheading' => 'Answers to common questions about admissions, fees, and school life.',
    ]],
    ['faq', [
        'heading' => '',
        'items' => [
            ['question' => 'What levels does Edutop offer?', 'answer' => 'We offer education from Pre-School through Intermediate/College (FA/FSc), all on one campus in Quaidabad, Khushab.'],
            ['question' => 'How can I apply for admission?', 'answer' => 'You can visit our campus, call our admissions office, or use the Contact form on this website to schedule a visit and begin the enrollment process.'],
            ['question' => 'What is the fee structure?', 'answer' => 'Fee details vary by class level. Please contact our admissions office for the current fee structure.'],
            ['question' => 'Does Edutop provide transport?', 'answer' => 'Please contact the school office to confirm current transport routes and availability.'],
            ['question' => 'What are the school timings?', 'answer' => 'Please contact our office for current class timings, which may vary by level.'],
            ['question' => 'Is there a school uniform?', 'answer' => 'Yes, students are required to wear the official Edutop uniform. Details are provided at the time of admission.'],
        ],
    ]],
]);

// ---- Testimonials ----
replaceSections('testimonials', [
    ['hero', [
        'heading' => 'Testimonials',
        'subheading' => 'Hear from the families and students who are part of the Edutop community.',
    ]],
    ['testimonials', [
        'heading' => '',
        'items' => [
            ['name' => 'A Parent', 'role' => 'Parent of a Primary Student', 'quote' => 'The teachers know each child individually. My son looks forward to school every day.', 'rating' => 5],
            ['name' => 'A Parent', 'role' => 'Parent of a Matriculation Student', 'quote' => 'Consistent, disciplined, and genuinely invested in results. We have seen real progress.', 'rating' => 5],
            ['name' => 'An Alumnus', 'role' => 'Former Student', 'quote' => 'Edutop gave me the foundation I needed to succeed in college and beyond.', 'rating' => 5],
        ],
    ]],
]);

// ---- Why EduTop ----
replaceSections('why-edutop', [
    ['hero', [
        'heading' => 'Why Choose Edutop',
        'subheading' => 'What sets our school and college apart in Quaidabad, Khushab.',
    ]],
    ['icon_grid', [
        'heading' => 'What Sets Us Apart',
        'subheading' => '',
        'items' => [
            ['icon' => '🎓', 'title' => 'One Campus, Every Stage', 'description' => 'From Pre-School through Intermediate/College — no need to change schools as your child grows.'],
            ['icon' => '👩‍🏫', 'title' => 'Experienced Faculty', 'description' => 'A qualified teaching staff committed to individual student attention.'],
            ['icon' => '🛡️', 'title' => 'Safe & Disciplined Environment', 'description' => 'A secure campus with clear standards of conduct.'],
            ['icon' => '📚', 'title' => 'Strong Academic Results', 'description' => 'A consistent track record of student achievement.'],
            ['icon' => '⚽', 'title' => 'Co-Curricular Balance', 'description' => 'Sports, arts, and activities alongside academics.'],
            ['icon' => '💬', 'title' => 'Engaged Management', 'description' => "An accessible administration invested in every family's experience."],
        ],
    ]],
    ['stats', [
        'heading' => '',
        'items' => [
            ['value' => 'XX', 'label' => 'Years Serving Quaidabad'],
            ['value' => 'XX+', 'label' => 'Students Enrolled'],
            ['value' => 'XX+', 'label' => 'Qualified Teachers'],
        ],
    ]],
]);

// ---- SEO ----
setSeo('home', 'Edutop Pakistan School Network | Pre-School to Intermediate in Quaidabad, Khushab', 'Edutop Pakistan School Network offers quality education from Pre-School to Intermediate/College in Quaidabad, Khushab. Admissions open — schedule a campus visit today.');
setSeo('about', 'About Us | Edutop Pakistan School Network', 'Learn about Edutop Pakistan School Network, an educational institution in Quaidabad, Khushab offering Pre-School through Intermediate/College education.');
setSeo('careers', 'Careers | Edutop Pakistan School Network', 'Explore teaching and staff opportunities at Edutop Pakistan School Network in Quaidabad, Khushab.');
setSeo('contact', 'Contact Us | Edutop Pakistan School Network', 'Get in touch with Edutop Pakistan School Network in Quaidabad, Khushab for admissions and inquiries.');
setSeo('faq', 'FAQs | Edutop Pakistan School Network', 'Frequently asked questions about admissions, fees, and school life at Edutop Pakistan School Network.');
setSeo('testimonials', 'Testimonials | Edutop Pakistan School Network', 'Hear from Edutop Pakistan School Network families and students in Quaidabad, Khushab.');
setSeo('why-edutop', 'Why Choose Edutop | Edutop Pakistan School Network', 'Discover what sets Edutop Pakistan School Network apart as a Pre-School to Intermediate/College institution in Quaidabad, Khushab.');
echo "SEO updated for all 7 pages.\n";

// ---- Primary menu: point at the real pages instead of the old SaaS-product pages ----
$primaryMenuId = Menu::ensure('primary', 'Primary (Header)');
foreach (MenuItem::forMenu($primaryMenuId) as $existingItem) {
    MenuItem::delete((int) $existingItem['id']);
}
$navPages = ['home' => 'Home', 'about' => 'About', 'why-edutop' => 'Why Edutop', 'faq' => 'FAQ', 'testimonials' => 'Testimonials', 'careers' => 'Careers', 'contact' => 'Contact'];
foreach ($navPages as $slug => $label) {
    $page = Page::findBySlug($slug);
    if ($page) {
        $url = $page['is_home'] ? '/' : '/' . $page['slug'];
        MenuItem::create($primaryMenuId, null, $label, $url);
    }
}
echo "Primary menu rebuilt.\n";

echo "Done.\n";
