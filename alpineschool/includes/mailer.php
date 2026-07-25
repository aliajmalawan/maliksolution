<?php
declare(strict_types=1);
// Minimal SMTP client + notification sender. Uses the smtp_* settings from Admin → Integrations.
// Falls back to PHP mail() when SMTP isn't configured. Never throws — returns a status string.

/** @return array{ok: bool, message: string} */
function send_mail(PDO $pdo, string $toList, string $subject, string $htmlBody): array
{
    $recipients = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $toList) ?: [])));
    $recipients = array_values(array_filter($recipients, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    if (!$recipients) {
        return ['ok' => false, 'message' => 'No valid recipient email addresses configured.'];
    }

    $siteName = get_setting($pdo, 'site_name', 'Website');
    $fromEmail = get_setting($pdo, 'smtp_from_email') ?: get_setting($pdo, 'email');
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    $host = trim(get_setting($pdo, 'smtp_host'));
    if ($host === '') {
        // No SMTP configured — try PHP's mail().
        $headers = "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . 'From: ' . $siteName . ' <' . $fromEmail . ">\r\n";
        $sent = @mail(implode(', ', $recipients), $subject, $htmlBody, $headers);
        return $sent
            ? ['ok' => true, 'message' => 'Sent via PHP mail().']
            : ['ok' => false, 'message' => 'SMTP is not configured and PHP mail() failed. Add SMTP details in Admin → Integrations.'];
    }

    $port = (int)(get_setting($pdo, 'smtp_port', '587') ?: 587);
    $username = get_setting($pdo, 'smtp_username');
    $password = get_setting($pdo, 'smtp_password');

    return smtp_send($host, $port, $username, $password, $fromEmail, $siteName, $recipients, $subject, $htmlBody);
}

/** @return array{ok: bool, message: string} */
function smtp_send(string $host, int $port, string $user, string $pass, string $from, string $fromName, array $to, string $subject, string $html): array
{
    $transport = $port === 465 ? 'ssl://' : '';
    $socket = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 12);
    if (!$socket) {
        return ['ok' => false, 'message' => "Could not connect to {$host}:{$port} — {$errstr}"];
    }
    stream_set_timeout($socket, 12);

    $read = function () use ($socket): string {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $command, string $expect) use ($socket, $read): ?string {
        fwrite($socket, $command . "\r\n");
        $response = $read();
        return str_starts_with(trim($response), $expect) ? null : trim($response);
    };

    $greeting = $read();
    if (!str_starts_with(trim($greeting), '220')) {
        fclose($socket);
        return ['ok' => false, 'message' => 'Unexpected SMTP greeting: ' . trim($greeting)];
    }

    $ehloHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if ($err = $cmd('EHLO ' . $ehloHost, '250')) {
        fclose($socket);
        return ['ok' => false, 'message' => 'EHLO failed: ' . $err];
    }

    if ($port !== 465) {
        if ($err = $cmd('STARTTLS', '220')) {
            fclose($socket);
            return ['ok' => false, 'message' => 'STARTTLS failed: ' . $err];
        }
        if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['ok' => false, 'message' => 'Could not start TLS encryption.'];
        }
        if ($err = $cmd('EHLO ' . $ehloHost, '250')) {
            fclose($socket);
            return ['ok' => false, 'message' => 'EHLO after TLS failed: ' . $err];
        }
    }

    if ($user !== '') {
        if ($err = $cmd('AUTH LOGIN', '334')) {
            fclose($socket);
            return ['ok' => false, 'message' => 'AUTH LOGIN rejected: ' . $err];
        }
        if ($err = $cmd(base64_encode($user), '334')) {
            fclose($socket);
            return ['ok' => false, 'message' => 'SMTP username rejected.'];
        }
        if ($err = $cmd(base64_encode($pass), '235')) {
            fclose($socket);
            return ['ok' => false, 'message' => 'SMTP password rejected.'];
        }
    }

    if ($err = $cmd('MAIL FROM:<' . $from . '>', '250')) {
        fclose($socket);
        return ['ok' => false, 'message' => 'MAIL FROM rejected: ' . $err];
    }
    foreach ($to as $recipient) {
        if ($err = $cmd('RCPT TO:<' . $recipient . '>', '250')) {
            fclose($socket);
            return ['ok' => false, 'message' => 'Recipient rejected (' . $recipient . '): ' . $err];
        }
    }
    if ($err = $cmd('DATA', '354')) {
        fclose($socket);
        return ['ok' => false, 'message' => 'DATA rejected: ' . $err];
    }

    $headers = 'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . ">\r\n"
        . 'To: ' . implode(', ', $to) . "\r\n"
        . 'Subject: ' . mb_encode_mimeheader($subject) . "\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . 'Date: ' . date('r') . "\r\n";
    // Dot-stuff lines that begin with a period.
    $body = preg_replace('/^\./m', '..', $html);
    fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
    $response = $read();
    if (!str_starts_with(trim($response), '250')) {
        fclose($socket);
        return ['ok' => false, 'message' => 'Message not accepted: ' . trim($response)];
    }

    $cmd('QUIT', '221');
    fclose($socket);
    return ['ok' => true, 'message' => 'Sent to ' . implode(', ', $to) . '.'];
}

/** Build the HTML body for a form-submission notification. */
function submission_email_html(PDO $pdo, string $formName, array $rows): string
{
    $siteName = e(get_setting($pdo, 'site_name'));
    $html = '<div style="font-family:Arial,sans-serif;max-width:600px;">'
        . '<h2 style="color:#2E1B6B;">New ' . e($formName) . ' submission</h2>'
        . '<p style="color:#555;">Received on ' . date('d M Y \a\t H:i') . ' via ' . $siteName . '.</p>'
        . '<table cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse;width:100%;">';
    foreach ($rows as $label => $value) {
        $html .= '<tr>'
            . '<td style="border:1px solid #e5e7ef;background:#f8f9fc;font-weight:bold;width:35%;">' . e((string)$label) . '</td>'
            . '<td style="border:1px solid #e5e7ef;">' . nl2br(e((string)$value)) . '</td>'
            . '</tr>';
    }
    $html .= '</table>'
        . '<p style="color:#888;font-size:12px;margin-top:18px;">This is an automated notification from your website admin panel.</p>'
        . '</div>';
    return $html;
}
