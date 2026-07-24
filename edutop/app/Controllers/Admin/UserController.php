<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/users/index', [
            'users' => User::allWithRoles(),
        ], 'admin/layouts/main');
    }

    public function create(Request $request): void
    {
        $this->view('admin/users/edit', [
            'user' => null,
            'roles' => Role::all('name ASC'),
        ], 'admin/layouts/main');
    }

    public function store(Request $request): void
    {
        $name = Sanitizer::text((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $roleId = (int) $request->input('role_id');

        $validator = new Validator(['name' => $name, 'email' => $email, 'password' => $password]);
        $validator->required('name')->required('email')->email('email')->required('password');

        if (!$validator->fails() && User::findByEmail($email)) {
            $this->flashError('That email address is already in use.');
            $this->redirect('/admin/users/create');
        }

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect('/admin/users/create');
        }

        $id = User::create($name, $email, $password, $roleId ?: (int) Role::findBySlug('editor')['id']);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'create', 'users', "Created user #{$id} ({$email})", $request->ip());

        $this->flashSuccess('User created successfully.');
        $this->redirect('/admin/users');
    }

    public function edit(Request $request): void
    {
        $id = (int) $request->param('id');
        $user = User::withRole($id);
        if (!$user) {
            $this->flashError('User not found.');
            $this->redirect('/admin/users');
        }

        $this->view('admin/users/edit', [
            'user' => $user,
            'roles' => Role::all('name ASC'),
        ], 'admin/layouts/main');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        $existing = User::find($id);
        if (!$existing) {
            $this->flashError('User not found.');
            $this->redirect('/admin/users');
        }

        $name = Sanitizer::text((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $roleId = (int) $request->input('role_id');
        $status = $request->input('status') === 'inactive' ? 'inactive' : 'active';
        $password = (string) $request->input('password');

        $validator = new Validator(['name' => $name, 'email' => $email]);
        $validator->required('name')->required('email')->email('email');

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect("/admin/users/{$id}/edit");
        }

        User::updateProfile($id, $name, $email, $roleId, $status);

        if ($password !== '') {
            if (strlen($password) < 10) {
                $this->flashError('New password must be at least 10 characters.');
                $this->redirect("/admin/users/{$id}/edit");
            }
            User::updatePassword($id, $password);
        }

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'users', "Updated user #{$id}", $request->ip());

        $this->flashSuccess('User updated successfully.');
        $this->redirect('/admin/users');
    }

    public function destroy(Request $request): void
    {
        $id = (int) $request->param('id');
        $actor = Auth::user();

        if ($actor && (int) $actor['id'] === $id) {
            $this->flashError('You cannot delete your own account.');
            $this->redirect('/admin/users');
        }

        User::deleteById($id);
        ActivityLog::record($actor['id'] ?? null, 'delete', 'users', "Deleted user #{$id}", $request->ip());

        $this->flashSuccess('User deleted.');
        $this->redirect('/admin/users');
    }
}
