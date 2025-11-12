<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ChatifySubdomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Chatify bindings (since we're disabling auto-discovery)
        app()->bind('ChatifyMessenger', function () {
            return new \Chatify\ChatifyMessenger;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load Chatify views (since we're disabling auto-discovery)
        $this->loadViewsFrom(base_path('vendor/munafio/chatify/src/views'), 'Chatify');

        // Override Chatify route loading to be subdomain-aware
        $this->loadSubdomainAwareChatifyRoutes();
    }

    /**
     * Load Chatify routes within subdomain context
     */
    protected function loadSubdomainAwareChatifyRoutes()
    {
        Route::domain('{subdomain}.'.config('app.domain'))->group(function () {
            // Web routes
            Route::prefix(config('chatify.routes.prefix'))
                ->middleware(config('chatify.routes.middleware'))
                ->namespace(config('chatify.routes.namespace'))
                ->group(function () {
                    $this->loadRoutesFrom(base_path('routes/chatify/web.php'));
                });

            // API routes
            Route::prefix(config('chatify.api_routes.prefix'))
                ->middleware(config('chatify.api_routes.middleware'))
                ->namespace(config('chatify.api_routes.namespace'))
                ->group(function () {
                    $this->loadRoutesFrom(base_path('routes/chatify/api.php'));
                });
        });
    }
}