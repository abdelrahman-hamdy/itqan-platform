<?php

namespace App\Providers;

use App\Contracts\LiveKitServiceInterface;
use App\Helpers\AcademyHelper;
use App\Models\AcademicSession;
use App\Models\AcademicSessionAttendance;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\Payment;
use App\Models\QuizAssignment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\SessionRecording;
use App\Models\StudentProfile;
use App\Models\StudentSessionReport;
use App\Models\SupervisorResponsibility;
use App\Models\TeacherPayout;
use App\Models\User;
use App\Observers\AcademicSessionAttendanceObserver;
use App\Observers\AcademicSessionObserver;
use App\Observers\BaseSessionObserver;
use App\Observers\BaseSubscriptionObserver;
use App\Observers\MediaObserver;
use App\Observers\QuranSessionObserver;
use App\Observers\QuranTrialRequestObserver;
use App\Observers\StudentProfileObserver;
use App\Observers\StudentSessionReportObserver;
use App\Observers\SupervisorResponsibilityObserver;
use App\Observers\UserObserver;
use App\Policies\AcademyPolicy;
use App\Policies\HomeworkPolicy;
use App\Policies\InteractiveCoursePolicy;
use App\Policies\InteractiveCourseSessionPolicy;
use App\Policies\MeetingAttendancePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\QuizAssignmentPolicy;
use App\Policies\RecordingPolicy;
use App\Policies\SessionPolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TeacherPayoutPolicy;
use App\Policies\TeacherProfilePolicy;
use App\Services\LiveKitService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Health\Checks\LogFilesCheck;
use App\Health\Checks\MediaLibrarySizeCheck;
use App\Health\Checks\PHPMemoryCheck;
use App\Health\Checks\ServerMemoryCheck;
use App\Health\Checks\TenantStorageCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\RedisMemoryUsageCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind service interfaces to implementations
        $this->app->bind(LiveKitServiceInterface::class, LiveKitService::class);
        $this->app->bind(\App\Contracts\SessionStatusServiceInterface::class, \App\Services\UnifiedSessionStatusService::class);
        $this->app->bind(\App\Contracts\UnifiedSessionStatusServiceInterface::class, \App\Services\UnifiedSessionStatusService::class);
        $this->app->bind(\App\Contracts\EarningsCalculationServiceInterface::class, \App\Services\EarningsCalculationService::class);
        $this->app->bind(\App\Contracts\MeetingAttendanceServiceInterface::class, \App\Services\MeetingAttendanceService::class);
        $this->app->bind(\App\Contracts\HomeworkServiceInterface::class, \App\Services\HomeworkService::class);
        $this->app->bind(\App\Contracts\StudentDashboardServiceInterface::class, \App\Services\StudentDashboardService::class);
        $this->app->bind(\App\Contracts\QuizServiceInterface::class, \App\Services\QuizService::class);
        $this->app->bind(\App\Contracts\SearchServiceInterface::class, \App\Services\SearchService::class);
        $this->app->bind(\App\Contracts\StudentStatisticsServiceInterface::class, \App\Services\StudentStatisticsService::class);
        $this->app->bind(\App\Contracts\CircleEnrollmentServiceInterface::class, \App\Services\CircleEnrollmentService::class);
        $this->app->bind(\App\Contracts\SubscriptionServiceInterface::class, \App\Services\SubscriptionService::class);
        $this->app->bind(\App\Contracts\NotificationServiceInterface::class, \App\Services\NotificationService::class);
        $this->app->bind(\App\Contracts\AutoMeetingCreationServiceInterface::class, \App\Services\AutoMeetingCreationService::class);
        $this->app->bind(\App\Contracts\RecordingServiceInterface::class, \App\Services\RecordingService::class);
        $this->app->bind(\App\Contracts\ChatPermissionServiceInterface::class, \App\Services\ChatPermissionService::class);

        // Override Filament's RedirectToTenantController to fix Livewire redirect return type issue
        $this->app->bind(
            \Filament\Http\Controllers\RedirectToTenantController::class,
            \App\Http\Controllers\Filament\RedirectToTenantController::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production environment
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register middleware aliases
        Route::aliasMiddleware('auth', \App\Http\Middleware\CustomAuthenticate::class);
        Route::aliasMiddleware('role', \App\Http\Middleware\RoleMiddleware::class);
        Route::aliasMiddleware('tenant', \App\Http\Middleware\TenantMiddleware::class);
        Route::aliasMiddleware('academy.context', \App\Http\Middleware\AcademyContext::class);
        Route::aliasMiddleware('resolve.tenant', \App\Http\Middleware\ResolveTenantFromSubdomain::class);

        // Share academy context with all views
        View::composer('*', function ($view) {
            $view->with('currentAcademy', AcademyHelper::getCurrentAcademy());
            $view->with('hasAcademySelected', AcademyHelper::hasAcademySelected());
        });

        // Register morph map for polymorphic relationships
        // This ensures consistent database values instead of full class names
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'quran_session' => \App\Models\QuranSession::class,
            'academic_session' => \App\Models\AcademicSession::class,
            'interactive_course_session' => \App\Models\InteractiveCourseSession::class,
            'quran_subscription' => \App\Models\QuranSubscription::class,
            'academic_subscription' => \App\Models\AcademicSubscription::class,
            'course_subscription' => \App\Models\CourseSubscription::class,
        ]);

        // Register Media Observer to handle UTF-8 filename sanitization
        Media::observe(MediaObserver::class);

        // Register AcademicSessionAttendance Observer for attendance-based progress tracking
        AcademicSessionAttendance::observe(AcademicSessionAttendanceObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Session Observers (Layered Pattern)
        |--------------------------------------------------------------------------
        |
        | Sessions use a layered observer pattern where multiple observers handle
        | different concerns. This is intentional:
        |
        | Layer 1: BaseSessionObserver - Handles common session functionality
        |   - Automatic meeting creation on status change to ready/ongoing
        |   - Shared behavior across all session types
        |
        | Layer 2: Type-specific observers - Handle unique session behaviors
        |   - QuranSessionObserver: Trial request status synchronization
        |   - AcademicSessionObserver: Homework assignment notifications
        |
        | Both observers fire for QuranSession and AcademicSession (intentional).
        */

        // Layer 1: Common session behaviors
        QuranSession::observe(BaseSessionObserver::class);
        AcademicSession::observe(BaseSessionObserver::class);
        InteractiveCourseSession::observe(BaseSessionObserver::class);

        // Layer 2: Type-specific behaviors
        QuranSession::observe(QuranSessionObserver::class);
        AcademicSession::observe(AcademicSessionObserver::class);

        // Register StudentSessionReport Observer for pivot table counter updates
        StudentSessionReport::observe(StudentSessionReportObserver::class);

        // Register StudentProfile Observer for parent-student relationship sync
        StudentProfile::observe(StudentProfileObserver::class);

        // Register QuranTrialRequest Observer for trial notifications
        QuranTrialRequest::observe(QuranTrialRequestObserver::class);

        // Register SupervisorResponsibility Observer for chat membership sync
        SupervisorResponsibility::observe(SupervisorResponsibilityObserver::class);

        // Register BaseSubscription Observer for all subscription types
        // Handles subscription code generation, default values, status transitions
        QuranSubscription::observe(BaseSubscriptionObserver::class);
        AcademicSubscription::observe(BaseSubscriptionObserver::class);
        CourseSubscription::observe(BaseSubscriptionObserver::class);

        // Register User Observer for admin-created users auto-verification
        User::observe(UserObserver::class);

        // Override WireChat Info component with custom implementation
        Livewire::component('wirechat.chat.info', \App\Livewire\Chat\Info::class);

        // Register policies for authorization
        // Session policies - use SessionPolicy for all session types
        Gate::policy(QuranSession::class, SessionPolicy::class);
        Gate::policy(AcademicSession::class, SessionPolicy::class);
        Gate::policy(InteractiveCourseSession::class, SessionPolicy::class);

        // Subscription policies - use SubscriptionPolicy for all subscription types
        Gate::policy(QuranSubscription::class, SubscriptionPolicy::class);
        Gate::policy(AcademicSubscription::class, SubscriptionPolicy::class);
        Gate::policy(CourseSubscription::class, SubscriptionPolicy::class);

        // Profile policies
        Gate::policy(StudentProfile::class, StudentProfilePolicy::class);
        Gate::policy(QuranTeacherProfile::class, TeacherProfilePolicy::class);
        Gate::policy(AcademicTeacherProfile::class, TeacherProfilePolicy::class);

        // Payment policy
        Gate::policy(Payment::class, PaymentPolicy::class);

        // Certificate policy
        Gate::policy(Certificate::class, \App\Policies\CertificatePolicy::class);

        // Homework and Quiz policies
        Gate::policy(InteractiveCourseHomework::class, HomeworkPolicy::class);
        Gate::policy(QuizAssignment::class, QuizAssignmentPolicy::class);

        // Interactive Course policies
        Gate::policy(InteractiveCourse::class, InteractiveCoursePolicy::class);
        Gate::policy(InteractiveCourseSession::class, InteractiveCourseSessionPolicy::class);

        // Meeting Attendance policy
        Gate::policy(MeetingAttendance::class, MeetingAttendancePolicy::class);

        // Academy policy
        Gate::policy(Academy::class, AcademyPolicy::class);

        // Recording policy
        Gate::policy(SessionRecording::class, RecordingPolicy::class);

        // Teacher Payout policy
        Gate::policy(TeacherPayout::class, TeacherPayoutPolicy::class);

        // Configure Spatie Health checks for system monitoring
        Health::checks([
            // Application Health Checks
            OptimizedAppCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new()->expectEnvironment('production'),

            // Database & Cache Checks
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan(50)
                ->failWhenMoreConnectionsThan(100),
            RedisCheck::new(),
            RedisMemoryUsageCheck::new()
                ->warnWhenAboveMb(400)
                ->failWhenAboveMb(500),
            CacheCheck::new(),

            // System Resource Checks
            ServerMemoryCheck::new()
                ->warnWhenAbovePercent(80)
                ->failWhenAbovePercent(95),
            PHPMemoryCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            // Storage Checks
            TenantStorageCheck::new()
                ->warnWhenAboveGb(5)
                ->failWhenAboveGb(10),
            MediaLibrarySizeCheck::new()
                ->warnWhenAboveGb(10)
                ->failWhenAboveGb(20),
            LogFilesCheck::new()
                ->warnWhenAboveMb(100)
                ->failWhenAboveMb(500),

            // Scheduler Check
            // Note: QueueCheck removed due to inherent timing issues causing false failures.
            // Queue health is verified via: RedisCheck (queue backend), ScheduleCheck (scheduler),
            // and supervisor monitoring of queue workers.
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2),
        ]);
    }
}
