<?php

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (!Session::get('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf_token');
    }

    public static function verify(?string $submitted): bool
    {
        $token = Session::get('_csrf_token');
        if (!$token || !$submitted) {
            return false;
        }
        return hash_equals($token, $submitted);
    }
}
