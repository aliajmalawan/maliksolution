<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$results = $pdo->query('SELECT * FROM results ORDER BY published_at DESC')->fetchAll();

$pageTitle = 'Results';
$breadcrumbs = [['label' => 'Results']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Results</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:900px;">
    <div class="section-header">
      <span class="eyebrow">Academic Performance</span>
      <h2>Examination Results</h2>
      <p>Published results and result announcements from The Alpine School, Haroonabad Campus.</p>
    </div>

    <?php if (empty($results)): ?>
      <p class="text-center" style="color:var(--text-light);">No results have been published yet. Please check back soon.</p>
    <?php else: ?>
      <div class="table-wrap" style="background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);padding:10px 20px;">
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="text-align:left;">
              <th style="padding:14px 12px;border-bottom:2px solid var(--primary);">Title</th>
              <th style="padding:14px 12px;border-bottom:2px solid var(--primary);">Class</th>
              <th style="padding:14px 12px;border-bottom:2px solid var(--primary);">Published</th>
              <th style="padding:14px 12px;border-bottom:2px solid var(--primary);"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $result): ?>
            <tr>
              <td style="padding:14px 12px;border-bottom:1px solid #eee;"><?= e($result['title']) ?></td>
              <td style="padding:14px 12px;border-bottom:1px solid #eee;"><?= e($result['class_name']) ?></td>
              <td style="padding:14px 12px;border-bottom:1px solid #eee;"><?= format_date($result['published_at']) ?></td>
              <td style="padding:14px 12px;border-bottom:1px solid #eee;">
                <?php if ($result['file_path']): ?>
                  <a href="<?= BASE_URL ?>/<?= e($result['file_path']) ?>" class="btn btn-dark btn-sm" target="_blank" rel="noopener">View Result</a>
                <?php else: ?>
                  <span style="color:var(--text-light);">Available at campus</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
