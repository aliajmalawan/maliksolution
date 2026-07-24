<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Lead;

class LeadController extends Controller
{
    public function index(Request $request): void
    {
        $type = (string) $request->input('type', '');
        $status = in_array($request->input('status'), ['new', 'contacted', 'closed'], true) ? (string) $request->input('status') : '';
        $search = trim((string) $request->input('q', ''));

        $this->view('admin/leads/index', [
            'leads' => Lead::adminList($type, $status, $search),
            'typeFilter' => $type,
            'statusFilter' => $status,
            'search' => $search,
            'statusCounts' => Lead::statusCounts($type),
            'typeCounts' => [
                'contact' => Lead::countByType('contact'),
                'demo' => Lead::countByType('demo'),
                'admission' => Lead::countByType('admission'),
            ],
        ], 'admin/layouts/main');
    }

    public function show(Request $request): void
    {
        $id = (int) $request->param('id');
        $lead = Lead::find($id);
        if (!$lead) {
            $this->flashError('Lead not found.');
            $this->redirect('/admin/leads');
        }

        $this->view('admin/leads/show', ['lead' => $lead], 'admin/layouts/main');
    }

    public function updateStatus(Request $request): void
    {
        $id = (int) $request->param('id');
        $status = in_array($request->input('status'), ['new', 'contacted', 'closed'], true) ? $request->input('status') : 'new';

        Lead::setStatus($id, $status);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'leads', "Updated lead #{$id} status to {$status}", $request->ip());

        $this->flashSuccess('Lead updated.');
        // Inline updates from the list return to the list; detail-page updates stay on the detail.
        $this->redirect($request->input('return') === 'index' ? '/admin/leads' : "/admin/leads/{$id}");
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->param('id');
        Lead::delete($id);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'leads', "Deleted lead #{$id}", $request->ip());

        $this->flashSuccess('Lead deleted.');
        $this->redirect('/admin/leads');
    }
}
