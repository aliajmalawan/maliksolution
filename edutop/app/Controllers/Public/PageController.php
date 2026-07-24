<?php

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Page;
use App\Models\PageView;
use App\Services\PageRenderer;

class PageController extends Controller
{
    public function show(Request $request): void
    {
        PageView::record($request);

        $slug = (string) $request->param('slug');
        $page = Page::findPublishedBySlug($slug);

        if (!$page) {
            PageRenderer::renderNotFound();
            return;
        }

        PageRenderer::renderPage($page);
    }
}
