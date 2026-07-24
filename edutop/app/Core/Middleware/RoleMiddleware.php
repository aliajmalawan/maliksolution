<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/** Route usage: 'RoleMiddleware:users.manage' — the part after ":" is the required permission slug. */
class RoleMiddleware
{
    public function handle(Request $request, ?string $param = null): bool
    {
        if (!Auth::check()) {
            Response::redirect('/admin/login');
        }

        if ($param !== null && !Auth::can($param)) {
            Response::abort(403, 'You do not have permission to access this page.');
        }

        return true;
    }
}
