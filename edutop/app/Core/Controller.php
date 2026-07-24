<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = null): void
    {
        echo View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function withOldInput(array $data): void
    {
        Session::set('_old_input', $data);
    }

    protected function flashSuccess(string $message): void
    {
        Session::set('_flash_success', $message);
    }

    protected function flashError(string $message): void
    {
        Session::set('_flash_error', $message);
    }
}
