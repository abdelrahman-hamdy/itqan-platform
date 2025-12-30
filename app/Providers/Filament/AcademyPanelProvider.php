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
use App\Http\Middleware\AcademyContext;
use App\Filament\Academy\Resources\RecordedCourseResource;
use App\Filament\Resources\AcademicTeacherProfileResource;
use App\Filament\Resources\StudentProfileResource;
use App\Filament\Resources\ParentProfileResource;
use App\Filament\Resources\QuranTeacherProfileResource;
use App\Filament\Resources\SupervisorProfileResource;
use App\Filament\Resources\QuranCircleResource;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Filament\Resources\QuranPackageResource;
use App\Filament\Resources\InteractiveCourseResource;

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
                \App\Filament\Resources\TeacherPayoutResource::class,

                // الإعدادات
                // AcademicSettingsResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Academy/Pages'), for: 'App\\Filament\\Academy\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Academy/Widgets'), for: 'App\\Filament\\Academy\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Academy\Widgets\QuranAcademyOverviewWidget::class,
                \App\Filament\Academy\Widgets\AcademicAcademyOverviewWidget::class,
                \App\Filament\Widgets\AcademyStatsWidget::class,
                \App\Filament\Widgets\AcademyContextWidget::class,
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
            ->login()
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
