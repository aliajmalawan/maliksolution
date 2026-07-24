<?php

namespace App\Core\Middleware;

use App\Core\Env;
use App\Core\Nonce;
use App\Core\Request;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, ?string $param = null): bool
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // 'nonce-...' lets specific trusted inline scripts (analytics snippets) run
        // without weakening script-src with a blanket 'unsafe-inline'.
        $nonce = Nonce::value();
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' https: 'nonce-{$nonce}'; font-src 'self' https: data:; frame-src https:; frame-ancestors 'self';");

        if (Env::bool('APP_FORCE_HTTPS', false)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

            if (($_SERVER['HTTPS'] ?? 'off') === 'off') {
                $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                header('Location: ' . $url, true, 301);
                return false;
            }
        }

        return true;
    }
}
