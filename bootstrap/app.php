<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // WireChat is now used for all chat functionality
            // Old Chatify routes removed
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\ContentSecurityPolicy::class,
            \App\Http\Middleware\ResolveTenantFromSubdomain::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'interactive.course' => \App\Http\Middleware\InteractiveCourseMiddleware::class,
            'control-participants' => \App\Http\Middleware\CanControlParticipants::class,
            'redirect.authenticated.public' => \App\Http\Middleware\RedirectAuthenticatedPublicViews::class,
            'child.selection' => \App\Http\Middleware\ChildSelectionMiddleware::class,
            'parent.readonly' => \App\Http\Middleware\ParentReadOnlyMiddleware::class,

            // API Middleware
            'api.locale' => \App\Http\Middleware\Api\SetApiLocale::class,
            'api.resolve.academy' => \App\Http\Middleware\Api\ResolveAcademy::class,
            'api.academy.active' => \App\Http\Middleware\Api\EnsureAcademyActive::class,
            'api.academy.registration' => \App\Http\Middleware\Api\EnsureAcademyAllowsRegistration::class,
            'api.user.academy' => \App\Http\Middleware\Api\EnsureUserBelongsToAcademy::class,
            'api.is.student' => \App\Http\Middleware\Api\EnsureUserIsStudent::class,
            'api.is.parent' => \App\Http\Middleware\Api\EnsureUserIsParent::class,
            'api.is.teacher' => \App\Http\Middleware\Api\EnsureUserIsTeacher::class,
            'api.is.quran-teacher' => \App\Http\Middleware\Api\EnsureUserIsQuranTeacher::class,
            'api.is.academic-teacher' => \App\Http\Middleware\Api\EnsureUserIsAcademicTeacher::class,
            'api.cache' => \App\Http\Middleware\Api\CacheHeaders::class,
            'api.log' => \App\Http\Middleware\Api\LogApiRequests::class,
        ]);

        // CRITICAL: Exclude webhook endpoints from CSRF protection
        // External services send webhooks without CSRF tokens
        $middleware->validateCsrfTokens(except: [
            'webhooks/livekit',   // LiveKit webhook endpoint
            'webhooks/paymob',    // Paymob payment webhook
            'webhooks/easykash',  // EasyKash payment webhook
            'api/payments/easykash/callback',  // EasyKash payment callback
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $isLocal = config('app.env') === 'local';

        // Extend Quran circle schedules
        $extendCommand = $schedule->command('quran:extend-schedules --days=180')
            ->name('extend-circle-schedules')
            ->withoutOverlapping();

        if ($isLocal) {
            $extendCommand->everyFifteenMinutes(); // Every 15 minutes for development
        } else {
            $extendCommand->weekly()->mondays()->at('01:30'); // Weekly for production
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if (str_contains($request->path(), 'academic-packages') && str_contains($request->path(), 'subscribe')) {
                \Log::error('ACADEMIC SUBSCRIPTION EXCEPTION CAUGHT', [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'data' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
    })->create();
