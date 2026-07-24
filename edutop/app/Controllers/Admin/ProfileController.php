<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Core\Validator;
use App\Models\ActivityLog;

class ProfileController extends Controller
{
    public function edit(Request $request): void
    {
        $this->view('admin/profile/edit', [
            'user' => Auth::user(),
        ], 'admin/layouts/main');
    }

    public function update(Request $request): void
    {
        $user = Auth::user();
        $name = Sanitizer::text((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');

        $validator = new Validator(['name' => $name, 'email' => $email]);
        $validator->required('name')->required('email')->email('email');

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect('/admin/profile');
        }

        Database::query('UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?', [$name, $email, $user['id']]);

        if ($newPassword !== '') {
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $this->flashError('Current password is incorrect.');
                $this->redirect('/admin/profile');
            }
            if (strlen($newPassword) < 10) {
                $this->flashError('New password must be at least 10 characters.');
                $this->redirect('/admin/profile');
            }
            \App\Models\User::updatePassword((int) $user['id'], $newPassword);
        }

        ActivityLog::record((int) $user['id'], 'update', 'profile', 'Updated own profile', $request->ip());

        $this->flashSuccess('Profile updated.');
        $this->redirect('/admin/profile');
    }
}
