<?php

namespace App\Core;

class Request
{
    private array $params = [];

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Route path relative to the app base, e.g. "/admin/users/5" */
    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $base = self::basePath();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . ltrim($uri, '/');
        $uri = rtrim($uri, '/');

        return $uri === '' ? '/' : $uri;
    }

    public static function basePath(): string
    {
        // Derive the app's base path from the front controller's location,
        // so this works regardless of the folder name the app is deployed under.
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        // SCRIPT_NAME points at public/index.php; the base is one level up.
        $base = rtrim(dirname($scriptDir), '/');
        return $base === '.' ? '' : $base;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->params;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->method() === 'GET' ? $_GET : $_POST;
    }

    public function only(array $keys): array
    {
        $source = $this->all();
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $source)) {
                $result[$key] = $source[$key];
            }
        }
        return $result;
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function ip(): string
    {
        // XAMPP/local single-server setup: trust REMOTE_ADDR directly.
        // If deployed behind a trusted proxy/load balancer, validate and use
        // X-Forwarded-For from that trusted proxy only.
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }
}
