<?php

namespace App\Core\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware
{
    public function handle(Request $request, ?string $param = null): bool
    {
        if ($request->isPost() && !Csrf::verify($request->input('_csrf'))) {
            // 419 ("Page Expired") is a common convention but isn't a registered
            // HTTP status, so some SAPI/server combos coerce it to a bare 500.
            // 403 is standard and still accurately conveys a rejected request.
            Response::abort(403, 'Your session has expired. Please refresh the page and try again.');
        }
        return true;
    }
}
