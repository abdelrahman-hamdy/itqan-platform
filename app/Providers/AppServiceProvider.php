<?php

namespace App\Providers;

use App\Helpers\AcademyHelper;
use App\Models\AcademicSession;
use App\Models\AcademicSessionAttendance;
use App\Models\QuranSession;
use App\Observers\AcademicSessionAttendanceObserver;
use App\Observers\AcademicSessionObserver;
use App\Observers\MediaObserver;
use App\Observers\QuranSessionObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware aliases
        Route::aliasMiddleware('auth', \App\Http\Middleware\CustomAuthenticate::class);
        Route::aliasMiddleware('role', \App\Http\Middleware\RoleMiddleware::class);
        Route::aliasMiddleware('tenant', \App\Http\Middleware\TenantMiddleware::class);
        Route::aliasMiddleware('academy.context', \App\Http\Middleware\AcademyContext::class);
        Route::aliasMiddleware('resolve.tenant', \App\Http\Middleware\ResolveTenantFromSubdomain::class);

        // Route model bindings handled manually in controllers

        // Share academy context with all views
        View::composer('*', function ($view) {
            $view->with('currentAcademy', AcademyHelper::getCurrentAcademy());
            $view->with('hasAcademySelected', AcademyHelper::hasAcademySelected());
        });

        // Register Media Observer to handle UTF-8 filename sanitization
        Media::observe(MediaObserver::class);

        // Register AcademicSession Observer for auto-progress tracking
        AcademicSession::observe(AcademicSessionObserver::class);

        // Register AcademicSessionAttendance Observer for attendance-based progress tracking
        AcademicSessionAttendance::observe(AcademicSessionAttendanceObserver::class);

        // Register QuranSession Observer for trial request status synchronization
        QuranSession::observe(QuranSessionObserver::class);

        // Override WireChat Info component with custom implementation
        Livewire::component('wirechat.chat.info', \App\Livewire\Chat\Info::class);

        // Render hooks can be added here if needed
    }
}
