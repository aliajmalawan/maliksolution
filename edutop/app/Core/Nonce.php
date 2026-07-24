<?php

namespace App\Core;

/**
 * One random CSP nonce per request, lazily generated and cached. Lets a
 * small number of trusted inline <script> blocks (analytics snippets) run
 * under a strict CSP without adding 'unsafe-inline' to script-src globally.
 */
class Nonce
{
    private static ?string $value = null;

    public static function value(): string
    {
        if (self::$value === null) {
            self::$value = bin2hex(random_bytes(16));
        }
        return self::$value;
    }
}
