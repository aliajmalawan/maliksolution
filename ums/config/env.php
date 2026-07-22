<?php
declare(strict_types=1);

/**
 * Minimal .env loader.
 * Reads KEY=VALUE pairs (supports quoted values and # comments)
 * and exposes them through env('KEY', 'default').
 */

if (!function_exists('env')) {
    /** Read one environment value loaded from config/.env */
    function env(string $key, string $default = ''): string
    {
        static $vars = null;
        if ($vars === null) {
            $vars = [];
            $file = __DIR__ . '/.env';
            if (is_readable($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                        continue;
                    }
                    [$k, $v] = explode('=', $line, 2);
                    $v = trim($v);
                    // strip inline comments on unquoted values
                    if ($v !== '' && $v[0] !== '"' && $v[0] !== "'") {
                        $v = trim(explode(' #', $v)[0]);
                    }
                    // strip surrounding quotes
                    if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[-1] === $v[0]) {
                        $v = substr($v, 1, -1);
                    }
                    $vars[trim($k)] = $v;
                }
            }
        }
        return $vars[$key] ?? $default;
    }
}
