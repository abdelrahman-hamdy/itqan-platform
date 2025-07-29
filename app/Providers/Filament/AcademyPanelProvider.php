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
use App\Models\Academy;

class AcademyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('academy')
            ->path('/panel')
            ->tenant(Academy::class)
            ->tenantMenuItems([
                // Define tenant switcher items if needed
            ])
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'gray' => Color::Gray,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(asset('images/favicon.ico'))
            ->brandName('لوحة إدارة الأكاديمية')
            ->brandLogo(asset('images/logo.png'))
            ->navigationGroups([
                'لوحة التحكم' => 'لوحة التحكم',
                'إدارة المستخدمين' => 'إدارة المستخدمين',
                'المحتوى التعليمي' => 'المحتوى التعليمي',
                'الجلسات والدورات' => 'الجلسات والدورات',
                'التقارير المالية' => 'التقارير المالية',
                'الإعدادات' => 'الإعدادات',
            ])
            ->discoverResources(in: app_path('Filament/Academy/Resources'), for: 'App\\Filament\\Academy\\Resources')
            ->discoverPages(in: app_path('Filament/Academy/Pages'), for: 'App\\Filament\\Academy\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Academy/Widgets'), for: 'App\\Filament\\Academy\\Widgets')
            ->widgets([
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->login()
            ->profile();
    }
}
