<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Env;
use App\Core\Logger;
use App\Core\Middleware\MaintenanceMiddleware;
use App\Core\Middleware\SecurityHeadersMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

$debug = Env::bool('APP_DEBUG', false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    Logger::error($message, ['file' => $file, 'line' => $line, 'severity' => $severity]);
    return true; // don't fall through to PHP's default handler (avoids leaking paths)
});

set_exception_handler(function (\Throwable $e): void {
    Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
    Response::abort(500, 'Something went wrong. Please try again later.');
});

if ((new SecurityHeadersMiddleware())->handle(new Request()) === false) {
    exit; // HTTPS enforcement redirect already sent
}

Session::start();

if ((new MaintenanceMiddleware())->handle(new Request()) === false) {
    exit; // maintenance page already rendered
}

$router = new Router();
require dirname(__DIR__) . '/config/routes.php';

$router->dispatch(new Request());
