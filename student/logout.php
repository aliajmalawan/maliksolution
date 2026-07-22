<?php
declare(strict_types=1);
session_start();
session_unset();
session_destroy();
header('Location: login.php?msg=logged_out');
exit;
