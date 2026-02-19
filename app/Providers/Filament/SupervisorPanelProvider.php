<?php

namespace App\Providers\Filament;

use App\Filament\Resources\HomeworkSubmissionsResource;
use App\Filament\Supervisor\Pages\Dashboard;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\VerifySupervisorRole;
use App\Services\AcademyContextService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class SupervisorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('supervisor')
            ->path('supervisor-panel')
            ->colors([
                'primary' => Color::Purple,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'gray' => Color::Gray,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(fn () => getFavicon())
            ->brandName('لوحة المشرف')
            ->brandLogo(fn () => view('filament.components.brand-logo', ['panelColor' => 'purple', 'panelType' => 'supervisor']))
            ->navigationGroups([
                'إدارة القرآن',
                'الدروس الأكاديمية',
                'الدورات التفاعلية',
                'التقارير',
                'الاختبارات',
                'إدارة المعلمين',
                'ملفي الشخصي',
            ])
            ->discoverResources(in: app_path('Filament/Supervisor/Resources'), for: 'App\\Filament\\Supervisor\\Resources')
            ->resources([
                HomeworkSubmissionsResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Supervisor/Pages'), for: 'App\\Filament\\Supervisor\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Supervisor/Widgets'), for: 'App\\Filament\\Supervisor\\Widgets')
            ->widgets([
                // Widgets controlled by Dashboard page
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
                VerifySupervisorRole::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->login(Login::class)
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
                            'daysOfWeek' => [6, 0, 1, 2, 3, 4, 5],
                            'startTime' => '08:00',
                            'endTime' => '22:00',
                        ],
                    ]),
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::STYLES_AFTER,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@vite(["resources/css/filament-custom.css"])')
            );
    }
}
