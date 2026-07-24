<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request, ?string $param = null): bool
    {
        if (!Auth::check()) {
            Response::redirect('/admin/login');
        }
        return true;
    }
}
