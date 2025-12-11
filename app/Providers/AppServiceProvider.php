<?php

namespace App\Providers;

use App\Helpers\AcademyHelper;
use App\Models\AcademicSession;
use App\Models\AcademicSessionAttendance;
use App\Models\AcademicSubscription;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\HomeworkSubmission;
use App\Models\InteractiveCourseSession;
use App\Models\Payment;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use App\Models\StudentSessionReport;
use App\Observers\AcademicSessionAttendanceObserver;
use App\Observers\AcademicSessionObserver;
use App\Observers\BaseSessionObserver;
use App\Observers\BaseSubscriptionObserver;
use App\Observers\HomeworkSubmissionObserver;
use App\Observers\MediaObserver;
use App\Observers\QuranSessionObserver;
use App\Observers\StudentProfileObserver;
use App\Observers\StudentSessionReportObserver;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use App\Policies\ParentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\SessionPolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TeacherProfilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        // Register Media Observer to handle UTF-8 filename sanitization
        Media::observe(MediaObserver::class);

        // Register AcademicSessionAttendance Observer for attendance-based progress tracking
        AcademicSessionAttendance::observe(AcademicSessionAttendanceObserver::class);

        // Register BaseSession Observer for automatic meeting creation on status change
        // This ensures meetings are created immediately when status changes to ready/ongoing
        QuranSession::observe(BaseSessionObserver::class);
        AcademicSession::observe(BaseSessionObserver::class);
        InteractiveCourseSession::observe(BaseSessionObserver::class);

        // Register QuranSession Observer for trial request status synchronization
        QuranSession::observe(QuranSessionObserver::class);

        // Register AcademicSession Observer for homework assignment notifications
        AcademicSession::observe(AcademicSessionObserver::class);

        // Register HomeworkSubmission Observer for homework grading notifications
        HomeworkSubmission::observe(HomeworkSubmissionObserver::class);

        // Register StudentSessionReport Observer for pivot table counter updates
        StudentSessionReport::observe(StudentSessionReportObserver::class);

        // Register StudentProfile Observer for parent-student relationship sync
        StudentProfile::observe(StudentProfileObserver::class);

        // Register BaseSubscription Observer for all subscription types
        // Handles subscription code generation, default values, status transitions
        QuranSubscription::observe(BaseSubscriptionObserver::class);
        AcademicSubscription::observe(BaseSubscriptionObserver::class);
        CourseSubscription::observe(BaseSubscriptionObserver::class);

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

        // Certificate policy - keep ParentPolicy for parent-specific checks
        Gate::policy(Certificate::class, ParentPolicy::class);

        // Render hooks can be added here if needed
    }
}
