<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$faqs = $pdo->query('SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();

$pageTitle = 'FAQs';
$breadcrumbs = [['label' => 'FAQs']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Frequently Asked Questions</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:820px;">
    <?php if (empty($faqs)): ?>
      <p class="text-center" style="color:var(--text-light);">No FAQs have been published yet. Please <a href="contact.php">contact us</a> with your questions.</p>
    <?php else: ?>
      <div class="faq-list">
        <?php foreach ($faqs as $faq): ?>
        <details class="faq-item">
          <summary><?= e($faq['question']) ?></summary>
          <p><?= nl2br(e($faq['answer'])) ?></p>
        </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.faq-list { display:flex; flex-direction:column; gap:12px; }
.faq-item { background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow); padding:0; overflow:hidden; }
.faq-item summary { padding:18px 22px; font-weight:600; cursor:pointer; list-style:none; position:relative; padding-right:44px; }
.faq-item summary::-webkit-details-marker { display:none; }
.faq-item summary::after { content:'+'; position:absolute; right:20px; top:50%; transform:translateY(-50%); font-size:22px; color:var(--primary); }
.faq-item[open] summary::after { content:'−'; }
.faq-item p { padding:0 22px 18px; margin:0; color:var(--text-light); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
