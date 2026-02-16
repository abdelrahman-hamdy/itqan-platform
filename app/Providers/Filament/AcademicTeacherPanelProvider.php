<?php

namespace App\Providers\Filament;

use App\Models\Academy;
use App\Services\AcademyContextService;
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
    protected function getTenantFavicon(): string
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if ($tenant && $tenant->favicon) {
            return \Illuminate\Support\Facades\Storage::url($tenant->favicon);
        }

        return asset('favicon.ico');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('academic-teacher')
            ->path('/academic-teacher-panel')
            ->tenant(Academy::class, slugAttribute: 'subdomain', ownershipRelationship: 'academy')
            ->tenantDomain('{tenant:subdomain}.'.config('app.domain'))
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Red,
                'gray' => Color::Gray,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(fn () => $this->getTenantFavicon())
            ->brandName('لوحة المعلم الأكاديمي')
            ->brandLogo(fn () => view('filament.components.brand-logo', ['panelColor' => 'blue', 'panelType' => 'academic-teacher']))
            ->navigationGroups([
                'لوحة التحكم',
                'دروسي الفردية',
                'الدورات التفاعلية',
                'جلساتي',
                'الواجبات الأكاديمية',
                'الاختبارات',
                'التقييمات',
                'التقارير والحضور',
                'الشهادات',
                'الأرباح',
                'ملفي الشخصي',
            ])
            ->discoverResources(in: app_path('Filament/AcademicTeacher/Resources'), for: 'App\\Filament\\AcademicTeacher\\Resources')
            ->resources([
                \App\Filament\Resources\HomeworkSubmissionsResource::class,
            ])
            ->discoverPages(in: app_path('Filament/AcademicTeacher/Pages'), for: 'App\\Filament\\AcademicTeacher\\Pages')
            ->pages([
                \App\Filament\AcademicTeacher\Pages\Dashboard::class,
                \App\Filament\Shared\Pages\UnifiedTeacherCalendar::class,
            ])
            ->discoverWidgets(in: app_path('Filament/AcademicTeacher/Widgets'), for: 'App\\Filament\\AcademicTeacher\\Widgets')
            ->discoverWidgets(in: app_path('Filament/Shared/Widgets'), for: 'App\\Filament\\Shared\\Widgets')
            ->widgets([
                // Widgets registered for Livewire - Dashboard controls actual display
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
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->userMenuItems([
                'profile-page' => \Filament\Navigation\MenuItem::make()
                    ->label('الملف الشخصي العام')
                    ->url(fn (): string => auth()->user()->academicTeacherProfile && auth()->user()->academy
                        ? route('academic-teachers.show', [
                            'subdomain' => auth()->user()->academy->subdomain,
                            'teacherId' => auth()->user()->academicTeacherProfile->id,
                        ])
                        : '#')
                    ->icon('heroicon-o-user-circle')
                    ->openUrlInNewTab()
                    ->visible(fn (): bool => auth()->user()->academicTeacherProfile !== null),
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
                PanelsRenderHook::TOPBAR_END,
                fn (): string => view('filament.academic-teacher.render-hooks.messages-count')->render()
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => view('filament.components.profile-button', [
                    'profileUrl' => auth()->user()?->academy
                        ? route('teacher.profile', [
                            'subdomain' => auth()->user()->academy->subdomain,
                        ])
                        : null,
                    'label' => 'لوحتي الرئيسية',
                ])->render()
            );
    }
}
