<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Quizzes'; $active_nav = 'quizzes';
require __DIR__ . '/header.php';
?>
<div class="page-hd"><div><h1><i class="fa-solid fa-circle-question me-2 text-primary"></i>Quizzes</h1><p>Create and manage quizzes for your subjects.</p></div></div>
<div class="cardx"><div class="empty-st"><i class="fa-solid fa-circle-question"></i><p>Quiz creation opens once subjects are assigned to you.<br>Check <strong>My Subjects</strong> or contact administration.</p></div></div>
<?php require __DIR__ . '/footer.php'; ?>
