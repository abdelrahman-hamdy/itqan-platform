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
use App\Http\Controllers\Supervisor\SupervisorTrialSessionsController;
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

        // Sessions Monitoring
        Route::get('/sessions-monitoring', [SupervisorCalendarController::class, 'monitoring'])->name('sessions-monitoring.index');

        // Quizzes
        Route::get('/quizzes', [SupervisorQuizzesController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/{quiz}', [SupervisorQuizzesController::class, 'show'])->name('quizzes.show');

        // Session Reports
        Route::get('/session-reports', [SupervisorSessionReportsController::class, 'index'])->name('session-reports.index');

        // Certificates
        Route::get('/certificates', [SupervisorCertificatesController::class, 'index'])->name('certificates.index');

        // Profile
        Route::get('/profile', [SupervisorProfileController::class, 'index'])->name('profile');
    });
});
