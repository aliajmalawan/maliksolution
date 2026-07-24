<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Models\ActivityLog;
use App\Models\BlogTag;

class BlogTagController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/blog/tags/index', [
            'tags' => BlogTag::all('name ASC'),
        ], 'admin/layouts/main');
    }

    public function store(Request $request): void
    {
        $name = Sanitizer::text((string) $request->input('name'));
        if ($name === '') {
            $this->flashError('Name is required.');
            $this->redirect('/admin/blog/tags');
        }

        $slug = Sanitizer::slug($name);
        if (BlogTag::slugExists($slug)) {
            $this->flashError('A tag with that name already exists.');
            $this->redirect('/admin/blog/tags');
        }

        BlogTag::create($name, $slug);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'create', 'blog', "Created tag '{$name}'", $request->ip());

        $this->flashSuccess('Tag created.');
        $this->redirect('/admin/blog/tags');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->param('id');
        BlogTag::deleteById($id);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'blog', "Deleted tag #{$id}", $request->ip());

        $this->flashSuccess('Tag deleted.');
        $this->redirect('/admin/blog/tags');
    }
}
