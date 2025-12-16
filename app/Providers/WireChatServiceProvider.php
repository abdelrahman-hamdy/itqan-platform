<?php

namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\Drawer;
use Namu\WireChat\Livewire\Chat\Group\AddMembers;
use Namu\WireChat\Livewire\Chat\Group\Info as GroupInfo;
use Namu\WireChat\Livewire\Chat\Group\Members;
use Namu\WireChat\Livewire\Chat\Group\Permissions;
use Namu\WireChat\Livewire\Chat\Info;
use Namu\WireChat\Livewire\Chats\Chats;
use Namu\WireChat\Livewire\Modals\Modal;
use Namu\WireChat\Livewire\New\Chat as NewChat;
use Namu\WireChat\Livewire\New\Group as NewGroup;
use Namu\WireChat\Livewire\Pages\Chat as View;
use Namu\WireChat\Livewire\Pages\Chats as Index;
use Namu\WireChat\Livewire\Widgets\WireChat;
use Namu\WireChat\Middleware\BelongsToConversation;
use Namu\WireChat\Services\WireChatService;

/**
 * Custom WireChat Service Provider
 *
 * Extends ServiceProvider directly and copies all WireChat functionality
 * EXCEPT the route loading. We define our own routes in routes/web.php
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
            return new WireChatService;
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
        Livewire::component('wirechat', WireChat::class);
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
        Blade::directive('wirechatStyles', function () {
            $path = __DIR__.'/../../vendor/namu/wirechat/resources/views/components/styles.blade.php';

            return "<?php echo view('wirechat::components.styles')->render(); ?>";
        });
    }
}
