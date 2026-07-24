<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): void
    {
        $roles = Role::all('name ASC');
        foreach ($roles as &$role) {
            $role['permission_count'] = count(Role::permissionSlugs((int) $role['id']));
            $role['user_count'] = Role::userCount((int) $role['id']);
        }

        $this->view('admin/roles/index', ['roles' => $roles], 'admin/layouts/main');
    }

    /** Read-only permissions x roles grid for at-a-glance RBAC auditing; editing stays on edit(). */
    public function matrix(Request $request): void
    {
        $roles = Role::all('name ASC');
        $permissionModules = require dirname(__DIR__, 3) . '/config/permissions.php';

        $grantsByRole = [];
        foreach ($roles as $role) {
            $grantsByRole[$role['id']] = $role['slug'] === 'super-admin'
                ? null // null = "all", rendered distinctly rather than checking every cell
                : Role::permissionSlugs((int) $role['id']);
        }

        $this->view('admin/roles/matrix', [
            'roles' => $roles,
            'permissionModules' => $permissionModules,
            'grantsByRole' => $grantsByRole,
        ], 'admin/layouts/main');
    }

    public function edit(Request $request): void
    {
        $id = (int) $request->param('id');
        $role = Role::find($id);
        if (!$role) {
            $this->flashError('Role not found.');
            $this->redirect('/admin/roles');
        }

        $permissionModules = require dirname(__DIR__, 3) . '/config/permissions.php';
        $allPermissions = Database::all('SELECT id, slug, module, label FROM permissions ORDER BY module, slug');
        $granted = Role::permissionSlugs($id);

        $this->view('admin/roles/edit', [
            'role' => $role,
            'permissionModules' => $permissionModules,
            'allPermissions' => $allPermissions,
            'granted' => $granted,
            'isSuperAdmin' => $role['slug'] === 'super-admin',
        ], 'admin/layouts/main');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        $role = Role::find($id);
        if (!$role) {
            $this->flashError('Role not found.');
            $this->redirect('/admin/roles');
        }

        if ($role['slug'] === 'super-admin') {
            $this->flashError('Super Admin always has full access and cannot be edited.');
            $this->redirect('/admin/roles');
        }

        $submittedIds = $request->input('permissions', []);
        $submittedIds = is_array($submittedIds) ? array_map('intval', $submittedIds) : [];

        Role::setPermissions($id, $submittedIds);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'roles', "Updated permissions for role '{$role['name']}'", $request->ip());

        $this->flashSuccess('Role permissions updated.');
        $this->redirect('/admin/roles');
    }
}
