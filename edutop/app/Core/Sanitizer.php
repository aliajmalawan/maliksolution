<?php

namespace App\Core;

class Sanitizer
{
    /** Trim + strip tags for plain-text fields (names, titles, etc). */
    public static function text(?string $value): string
    {
        return trim(strip_tags($value ?? ''));
    }

    /** Safe filename: alnum/dash/underscore/dot only, no path separators. */
    public static function filename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        return $filename === '' ? 'file' : $filename;
    }

    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    /**
     * Normalizes an admin-defined form-field name into a safe HTML name/POST
     * key. Used identically by the admission-form renderer and its submit
     * handler so a field's rendered <input name> always matches the key the
     * controller looks up, regardless of what the admin typed.
     */
    public static function fieldKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value);
        $value = trim($value, '_');
        return $value === '' ? 'field' : $value;
    }

    private const RICH_TEXT_ALLOWED_TAGS = [
        'p', 'br', 'strong', 'em', 'b', 'i', 'u', 'ul', 'ol', 'li',
        'a', 'h2', 'h3', 'h4', 'blockquote', 'img',
    ];

    private const RICH_TEXT_ALLOWED_ATTRS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt'],
    ];

    private const RICH_TEXT_STRIP_ENTIRELY = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'link', 'meta', 'svg',
    ];

    /**
     * Allow-list HTML sanitizer for rich-text fields, built on PHP's built-in
     * DOMDocument (no external HTML purifier dependency). Applied server-side
     * on every save regardless of who submitted it — defense in depth against
     * stored XSS from lower-privileged editor roles.
     */
    public static function richText(?string $html): string
    {
        $html = trim($html ?? '');
        if ($html === '') {
            return '';
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        // The XML PI forces UTF-8 interpretation without appearing in the output
        // (we only re-serialize the wrapper div's children, never the PI itself).
        $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $container = $doc->getElementsByTagName('div')->item(0);
        if (!$container) {
            return '';
        }

        self::sanitizeNode($container);

        $result = '';
        foreach (iterator_to_array($container->childNodes) as $child) {
            $result .= $doc->saveHTML($child);
        }

        return trim($result);
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue; // text nodes are safe as-is; DOM serialization escapes them
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::RICH_TEXT_STRIP_ENTIRELY, true)) {
                $node->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::RICH_TEXT_ALLOWED_TAGS, true)) {
                // Unwrap: keep the (already-sanitized-below) children, drop the tag itself.
                self::sanitizeNode($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            if ($child->hasAttributes()) {
                $allowedForTag = self::RICH_TEXT_ALLOWED_ATTRS[$tag] ?? [];
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $attrName = strtolower($attr->name);
                    $isEventHandler = str_starts_with($attrName, 'on');
                    $isUnsafeUrl = in_array($attrName, ['href', 'src'], true)
                        && preg_match('/^\s*(javascript|data|vbscript):/i', $attr->value);

                    if ($isEventHandler || $isUnsafeUrl || !in_array($attrName, $allowedForTag, true)) {
                        $child->removeAttribute($attr->name);
                    }
                }
                if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }

            self::sanitizeNode($child);
        }
    }

    private const CUSTOM_CODE_ALLOWED_TAGS = [
        'div', 'span', 'section', 'p', 'br', 'hr', 'strong', 'em', 'b', 'i', 'u', 'small',
        'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote',
        'img', 'figure', 'figcaption', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'button',
    ];

    private const CUSTOM_CODE_ALLOWED_ATTRS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'button' => ['type'],
    ];

    private const CUSTOM_CODE_UNIVERSAL_ATTRS = ['class', 'id', 'style'];

    private const CUSTOM_CODE_STRIP_ENTIRELY = [
        'script', 'iframe', 'object', 'embed', 'form', 'input', 'link', 'meta', 'svg', 'textarea', 'select',
    ];

    /**
     * Allow-list sanitizer for the section-level "Custom Design (Code)" HTML
     * field. Broader than richText() — permits class/id/style so admins can
     * build one-off layouts — but still an admin-manage-gated escape hatch,
     * not a place to trust arbitrary input: script/iframe/forms/event
     * handlers/javascript: URLs are still stripped as defense in depth.
     */
    public static function customCode(?string $html): string
    {
        $html = trim($html ?? '');
        if ($html === '') {
            return '';
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $container = $doc->getElementsByTagName('div')->item(0);
        if (!$container) {
            return '';
        }

        self::sanitizeCustomCodeNode($container);

        $result = '';
        foreach (iterator_to_array($container->childNodes) as $child) {
            $result .= $doc->saveHTML($child);
        }

        return trim($result);
    }

    private static function sanitizeCustomCodeNode(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::CUSTOM_CODE_STRIP_ENTIRELY, true)) {
                $node->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::CUSTOM_CODE_ALLOWED_TAGS, true)) {
                self::sanitizeCustomCodeNode($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            if ($child->hasAttributes()) {
                $allowedForTag = array_merge(self::CUSTOM_CODE_UNIVERSAL_ATTRS, self::CUSTOM_CODE_ALLOWED_ATTRS[$tag] ?? []);
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $attrName = strtolower($attr->name);
                    $isEventHandler = str_starts_with($attrName, 'on');
                    $isUnsafeUrl = in_array($attrName, ['href', 'src'], true)
                        && preg_match('/^\s*(javascript|data|vbscript):/i', $attr->value);
                    $isUnsafeStyle = $attrName === 'style'
                        && preg_match('/expression\s*\(|javascript:|@import/i', $attr->value);
                    // data-*/aria-*/role are inert without scripts (which are stripped);
                    // site.js needs data-* for counters and scroll animations.
                    $isGenericSafe = str_starts_with($attrName, 'data-')
                        || str_starts_with($attrName, 'aria-')
                        || $attrName === 'role';

                    if ($isEventHandler || $isUnsafeUrl || $isUnsafeStyle
                        || (!$isGenericSafe && !in_array($attrName, $allowedForTag, true))) {
                        $child->removeAttribute($attr->name);
                    }
                }
                if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }

            self::sanitizeCustomCodeNode($child);
        }
    }

    /**
     * Light sanitizer for the section-level custom CSS field: prevents
     * breaking out of the injected <style> tag and blocks the handful of
     * legacy CSS-based script vectors, otherwise passes CSS through as-is.
     */
    public static function customCss(?string $css): string
    {
        $css = trim($css ?? '');
        if ($css === '') {
            return '';
        }
        $css = str_ireplace('</style', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/javascript\s*:/i', '', $css);
        return $css;
    }
}
