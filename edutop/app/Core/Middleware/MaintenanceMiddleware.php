<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Models\Page;
use App\Models\Setting;
use App\Services\PageRenderer;

/**
 * Invoked directly in public/index.php (same precedent as
 * SecurityHeadersMiddleware — called outside the per-route list, since it
 * must run for every public request). Skips /admin entirely and any
 * authenticated session, so admins can keep working while the public sees
 * the maintenance page.
 */
class MaintenanceMiddleware
{
    public function handle(Request $request, ?string $param = null): bool
    {
        if (Setting::get('maintenance_mode') !== '1') {
            return true;
        }

        if (str_starts_with($request->path(), '/admin') || Auth::check()) {
            return true;
        }

        $page = Page::findPublishedBySlug('coming-soon');
        http_response_code(503);
        header('Retry-After: 3600');

        if ($page) {
            PageRenderer::renderPage($page);
        } else {
            echo '<!DOCTYPE html><html><head><title>Maintenance</title></head><body style="font-family:sans-serif;text-align:center;padding:4rem;"><h1>We\'ll be right back</h1><p>This site is undergoing scheduled maintenance.</p></body></html>';
        }

        return false;
    }
}
