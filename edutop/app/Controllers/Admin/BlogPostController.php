<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostSeo;
use App\Models\BlogTag;

class BlogPostController extends Controller
{
    public function index(Request $request): void
    {
        $status = (string) $request->input('status', '');
        $this->view('admin/blog/posts/index', [
            'posts' => BlogPost::adminList($status),
            'statusFilter' => $status,
        ], 'admin/layouts/main');
    }

    public function create(Request $request): void
    {
        $this->view('admin/blog/posts/form', [
            'post' => null,
            'categories' => BlogCategory::all('name ASC'),
            'tags' => BlogTag::all('name ASC'),
            'postCategoryIds' => [],
            'postTagIds' => [],
            'seo' => [],
        ], 'admin/layouts/main');
    }

    public function store(Request $request): void
    {
        $this->save($request, null);
    }

    public function edit(Request $request): void
    {
        $post = $this->findPostOr404($request);

        $this->view('admin/blog/posts/form', [
            'post' => $post,
            'categories' => BlogCategory::all('name ASC'),
            'tags' => BlogTag::all('name ASC'),
            'postCategoryIds' => array_column(BlogCategory::forPost((int) $post['id']), 'id'),
            'postTagIds' => array_column(BlogTag::forPost((int) $post['id']), 'id'),
            'seo' => BlogPostSeo::forPost((int) $post['id']) ?? [],
        ], 'admin/layouts/main');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        $post = BlogPost::find($id);
        if (!$post) {
            $this->flashError('Post not found.');
            $this->redirect('/admin/blog/posts');
        }
        $this->save($request, $id);
    }

    private function save(Request $request, ?int $id): void
    {
        $title = Sanitizer::text((string) $request->input('title'));
        $slugInput = Sanitizer::slug((string) $request->input('slug'));
        $slug = $slugInput !== '' ? $slugInput : Sanitizer::slug($title);
        $excerpt = Sanitizer::text((string) $request->input('excerpt'));
        $content = Sanitizer::richText((string) $request->input('content'));
        $featuredImage = ((int) $request->input('featured_image')) ?: null;
        $commentsEnabled = $request->input('comments_enabled') === '1' ? 1 : 0;
        $isFeatured = $request->input('is_featured') === '1' ? 1 : 0;

        $requestedStatus = $request->input('status') === 'scheduled' ? 'scheduled'
            : ($request->input('status') === 'published' ? 'published' : 'draft');

        // Publishing/scheduling requires blog.publish; without it, silently save as a draft.
        $wasForcedToDraft = false;
        if (in_array($requestedStatus, ['published', 'scheduled'], true) && !Auth::can('blog.publish')) {
            $requestedStatus = 'draft';
            $wasForcedToDraft = true;
        }

        $scheduledAt = null;
        $publishedAt = null;
        if ($requestedStatus === 'scheduled') {
            $scheduledAt = (string) $request->input('scheduled_at') ?: null;
        } elseif ($requestedStatus === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $validator = new Validator(['title' => $title, 'slug' => $slug]);
        $validator->required('title')->required('slug');

        $redirectOnError = $id ? "/admin/blog/posts/{$id}/edit" : '/admin/blog/posts/create';

        if (!$validator->fails() && BlogPost::slugExists($slug, $id)) {
            $this->flashError('That slug is already in use by another post.');
            $this->redirect($redirectOnError);
        }

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect($redirectOnError);
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt ?: null,
            'content' => $content,
            'featured_image' => $featuredImage,
            'author_id' => Auth::user()['id'] ?? null,
            'status' => $requestedStatus,
            'scheduled_at' => $scheduledAt,
            'published_at' => $publishedAt,
            'comments_enabled' => $commentsEnabled,
            'is_featured' => $isFeatured,
        ];

        if ($id) {
            // Preserve original published_at if the post was already published before this edit.
            $existing = BlogPost::find($id);
            if ($existing['published_at'] && $requestedStatus === 'published') {
                $data['published_at'] = $existing['published_at'];
            }
            BlogPost::update($id, $data);
            $postId = $id;
        } else {
            $postId = BlogPost::create($data);
        }

        $categoryIds = $request->input('categories', []);
        BlogPost::setCategories($postId, is_array($categoryIds) ? array_map('intval', $categoryIds) : []);

        $tagIds = $request->input('tags', []);
        BlogPost::setTags($postId, is_array($tagIds) ? array_map('intval', $tagIds) : []);

        // Basic inline SEO fields (fuller SEO controls live on the separate SEO
        // tab). BlogPostSeo::upsert() only touches the columns it's given, so
        // this can't clobber og_title/canonical_url/etc. set there.
        BlogPostSeo::upsert($postId, [
            'seo_title' => Sanitizer::text((string) $request->input('seo_title')) ?: null,
            'meta_description' => Sanitizer::text((string) $request->input('meta_description')) ?: null,
        ]);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, $id ? 'update' : 'create', 'blog', "Saved post #{$postId} ({$title})", $request->ip());

