<?php

use App\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'EduTop'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => Env::get('APP_URL', ''),
    'timezone' => 'UTC',
];
