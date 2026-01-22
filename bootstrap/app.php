<?php

use App\Http\Middleware\CheckFeatureAccess;
use App\Http\Middleware\ComingSoonMode;
use App\Http\Middleware\EnsureAccountIsSubscribed;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetPermissionsTeamContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/ai.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->web(append: [
            ComingSoonMode::class,
            HandleInertiaRequests::class,
            SetPermissionsTeamContext::class,
        ]);

        $middleware->alias([
            'subscribed' => EnsureAccountIsSubscribed::class,
            'feature' => CheckFeatureAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
