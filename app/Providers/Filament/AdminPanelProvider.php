<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AcademyContext;
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
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->brandName('منصة إتقان للأعمال')
            ->brandLogo(fn () => view('filament.components.brand-logo', ['panelColor' => 'amber', 'panelType' => 'admin']))
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->font('Tajawal') // Arabic font
            ->favicon(asset('favicon.ico'))
            ->resources($this->getResources())
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Shared\Pages\NotificationPreferences::class,
            ])
            ->widgets([
                // Only show these specific widgets - no auto-discovery
                \App\Filament\Widgets\SuperAdminStatsWidget::class,
                \App\Filament\Widgets\SuperAdminMonthlyStatsWidget::class,
                \App\Filament\Widgets\UserAnalyticsChartWidget::class,
                \App\Filament\Widgets\SessionAnalyticsChartWidget::class,
                \App\Filament\Widgets\RecentBusinessRequestsWidget::class,
                \App\Filament\Widgets\SentryStatsWidget::class, // Registered for Log Viewer page, hidden from dashboard via canView()
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
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')
            ->sidebarFullyCollapsibleOnDesktop()
            ->navigationGroups([
                'إدارة النظام',
                'إدارة الأكاديميات',
                'إدارة المستخدمين',
                'إدارة القرآن',
                'إدارة تحفيظ القرآن',
                'إدارة التعليم الأكاديمي',
                'إدارة الدورات التفاعلية',
                'إدارة الدورات المسجلة',
                'إدارة الاختبارات',
                'المالية',
                'إعدادات المعلمين',
                'التقارير والحضور',
                'إدارة الشهادات',
                'أدوات المطور',
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentSpatieLaravelHealthPlugin::make()
                    ->usingPage(\ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults::class)
                    ->authorize(fn (): bool => auth()->user()?->isSuperAdmin())
                    ->navigationGroup('أدوات المطور')
                    ->navigationLabel('حالة النظام')
                    ->navigationIcon('heroicon-o-heart')
                    ->navigationSort(1),
            ])
            ->renderHook(
                'panels::topbar.start',
                fn (): string => view('filament.hooks.academy-selector')->render()
            )
            ->renderHook(
                'panels::user-menu.before',
                fn (): string => view('filament.hooks.topbar-buttons')->render()
            );
    }

    protected function getResources(): array
    {
        // Register all resources - each resource will handle its own visibility through canViewAny()
        return [
            // إدارة النظام - Super Admin Resources
            \App\Filament\Resources\AcademyManagementResource::class,
            \App\Filament\Resources\AcademyGeneralSettingsResource::class,
            \App\Filament\Resources\PaymentSettingsResource::class,

            // إدارة المستخدمين - Academy Level
            \App\Filament\Resources\UserResource::class,
            \App\Filament\Resources\AdminResource::class,
            \App\Filament\Resources\StudentProfileResource::class,
            \App\Filament\Resources\ParentProfileResource::class,
            \App\Filament\Resources\SupervisorProfileResource::class,
            \App\Filament\Resources\AcademicTeacherProfileResource::class,

            // إدارة القرآن - Quran Management
            \App\Filament\Resources\QuranTeacherProfileResource::class,
            \App\Filament\Resources\QuranPackageResource::class,
            \App\Filament\Resources\QuranCircleResource::class,
            \App\Filament\Resources\QuranIndividualCircleResource::class,
            \App\Filament\Resources\QuranSubscriptionResource::class,
            \App\Filament\Resources\QuranSessionResource::class,
            \App\Filament\Resources\QuranTrialRequestResource::class,

            // إدارة التعليم الأكاديمي - Academic Management
            \App\Filament\Resources\AcademicGradeLevelResource::class,
            \App\Filament\Resources\AcademicSubjectResource::class,
            \App\Filament\Resources\AcademicPackageResource::class,
            \App\Filament\Resources\AcademicIndividualLessonResource::class,
            \App\Filament\Resources\InteractiveCourseResource::class,
            \App\Filament\Resources\AcademicSubscriptionResource::class,
            \App\Filament\Resources\AcademicSessionResource::class,
            \App\Filament\Resources\InteractiveCourseSessionResource::class,

            // إدارة الدورات المسجلة - Recorded Courses
            \App\Filament\Resources\RecordedCourseResource::class,

            // المالية - Financial Management
            \App\Filament\Resources\PaymentResource::class,
            \App\Filament\Resources\SavedPaymentMethodResource::class,

            // إعدادات المعلمين - Teacher Settings
            \App\Filament\Resources\TeacherReviewResource::class,
            \App\Filament\Resources\TeacherEarningResource::class,

            // التقارير والحضور - Reports & Attendance
            \App\Filament\Resources\StudentSessionReportResource::class,
            \App\Filament\Resources\AcademicSessionReportResource::class,
            \App\Filament\Resources\InteractiveSessionReportResource::class,
            \App\Filament\Resources\MeetingAttendanceResource::class,
            \App\Filament\Resources\HomeworkSubmissionsResource::class,

            // متابعة التقدم - Progress Tracking
            // Note: QuranProgressResource and InteractiveCourseProgressResource removed
            // Progress is now calculated dynamically from session reports
            \App\Filament\Resources\StudentProgressResource::class,

            // إدارة الشهادات - Certificates Management
            \App\Filament\Resources\CertificateResource::class,

            // إدارة الاختبارات - Quiz Management
            \App\Filament\Resources\QuizResource::class,
            \App\Filament\Resources\QuizAssignmentResource::class,

            // الإعدادات
            // Business Services - Access controlled by resource authorization
            \App\Filament\Resources\BusinessServiceCategoryResource::class,
            \App\Filament\Resources\BusinessServiceRequestResource::class,
            \App\Filament\Resources\PortfolioItemResource::class,
        ];
    }

    public function boot(): void
    {
        // Temporarily increase memory limit to debug memory exhaustion
        ini_set('memory_limit', '2048M');

        // Set Arabic locale for the admin panel
        app()->setLocale('ar');
    }
}
