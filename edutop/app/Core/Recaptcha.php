<?php

namespace App\Core;

use App\Models\Setting;

/**
 * Progressive reCAPTCHA v2: if no site/secret key is configured in Settings,
 * every method is a pass-through, so forms behave exactly as before. Uses
 * file_get_contents + a stream context for the server-side check — no cURL
 * or Composer dependency needed.
 */
class Recaptcha
{
    public static function isConfigured(): bool
    {
        return self::siteKey() !== '' && self::secretKey() !== '';
    }

    public static function siteKey(): string
    {
        return (string) (Setting::get('recaptcha_site_key') ?? '');
    }

    private static function secretKey(): string
    {
        return (string) (Setting::get('recaptcha_secret_key') ?? '');
    }

    public static function verify(?string $token): bool
    {
        if (!self::isConfigured()) {
            return true; // not configured — don't block submissions
        }

        if (!$token) {
            return false;
        }

        $payload = http_build_query([
            'secret' => self::secretKey(),
            'response' => $token,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);

        $result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($result === false) {
            Logger::warning('reCAPTCHA verification request failed');
            return false;
        }

        $decoded = json_decode($result, true);
        return is_array($decoded) && !empty($decoded['success']);
    }
}
