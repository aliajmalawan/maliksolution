<?php

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\BlogPostSeo;
use App\Models\BlogTag;
use App\Models\PageView;

class BlogController extends Controller
{
    private const PER_PAGE = 6;

    public function index(Request $request): void
    {
        PageView::record($request);
        BlogPost::publishDueScheduled();

        $page = max(1, (int) $request->input('page', 1));
        $filters = array_filter([
            'category' => (string) $request->input('category', ''),
            'tag' => (string) $request->input('tag', ''),
            'q' => trim((string) $request->input('q', '')),
        ]);

        $result = BlogPost::paginate($page, self::PER_PAGE, $filters);

        $this->view('public/blog/index', [
            'posts' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'totalPages' => (int) ceil($result['total'] / $result['perPage']),
            'categories' => BlogCategory::all('name ASC'),
            'tags' => BlogTag::all('name ASC'),
            'filters' => $filters,
        ], 'public/layouts/main');
    }

    public function show(Request $request): void
    {
        PageView::record($request);
        BlogPost::publishDueScheduled();

        $slug = (string) $request->param('slug');
        $post = BlogPost::findPublishedBySlug($slug);

        if (!$post) {
            \App\Services\PageRenderer::renderNotFound();
            return;
        }

        $this->view('public/blog/show', [
            'post' => $post,
            'seo' => BlogPostSeo::forPost((int) $post['id']) ?? [],
            'categories' => BlogCategory::forPost((int) $post['id']),
            'tags' => BlogTag::forPost((int) $post['id']),
            'related' => BlogPost::relatedPosts((int) $post['id']),
            'comments' => BlogComment::approvedForPost((int) $post['id']),
        ], 'public/layouts/main');
    }
}
