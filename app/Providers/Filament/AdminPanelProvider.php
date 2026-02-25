<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\Pages\Dashboard;
use App\Filament\Widgets\SuperAdminControlPanelWidget;
use App\Filament\Widgets\SuperAdminStatsWidget;
use App\Filament\Widgets\SuperAdminMonthlyStatsWidget;
use App\Filament\Widgets\UserAnalyticsChartWidget;
use App\Filament\Widgets\SessionAnalyticsChartWidget;
use App\Filament\Widgets\RecentBusinessRequestsWidget;
use App\Filament\Widgets\SentryStatsWidget;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults;
use App\Filament\Resources\AcademyManagementResource;
use App\Filament\Resources\AcademyGeneralSettingsResource;
use App\Filament\Resources\PaymentSettingsResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\StudentProfileResource;
use App\Filament\Resources\ParentProfileResource;
use App\Filament\Resources\SupervisorProfileResource;
use App\Filament\Resources\AcademicTeacherProfileResource;
use App\Filament\Resources\QuranTeacherProfileResource;
use App\Filament\Resources\QuranPackageResource;
use App\Filament\Resources\QuranCircleResource;
use App\Filament\Resources\QuranIndividualCircleResource;
use App\Filament\Resources\QuranSubscriptionResource;
use App\Filament\Resources\QuranSessionResource;
use App\Filament\Resources\QuranTrialRequestResource;
use App\Filament\Resources\AcademicGradeLevelResource;
use App\Filament\Resources\AcademicSubjectResource;
use App\Filament\Resources\AcademicPackageResource;
use App\Filament\Resources\AcademicIndividualLessonResource;
use App\Filament\Resources\InteractiveCourseResource;
use App\Filament\Resources\AcademicSubscriptionResource;
use App\Filament\Resources\AcademicSessionResource;
use App\Filament\Resources\InteractiveCourseSessionResource;
use App\Filament\Resources\RecordedCourseResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\SavedPaymentMethodResource;
use App\Filament\Resources\TeacherReviewResource;
use App\Filament\Resources\TeacherEarningResource;
use App\Filament\Resources\StudentSessionReportResource;
use App\Filament\Resources\AcademicSessionReportResource;
use App\Filament\Resources\InteractiveSessionReportResource;
use App\Filament\Resources\MeetingAttendanceResource;
use App\Filament\Resources\HomeworkSubmissionsResource;
use App\Filament\Resources\StudentProgressResource;
use App\Filament\Resources\CertificateResource;
use App\Filament\Resources\QuizResource;
use App\Filament\Resources\QuizAssignmentResource;
use App\Filament\Resources\BusinessServiceCategoryResource;
use App\Filament\Resources\BusinessServiceRequestResource;
use App\Filament\Resources\PortfolioItemResource;
use App\Filament\Resources\SessionRecordingResource;
use App\Http\Middleware\AcademyContext;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->login(Login::class)
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->brandName('منصة إتقان للأعمال')
            ->brandLogo(fn () => view('filament.components.brand-logo', ['panelColor' => 'amber', 'panelType' => 'admin']))
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(fn () => getFavicon())
            ->resources($this->getResources())
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                // Only show these specific widgets - no auto-discovery
                SuperAdminControlPanelWidget::class,
                SuperAdminStatsWidget::class,
                SuperAdminMonthlyStatsWidget::class,
                UserAnalyticsChartWidget::class,
                SessionAnalyticsChartWidget::class,
                RecentBusinessRequestsWidget::class,
                SentryStatsWidget::class, // Registered for Log Viewer page, hidden from dashboard via canView()
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
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->sidebarFullyCollapsibleOnDesktop()
            ->navigationGroups([
                __('filament.nav_groups.system_management'),
                __('filament.nav_groups.academy_management'),
                __('filament.nav_groups.user_management'),
                __('filament.nav_groups.quran_management'),
                __('filament.nav_groups.quran_memorization'),
                __('filament.nav_groups.academic_management'),
                __('filament.nav_groups.interactive_courses'),
                __('filament.nav_groups.recorded_courses'),
                __('filament.nav_groups.exams'),
                __('filament.nav_groups.payments'),
                __('filament.nav_groups.teacher_settings'),
                __('filament.nav_groups.reports_attendance'),
                __('filament.nav_groups.certificates'),
                __('filament.nav_groups.developer_tools'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(HealthCheckResults::class)
                    ->authorize(fn (): bool => auth()->user()?->isSuperAdmin())
                    ->navigationGroup(__('filament.nav_groups.developer_tools'))
                    ->navigationLabel('حالة النظام')
                    ->navigationIcon('heroicon-o-heart')
                    ->navigationSort(1),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@vite(["resources/css/filament-custom.css"])')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => view('filament.hooks.academy-selector')->render()
            )
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => view('filament.hooks.topbar-buttons')->render()
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => view('filament.hooks.mobile-filter-collapse')->render()
            )
            ->navigationItems([
                NavigationItem::make(__('help.nav_label'))
                    ->url('/help', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-question-mark-circle')
                    ->sort(99),
            ]);
    }

    protected function getResources(): array
    {
        // Register all resources - each resource will handle its own visibility through canViewAny()
        return [
            // إدارة النظام - Super Admin Resources
            AcademyManagementResource::class,
            AcademyGeneralSettingsResource::class,
            PaymentSettingsResource::class,

            // إدارة المستخدمين - Academy Level
            UserResource::class,
            AdminResource::class,
            StudentProfileResource::class,
            ParentProfileResource::class,
            SupervisorProfileResource::class,
            AcademicTeacherProfileResource::class,

            // إدارة القرآن - Quran Management
            QuranTeacherProfileResource::class,
            QuranPackageResource::class,
            QuranCircleResource::class,
            QuranIndividualCircleResource::class,
            QuranSubscriptionResource::class,
            QuranSessionResource::class,
            QuranTrialRequestResource::class,

            // إدارة التعليم الأكاديمي - Academic Management
            AcademicGradeLevelResource::class,
            AcademicSubjectResource::class,
            AcademicPackageResource::class,
            AcademicIndividualLessonResource::class,
            InteractiveCourseResource::class,
            AcademicSubscriptionResource::class,
            AcademicSessionResource::class,
            InteractiveCourseSessionResource::class,

            // تسجيلات الجلسات - Session Recordings
            SessionRecordingResource::class,

            // إدارة الدورات المسجلة - Recorded Courses
            RecordedCourseResource::class,

            // المالية - Financial Management
            PaymentResource::class,
            SavedPaymentMethodResource::class,

            // إعدادات المعلمين - Teacher Settings
            TeacherReviewResource::class,
            TeacherEarningResource::class,

            // التقارير والحضور - Reports & Attendance
            StudentSessionReportResource::class,
            AcademicSessionReportResource::class,
            InteractiveSessionReportResource::class,
            MeetingAttendanceResource::class,
            HomeworkSubmissionsResource::class,

            // متابعة التقدم - Progress Tracking
            // Note: QuranProgressResource and InteractiveCourseProgressResource removed
            // Progress is now calculated dynamically from session reports
            StudentProgressResource::class,

            // إدارة الشهادات - Certificates Management
            CertificateResource::class,

            // إدارة الاختبارات - Quiz Management
            QuizResource::class,
            QuizAssignmentResource::class,

            // الإعدادات
            // Business Services - Access controlled by resource authorization
            BusinessServiceCategoryResource::class,
            BusinessServiceRequestResource::class,
            PortfolioItemResource::class,
        ];
    }

    public function boot(): void
    {
        // Memory limit for admin panel operations
        ini_set('memory_limit', '512M');

        // Set Arabic locale for the admin panel
        app()->setLocale('ar');
    }
}
