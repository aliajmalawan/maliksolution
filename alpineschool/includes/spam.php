<?php
declare(strict_types=1);
// Layered spam protection for public forms: honeypot, time trap, rate limit, keyword filter.
// Usage in a form: echo spam_fields();  — then: $verdict = spam_check($pdo, $textValues);

/** Hidden honeypot + timestamp fields to drop into any public form. */
function spam_fields(): string
{
    $ts = time();
    $token = hash_hmac('sha256', (string)$ts, session_id() ?: 'alpine');
    return '<div style="position:absolute;left:-9999px;" aria-hidden="true">'
        . '<label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>'
        . '</div>'
        . '<input type="hidden" name="form_ts" value="' . $ts . '">'
        . '<input type="hidden" name="form_sig" value="' . $token . '">';
}

/**
 * Returns null when the submission looks legitimate, or a reason string when it's spam.
 * $textValues: all free-text values from the form, used for keyword scanning.
 */
function spam_check(PDO $pdo, array $textValues): ?string
{
    // 1. Honeypot — bots fill hidden fields.
    if (trim((string)($_POST['website'] ?? '')) !== '') {
        return 'honeypot';
    }

    // 2. Time trap — a real person takes at least a few seconds to fill a form.
    $ts = (int)($_POST['form_ts'] ?? 0);
    $sig = (string)($_POST['form_sig'] ?? '');
    $expected = hash_hmac('sha256', (string)$ts, session_id() ?: 'alpine');
    if ($ts <= 0 || !hash_equals($expected, $sig)) {
        return 'invalid form token';
    }
    $minSeconds = max(0, (int)get_setting($pdo, 'spam_min_seconds', '3'));
    $elapsed = time() - $ts;
    if ($elapsed < $minSeconds) {
        return 'submitted too fast';
    }
    if ($elapsed > 7200) {
        return 'form expired — please reload the page';
    }

    // 3. Rate limit per IP per hour.
    $maxPerHour = max(1, (int)get_setting($pdo, 'spam_max_per_hour', '5'));
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM form_submissions WHERE ip = ? AND created_at >= NOW() - INTERVAL 1 HOUR');
    $stmt->execute([$ip]);
    if ((int)$stmt->fetch()['c'] >= $maxPerHour) {
        return 'too many submissions from your network — please try again later';
    }

    // 4. Keyword blocklist.
    $keywords = array_filter(array_map('trim', explode(',', get_setting($pdo, 'spam_keywords', ''))));
    if ($keywords) {
        $haystack = mb_strtolower(implode(' ', array_map('strval', $textValues)));
        foreach ($keywords as $word) {
            if ($word !== '' && str_contains($haystack, mb_strtolower($word))) {
                return 'blocked content';
            }
        }
    }

    // 5. Crude link-flood check.
    $linkCount = 0;
    foreach ($textValues as $value) {
        $linkCount += preg_match_all('#https?://#i', (string)$value);
    }
    if ($linkCount > 3) {
        return 'too many links';
    }

    return null;
}
