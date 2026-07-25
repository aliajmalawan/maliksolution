<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';

$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php');
// Only redirect back to our own site.
if (strpos($back, (string)($_SERVER['HTTP_HOST'] ?? 'localhost')) === false) {
    $back = BASE_URL . '/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $email = trim((string)($_POST['newsletter_email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)');
        $stmt->execute([$email]);
        flash_set('success', 'Thank you for subscribing to our newsletter!');
    } else {
        flash_set('error', 'Please enter a valid email address.');
    }
}

redirect($back);
