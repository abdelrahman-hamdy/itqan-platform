<?php

/*
|--------------------------------------------------------------------------
| Supervisor Education Routes
|--------------------------------------------------------------------------
| Routes for the supervisor education frontend.
| Prefix: /manage, Middleware: auth, role:supervisor,super_admin,admin
*/

use App\Http\Controllers\SessionsMonitoringController;
use App\Http\Controllers\Supervisor\SupervisorAcademicLessonsController;
use App\Http\Controllers\Supervisor\SupervisorSessionsController;
use App\Http\Controllers\Supervisor\SupervisorCalendarController;
use App\Http\Controllers\Supervisor\SupervisorCertificatesController;
use App\Http\Controllers\Supervisor\SupervisorDashboardController;
use App\Http\Controllers\Supervisor\SupervisorGroupCirclesController;
use App\Http\Controllers\Supervisor\SupervisorIndividualCirclesController;
use App\Http\Controllers\Supervisor\SupervisorInteractiveCoursesController;
use App\Http\Controllers\Supervisor\SupervisorProfileController;
use App\Http\Controllers\Supervisor\SupervisorQuizzesController;
use App\Http\Controllers\Supervisor\SupervisorSessionReportsController;
use App\Http\Controllers\Supervisor\SupervisorTeachersController;
use App\Http\Controllers\Teacher\IndividualCircleReportController;
use App\Http\Controllers\Teacher\GroupCircleReportController;
use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\StudentInteractiveCourseController;
use App\Http\Controllers\Supervisor\SupervisorTrialSessionsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {
    Route::middleware(['auth', 'role:supervisor,super_admin,admin'])->prefix('manage')->name('manage.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [SupervisorDashboardController::class, 'index'])->name('dashboard');

        // Teachers
        Route::get('/teachers', [SupervisorTeachersController::class, 'index'])->name('teachers.index');

        // Group Circles
        Route::get('/group-circles', [SupervisorGroupCirclesController::class, 'index'])->name('group-circles.index');
        Route::get('/group-circles/{circle}', [SupervisorGroupCirclesController::class, 'show'])->name('group-circles.show');

        // Individual Circles
        Route::get('/individual-circles', [SupervisorIndividualCirclesController::class, 'index'])->name('individual-circles.index');
        Route::get('/individual-circles/{circle}', [SupervisorIndividualCirclesController::class, 'show'])->name('individual-circles.show');

        // Trial Sessions
        Route::get('/trial-sessions', [SupervisorTrialSessionsController::class, 'index'])->name('trial-sessions.index');
        Route::get('/trial-sessions/{trialRequest}', [SupervisorTrialSessionsController::class, 'show'])->name('trial-sessions.show');

        // Academic Lessons
        Route::get('/academic-lessons', [SupervisorAcademicLessonsController::class, 'index'])->name('academic-lessons.index');
        Route::get('/academic-lessons/{subscription}', [SupervisorAcademicLessonsController::class, 'show'])->name('academic-lessons.show');

        // Interactive Courses
        Route::get('/interactive-courses', [SupervisorInteractiveCoursesController::class, 'index'])->name('interactive-courses.index');
        Route::get('/interactive-courses/{course}', [SupervisorInteractiveCoursesController::class, 'show'])->name('interactive-courses.show');

        // Calendar
        Route::get('/calendar', [SupervisorCalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [SupervisorCalendarController::class, 'getEvents'])->name('calendar.events');
        Route::get('/calendar/schedulable-items', [SupervisorCalendarController::class, 'getSchedulableItems'])->name('calendar.schedulable-items');
        Route::get('/calendar/recommendations', [SupervisorCalendarController::class, 'getRecommendations'])->name('calendar.recommendations');
        Route::post('/calendar/schedule', [SupervisorCalendarController::class, 'createSchedule'])->name('calendar.schedule');
        Route::post('/calendar/check-conflicts', [SupervisorCalendarController::class, 'checkConflicts'])->name('calendar.check-conflicts');
        Route::put('/calendar/reschedule', [SupervisorCalendarController::class, 'rescheduleEvent'])->name('calendar.reschedule');
        Route::get('/calendar/session-detail', [SupervisorCalendarController::class, 'getSessionDetail'])->name('calendar.session-detail');
        Route::put('/calendar/update-session', [SupervisorCalendarController::class, 'updateSession'])->name('calendar.update-session');
        Route::post('/calendar/quran-homework', [SupervisorCalendarController::class, 'saveQuranHomework'])->name('calendar.quran-homework');
        Route::post('/calendar/academic-homework', [SupervisorCalendarController::class, 'saveAcademicHomework'])->name('calendar.academic-homework');

        // Sessions Management
        Route::get('/sessions', [SupervisorSessionsController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{sessionType}/{sessionId}', [SupervisorSessionsController::class, 'show'])
            ->name('sessions.show')->whereIn('sessionType', ['quran', 'academic', 'interactive']);
        Route::patch('/sessions/{sessionType}/{sessionId}', [SupervisorSessionsController::class, 'update'])
            ->name('sessions.update')->whereIn('sessionType', ['quran', 'academic', 'interactive']);
        Route::post('/sessions/{sessionType}/{sessionId}/cancel', [SupervisorSessionsController::class, 'cancel'])
            ->name('sessions.cancel')->whereIn('sessionType', ['quran', 'academic', 'interactive']);

        // Redirect old monitoring route
        Route::get('/sessions-monitoring', fn (Request $request, $subdomain) => redirect()->route('manage.sessions.index', ['subdomain' => $subdomain] + $request->query()))->name('sessions-monitoring.index');

        // Quizzes
        Route::get('/quizzes', [SupervisorQuizzesController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/{quiz}', [SupervisorQuizzesController::class, 'show'])->name('quizzes.show');

        // Session Reports
        Route::get('/session-reports', [SupervisorSessionReportsController::class, 'index'])->name('session-reports.index');

        // Report Detail Pages (reuse teacher report controllers with supervisor layout)
        Route::get('/individual-circles/{circle}/report', [IndividualCircleReportController::class, 'show'])->name('individual-circles.report');
        Route::get('/group-circles/{circle}/students/{student}/report', [GroupCircleReportController::class, 'studentReport'])->name('group-circles.student-report');
        Route::get('/academic-subscriptions/{subscription}/report', [AcademicSessionController::class, 'subscriptionReport'])->name('academic-subscriptions.report');
        Route::get('/interactive-courses/{course}/students/{student}/report', [StudentInteractiveCourseController::class, 'interactiveCourseStudentReport'])->name('interactive-courses.student-report');

        // Certificates
        Route::get('/certificates', [SupervisorCertificatesController::class, 'index'])->name('certificates.index');

        // Profile
        Route::get('/profile', [SupervisorProfileController::class, 'index'])->name('profile');
    });
});
