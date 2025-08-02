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
        // Temporarily disabled all global middleware to debug route registration
        // $middleware->web([
        //     \App\Http\Middleware\TenantMiddleware::class,
        //     // Temporarily disabled AcademyContext to prevent infinite loops
        //     // \App\Http\Middleware\AcademyContext::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
