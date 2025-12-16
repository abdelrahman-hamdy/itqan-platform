<?php

namespace App\Providers;

use Namu\WireChat\WireChatServiceProvider as BaseWireChatServiceProvider;

/**
 * Custom WireChat Service Provider
 *
 * Extends the base WireChat service provider but prevents it from
 * loading its default routes. We define our own routes in routes/web.php
 * inside the subdomain group for proper multi-tenant support.
 */
class WireChatServiceProvider extends BaseWireChatServiceProvider
{
    /**
     * Override boot method to skip route loading
     */
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__.'/../../vendor/namu/wirechat/resources/views', 'wirechat');

        // Publish assets (only in console)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../vendor/namu/wirechat/resources/views' => resource_path('views/vendor/wirechat'),
            ], 'wirechat-views');

            $this->publishes([
                __DIR__.'/../../vendor/namu/wirechat/lang' => lang_path('vendor/wirechat'),
            ], 'wirechat-translations');

            $this->publishes([
                __DIR__.'/../../vendor/namu/wirechat/config/wirechat.php' => config_path('wirechat.php'),
            ], 'wirechat-config');
        }

        // Load channels for broadcasting
        $this->loadRoutesFrom(__DIR__.'/../../vendor/namu/wirechat/routes/channels.php');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../../vendor/namu/wirechat/lang', 'wirechat');

        // Register Livewire components
        $this->registerLivewireComponents();

        // Load middlewares
        $this->registerMiddlewares();

        // Load assets and styles
        $this->loadAssets();
        $this->loadStyles();

        // NOTE: We intentionally DO NOT call:
        // $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        // Routes are defined in routes/web.php inside the subdomain group
    }

    /**
     * Register Livewire components
     */
    protected function registerLivewireComponents(): void
    {
        // The base provider registers these, but we need to ensure they're loaded
        \Livewire\Livewire::component('wirechat.chats', \Namu\WireChat\Livewire\Pages\Chats::class);
        \Livewire\Livewire::component('wirechat.chat', \Namu\WireChat\Livewire\Pages\Chat::class);
        \Livewire\Livewire::component('wirechat.chat-box', \Namu\WireChat\Livewire\Chat\ChatBox::class);
        \Livewire\Livewire::component('wirechat.chat-list', \Namu\WireChat\Livewire\Chat\ChatList::class);
        \Livewire\Livewire::component('wirechat.new-chat', \Namu\WireChat\Livewire\Chat\NewChat::class);
        \Livewire\Livewire::component('wirechat.info', \Namu\WireChat\Livewire\Info\Info::class);
    }

    /**
     * Register middlewares
     */
    protected function registerMiddlewares(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('belongsToConversation', \Namu\WireChat\Http\Middleware\BelongsToConversation::class);
    }

    /**
     * Load assets
     */
    protected function loadAssets(): void
    {
        // Assets are loaded via Vite in our custom setup
    }

    /**
     * Load styles
     */
    protected function loadStyles(): void
    {
        // Styles are loaded via our custom CSS
    }
}
