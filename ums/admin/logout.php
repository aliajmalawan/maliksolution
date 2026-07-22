<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

ums_logout();
redirect(UMS_URL . '/admin/login.php');
