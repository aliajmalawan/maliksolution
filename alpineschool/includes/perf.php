<?php
declare(strict_types=1);
// Performance layer: asset minification + fingerprinting, HTML minification,
// and a lightweight file cache for settings/menu so pages don't re-query them.

define('CACHE_DIR', dirname(__DIR__) . '/cache');

/** Ensure the cache directory exists and is not web-readable. */
function cache_dir(): string
{
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
        file_put_contents(CACHE_DIR . '/.htaccess', "Require all denied\n");
    }
    return CACHE_DIR;
}

/** Read a cached value, or build it with $builder and store it. TTL 0 = until invalidated. */
function cache_remember(string $key, callable $builder, int $ttl = 0)
{
    if (get_setting_raw('perf_cache') === '0') {
        return $builder();
    }

    $file = cache_dir() . '/' . preg_replace('/[^a-z0-9_-]/i', '', $key) . '.php';
    if (is_file($file)) {
        $payload = @include $file;
        if (is_array($payload) && isset($payload['data'])) {
            $fresh = $ttl === 0 || (time() - (int)($payload['time'] ?? 0)) < $ttl;
            if ($fresh) {
                return $payload['data'];
            }
        }
    }

    $data = $builder();
    file_put_contents(
        $file,
        '<?php return ' . var_export(['time' => time(), 'data' => $data], true) . ';',
        LOCK_EX
    );
    return $data;
}

/** Drop cached entries (all, or one key). Called whenever admin content changes. */
function cache_clear(?string $key = null): int
{
    $dir = cache_dir();
    $files = $key !== null
        ? [$dir . '/' . preg_replace('/[^a-z0-9_-]/i', '', $key) . '.php']
        : (glob($dir . '/*.php') ?: []);

    $n = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $n++;
        }
    }
    return $n;
}

/** Settings lookup that avoids a DB hit when the perf cache is warm. */
function get_setting_raw(string $key): ?string
{
    static $settings = null;
    if ($settings === null) {
        $file = CACHE_DIR . '/settings.php';
        if (is_file($file)) {
            $payload = @include $file;
            $settings = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        } else {
            $settings = [];
        }
    }
    return $settings[$key] ?? null;
}

/**
 * Minify a CSS/JS asset, store it next to the original, and return a fingerprinted URL.
 * Falls back to the plain file when minification is disabled.
 */
function asset_url(PDO $pdo, string $relPath): string
{
    $root = dirname(__DIR__);
    $full = $root . '/' . ltrim($relPath, '/');
    if (!is_file($full)) {
        return BASE_URL . '/' . ltrim($relPath, '/');
    }

    $minifyOn = get_setting($pdo, 'perf_minify', '1') === '1';
    if (!$minifyOn) {
        return BASE_URL . '/' . ltrim($relPath, '/') . '?v=' . filemtime($full);
    }

    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $minPath = preg_replace('/\.(css|js)$/i', '.min.$1', $relPath);
    $minFull = $root . '/' . ltrim($minPath, '/');

    // Rebuild when the source is newer than the minified copy.
    if (!is_file($minFull) || filemtime($full) > filemtime($minFull)) {
        $source = (string)file_get_contents($full);
        $minified = $ext === 'css' ? minify_css($source) : minify_js($source);
        file_put_contents($minFull, $minified, LOCK_EX);
        // Pre-compress for mod_rewrite to serve directly.
        if (function_exists('gzencode')) {
            file_put_contents($minFull . '.gz', gzencode($minified, 9), LOCK_EX);
        }
    }

    return BASE_URL . '/' . ltrim($minPath, '/') . '?v=' . filemtime($minFull);
}

/** Conservative CSS minifier: strips comments, collapses whitespace, trims separators. */
function minify_css(string $css): string
{
    $css = preg_replace('#/\*(?!!).*?\*/#s', '', $css);          // comments (keep /*! ... */)
    $css = preg_replace('/\s+/', ' ', (string)$css);              // collapse whitespace
    $css = preg_replace('/\s*([{}:;,>~+])\s*/', '$1', (string)$css);
    $css = preg_replace('/;}/', '}', (string)$css);               // trailing semicolons
    $css = preg_replace('/\s*!important/', '!important', (string)$css);
    return trim((string)$css);
}

/**
 * Conservative JS minifier: removes comments and indentation only.
 * (Deliberately does NOT touch statements — safe without a full parser.)
 */
function minify_js(string $js): string
{
    $out = '';
    $len = strlen($js);
    $i = 0;
    while ($i < $len) {
        $c = $js[$i];
        $next = $js[$i + 1] ?? '';

        // Skip string literals verbatim.
        if ($c === '"' || $c === "'" || $c === '`') {
            $quote = $c;
            $out .= $c;
            $i++;
            while ($i < $len) {
                $out .= $js[$i];
                if ($js[$i] === '\\') {
                    $out .= $js[$i + 1] ?? '';
                    $i += 2;
                    continue;
                }
                if ($js[$i] === $quote) {
                    $i++;
                    break;
                }
                $i++;
            }
            continue;
        }

        // Line comment
        if ($c === '/' && $next === '/') {
            while ($i < $len && $js[$i] !== "\n") {
                $i++;
            }
            continue;
        }
        // Block comment
        if ($c === '/' && $next === '*') {
            $end = strpos($js, '*/', $i + 2);
            $i = $end === false ? $len : $end + 2;
            continue;
        }

        $out .= $c;
        $i++;
    }

    // Collapse indentation and blank lines, keep newlines (ASI-safe).
    $lines = array_filter(array_map('trim', explode("\n", $out)), fn($l) => $l !== '');
    return implode("\n", $lines);
}

/** Minify the final HTML output (safe: skips <pre>, <textarea>, <script>, <style>). */
function minify_html(string $html): string
{
    $protected = [];
    $html = preg_replace_callback(
        '#<(pre|textarea|script|style)\b[^>]*>.*?</\1>#is',
        function ($m) use (&$protected) {
            $token = '@@PROTECTED' . count($protected) . '@@';
            $protected[$token] = $m[0];
            return $token;
        },
        $html
    ) ?? $html;

    $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);   // comments (keep IE conditionals)
    $html = preg_replace('/\s+/', ' ', (string)$html);           // collapse whitespace
    $html = preg_replace('/>\s+</', '><', (string)$html);        // between tags

    return str_replace(array_keys($protected), array_values($protected), (string)$html);
}

/** Start HTML minification for this request (call before any output). */
function start_html_minify(PDO $pdo): void
{
    if (get_setting($pdo, 'perf_html_minify', '1') !== '1') {
        return;
    }
    ob_start(function (string $buffer): string {
        // Only minify HTML responses.
        foreach (headers_list() as $header) {
            if (stripos($header, 'content-type:') === 0 && stripos($header, 'text/html') === false) {
                return $buffer;
            }
        }
        return minify_html($buffer);
    });
}

/** Image dimensions for width/height attributes (prevents layout shift). Cached per file. */
function img_dimensions(string $relPath): string
{
    static $cache = [];
    if (isset($cache[$relPath])) {
        return $cache[$relPath];
    }
    $full = dirname(__DIR__) . '/' . ltrim($relPath, '/');
    $attrs = '';
    if (is_file($full)) {
        $dims = @getimagesize($full);
        if ($dims) {
            $attrs = ' width="' . $dims[0] . '" height="' . $dims[1] . '"';
        }
    }
    return $cache[$relPath] = $attrs;
}
