<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Models\ActivityLog;
use App\Models\BlogCategory;

class BlogCategoryController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/blog/categories/index', [
            'categories' => BlogCategory::all('name ASC'),
        ], 'admin/layouts/main');
    }

    public function store(Request $request): void
    {
        $name = Sanitizer::text((string) $request->input('name'));
        if ($name === '') {
            $this->flashError('Name is required.');
            $this->redirect('/admin/blog/categories');
        }

        $slug = Sanitizer::slug($name);
        if (BlogCategory::slugExists($slug)) {
            $this->flashError('A category with that name already exists.');
            $this->redirect('/admin/blog/categories');
        }

        BlogCategory::create($name, $slug);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'create', 'blog', "Created category '{$name}'", $request->ip());

        $this->flashSuccess('Category created.');
        $this->redirect('/admin/blog/categories');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->param('id');
        BlogCategory::deleteById($id);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'blog', "Deleted category #{$id}", $request->ip());

        $this->flashSuccess('Category deleted.');
        $this->redirect('/admin/blog/categories');
    }
}
