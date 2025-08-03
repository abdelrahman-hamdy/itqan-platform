<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use App\Helpers\AcademyHelper;

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
        
        // Share academy context with all views
        View::composer('*', function ($view) {
            $view->with('currentAcademy', AcademyHelper::getCurrentAcademy());
            $view->with('hasAcademySelected', AcademyHelper::hasAcademySelected());
        });
    }
}
