<?php

namespace App\Providers\Filament;

use App\Models\Academy;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

class AcademicTeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('academic-teacher')
            ->path('/academic-teacher-panel')
            ->tenant(Academy::class, slugAttribute: 'subdomain')
            ->tenantDomain('{tenant:subdomain}.'.config('app.domain'))
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'gray' => Color::Gray,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(asset('images/favicon.ico'))
            ->brandName('لوحة المعلم الأكاديمي')
            ->brandLogo(asset('images/itqan-logo.svg'))
            ->navigationGroups([
                'لوحة التحكم',
                'دروسي الفردية',
                'الدورات التفاعلية',
                'جلساتي',
                'الواجبات الأكاديمية',
                'التقييمات',
                'ملفي الشخصي',
            ])
            ->discoverResources(in: app_path('Filament/AcademicTeacher/Resources'), for: 'App\\Filament\\AcademicTeacher\\Resources')
            ->discoverPages(in: app_path('Filament/AcademicTeacher/Pages'), for: 'App\\Filament\\AcademicTeacher\\Pages')
            ->pages([
                \App\Filament\AcademicTeacher\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/AcademicTeacher/Widgets'), for: 'App\\Filament\\AcademicTeacher\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\AcademicTeacher\Widgets\AcademicTeacherOverviewWidget::class,
                \App\Filament\AcademicTeacher\Widgets\AcademicCalendarWidget::class,
                \App\Filament\AcademicTeacher\Widgets\RecentAcademicSessionsWidget::class,
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
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(true)
                    ->timezone(config('app.timezone'))
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
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.academic-teacher.render-hooks.messages-count')->render()
            );
    }
}
