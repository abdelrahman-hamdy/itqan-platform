<?php

namespace App\Providers\Filament;

use App\Filament\Academy\Pages\Dashboard;
use App\Filament\Academy\Resources\AcademicPackageResource;
use App\Filament\Academy\Resources\AcademicSubscriptionResource;
use App\Filament\Academy\Resources\AcademicTeacherProfileResource;
use App\Filament\Academy\Resources\ParentProfileResource;
use App\Filament\Academy\Resources\PaymentResource;
use App\Filament\Academy\Resources\QuranPackageResource;
use App\Filament\Academy\Resources\QuranSubscriptionResource;
use App\Filament\Academy\Resources\QuranTeacherProfileResource;
use App\Filament\Academy\Resources\RecordedCourseResource;
use App\Filament\Academy\Resources\SavedPaymentMethodResource;
use App\Filament\Academy\Resources\StudentProfileResource;
use App\Filament\Academy\Resources\SupervisorProfileResource;
use App\Filament\Academy\Widgets\AcademyMonthlyStatsWidget;
use App\Filament\Academy\Widgets\AcademySessionAnalyticsChartWidget;
use App\Filament\Academy\Widgets\AcademyStatsWidget;
use App\Filament\Academy\Widgets\AcademyUserAnalyticsChartWidget;
use App\Filament\Academy\Widgets\RenewalMetricsWidget;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\HomeworkSubmissionsResource;
use App\Filament\Resources\InteractiveCourseResource;
use App\Filament\Resources\PaymentSettingsResource;
use App\Filament\Resources\QuranCircleResource;
use App\Filament\Resources\QuranTrialRequestResource;
use App\Filament\Resources\TeacherEarningResource;
use App\Filament\Resources\TeacherReviewResource;
use App\Http\Middleware\AcademyContext;
use App\Models\Academy;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AcademyPanelProvider extends PanelProvider
{
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
            ->favicon(fn () => getFavicon(Filament::getTenant()))
            ->brandName('لوحة إدارة الأكاديمية')
            ->brandLogo(fn () => view('filament.components.brand-logo', ['panelColor' => 'blue', 'panelType' => 'academy']))
            ->navigationGroups([
                __('filament.nav_groups.dashboard'),
                __('filament.nav_groups.user_management'),
                __('filament.nav_groups.quran_management'),
                __('filament.nav_groups.academic_management'),
                __('filament.nav_groups.recorded_courses'),
                __('filament.nav_groups.reports_attendance'),
                __('filament.nav_groups.certificates'),
                __('filament.nav_groups.exams'),
                __('filament.nav_groups.reviews'),
                __('filament.nav_groups.teacher_settings'),
                __('filament.nav_groups.payments'),
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
                QuranTrialRequestResource::class,

                // إدارة التعليم الأكاديمي
                InteractiveCourseResource::class,
                AcademicPackageResource::class,
                AcademicSubscriptionResource::class,

                // إدارة الدورات المسجلة
                RecordedCourseResource::class,

                // المالية
                PaymentResource::class,
                SavedPaymentMethodResource::class,

                // إعدادات المعلمين - Teacher Settings
                TeacherReviewResource::class,
                TeacherEarningResource::class,

                // التقارير والحضور
                HomeworkSubmissionsResource::class,

                // الإعدادات
                PaymentSettingsResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Academy/Pages'), for: 'App\\Filament\\Academy\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Academy/Widgets'), for: 'App\\Filament\\Academy\\Widgets')
            ->widgets([
                // Main stats widgets - same structure as super admin but scoped to academy
                AcademyStatsWidget::class,
                RenewalMetricsWidget::class,
                AcademyMonthlyStatsWidget::class,
                AcademyUserAnalyticsChartWidget::class,
                AcademySessionAnalyticsChartWidget::class,
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
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->login(Login::class)
            ->renderHook(
                \Filament\View\PanelsRenderHook::STYLES_AFTER,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@vite(["resources/css/filament-custom.css"])')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => view('filament.hooks.academy-selector')->render()
            )
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => view('filament.hooks.topbar-buttons')->render()
            )
            ->navigationItems([
                NavigationItem::make(__('help.nav_label'))
                    ->url('/help', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-question-mark-circle')
                    ->sort(99),
            ]);
    }
}
