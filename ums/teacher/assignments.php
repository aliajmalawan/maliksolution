<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
$page_title = 'Assignments'; $active_nav = 'assignments';
require __DIR__ . '/header.php';
?>
<div class="page-hd"><div><h1><i class="fa-solid fa-file-pen me-2 text-primary"></i>Assignments</h1><p>Create and manage assignments for your subjects.</p></div></div>
<div class="cardx"><div class="empty-st"><i class="fa-solid fa-file-pen"></i><p>Assignment creation opens once subjects are assigned to you.<br>Check <strong>My Subjects</strong> or contact administration.</p></div></div>
<?php require __DIR__ . '/footer.php'; ?>
