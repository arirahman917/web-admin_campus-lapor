<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'auth.api' => \App\Http\Middleware\AuthenticateApiToken::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'mobile/register',
            'mobile/login',
            'mobile/reports',
            'chat/send',
            'chat/thread/*/read',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
