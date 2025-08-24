<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            \App\Http\Middleware\ResolveTenantFromSubdomain::class,
        ]);
        
        // Register route middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $isLocal = config('app.env') === 'local';
        
        // Prepare upcoming sessions 
        $prepareCommand = $schedule->command('sessions:prepare --queue')
                ->name('prepare-sessions')
                ->withoutOverlapping()
                ->runInBackground();
        
        if ($isLocal) {
            $prepareCommand->everyMinute(); // Every minute for development
        } else {
            $prepareCommand->everyFifteenMinutes(); // Every 15 minutes for production
        }

        // Generate weekly sessions
        $generateCommand = $schedule->command('sessions:generate --queue')
                ->name('generate-sessions')
                ->withoutOverlapping();
        
        if ($isLocal) {
            $generateCommand->everyFiveMinutes(); // Every 5 minutes for development testing
        } else {
            $generateCommand->weekly()->sundays()->at('02:00'); // Weekly for production
        }

        // Cleanup expired tokens
        $cleanupCommand = $schedule->command('tokens:cleanup --queue')
                ->name('cleanup-tokens')
                ->withoutOverlapping();
        
        if ($isLocal) {
            $cleanupCommand->everyTenMinutes(); // Every 10 minutes for development
        } else {
            $cleanupCommand->daily()->at('03:00'); // Daily for production
        }

        // Generate additional sessions (mid-week top-up)
        $topupCommand = $schedule->command('sessions:generate --queue --weeks=2')
                ->name('midweek-session-generation')
                ->withoutOverlapping();
        
        if ($isLocal) {
            $topupCommand->everyTenMinutes(); // Every 10 minutes for development
        } else {
            $topupCommand->weekly()->wednesdays()->at('02:00'); // Weekly for production
        }

        // Generate Quran circle recurring sessions
        $quranSessionsCommand = $schedule->command('quran:generate-sessions --days=30')
                ->name('quran-recurring-sessions')
                ->withoutOverlapping()
                ->runInBackground();
        
        if ($isLocal) {
            $quranSessionsCommand->everyThreeMinutes(); // Every 3 minutes for development
        } else {
            $quranSessionsCommand->daily()->at('01:00'); // Daily for production
        }

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
        //
    })->create();