        if ($wasForcedToDraft) {
            $this->flashError("You don't have permission to publish. Saved as a draft instead.");
        } else {
            $this->flashSuccess('Post saved.');
        }
        $this->redirect("/admin/blog/posts/{$postId}/edit");
    }

    public function destroy(Request $request): void
    {
        $post = $this->findPostOr404($request);
        Database::query('DELETE FROM blog_posts WHERE id = ?', [$post['id']]);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'blog', "Deleted post #{$post['id']} ({$post['title']})", $request->ip());

        $this->flashSuccess('Post deleted.');
        $this->redirect('/admin/blog/posts');
    }

    public function seo(Request $request): void
    {
        $post = $this->findPostOr404($request);
        $this->view('admin/blog/posts/seo', [
            'post' => $post,
            'seo' => BlogPostSeo::forPost((int) $post['id']) ?? [],
        ], 'admin/layouts/main');
    }

    public function updateSeo(Request $request): void
    {
        $id = (int) $request->param('id');
        $post = BlogPost::find($id);
        if (!$post) {
            $this->flashError('Post not found.');
            $this->redirect('/admin/blog/posts');
        }

        $schemaMarkup = trim((string) $request->input('schema_markup'));
        if ($schemaMarkup !== '' && json_decode($schemaMarkup) === null) {
            $this->flashError('Schema Markup must be valid JSON (or left blank).');
            $this->redirect("/admin/blog/posts/{$id}/seo");
        }

        BlogPostSeo::upsert($id, [
            'seo_title' => Sanitizer::text((string) $request->input('seo_title')) ?: null,
            'meta_description' => Sanitizer::text((string) $request->input('meta_description')) ?: null,
            'meta_keywords' => Sanitizer::text((string) $request->input('meta_keywords')) ?: null,
            'canonical_url' => Sanitizer::text((string) $request->input('canonical_url')) ?: null,
            'og_title' => Sanitizer::text((string) $request->input('og_title')) ?: null,
            'og_description' => Sanitizer::text((string) $request->input('og_description')) ?: null,
            'og_image' => ((int) $request->input('og_image')) ?: null,
            'twitter_card' => in_array($request->input('twitter_card'), ['summary', 'summary_large_image'], true) ? $request->input('twitter_card') : 'summary_large_image',
            'robots' => Sanitizer::text((string) $request->input('robots')) ?: 'index,follow',
            'schema_markup' => $schemaMarkup ?: null,
        ]);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'blog', "Updated SEO for post #{$id}", $request->ip());

        $this->flashSuccess('SEO settings saved.');
        $this->redirect("/admin/blog/posts/{$id}/seo");
    }

    private function findPostOr404(Request $request): array
    {
        $id = (int) $request->param('id');
        $post = BlogPost::find($id);
        if (!$post) {
            $this->flashError('Post not found.');
            $this->redirect('/admin/blog/posts');
        }
        return $post;
    }
}
