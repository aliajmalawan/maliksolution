<?php
declare(strict_types=1);

/** Shared view/request helpers — kept tiny and dependency-free. */

/** HTML-escape shorthand. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Redirect and stop. Accepts absolute or UMS-relative paths. */
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** Set a one-shot flash message: flash('success', 'Saved.'). */
function flash_set(string $type, string $message): void
{
    $_SESSION['ums_flash'] = ['type' => $type, 'message' => $message];
}

/** Read + clear the flash message (or null). */
function flash_get(): ?array
{
    $f = $_SESSION['ums_flash'] ?? null;
    unset($_SESSION['ums_flash']);
    return $f;
}

/** CSRF token for forms (one per session). */
function csrf_token(): string
{
    if (empty($_SESSION['ums_csrf'])) {
        $_SESSION['ums_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ums_csrf'];
}

/** Hidden CSRF input field. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/** Validate a submitted CSRF token. */
function csrf_check(): bool
{
    return hash_equals($_SESSION['ums_csrf'] ?? '', (string)($_POST['_token'] ?? ''));
}

/** Record an action in the UMS activity log (never breaks the page). */
function ums_log(string $action, string $details = ''): void
{
    try {
        $stmt = ums_db()->prepare(
            'INSERT INTO ' . tbl('activity_log') . ' (user_id, user_name, action, details, ip) VALUES (?, ?, ?, ?, ?)'
        );
        $uid   = (int)($_SESSION['ums_user']['id'] ?? 0);
        $uname = (string)($_SESSION['ums_user']['name'] ?? 'system');
        $ip    = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $stmt->bind_param('issss', $uid, $uname, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $t) { /* logging must never break the request */ }
}
