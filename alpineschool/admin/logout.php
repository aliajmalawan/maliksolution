<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

$_SESSION = [];
session_destroy();
redirect(BASE_URL . '/admin/login.php');
