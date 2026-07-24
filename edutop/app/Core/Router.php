<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, string $handler, array $middleware): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['path'], $path);
            if ($params === null) {
                continue;
            }

            $request = new Request($params);

            foreach ($route['middleware'] as $middlewareEntry) {
                [$middlewareName, $middlewareParam] = array_pad(explode(':', $middlewareEntry, 2), 2, null);
                $middlewareClass = "App\\Core\\Middleware\\{$middlewareName}";

                if (!class_exists($middlewareClass)) {
                    Response::abort(500, 'Middleware not found: ' . $middlewareName);
                }

                $middleware = new $middlewareClass();
                $result = $middleware->handle($request, $middlewareParam);
                if ($result === false) {
                    return; // middleware already sent a response
                }
            }

            $this->callHandler($route['handler'], $request);
            return;
        }

        \App\Services\PageRenderer::renderNotFound();
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        if (count($routeParts) !== count($requestParts)) {
            return null;
        }

        $params = [];
        foreach ($routeParts as $i => $part) {
            if (str_starts_with($part, ':')) {
                $params[substr($part, 1)] = $requestParts[$i];
            } elseif ($part !== $requestParts[$i]) {
                return null;
            }
        }

        return $params;
    }

    private function callHandler(string $handler, Request $request): void
    {
        [$controllerName, $action] = explode('@', $handler);
        $class = "App\\Controllers\\{$controllerName}";

        if (!class_exists($class)) {
            Response::abort(500, 'Controller not found');
        }

        $controller = new $class();
        if (!method_exists($controller, $action)) {
            Response::abort(500, 'Action not found');
        }

        $controller->$action($request);
    }
}
