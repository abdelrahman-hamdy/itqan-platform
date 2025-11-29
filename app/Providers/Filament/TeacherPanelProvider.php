<?php

namespace App\Providers\Filament;

use App\Models\Academy;
use App\Services\AcademyContextService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class TeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('teacher')
            ->path('/teacher-panel')
            ->tenant(Academy::class, slugAttribute: 'subdomain')
            ->tenantDomain('{tenant:subdomain}.'.config('app.domain'))
            ->colors([
                'primary' => Color::Green,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'gray' => Color::Gray,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(asset('images/favicon.ico'))
            ->brandName('لوحة المعلم')
            ->brandLogo(asset('images/itqan-logo.svg'))
            ->navigationGroups([
                'لوحة التحكم',
                'جلساتي',
                'طلبات القرآن',
                'الواجبات',
                'الاختبارات',
                'دوراتي',
                'الشهادات',
                'ملفي الشخصي',
            ])
            ->discoverResources(in: app_path('Filament/Teacher/Resources'), for: 'App\\Filament\\Teacher\\Resources')
            ->discoverPages(in: app_path('Filament/Teacher/Pages'), for: 'App\\Filament\\Teacher\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Teacher/Widgets'), for: 'App\\Filament\\Teacher\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Teacher\Widgets\QuranTeacherOverviewWidget::class,
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
            ->profile()
            ->userMenuItems([
                'profile-page' => \Filament\Navigation\MenuItem::make()
                    ->label('الملف الشخصي العام')
                    ->url(fn (): string => route('teacher.profile', [
                        'subdomain' => auth()->user()->academy->subdomain ?? 'default'
                    ]))
                    ->icon('heroicon-o-user-circle')
                    ->openUrlInNewTab(),
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(true)
                    ->timezone(AcademyContextService::getTimezone())
                    ->locale(config('app.locale'))
                    ->plugins(['interaction', 'dayGrid', 'timeGrid'], false)
                    ->config([
                        'firstDay' => 6, // Saturday start
                        'headerToolbar' => [
                            'left' => 'prev,next today',
                            'center' => 'title',
                            'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
                        ],
                        'slotMinTime' => '06:00:00',
                        'slotMaxTime' => '23:00:00',
                        'height' => 'auto',
                        'expandRows' => true,
                        'nowIndicator' => true,
                        'businessHours' => [
                            'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5], // Sunday to Saturday
                            'startTime' => '08:00',
                            'endTime' => '22:00',
                        ],
                    ]),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<script src="'.asset('js/teacher-breadcrumb-fix.js').'"></script>'
            );
    }
}
