<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;

use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Services\AcademyContextService;
use App\Http\Middleware\AcademyContext;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {        
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->login()
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->brandName('منصة إتقان - لوحة التحكم')
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->font('Cairo')
            ->favicon(asset('images/favicon.ico'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\PlatformOverviewWidget::class,
                \App\Filament\Widgets\QuranOverviewWidget::class,
                \App\Filament\Widgets\AcademyStatsWidget::class,
                \App\Filament\Widgets\AcademyContextWidget::class,
                \App\Filament\Widgets\RecentActivitiesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                AcademyContext::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->sidebarFullyCollapsibleOnDesktop()
            ->navigationGroups([
                'إدارة النظام',
                'إدارة المستخدمين',
                'إدارة القرآن',
                'إدارة التعليم الأكاديمي',
                'إدارة الدورات المسجلة',
                'الإعدادات',
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                'panels::topbar.start', 
                fn (): string => view('filament.hooks.academy-selector')->render()
            );
    }
    
    public function boot(): void
    {
        // Temporarily increase memory limit to debug memory exhaustion
        ini_set('memory_limit', '2048M');
        
        // Set Arabic locale for the admin panel
        app()->setLocale('ar');
    }
}
