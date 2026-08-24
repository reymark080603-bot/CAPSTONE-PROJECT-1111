<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Disable CSRF verification for login & registration routes to prevent 419 Page Expired
        $middleware->validateCsrfTokens(except: [
            '/staff/login',
            '/student/login',
            '/login',
            '/librarian/login',
            '/register',
        ]);

        $middleware->web([
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\AutoReturnOverdueLoans::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();