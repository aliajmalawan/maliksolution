<?php

/**
 * Seeds all 16 pages from the spec. Home gets a full, realistic section set
 * (Hero -> Features -> Stats -> Why Choose Us -> Modules -> Testimonials ->
 * Clients -> CTA -> FAQ) demonstrating the section-builder end to end. Every
 * other page gets a starter Hero + Rich Text section, ready to be expanded
 * from the Admin Dashboard without any code changes. Safe to re-run: pages
 * are looked up by slug and skipped if they already exist.
 * Usage: php database/seeds/seed_pages.php
 */

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Setting;

function ensurePage(string $slug, string $title, bool $isHome = false): int
{
    $existing = Page::findBySlug($slug);
    if ($existing) {
        return (int) $existing['id'];
    }

    $id = Page::create($slug, $title, 'published');
    if ($isHome) {
        \App\Core\Database::query('UPDATE pages SET is_home = 1 WHERE id = ?', [$id]);
    }
    return $id;
}

function addStarterSections(int $pageId, string $heading, string $subheading = ''): void
{
    if (PageSection::forPage($pageId)) {
        return; // already has content — don't overwrite admin edits on re-run
    }

    PageSection::create($pageId, 'hero', [
        'heading' => $heading,
        'subheading' => $subheading,
        'image' => null,
        'button_text' => '',
        'button_url' => '',
    ]);

    PageSection::create($pageId, 'rich_text', [
        'heading' => '',
        'content' => '<p>Content coming soon — edit this page from the Admin Dashboard.</p>',
    ]);
}

// ---- 1. Home (full showcase section set) ----
$homeId = ensurePage('home', 'Home', true);

