<?php

namespace App\Providers\Filament;

use App\Filament\Academy\Resources\RecordedCourseResource;
use App\Filament\Resources\AcademicTeacherProfileResource;
use App\Filament\Resources\InteractiveCourseResource;
use App\Filament\Resources\ParentProfileResource;
use App\Filament\Resources\QuranCircleResource;
use App\Filament\Resources\QuranPackageResource;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Filament\Resources\QuranTeacherProfileResource;
use App\Filament\Resources\StudentProfileResource;
use App\Filament\Resources\SupervisorProfileResource;
use App\Http\Middleware\AcademyContext;
use App\Models\Academy;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AcademyPanelProvider extends PanelProvider
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
            ->id('academy')
            ->path('/panel')
            ->tenant(Academy::class, ownershipRelationship: 'academy')
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
            ->favicon(fn () => $this->getTenantFavicon())
            ->brandName('لوحة إدارة الأكاديمية')
            ->brandLogo(fn () => view('filament.components.brand-logo', ['panelColor' => 'blue', 'panelType' => 'academy']))
            ->navigationGroups([
                __('filament.nav_groups.dashboard'),
                __('filament.nav_groups.user_management'),
                __('filament.nav_groups.quran_management'),
                __('filament.nav_groups.academic_management'),
                __('filament.nav_groups.recorded_courses'),
                __('filament.nav_groups.teacher_settings'),
                __('filament.nav_groups.settings'),
            ])
            ->discoverResources(in: app_path('Filament/Academy/Resources'), for: 'App\\Filament\\Academy\\Resources')
            ->resources([
                // إدارة المستخدمين
                StudentProfileResource::class,
                ParentProfileResource::class,
                SupervisorProfileResource::class,
                AcademicTeacherProfileResource::class,
                QuranTeacherProfileResource::class,

                // إدارة القرآن
                QuranCircleResource::class,
                QuranSubscriptionResource::class,
                QuranPackageResource::class,
                \App\Filament\Resources\QuranTrialRequestResource::class,

                // إدارة التعليم الأكاديمي
                InteractiveCourseResource::class,
                \App\Filament\Resources\AcademicSubscriptionResource::class,

                // إدارة الدورات المسجلة
                RecordedCourseResource::class,

                // إعدادات المعلمين - Teacher Settings
                \App\Filament\Resources\TeacherReviewResource::class,
                \App\Filament\Resources\TeacherEarningResource::class,

                // الإعدادات
                \App\Filament\Resources\PaymentSettingsResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Academy/Pages'), for: 'App\\Filament\\Academy\\Pages')
            ->pages([
                \App\Filament\Academy\Pages\Dashboard::class,
            ])
            ->widgets([
                // Main stats widgets - same structure as super admin but scoped to academy
                \App\Filament\Academy\Widgets\AcademyStatsWidget::class,
                \App\Filament\Academy\Widgets\AcademyMonthlyStatsWidget::class,
                \App\Filament\Academy\Widgets\AcademyUserAnalyticsChartWidget::class,
                \App\Filament\Academy\Widgets\AcademySessionAnalyticsChartWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->profile()
            ->renderHook(
                'panels::topbar.start',
                fn (): string => view('filament.hooks.academy-selector')->render()
            )
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => view('filament.hooks.topbar-buttons')->render()
            );
    }
}
