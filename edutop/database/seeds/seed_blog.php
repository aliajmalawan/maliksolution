<?php

/**
 * Seeds a small set of demo blog categories, tags, and published posts —
 * enough to demonstrate listing, pagination, category/tag filtering, and
 * related posts without Phase 2-scale content. Idempotent (checks by slug).
 * Usage: php database/seeds/seed_blog.php
 */

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Sanitizer;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;

function ensureCategory(string $name): int
{
    $slug = Sanitizer::slug($name);
    $existing = BlogCategory::findBySlug($slug);
    return $existing ? (int) $existing['id'] : BlogCategory::create($name, $slug);
}

function ensureTag(string $name): int
{
    $slug = Sanitizer::slug($name);
    $existing = BlogTag::findBySlug($slug);
    return $existing ? (int) $existing['id'] : BlogTag::create($name, $slug);
}

$categoryIds = [
    'product' => ensureCategory('Product Updates'),
    'tips' => ensureCategory('School Management Tips'),
];

$tagIds = [
    'attendance' => ensureTag('Attendance'),
    'fees' => ensureTag('Fees'),
    'admissions' => ensureTag('Admissions'),
];

echo "Categories and tags seeded.\n";

$posts = [
    [
        'title' => '5 Ways EduTop Simplifies Fee Collection',
        'excerpt' => 'From automated invoices to real-time payment tracking, here is how schools are cutting fee-collection time in half with EduTop.',
        'content' => '<p>Collecting fees on time is one of the biggest administrative headaches for schools. EduTop\'s fee management module was built to remove that friction entirely.</p>'
            . '<h3>1. Automated Invoicing</h3><p>Invoices generate automatically each billing cycle, with no manual spreadsheet work required.</p>'
            . '<h3>2. Online Payments</h3><p>Parents can pay directly through the parent portal, and payment status updates in real time.</p>'
            . '<h3>3. Overdue Reminders</h3><p>Automatic reminders go out to parents before and after a due date, reducing late payments.</p>'
            . '<h3>4. Clear Reporting</h3><p>Administrators get a live dashboard of collected, pending, and overdue fees across the school.</p>'
            . '<h3>5. Flexible Plans</h3><p>Support for installment plans, scholarships, and custom fee structures per student.</p>'
            . '<p>Together, these features have helped schools cut fee-collection administrative time by more than half.</p>',
        'category' => 'product',
        'tags' => ['fees'],
    ],
    [
        'title' => 'How Digital Attendance Improves Accountability',
        'excerpt' => 'Paper attendance registers are error-prone and slow. Here is what changes when a school switches to digital attendance tracking.',
        'content' => '<p>Attendance tracking might seem like a small piece of school administration, but it has an outsized effect on accountability and safety.</p>'
            . '<h3>Real-Time Visibility</h3><p>Teachers mark attendance from any device, and administrators see school-wide attendance instantly instead of waiting on paper registers.</p>'
            . '<h3>Automatic Parent Alerts</h3><p>Parents are notified immediately if their child is marked absent, closing the loop between school and home.</p>'
            . '<h3>Accurate Reporting</h3><p>Attendance trends and patterns become visible over time, helping identify students who may need extra support.</p>'
            . '<p>Digital attendance is often the first module schools adopt with EduTop — and the one with the fastest, most visible impact.</p>',
        'category' => 'tips',
        'tags' => ['attendance'],
    ],
    [
        'title' => 'A Simpler Admissions Process, Start to Finish',
        'excerpt' => 'From the first inquiry to the final enrollment decision, see how EduTop streamlines the admissions journey for schools and families alike.',
        'content' => '<p>Admissions season is high-stakes for both schools and families. EduTop\'s admissions module keeps every application organized and moving forward.</p>'
            . '<h3>Online Application Forms</h3><p>Families apply directly through a branded online form — no paper packets required.</p>'
            . '<h3>Application Tracking</h3><p>Admissions staff can track every applicant\'s status, documents, and communication in one place.</p>'
            . '<h3>Faster Decisions</h3><p>Automated workflows route applications to the right reviewers, cutting decision time significantly.</p>'
            . '<p>A smoother admissions process means less staff overhead and a better first impression for prospective families.</p>',
        'category' => 'tips',
        'tags' => ['admissions'],
    ],
];

foreach ($posts as $postData) {
    $slug = Sanitizer::slug($postData['title']);
    if (BlogPost::findBySlug($slug)) {
        continue;
    }

    $id = BlogPost::create([
        'title' => $postData['title'],
        'slug' => $slug,
        'excerpt' => $postData['excerpt'],
        'content' => $postData['content'],
        'featured_image' => null,
        'author_id' => 1,
        'status' => 'published',
        'scheduled_at' => null,
        'published_at' => date('Y-m-d H:i:s'),
        'comments_enabled' => 1,
    ]);

    BlogPost::setCategories($id, [$categoryIds[$postData['category']]]);
    BlogPost::setTags($id, array_map(fn($t) => $tagIds[$t], $postData['tags']));

    echo "Created post: {$postData['title']}\n";
}

echo "Blog seeding complete.\n";
