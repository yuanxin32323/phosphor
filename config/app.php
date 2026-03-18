<?php declare(strict_types=1);

use Phosphor\Config\AppConfig;
use Phosphor\Config\AppDefinition;
use Apps\Api\Controllers\UserController;
use Apps\Admin\Controllers\DashboardController;

return new AppConfig(
    name: 'Phosphor',
    env: env('APP_ENV', 'local'),
    debug: env('APP_DEBUG', 'true') === 'true',
    apps: [
        'api' => new AppDefinition(
            namespace: 'Apps\\Api',
            prefix: '/api',
            middleware: [],
            controllers: [
                UserController::class,
            ],
        ),
        'admin' => new AppDefinition(
            namespace: 'Apps\\Admin',
            prefix: '/admin',
            middleware: [],
            controllers: [
                DashboardController::class,
            ],
        ),
    ],
);
