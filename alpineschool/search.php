<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$q = trim((string)($_GET['q'] ?? ''));
$results = [];

if ($q !== '' && mb_strlen($q) >= 2) {
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare('SELECT slug, title, meta_description FROM pages WHERE title LIKE ? OR body LIKE ? LIMIT 10');
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'type' => 'Page',
            'title' => $row['title'],
            'snippet' => $row['meta_description'],
            'url' => $row['slug'] . '.php',
        ];
    }

    $stmt = $pdo->prepare('SELECT slug, title, excerpt FROM news WHERE is_published = 1 AND (title LIKE ? OR body LIKE ?) ORDER BY published_at DESC LIMIT 10');
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'type' => 'News',
            'title' => $row['title'],
            'snippet' => $row['excerpt'],
            'url' => 'news-detail.php?slug=' . urlencode($row['slug']),
        ];
    }

    $stmt = $pdo->prepare('SELECT slug, title, excerpt FROM blogs WHERE is_published = 1 AND (title LIKE ? OR body LIKE ?) ORDER BY published_at DESC LIMIT 10');
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'type' => 'Blog',
            'title' => $row['title'],
            'snippet' => $row['excerpt'],
            'url' => 'blog-detail.php?slug=' . urlencode($row['slug']),
        ];
    }

    $stmt = $pdo->prepare('SELECT title, description, event_date FROM events WHERE title LIKE ? OR description LIKE ? ORDER BY event_date DESC LIMIT 10');
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'type' => 'Event',
            'title' => $row['title'],
            'snippet' => format_date($row['event_date']) . ' — ' . mb_substr((string)$row['description'], 0, 120),
            'url' => 'events.php',
        ];
    }

    $stmt = $pdo->prepare('SELECT name, designation, department FROM faculty WHERE name LIKE ? OR designation LIKE ? OR department LIKE ? LIMIT 10');
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'type' => 'Faculty',
            'title' => $row['name'],
            'snippet' => trim($row['designation'] . ($row['department'] ? ' — ' . $row['department'] : '')),
            'url' => 'faculty.php',
        ];
    }
}

$pageTitle = $q !== '' ? 'Search: ' . $q : 'Search';
$breadcrumbs = [['label' => 'Search']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Search</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:820px;">
    <form method="get" action="search.php" class="search-form">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search pages, news, blogs, events, faculty…" autofocus>
      <button type="submit" class="btn btn-dark">Search</button>
    </form>

    <?php if ($q === ''): ?>
      <p style="color:var(--text-light);margin-top:24px;">Type something above to search the site.</p>
    <?php elseif (mb_strlen($q) < 2): ?>
      <p style="color:var(--text-light);margin-top:24px;">Please enter at least 2 characters.</p>
    <?php elseif (empty($results)): ?>
      <p style="color:var(--text-light);margin-top:24px;">No results found for "<strong><?= e($q) ?></strong>". Try a different keyword.</p>
    <?php else: ?>
      <p style="color:var(--text-light);margin-top:24px;"><?= count($results) ?> result<?= count($results) === 1 ? '' : 's' ?> for "<strong><?= e($q) ?></strong>"</p>
      <div class="search-results">
        <?php foreach ($results as $r): ?>
        <a href="<?= e($r['url']) ?>" class="search-result">
          <span class="search-type"><?= e($r['type']) ?></span>
          <strong><?= e($r['title']) ?></strong>
          <?php if ($r['snippet']): ?><small><?= e($r['snippet']) ?></small><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.search-form { display:flex; gap:12px; }
.search-form input { flex:1; padding:14px 18px; border:1.5px solid #ddd; border-radius:10px; font-size:16px; font-family:inherit; }
.search-form input:focus { outline:none; border-color:var(--primary); }
.search-results { display:flex; flex-direction:column; gap:12px; margin-top:20px; }
.search-result { display:flex; flex-direction:column; gap:4px; background:var(--white); border-radius:var(--radius); padding:18px 22px; box-shadow:var(--shadow); transition:var(--transition); }
.search-result:hover { transform:translateY(-2px); box-shadow:var(--shadow-lg); }
.search-result .search-type { align-self:flex-start; background:rgba(46,27,107,.08); color:var(--primary); padding:3px 12px; border-radius:999px; font-size:12px; font-weight:600; }
.search-result small { color:var(--text-light); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
