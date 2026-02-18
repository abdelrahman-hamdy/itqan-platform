<?php

namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chat\Drawer;
use Wirechat\Wirechat\Livewire\Chat\Group\AddMembers;
use Wirechat\Wirechat\Livewire\Chat\Group\Info as GroupInfo;
use Wirechat\Wirechat\Livewire\Chat\Group\Members;
use Wirechat\Wirechat\Livewire\Chat\Group\Permissions;
use Wirechat\Wirechat\Livewire\Chat\Info;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Modals\Modal;
use Wirechat\Wirechat\Livewire\New\Chat as NewChat;
use Wirechat\Wirechat\Livewire\New\Group as NewGroup;
use Wirechat\Wirechat\Livewire\Pages\Chat as View;
use Wirechat\Wirechat\Livewire\Pages\Chats as Index;
use Wirechat\Wirechat\Livewire\Widgets\Wirechat as WireChatWidget;
use Wirechat\Wirechat\Middleware\BelongsToConversation;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelRegistry;
use Wirechat\Wirechat\Services\WirechatService;

/**
 * Custom WireChat Service Provider
 *
 * Extends ServiceProvider directly and copies all WireChat functionality
 * EXCEPT the route loading. We define our own routes in routes/web/chat.php
 * inside the subdomain group for proper multi-tenant support.
 */
class WireChatServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../vendor/namu/wirechat/config/wirechat.php',
            'wirechat'
        );

        // Register facade
        $this->app->singleton('wirechat', function ($app) {
            return new WirechatService;
        });

        // Register default WireChat panel (required since v0.5.0)
        $this->app->afterResolving(PanelRegistry::class, function (PanelRegistry $registry) {
            $panel = Panel::make()
                ->id('itqan')
                ->default()
                ->registerRoutes(false)
                ->middleware(['web', 'auth:web'])
                ->guards(['web'])
                ->layout('wirechat::layouts.app')
                ->broadcasting(true)
                ->messagesQueue('messages')
                ->eventsQueue('default')
                ->groups(true)
                ->maxGroupMembers(1000)
                ->attachments(true)
                ->chatsSearch(true)
                ->searchableAttributes(['name'])
                ->createChatAction(true)
                ->createGroupAction(true);

            $registry->register($panel);
        });
    }

    /**
     * Bootstrap services - skip route loading for multi-tenant support
     */
    public function boot(): void
    {
        // Load Livewire components
        $this->loadLivewireComponents();

        // NOTE: We intentionally DO NOT load routes from:
        // $this->loadRoutesFrom(__DIR__.'/../../vendor/namu/wirechat/routes/web.php');
        // Routes are defined in routes/web.php inside the subdomain group

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

            $this->publishes([
                __DIR__.'/../../vendor/namu/wirechat/database/migrations' => database_path('migrations'),
            ], 'wirechat-migrations');
        }

        // Load channels for broadcasting
        $this->loadRoutesFrom(__DIR__.'/../../vendor/namu/wirechat/routes/channels.php');

        // Load assets (Blade directives)
        $this->loadAssets();

        // Load styles
        $this->loadStyles();

        // Register middlewares
        $this->registerMiddlewares();

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../../vendor/namu/wirechat/lang', 'wirechat');
    }

    /**
     * Register all Livewire components
     */
    protected function loadLivewireComponents(): void
    {
        // Pages
        Livewire::component('wirechat.pages.index', Index::class);
        Livewire::component('wirechat.pages.view', View::class);

        // Chats
        Livewire::component('wirechat.chats', Chats::class);

        // Modal
        Livewire::component('wirechat.modal', Modal::class);

        // New chat/group
        Livewire::component('wirechat.new.chat', NewChat::class);
        Livewire::component('wirechat.new.group', NewGroup::class);

        // Chat/Group related components
        Livewire::component('wirechat.chat', Chat::class);
        Livewire::component('wirechat.chat.info', Info::class);
        Livewire::component('wirechat.chat.group.info', GroupInfo::class);
        Livewire::component('wirechat.chat.drawer', Drawer::class);
        Livewire::component('wirechat.chat.group.add-members', AddMembers::class);
        Livewire::component('wirechat.chat.group.members', Members::class);
        Livewire::component('wirechat.chat.group.permissions', Permissions::class);

        // Stand-alone widget component
        Livewire::component('wirechat', WireChatWidget::class);
    }

    /**
     * Register middlewares
     */
    protected function registerMiddlewares(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('belongsToConversation', BelongsToConversation::class);
    }

    /**
     * Load assets (Blade directives)
     */
    protected function loadAssets(): void
    {
        Blade::directive('wirechatAssets', function () {
            return "<?php if(auth()->check()): ?>
                        <?php
                            echo Blade::render('@livewire(\'wirechat.modal\')');
                            echo Blade::render('<x-wirechat::toast/>');
                            echo Blade::render('<x-wirechat::notification/>');
                        ?>
                <?php endif; ?>";
        });
    }

    /**
     * Load styles
     */
    protected function loadStyles(): void
    {
        $primaryColor = \Wirechat\Wirechat\Facades\Wirechat::getColor();
        Blade::directive('wirechatStyles', function () use ($primaryColor) {
            return "<?php echo <<<EOT
                <style>
                    :root {
                        --wc-brand-primary: {$primaryColor};

                        --wc-light-primary: #fff;  /* white */
                        --wc-light-secondary: oklch(0.967 0.003 264.542);/* --color-gray-100 */
                        --wc-light-accent: oklch(0.985 0.002 247.839);/* --color-gray-50 */
                        --wc-light-border: oklch(0.928 0.006 264.531);/* --color-gray-200 */

                        --wc-dark-primary: oklch(0.21 0.034 264.665); /* --color-zinc-900 */
                        --wc-dark-secondary: oklch(0.278 0.033 256.848);/* --color-zinc-800 */
                        --wc-dark-accent: oklch(0.373 0.034 259.733);/* --color-zinc-700 */
                        --wc-dark-border: oklch(0.373 0.034 259.733);/* --color-zinc-700 */
                    }
                    [x-cloak] {
                        display: none !important;
                    }
                </style>
            EOT; ?>";
        });
    }
}
