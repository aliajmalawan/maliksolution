<?php

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Page;
use App\Models\PageView;
use App\Services\PageRenderer;

class HomeController extends Controller
{
    public function index(Request $request): void
    {
        PageView::record($request);

        $page = Page::findHome();

        if (!$page) {
            PageRenderer::renderNotFound();
            return;
        }

        PageRenderer::renderPage($page);
    }
}
