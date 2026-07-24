<?php

/**
 * Global helper functions available in every view file (plain PHP includes
 * run in the global namespace, so these are deliberately NOT namespaced).
 */

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return Request::basePath() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Request::basePath() . '/public/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('safe_url')) {
    /**
     * Neutralizes dangerous URL schemes (javascript:, data:, vbscript:, ...) in
     * admin-editable link fields (menu items, section buttons/logos, social
     * links) before they're rendered to public visitors. A true http(s) or
     * protocol-relative URL passes through; anything else — including a bare
     * "javascript:alert(1)" — is routed through url(), which prefixes it with
     * the app's base path and so is rendered as an inert relative path segment
     * instead of an executable URI.
     */
    function safe_url(string $url): string
    {
        return preg_match('#^(https?:)?//#i', $url) ? $url : url($url);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        $old = Session::get('_old_input', []);
        return e($old[$key] ?? $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key): ?string
    {
        $value = Session::flash($key);
        return $value === null ? null : (string) $value;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return \App\Core\Auth::user();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return \App\Core\Auth::can($permission);
    }
}

if (!function_exists('current_path')) {
    /** Bare current path (no base-path prefix) — matches what url()/redirect() expect as input. */
    function current_path(): string
    {
        return (new Request())->path();
    }
}

if (!function_exists('recaptcha_field')) {
    /** Renders the reCAPTCHA v2 widget markup, or nothing if not configured in Settings. */
    function recaptcha_field(): string
    {
        if (!\App\Core\Recaptcha::isConfigured()) {
            return '';
        }
        return '<div class="g-recaptcha mb-3" data-sitekey="' . e(\App\Core\Recaptcha::siteKey()) . '"></div>';
    }
}

if (!function_exists('media_url')) {
    /** Resolves a media library item's ID (as stored in section/settings fields) to a public URL. */
    function media_url(mixed $mediaId): ?string
    {
        $mediaId = (int) $mediaId;
        if ($mediaId <= 0) {
            return null;
        }
        $media = \App\Models\Media::find($mediaId);
        return $media ? url('/public/uploads' . $media['path']) : null;
    }
}
