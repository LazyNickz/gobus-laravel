<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareGroups = [
        'web' => [
            // ...existing middleware...
        ],

        'api' => [
            // ...existing middleware...
        ],
    ];

    protected $routeMiddleware = [
        // ...existing middleware...
        'gobus.auth' => \App\Http\Middleware\EnsureUserIsAuthenticated::class,
        'gobus.admin' => \App\Http\Middleware\EnsureAdminIsAuthenticated::class,
    ];
}