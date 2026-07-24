<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\BlogComment;

class BlogCommentController extends Controller
{
    public function index(Request $request): void
    {
        $status = (string) $request->input('status', 'pending');
        $this->view('admin/blog/comments/index', [
            'comments' => BlogComment::adminList($status),
            'statusFilter' => $status,
            'pendingCount' => BlogComment::pendingCount(),
        ], 'admin/layouts/main');
    }

    public function approve(Request $request): void
    {
        $this->changeStatus($request, 'approved');
    }

    public function spam(Request $request): void
    {
        $this->changeStatus($request, 'spam');
    }

    private function changeStatus(Request $request, string $status): void
    {
        $id = (int) $request->param('id');
        BlogComment::setStatus($id, $status);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'blog', "Marked comment #{$id} as {$status}", $request->ip());

        $this->flashSuccess('Comment updated.');
        $this->redirect('/admin/blog/comments');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->param('id');
        BlogComment::delete($id);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'blog', "Deleted comment #{$id}", $request->ip());

        $this->flashSuccess('Comment deleted.');
        $this->redirect('/admin/blog/comments');
    }
}
