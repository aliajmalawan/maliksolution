<?php
declare(strict_types=1);
// AJAX endpoint for dynamic forms. Runs the *same* validation/spam/mail pipeline
// as the normal POST path — JavaScript only changes how the result is delivered.
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/form-render.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_out(array $payload, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['status' => 'error', 'message' => 'Method not allowed.', 'errors' => []], 405);
}

$slug = trim((string)($_POST['form_slug'] ?? ''));
$form = $slug !== '' ? load_form($pdo, $slug) : null;

if (!$form) {
    json_out(['status' => 'error', 'message' => 'This form is no longer available.', 'errors' => []], 404);
}

$result = handle_form_submit($pdo, $form);

json_out([
    'status' => $result['status'],
    'message' => $result['message'],
    'errors' => $result['errors'] ?? [],
    // A fresh token so the visitor can submit again without reloading the page.
    'csrf' => csrf_token(),
]);
