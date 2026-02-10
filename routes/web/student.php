<?php

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
| All student-facing routes including profile, subscriptions, sessions,
| homework, quizzes, certificates, and course enrollment.
*/

use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuranSessionController;
use App\Http\Controllers\RecordedCourseController;
use App\Http\Controllers\Student\CircleReportController;
use App\Http\Controllers\Student\HomeworkController;
use App\Http\Controllers\Student\TrialRequestController;
use App\Http\Controllers\StudentAcademicController;
use App\Http\Controllers\StudentCalendarController;
use App\Http\Controllers\StudentInteractiveCourseController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\StudentQuranController;
use App\Http\Controllers\StudentSubscriptionController;
use App\Http\Controllers\UnifiedInteractiveCourseController;
use App\Http\Controllers\UnifiedQuranTeacherController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    Route::middleware(['auth', 'role:student'])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Student Profile & Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/profile', [StudentProfileController::class, 'index'])->name('student.profile');
        Route::get('/search', [StudentAcademicController::class, 'search'])->name('student.search');

        // Profile editing (some routes in auth.php for historical reasons)
        // Route::get('/profile/edit', ...) - see auth.php
        // Route::put('/profile/update', ...) - see auth.php

        /*
        |--------------------------------------------------------------------------
        | Student Subscriptions & Payments
        |--------------------------------------------------------------------------
        */

        Route::get('/payments', [StudentPaymentController::class, 'payments'])->name('student.payments');
        Route::get('/subscriptions', [StudentSubscriptionController::class, 'subscriptions'])->name('student.subscriptions');
        Route::patch('/subscriptions/{type}/{id}/toggle-auto-renew', [StudentSubscriptionController::class, 'toggleAutoRenew'])->name('student.subscriptions.toggle-auto-renew');
        Route::patch('/subscriptions/{type}/{id}/cancel', [StudentSubscriptionController::class, 'cancelSubscription'])->name('student.subscriptions.cancel');

        /*
        |--------------------------------------------------------------------------
        | Student Enrollment Routes
        |--------------------------------------------------------------------------
        */

        // 301 Permanent Redirects - OLD student routes to NEW unified routes
        Route::permanentRedirect('/my-quran-teachers', '/quran-teachers');
        Route::permanentRedirect('/my-quran-circles', '/quran-circles');
        Route::permanentRedirect('/my-academic-teachers', '/academic-teachers');
        Route::permanentRedirect('/my-interactive-courses', '/interactive-courses');

        // Quran Teacher Subscription Booking (Trial routes now in public.php)
        Route::get('/quran-teachers/{teacherId}/subscribe/{packageId}', [UnifiedQuranTeacherController::class, 'showSubscriptionBooking'])->name('quran-teachers.subscribe');
        Route::post('/quran-teachers/{teacherId}/subscribe/{packageId}', [UnifiedQuranTeacherController::class, 'submitSubscriptionRequest'])->name('quran-teachers.subscribe.submit');

        // Academic Package Subscription
        Route::get('/academic-packages/teachers/{teacher}/subscribe/{packageId}', [\App\Http\Controllers\PublicAcademicPackageController::class, 'showSubscriptionForm'])->name('public.academic-packages.subscribe');
        Route::post('/academic-packages/teachers/{teacher}/subscribe/{packageId}', [\App\Http\Controllers\PublicAcademicPackageController::class, 'submitSubscriptionRequest'])->name('public.academic-packages.subscribe.submit');

        // Interactive Course Enrollment
        Route::post('/interactive-courses/{courseId}/enroll', [UnifiedInteractiveCourseController::class, 'enroll'])->name('interactive-courses.enroll');

        /*
        |--------------------------------------------------------------------------
        | Student Sessions Routes
        |--------------------------------------------------------------------------
        */

        // Quran Sessions
        Route::get('/sessions/{sessionId}', [QuranSessionController::class, 'showForStudent'])->name('student.sessions.show');
        Route::put('/sessions/{sessionId}/feedback', [QuranSessionController::class, 'addFeedback'])->name('student.sessions.feedback');

        // Academic Subscriptions
        Route::get('/academic-subscriptions/{subscriptionId}', [StudentAcademicController::class, 'showAcademicSubscription'])->name('student.academic-subscriptions.show');
        Route::get('/academic-subscriptions/{subscription}/report', [AcademicSessionController::class, 'studentSubscriptionReport'])->name('student.academic-subscriptions.report');

        // Academic Sessions
        Route::get('/academic-sessions/{session}', [StudentAcademicController::class, 'showAcademicSession'])->name('student.academic-sessions.show');
        Route::put('/academic-sessions/{session}/feedback', [AcademicSessionController::class, 'addStudentFeedback'])->name('student.academic-sessions.feedback');
        Route::post('/academic-sessions/{session}/submit-homework', [AcademicSessionController::class, 'submitHomework'])->name('student.academic-sessions.submit-homework');

        // Interactive Course Sessions
        Route::prefix('student')->name('student.')->group(function () {
            Route::get('/interactive-sessions/{session}', [StudentInteractiveCourseController::class, 'showInteractiveCourseSession'])->name('interactive-sessions.show');
            Route::post('/interactive-sessions/{session}/feedback', [StudentInteractiveCourseController::class, 'addInteractiveSessionFeedback'])->name('interactive-sessions.feedback');
            Route::post('/interactive-sessions/{session}/homework', [StudentInteractiveCourseController::class, 'submitInteractiveCourseHomework'])->name('interactive-sessions.homework');
            Route::get('/interactive-courses/{course}/report', [StudentInteractiveCourseController::class, 'studentInteractiveCourseReport'])->name('interactive-courses.report');
        });

        // 301 Redirect - OLD interactive course detail to NEW unified route
        Route::permanentRedirect('/my-interactive-courses/{course}', '/interactive-courses/{course}');

        /*
        |--------------------------------------------------------------------------
        | Student Homework Routes
        |--------------------------------------------------------------------------
        */

        Route::prefix('homework')->name('student.homework.')->group(function () {
            Route::get('/', [HomeworkController::class, 'index'])->name('index');
            Route::get('/{id}/{type}/submit', [HomeworkController::class, 'submit'])->name('submit');
            Route::post('/{id}/{type}/submit', [HomeworkController::class, 'submitProcess'])->name('submit.process');
            Route::get('/{id}/{type}/view', [HomeworkController::class, 'view'])->name('view');
        });

        Route::get('/my-assignments', function () {
            return view('student.assignments');
        })->name('student.assignments');

        /*
        |--------------------------------------------------------------------------
        | Student Quizzes Routes
        |--------------------------------------------------------------------------
        */

        Route::get('/quizzes', function ($subdomain) {
            return app(QuizController::class)->index();
        })->name('student.quizzes');

        Route::get('/student-quiz-start/{quiz_id}', function ($subdomain, $quiz_id) {
            \Log::info('Quiz start route reached (closure wrapper)', ['subdomain' => $subdomain, 'quiz_id' => $quiz_id, 'user_id' => auth()->id()]);

            return app(QuizController::class)->start($quiz_id);
        })->name('student.quiz.start');

        Route::get('/student-quiz-take/{attempt_id}', function ($subdomain, $attempt_id) {
            \Log::info('Quiz take route reached', ['subdomain' => $subdomain, 'attempt_id' => $attempt_id]);

            return app(QuizController::class)->take($attempt_id);
        })->name('student.quiz.take');

        Route::post('/student-quiz-submit/{attempt_id}', function (\Illuminate\Http\Request $request, $subdomain, $attempt_id) {
            \Log::info('Quiz submit route reached', ['subdomain' => $subdomain, 'attempt_id' => $attempt_id]);

            return app(QuizController::class)->submit($request, $attempt_id);
        })->name('student.quiz.submit');

        Route::get('/student-quiz-results/{quiz_id}', function ($subdomain, $quiz_id) {
            \Log::info('Quiz results route reached', ['subdomain' => $subdomain, 'quiz_id' => $quiz_id]);

            return app(QuizController::class)->result($quiz_id);
        })->name('student.quiz.result');

        /*
        |--------------------------------------------------------------------------
        | Student Certificates Routes
        |--------------------------------------------------------------------------
        */

        Route::get('/certificates', [CertificateController::class, 'index'])->name('student.certificates');
        Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('student.certificate.download');
        Route::get('/certificates/{certificate}/view', [CertificateController::class, 'view'])->name('student.certificate.view');
        Route::post('/certificates/request-interactive', [CertificateController::class, 'requestForInteractiveCourse'])->name('student.certificate.request-interactive');

        /*
        |--------------------------------------------------------------------------
        | Student Circle Reports
        |--------------------------------------------------------------------------
        */

        Route::get('/individual-circles/{circle}/report', [CircleReportController::class, 'showIndividual'])->name('student.individual-circles.report');
        Route::get('/group-circles/{circle}/report', [CircleReportController::class, 'showGroup'])->name('student.group-circles.report');

        /*
        |--------------------------------------------------------------------------
        | Student Trial Request Details
        |--------------------------------------------------------------------------
        */

        Route::get('/trial-requests/{trialRequest}', [TrialRequestController::class, 'show'])->name('student.trial-requests.show');

        /*
        |--------------------------------------------------------------------------
        | Student Calendar Routes
        |--------------------------------------------------------------------------
        */

        Route::get('/student/calendar', [StudentCalendarController::class, 'index'])->name('student.calendar');
        Route::get('/student/calendar/events', [StudentCalendarController::class, 'getEvents'])->name('student.calendar.events');

        /*
        |--------------------------------------------------------------------------
        | Student Course Routes
        |--------------------------------------------------------------------------
        | Note: The /courses listing route is defined in routes/web/public.php
        | as 'courses.index'. It's a unified route that works for both public
        | and authenticated users. Only enrollment routes are defined here.
        */

        // Course Enrollment (Requires Authentication)
        Route::post('/courses/{id}/enroll', [RecordedCourseController::class, 'enroll'])->name('courses.enroll')->where('id', '[0-9]+');
        Route::post('/api/courses/{id}/enroll', [RecordedCourseController::class, 'enrollApi'])->name('courses.enroll.api')->where('id', '[0-9]+');
        Route::get('/courses/{id}/checkout', [RecordedCourseController::class, 'checkout'])->name('courses.checkout')->where('id', '[0-9]+');
        Route::get('/courses/{id}/learn', [RecordedCourseController::class, 'learn'])->name('courses.learn')->where('id', '[0-9]+');

        /*
        |--------------------------------------------------------------------------
        | Student Quran Circle Management
        |--------------------------------------------------------------------------
        */

        // Quran circle management (from auth.php)
        Route::get('/circles/{circleId}', [StudentQuranController::class, 'showCircle'])->name('student.circles.show');
        Route::post('/circles/{circleId}/enroll', [StudentQuranController::class, 'enrollInCircle'])->name('student.circles.enroll');
        Route::post('/circles/{circleId}/leave', [StudentQuranController::class, 'leaveCircle'])->name('student.circles.leave');

        /*
        |--------------------------------------------------------------------------
        | Quran Subscription Payment Routes
        |--------------------------------------------------------------------------
        */

        Route::get('/quran/subscription/{subscription}/payment', [\App\Http\Controllers\QuranSubscriptionPaymentController::class, 'create'])->name('quran.subscription.payment');
        Route::post('/quran/subscription/{subscription}/payment', [\App\Http\Controllers\QuranSubscriptionPaymentController::class, 'store'])->name('quran.subscription.payment.submit');

        /*
        |--------------------------------------------------------------------------
        | Academic Subscription Payment Routes
        |--------------------------------------------------------------------------
        */

        Route::get('/academic/subscription/{subscription}/payment', [\App\Http\Controllers\AcademicSubscriptionPaymentController::class, 'create'])->name('academic.subscription.payment');
        Route::post('/academic/subscription/{subscription}/payment', [\App\Http\Controllers\AcademicSubscriptionPaymentController::class, 'store'])->name('academic.subscription.payment.submit');
    });

    /*
    |--------------------------------------------------------------------------
    | Unified Individual Circles Routes (Multi-role access)
    |--------------------------------------------------------------------------
    */

    // Unified routes accessible by authenticated users (authorization enforced in controller)
    Route::middleware(['auth'])->group(function () {
        Route::get('/individual-circles/{circle}', [\App\Http\Controllers\QuranIndividualCircleController::class, 'show'])
            ->name('individual-circles.show');
    });
});