if (!PageSection::forPage($homeId)) {
    PageSection::create($homeId, 'hero', [
        'heading' => 'Run Your School Smarter with EduTop',
        'subheading' => 'The all-in-one school management platform for admissions, attendance, fees, exams, and communication — trusted by schools worldwide.',
        'image' => null,
        'button_text' => 'Book a Free Demo',
        'button_url' => '/contact',
    ]);

    PageSection::create($homeId, 'icon_grid', [
        'heading' => 'Everything Your School Needs',
        'subheading' => 'One platform for every department',
        'items' => [
            ['icon' => '📝', 'title' => 'Admissions Management', 'description' => 'Streamline enrollment from inquiry to admission with online forms and approvals.'],
            ['icon' => '⏱️', 'title' => 'Attendance Tracking', 'description' => 'Automated attendance for students and staff with real-time parent alerts.'],
            ['icon' => '💳', 'title' => 'Fee Management', 'description' => 'Collect fees online, generate invoices, and track payments effortlessly.'],
            ['icon' => '📊', 'title' => 'Examination & Report Cards', 'description' => 'Create exams, grade digitally, and generate report cards in seconds.'],
            ['icon' => '🗓️', 'title' => 'Timetable & Scheduling', 'description' => 'Build conflict-free timetables for classes, teachers, and rooms.'],
            ['icon' => '💬', 'title' => 'Parent-Teacher Communication', 'description' => 'Keep parents informed with instant messages, notices, and updates.'],
        ],
    ]);

    PageSection::create($homeId, 'stats', [
        'heading' => 'Trusted by Schools Everywhere',
        'items' => [
            ['value' => '500+', 'label' => 'Schools Onboarded'],
            ['value' => '2M+', 'label' => 'Students Managed'],
            ['value' => '99.9%', 'label' => 'Uptime'],
            ['value' => '24/7', 'label' => 'Support Available'],
        ],
    ]);

    PageSection::create($homeId, 'icon_grid', [
        'heading' => 'Why Choose EduTop',
        'subheading' => 'Built for modern schools',
        'items' => [
            ['icon' => '🎯', 'title' => 'Easy to Use', 'description' => 'Intuitive dashboards designed for teachers, parents, and admins alike.'],
            ['icon' => '🔒', 'title' => 'Secure & Reliable', 'description' => "Bank-grade encryption keeps your school's data safe."],
            ['icon' => '☁️', 'title' => 'Cloud-Based', 'description' => 'Access EduTop anytime, anywhere, on any device.'],
        ],
    ]);

    PageSection::create($homeId, 'icon_grid', [
        'heading' => 'Explore Our Modules',
        'subheading' => '',
        'items' => [
            ['icon' => '🎓', 'title' => 'Student Information System', 'description' => 'Centralized student records, documents, and history.'],
            ['icon' => '👥', 'title' => 'HR & Payroll', 'description' => 'Manage staff records, payroll, and leave requests.'],
            ['icon' => '📚', 'title' => 'Library Management', 'description' => 'Track books, issues, and returns digitally.'],
            ['icon' => '🚌', 'title' => 'Transport Management', 'description' => 'Live bus tracking and route management for student safety.'],
        ],
    ]);

    PageSection::create($homeId, 'testimonials', [
        'heading' => 'What Educators Say',
        'items' => [
            ['photo' => null, 'name' => 'Sarah Johnson', 'role' => 'Principal, Green Valley School', 'quote' => "EduTop transformed how we manage admissions and attendance. It's a game changer.", 'rating' => 5],
            ['photo' => null, 'name' => 'Michael Chen', 'role' => 'Administrator, Sunrise Academy', 'quote' => 'The fee management module alone saved us dozens of hours every month.', 'rating' => 5],
            ['photo' => null, 'name' => 'Priya Sharma', 'role' => 'Vice Principal, Oakwood International', 'quote' => 'Parents love the instant updates, and our staff loves the simplicity.', 'rating' => 5],
        ],
    ]);

    PageSection::create($homeId, 'clients', [
        'heading' => 'Trusted By Leading Schools',
        'items' => [
            ['logo' => null, 'name' => 'Green Valley School', 'url' => ''],
            ['logo' => null, 'name' => 'Sunrise Academy', 'url' => ''],
            ['logo' => null, 'name' => 'Oakwood International', 'url' => ''],
            ['logo' => null, 'name' => 'Maple Leaf High', 'url' => ''],
            ['logo' => null, 'name' => 'Riverside Academy', 'url' => ''],
            ['logo' => null, 'name' => 'Cedar Grove School', 'url' => ''],
        ],
    ]);

    PageSection::create($homeId, 'cta', [
        'heading' => 'Ready to Transform Your School?',
        'subtext' => 'Join hundreds of schools already using EduTop to simplify management and improve outcomes.',
        'button_text' => 'Get Started Today',
        'button_url' => '/contact',
        'background' => null,
    ]);

    PageSection::create($homeId, 'faq', [
        'heading' => 'Frequently Asked Questions',
        'items' => [
            ['question' => 'Is EduTop suitable for schools of any size?', 'answer' => 'Yes — EduTop scales from small schools to large multi-campus institutions.'],
            ['question' => 'Do you offer a free trial?', 'answer' => 'Yes, we offer a free demo and trial period so you can explore all features risk-free.'],
            ['question' => "Is my school's data secure?", 'answer' => 'Absolutely. We use bank-grade encryption and follow strict data protection practices.'],
            ['question' => 'Can parents access EduTop too?', 'answer' => 'Yes, parents get their own portal to track attendance, grades, fees, and announcements.'],
        ],
    ]);
}

