<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\BlogPost;
use App\Models\Lead;
use App\Models\LoginHistory;
use App\Models\PageView;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/dashboard/index', [
            'totalUsers' => User::count(),
            'totalBlogs' => BlogPost::count(),
            'totalLeads' => Lead::count(),
            'totalVisitors' => PageView::countTotal(),
            'dailyCounts' => PageView::dailyCounts(30),
            'topPages' => PageView::topPages(5),
            'recentDemoRequests' => Lead::recentByType('demo', 5),
            'recentActivity' => ActivityLog::recent(10),
            'recentLogins' => LoginHistory::recent(10),
        ], 'admin/layouts/main');
    }
}
