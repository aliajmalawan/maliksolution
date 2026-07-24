<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\LoginHistory;

class LogController extends Controller
{
    private const PER_PAGE = 25;

    public function activity(Request $request): void
    {
        $page = max(1, (int) $request->input('page', 1));
        $module = (string) $request->input('module', '');

        $result = ActivityLog::paginate($page, self::PER_PAGE, $module);

        $this->view('admin/logs/activity', [
            'logs' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => (int) ceil($result['total'] / self::PER_PAGE),
            'moduleFilter' => $module,
            'modules' => ActivityLog::distinctModules(),
        ], 'admin/layouts/main');
    }

    public function logins(Request $request): void
    {
        $page = max(1, (int) $request->input('page', 1));
        $status = (string) $request->input('status', '');

        $result = LoginHistory::paginate($page, self::PER_PAGE, $status);

        $this->view('admin/logs/logins', [
            'logins' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => (int) ceil($result['total'] / self::PER_PAGE),
            'statusFilter' => $status,
        ], 'admin/layouts/main');
    }
}
