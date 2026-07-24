<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'My Subjects'; $active_nav = 'subjects';
require __DIR__ . '/header.php';
?>
<div class="page-hd"><div><h1><i class="fa-solid fa-book-open me-2 text-primary"></i>My Subjects</h1><p>Subjects assigned to you by administration.</p></div></div>
<div class="cardx"><div class="empty-st"><i class="fa-solid fa-book-open"></i><p>No subjects assigned yet. Your department admin will assign these — check back soon.</p></div></div>
<?php require __DIR__ . '/footer.php'; ?>
