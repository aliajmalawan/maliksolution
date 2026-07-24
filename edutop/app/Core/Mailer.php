<?php

namespace App\Core;

/**
 * Minimal hand-rolled SMTP client (no third-party dependency). Supports
 * plain/STARTTLS/implicit-TLS connections with AUTH LOGIN, which covers
 * the common providers (Gmail app passwords, SES SMTP, Mailtrap, etc).
 */
class Mailer
{
    private $socket;
    private array $log = [];

    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        // Recipient address is echoed unencoded into raw SMTP commands (MAIL FROM/RCPT TO) and
        // headers below — a CRLF or angle bracket in it would let it break out of those lines
        // (SMTP command/header injection). Reject anything that isn't a well-formed address.
        if (filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            Logger::error('Mail send rejected: invalid recipient address', ['to' => $toEmail]);
            return false;
        }

        $mailer = new self();
        try {
            $mailer->deliver($toEmail, $toName, $subject, $htmlBody);
            return true;
        } catch (\Throwable $e) {
            Logger::error('Mail send failed', ['to' => $toEmail, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /** Settings (from the admin Email tab) win when set; .env is the fallback so existing setups keep working. */
    private static function config(string $settingKey, string $envKey, mixed $default = null): mixed
    {
        $fromSettings = \App\Models\Setting::get($settingKey);
        if ($fromSettings !== null && $fromSettings !== '') {
            return $fromSettings;
        }
        return Env::get($envKey, $default);
    }

    private function deliver(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $host = self::config('smtp_host', 'MAIL_HOST');
        $port = (int) self::config('smtp_port', 'MAIL_PORT', 587);
        $encryption = strtolower((string) self::config('smtp_encryption', 'MAIL_ENCRYPTION', 'tls'));
        $username = self::config('smtp_username', 'MAIL_USERNAME');
        $password = self::config('smtp_password', 'MAIL_PASSWORD');
        $fromEmail = self::config('mail_from_address', 'MAIL_FROM_ADDRESS', 'no-reply@localhost');
        $fromName = self::config('mail_from_name', 'MAIL_FROM_NAME', 'EduTop');

        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException('Configured from-address is not a valid email address.');
        }

        $transport = $encryption === 'ssl' ? 'ssl://' : '';
        $this->socket = @stream_socket_client(
            "{$transport}{$host}:{$port}",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]])
        );

        if (!$this->socket) {
            throw new \RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        }

        $this->expect(220);
        $this->command('EHLO ' . gethostname(), 250);

        if ($encryption === 'tls') {
            $this->command('STARTTLS', 220);
            $this->enableCrypto();
            $this->command('EHLO ' . gethostname(), 250);
        }

        if ($username && $password) {
            $this->command('AUTH LOGIN', 334);
            $this->command(base64_encode($username), 334);
            $this->command(base64_encode($password), 235);
        }

        $this->command('MAIL FROM:<' . $fromEmail . '>', 250);
        $this->command('RCPT TO:<' . $toEmail . '>', 250);
        $this->command('DATA', 354);

        $boundary = bin2hex(random_bytes(8));
        $headers = [
            'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>',
            'To: ' . $this->encodeHeader($toName) . ' <' . $toEmail . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . date('r'),
        ];

        $body = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $htmlBody) . "\r\n.";
        $this->command($body, 250);
        $this->command('QUIT', 221);

        fclose($this->socket);
    }

    /**
     * stream_socket_enable_crypto() can legitimately return int(0) — "handshake
     * not finished yet, call again" — not just bool. Treating any non-true
     * result as failure (as a single `if (!...)` check would) causes false
     * negatives; this is a well-known PHP/OpenSSL-on-Windows quirk that also
     * emits a spurious "SSL: The operation completed successfully" warning
     * even on ultimate success, which we suppress since it's cosmetic noise.
     */
    private function enableCrypto(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $result = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            if ($result === true) {
                return;
            }
            if ($result === false) {
                throw new \RuntimeException('STARTTLS negotiation failed.');
            }

            usleep(50000); // 50ms — result === 0, handshake still in progress
        }

        throw new \RuntimeException('STARTTLS negotiation timed out.');
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function command(string $command, int $expectedCode): string
    {
        fwrite($this->socket, $command . "\r\n");
        return $this->expect($expectedCode);
    }

    private function expect(int $expectedCode): string
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            // Multi-line SMTP responses use "-" after the code until the final line.
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new \RuntimeException("Unexpected SMTP response (expected {$expectedCode}): {$response}");
        }

        return $response;
    }
}
