<?php

namespace App\Providers;

use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Bottelet\TranslationChecker\TranslationManager;
use App\Contracts\SessionStatusServiceInterface;
use App\Services\UnifiedSessionStatusService;
use App\Contracts\UnifiedSessionStatusServiceInterface;
use App\Contracts\EarningsCalculationServiceInterface;
use App\Services\EarningsCalculationService;
use App\Contracts\MeetingAttendanceServiceInterface;
use App\Services\MeetingAttendanceService;
use App\Contracts\HomeworkServiceInterface;
use App\Services\HomeworkService;
use App\Contracts\StudentDashboardServiceInterface;
use App\Services\StudentDashboardService;
use App\Contracts\QuizServiceInterface;
use App\Services\QuizService;
use App\Contracts\SearchServiceInterface;
use App\Services\SearchService;
use App\Contracts\StudentStatisticsServiceInterface;
use App\Services\StudentStatisticsService;
use App\Contracts\CircleEnrollmentServiceInterface;
use App\Services\CircleEnrollmentService;
use App\Contracts\SubscriptionServiceInterface;
use App\Services\SubscriptionService;
use App\Contracts\NotificationServiceInterface;
use App\Services\NotificationService;
use App\Contracts\AutoMeetingCreationServiceInterface;
use App\Services\AutoMeetingCreationService;
use App\Contracts\RecordingServiceInterface;
use App\Services\RecordingService;
use App\Contracts\ChatPermissionServiceInterface;
use App\Services\ChatPermissionService;
use Filament\Http\Controllers\RedirectToTenantController;
use App\Http\Middleware\CustomAuthenticate;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantMiddleware;
use App\Http\Middleware\AcademyContext;
use App\Http\Middleware\ResolveTenantFromSubdomain;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\AcademicHomeworkSubmission;
use App\Observers\HomeworkSubmissionObserver;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Livewire\Chat\Chats;
use App\Livewire\Chat\Info;
use App\Livewire\Filament\DatabaseNotifications;
use App\Policies\CertificatePolicy;
use App\Models\QuizAttempt;
use App\Contracts\LiveKitServiceInterface;
use App\Health\Checks\LogFilesCheck;
use App\Health\Checks\MediaLibrarySizeCheck;
use App\Health\Checks\PHPMemoryCheck;
use App\Health\Checks\ServerMemoryCheck;
use App\Health\Checks\TenantStorageCheck;
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
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\SessionRecording;
use App\Models\StudentProfile;
use App\Models\StudentSessionReport;
use App\Models\SupervisorResponsibility;
use App\Models\User;
use App\Observers\AcademicSessionAttendanceObserver;
use App\Observers\AcademicSessionObserver;
use App\Observers\AcademyObserver;
use App\Observers\BaseSessionObserver;
use App\Observers\BaseSubscriptionObserver;
use App\Observers\MediaObserver;
use App\Observers\QuranSessionObserver;
use App\Observers\QuranTrialRequestObserver;
use App\Observers\SessionRecordingObserver;
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
use App\Policies\QuizAttemptPolicy;
use App\Policies\QuranIndividualCirclePolicy;
use App\Policies\RecordingPolicy;
use App\Policies\SessionPolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TeacherProfilePolicy;
use App\Services\LiveKitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
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
        // Register dev-only providers conditionally
        if (class_exists(TelescopeApplicationServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
        if (class_exists(TranslationManager::class)) {
            $this->app->register(TranslationCheckerServiceProvider::class);
        }

        // Bind service interfaces to implementations
        $this->app->bind(LiveKitServiceInterface::class, LiveKitService::class);
        $this->app->bind(SessionStatusServiceInterface::class, UnifiedSessionStatusService::class);
        $this->app->bind(UnifiedSessionStatusServiceInterface::class, UnifiedSessionStatusService::class);
        $this->app->bind(EarningsCalculationServiceInterface::class, EarningsCalculationService::class);
        $this->app->bind(MeetingAttendanceServiceInterface::class, MeetingAttendanceService::class);
        $this->app->bind(HomeworkServiceInterface::class, HomeworkService::class);
        $this->app->bind(StudentDashboardServiceInterface::class, StudentDashboardService::class);
        $this->app->bind(QuizServiceInterface::class, QuizService::class);
        $this->app->bind(SearchServiceInterface::class, SearchService::class);
        $this->app->bind(StudentStatisticsServiceInterface::class, StudentStatisticsService::class);
        $this->app->bind(CircleEnrollmentServiceInterface::class, CircleEnrollmentService::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
        $this->app->bind(AutoMeetingCreationServiceInterface::class, AutoMeetingCreationService::class);
        $this->app->bind(RecordingServiceInterface::class, RecordingService::class);
        $this->app->bind(ChatPermissionServiceInterface::class, ChatPermissionService::class);

        // Override Filament's RedirectToTenantController to fix Livewire redirect return type issue
        $this->app->bind(
            RedirectToTenantController::class,
            \App\Http\Controllers\Filament\RedirectToTenantController::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in non-production to catch N+1 queries early
        Model::preventLazyLoading(! $this->app->isProduction());

        // Force HTTPS in production environment
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Set default subdomain for URL generation in non-HTTP contexts (queue workers, CLI).
        // WireChat's NotifyParticipant event calls route('chat', ...) which requires the
        // {subdomain} parameter normally set by ResolveTenantFromSubdomain middleware.
        if ($this->app->runningInConsole()) {
            URL::defaults(['subdomain' => config('multitenancy.default_tenant_subdomain', 'itqan-academy')]);
        }

        // Register middleware aliases
        Route::aliasMiddleware('auth', CustomAuthenticate::class);
        Route::aliasMiddleware('role', RoleMiddleware::class);
        Route::aliasMiddleware('tenant', TenantMiddleware::class);
        Route::aliasMiddleware('academy.context', AcademyContext::class);
        Route::aliasMiddleware('resolve.tenant', ResolveTenantFromSubdomain::class);

        // Share academy context with all views
        View::composer('*', function ($view) {
            $view->with('currentAcademy', AcademyHelper::getCurrentAcademy());
            $view->with('hasAcademySelected', AcademyHelper::hasAcademySelected());
        });

        // Register morph map for polymorphic relationships
        // This ensures consistent database values instead of full class names
        Relation::morphMap([
            'quran_session' => QuranSession::class,
            'academic_session' => AcademicSession::class,
            'interactive_course_session' => InteractiveCourseSession::class,
            'quran_subscription' => QuranSubscription::class,
            'academic_subscription' => AcademicSubscription::class,
            'course_subscription' => CourseSubscription::class,
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
        // and admin-academy sync
        User::observe(UserObserver::class);

        // Register Academy Observer for admin-academy bidirectional sync
        Academy::observe(AcademyObserver::class);

        // Register SessionRecording Observer for S3/storage file cleanup on deletion
        SessionRecording::observe(SessionRecordingObserver::class);

        // Register Homework Submission Observers for submission/grading notifications
        AcademicHomeworkSubmission::observe(HomeworkSubmissionObserver::class);
        InteractiveCourseHomeworkSubmission::observe(HomeworkSubmissionObserver::class);

        // Override WireChat components with custom implementations
        Livewire::component('wirechat.chat.info', Info::class);
        Livewire::component('wirechat.chats', Chats::class);

        // Override Filament DatabaseNotifications with custom per-panel category filtering
        // Must run after Filament registers its components (after all providers boot)
        $this->app->booted(function () {
            Livewire::component('filament.livewire.database-notifications', DatabaseNotifications::class);

            // Override Filament's default Login component with our custom Login class.
            // Filament v4 registers its parent Login class under 'filament.auth.pages.login'
            // during panel boot, which can shadow our custom class. This ensures our
            // custom Login (with Arabic translations and last_login_at tracking) is used.
            Livewire::component('filament.auth.pages.login', \App\Filament\Pages\Auth\Login::class);
        });

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
        Gate::policy(Certificate::class, CertificatePolicy::class);

        // Homework and Quiz policies
        Gate::policy(InteractiveCourseHomework::class, HomeworkPolicy::class);
        Gate::policy(QuizAssignment::class, QuizAssignmentPolicy::class);
        Gate::policy(QuizAttempt::class, QuizAttemptPolicy::class);

        // Interactive Course policies
        Gate::policy(InteractiveCourse::class, InteractiveCoursePolicy::class);
        Gate::policy(InteractiveCourseSession::class, InteractiveCourseSessionPolicy::class);

        // Meeting Attendance policy
        Gate::policy(MeetingAttendance::class, MeetingAttendancePolicy::class);

        // Academy policy
        Gate::policy(Academy::class, AcademyPolicy::class);

        // Recording policy
        Gate::policy(SessionRecording::class, RecordingPolicy::class);

        // QuranIndividualCircle policy
        Gate::policy(QuranIndividualCircle::class, QuranIndividualCirclePolicy::class);

        // Pulse dashboard access - restrict to super admins in non-local environments
        Gate::define('viewPulse', function ($user = null) {
            return $user && $user->isSuperAdmin();
        });

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