// ---- 2-16. Every other page: starter Hero + Rich Text ----
$pages = [
    ['about', 'About', 'About EduTop', 'Empowering schools with smart technology since day one.'],
    ['features', 'Features', 'Features', 'Explore everything EduTop has to offer.'],
    ['modules', 'Modules', 'Modules', 'A closer look at every EduTop module.'],
    ['why-edutop', 'Why EduTop', 'Why EduTop', 'What sets us apart.'],
    ['pricing', 'Pricing', 'Pricing', 'Simple, transparent plans for schools of every size.'],
    ['clients', 'Clients', 'Our Clients', 'Schools that trust EduTop.'],
    ['testimonials', 'Testimonials', 'Testimonials', 'Hear from our community.'],
    ['faq', 'FAQ', 'Frequently Asked Questions', 'Answers to common questions.'],
    ['contact', 'Contact', 'Contact Us', "We'd love to hear from you."],
    ['privacy-policy', 'Privacy Policy', 'Privacy Policy', ''],
    ['terms-conditions', 'Terms & Conditions', 'Terms & Conditions', ''],
    ['cookies-policy', 'Cookies Policy', 'Cookies Policy', ''],
    ['careers', 'Careers', 'Careers', 'Join our team.'],
    ['404', '404 Not Found', 'Page Not Found', "Sorry, the page you're looking for doesn't exist."],
    ['coming-soon', 'Coming Soon', 'Something Exciting is Coming', "We're working hard to launch this page soon."],
];

foreach ($pages as [$slug, $title, $heroHeading, $heroSubheading]) {
    $id = ensurePage($slug, $title);
    addStarterSections($id, $heroHeading, $heroSubheading);

    if ($slug === 'contact') {
        $hasContactForm = array_filter(PageSection::forPage($id), fn($s) => $s['section_type'] === 'contact_form');
        if (!$hasContactForm) {
            PageSection::create($id, 'contact_form', [
                'heading' => 'Send Us a Message',
                'subtext' => "Fill out the form below and we'll get back to you shortly.",
            ]);
            echo "Added contact_form section to Contact page.\n";
        }
    }
}

echo count($pages) + 1 . " pages ensured (including Home).\n";

// ---- Menus ----
$primaryId = Menu::ensure('primary', 'Primary (Header)');
$footerId = Menu::ensure('footer', 'Footer');

if (empty(MenuItem::forMenu($primaryId))) {
    MenuItem::create($primaryId, null, 'Home', '/');
    MenuItem::create($primaryId, null, 'About', '/about');
    MenuItem::create($primaryId, null, 'Features', '/features');
    MenuItem::create($primaryId, null, 'Modules', '/modules');
    MenuItem::create($primaryId, null, 'Pricing', '/pricing');
    MenuItem::create($primaryId, null, 'Contact', '/contact');
    echo "Primary menu seeded.\n";
}

if (empty(MenuItem::forMenu($footerId))) {
    MenuItem::create($footerId, null, 'About', '/about');
    MenuItem::create($footerId, null, 'Careers', '/careers');
    MenuItem::create($footerId, null, 'Privacy Policy', '/privacy-policy');
    MenuItem::create($footerId, null, 'Terms & Conditions', '/terms-conditions');
    MenuItem::create($footerId, null, 'Cookies Policy', '/cookies-policy');
    MenuItem::create($footerId, null, 'Contact', '/contact');
    echo "Footer menu seeded.\n";
}

// ---- Baseline settings ----
$settingsDefaults = [
    'company' => [
        'site_name' => 'EduTop',
        'tagline' => 'Smart School Management Software',
    ],
    'contact' => [
        'phone' => '+1 (555) 123-4567',
        'email' => 'hello@edutop.test',
        'address' => '123 Education Ave, Springfield, USA',
        'whatsapp' => '+15551234567',
    ],
    'social' => [
        'facebook' => 'https://facebook.com/edutop',
        'twitter' => 'https://twitter.com/edutop',
        'linkedin' => 'https://linkedin.com/company/edutop',
        'instagram' => 'https://instagram.com/edutop',
    ],
];

foreach ($settingsDefaults as $group => $fields) {
    foreach ($fields as $key => $value) {
        if (Setting::get($key) === null) {
            Setting::set($key, $value, $group);
        }
    }
}
echo "Baseline settings seeded.\n";

echo "Done.\n";
