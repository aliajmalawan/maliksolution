<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$q = trim((string)($_GET['q'] ?? ''));
$results = [];

if ($q !== '' && mb_strlen($q) >= 2) {
    $like = '%' . $q . '%';

    /** Each source: [minRole, SQL, params, icon, section, link-builder] */
    $sources = [
        ['editor', 'SELECT id, title FROM pages WHERE title LIKE ? OR body LIKE ? LIMIT 8', [$like, $like], '📄', 'Pages', fn($r) => 'pages.php?edit=' . $r['id'], fn($r) => $r['title']],
        ['editor', 'SELECT id, title FROM news WHERE title LIKE ? OR body LIKE ? LIMIT 8', [$like, $like], '📰', 'News', fn($r) => 'news.php?edit=' . $r['id'], fn($r) => $r['title']],
        ['editor', 'SELECT id, title FROM blogs WHERE title LIKE ? OR body LIKE ? LIMIT 8', [$like, $like], '✍️', 'Blogs', fn($r) => 'blogs.php?edit=' . $r['id'], fn($r) => $r['title']],
        ['editor', 'SELECT id, title FROM events WHERE title LIKE ? OR description LIKE ? LIMIT 8', [$like, $like], '📅', 'Events', fn($r) => 'events.php?edit=' . $r['id'], fn($r) => $r['title']],
        ['editor', 'SELECT id, name AS title FROM faculty WHERE name LIKE ? OR designation LIKE ? LIMIT 8', [$like, $like], '👩‍🏫', 'Faculty', fn($r) => 'faculty.php?edit=' . $r['id'], fn($r) => $r['title']],
        ['editor', 'SELECT id, title FROM downloads WHERE title LIKE ? OR category LIKE ? LIMIT 8', [$like, $like], '📎', 'Downloads', fn($r) => 'downloads.php', fn($r) => $r['title']],
        ['editor', 'SELECT id, title FROM careers WHERE title LIKE ? OR description LIKE ? LIMIT 8', [$like, $like], '💼', 'Career', fn($r) => 'career.php?edit=' . $r['id'], fn($r) => $r['title']],
        ['editor', 'SELECT id, title FROM results WHERE title LIKE ? OR class_name LIKE ? LIMIT 8', [$like, $like], '🏅', 'Results', fn($r) => 'results.php?edit=' . $r['id'], fn($r) => $r['title']],
        ['admin', 'SELECT id, student_name, parent_name, phone FROM inquiries WHERE student_name LIKE ? OR parent_name LIKE ? OR phone LIKE ? LIMIT 8', [$like, $like, $like], '🎓', 'Admission Inquiries', fn($r) => 'inquiries.php', fn($r) => $r['student_name'] . ' (' . $r['parent_name'] . ', ' . $r['phone'] . ')'],
        ['admin', 'SELECT id, name, subject FROM contact_messages WHERE name LIKE ? OR subject LIKE ? OR message LIKE ? LIMIT 8', [$like, $like, $like], '✉️', 'Contact Messages', fn($r) => 'messages.php', fn($r) => $r['name'] . ($r['subject'] ? ' — ' . $r['subject'] : '')],
        ['super_admin', 'SELECT id, full_name, username FROM admins WHERE full_name LIKE ? OR username LIKE ? LIMIT 8', [$like, $like], '👥', 'Admin Users', fn($r) => 'users.php?edit=' . $r['id'], fn($r) => $r['full_name'] . ' (@' . $r['username'] . ')'],
    ];

    foreach ($sources as [$minRole, $sql, $params, $icon, $section, $linkFn, $labelFn]) {
        if (!has_role($currentAdmin, $minRole)) {
            continue;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['icon' => $icon, 'section' => $section, 'label' => $labelFn($row), 'link' => $linkFn($row)];
        }
    }
}

$pageTitle = 'Search';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2><?= $q === '' ? 'Search the admin panel' : 'Results for "' . e($q) . '" (' . count($results) . ')' ?></h2></div>

  <?php if ($q === ''): ?>
    <div class="empty-state">Use the search box in the top bar to find content, inquiries, messages, and users.</div>
  <?php elseif (mb_strlen($q) < 2): ?>
    <div class="empty-state">Please enter at least 2 characters.</div>
  <?php elseif (empty($results)): ?>
    <div class="empty-state">Nothing found for "<strong><?= e($q) ?></strong>". Try a different keyword.</div>
  <?php else: ?>
    <div class="feed">
      <?php foreach ($results as $r): ?>
        <a href="<?= e($r['link']) ?>" class="feed-item">
          <span class="f-ico"><?= $r['icon'] ?></span>
          <span>
            <strong><?= e($r['label']) ?></strong>
            <small><?= e($r['section']) ?></small>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
