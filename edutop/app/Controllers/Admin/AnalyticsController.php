<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\BlogPost;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageView;
use App\Models\User;

class AnalyticsController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/analytics/index', [
            'leadsTotal' => Lead::count(),
            'leadsStatusCounts' => Lead::statusCounts(),
            'leadsTypeCounts' => Lead::typeCounts(),
            'leadsMonthly' => Lead::monthlyCounts(6),
            'blogTotal' => BlogPost::count(),
            'blogStatusCounts' => BlogPost::statusCounts(),
            'pagesTotal' => Page::count(),
            'pagesStatusCounts' => Page::statusCounts(),
            'mediaTotal' => Media::count(),
            'usersTotal' => User::count(),
            'totalViews' => PageView::countTotal(),
            'views30' => PageView::countSince(30),
            'views7' => PageView::countSince(7),
            'viewsToday' => PageView::countToday(),
            'viewsYesterday' => PageView::countYesterday(),
            'viewsMonth' => PageView::countThisMonth(),
            'viewsYear' => PageView::countThisYear(),
            'onlineNow' => PageView::onlineNow(5),
            'uniqueVisitors30' => PageView::uniqueVisitorsSince(30),
            'dailyCounts' => PageView::dailyCounts(30),
            'topPages' => PageView::topPages(15),
            'topReferrers' => PageView::topReferrers(10),
            'agents' => PageView::agentBreakdown(30),
            'recentVisits' => PageView::recent(20),
        ], 'admin/layouts/main');
    }
}
