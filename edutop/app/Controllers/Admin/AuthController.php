<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Env;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;

class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect('/admin/dashboard');
        }

        if (Auth::attemptRememberLogin($request->ip(), $request->userAgent())) {
            $this->redirect('/admin/dashboard');
        }

        $this->view('admin/auth/login', [
            'error' => Session::flash('_flash_error'),
        ], 'admin/layouts/guest');
    }

    public function login(Request $request): void
    {
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');

        $validator = new Validator(['email' => $email, 'password' => $password]);
        $validator->required('email')->email('email')->required('password');

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect('/admin/login');
        }

        try {
            $user = Auth::attempt($email, $password, $request->ip(), $request->userAgent());
        } catch (\RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/admin/login');
        }

        if (!$user) {
            $this->flashError('Invalid email or password.');
            $this->redirect('/admin/login');
        }

        Auth::login($user, $request->ip(), $request->userAgent());

        if ($request->input('remember') === '1') {
            Auth::issueRememberCookie((int) $user['id']);
        }

        $this->redirect('/admin/dashboard');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
