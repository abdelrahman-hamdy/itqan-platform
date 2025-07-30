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
            ->colors([
                'primary' => Color::Blue, // Default color, will be overridden by CSS
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'gray' => Color::Gray,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(asset('images/favicon.ico')) // Default favicon
            ->brandName('منصة إتقان - لوحة التحكم') // Default brand name
            ->brandLogo(asset('images/logo.png')) // Default logo
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                \App\Filament\Resources\AcademyManagementResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\AcademyContextWidget::class,
                \App\Filament\Widgets\PlatformOverviewWidget::class,
                \App\Filament\Widgets\AcademyStatsWidget::class,
                \App\Filament\Widgets\RecentActivitiesWidget::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                AcademyContext::class, // Add academy context middleware
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                'إدارة النظام',
                'الدورات المسجلة',
                'إدارة المحتوى',
                'الإعدادات',
                'التقارير',
            ])
            ->topNavigation(false) // Use sidebar navigation for RTL support
            ->renderHook('panels::page.start', fn (): string => '<div dir="rtl" class="filament-rtl">') // RTL wrapper
            ->renderHook('panels::page.end', fn (): string => '</div>') // Close RTL wrapper
            ->renderHook(
                'panels::topbar.start',
                fn (): string => view('filament.hooks.academy-selector')->render()
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => view('filament.hooks.academy-branding')->render()
            );
    }
    
    public function boot(): void
    {
        // Set the locale to Arabic
        app()->setLocale('ar');
    }
}
