<?php

namespace App\Core;

class Response
{
    public static function redirect(string $path): never
    {
        $url = Request::basePath() . $path;
        header('Location: ' . $url, true, 302);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        $viewMap = [403 => 'errors/403', 404 => 'errors/404', 500 => 'errors/500'];
        $view = $viewMap[$status] ?? 'errors/500';
        echo View::render($view, ['message' => $message], 'public/layouts/minimal');
        exit;
    }
}
