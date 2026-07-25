<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

http_response_code(404);

$pageTitle = 'Page Not Found';
$breadcrumbs = [['label' => '404']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>404 — Page Not Found</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:620px;text-align:center;">
    <p style="font-size:80px;margin:0;">🧭</p>
    <h2>We couldn't find that page</h2>
    <p style="color:var(--text-light);">The page you're looking for may have been moved, renamed, or doesn't exist. Try searching the site or head back home.</p>
    <form method="get" action="<?= BASE_URL ?>/search.php" class="search-form" style="display:flex;gap:12px;margin:24px 0;">
      <input type="search" name="q" placeholder="Search the site…" style="flex:1;padding:14px 18px;border:1.5px solid #ddd;border-radius:10px;font-size:16px;font-family:inherit;">
      <button type="submit" class="btn btn-dark">Search</button>
    </form>
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-dark">← Back to Home</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
