<?php

/*
|--------------------------------------------------------------------------
| Supervisor Education Routes
|--------------------------------------------------------------------------
| Routes for the supervisor education frontend.
| Prefix: /manage, Middleware: auth, role:supervisor,super_admin,admin
*/

use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\SessionReportShowController;
use App\Http\Controllers\StudentInteractiveCourseController;
use App\Http\Controllers\Supervisor\SupervisorAcademicLessonsController;
use App\Http\Controllers\Supervisor\SupervisorAttendanceController;
use App\Http\Controllers\Supervisor\SupervisorCalendarController;
use App\Http\Controllers\Supervisor\SupervisorCertificatesController;
use App\Http\Controllers\Supervisor\SupervisorDashboardController;
use App\Http\Controllers\Supervisor\SupervisorGroupCirclesController;
use App\Http\Controllers\Supervisor\SupervisorHomeworkController;
use App\Http\Controllers\Supervisor\SupervisorIndividualCirclesController;
use App\Http\Controllers\Supervisor\SupervisorInteractiveCoursesController;
use App\Http\Controllers\Supervisor\SupervisorParentsController;
use App\Http\Controllers\Supervisor\SupervisorPaymentsController;
use App\Http\Controllers\Supervisor\SupervisorProfileController;
use App\Http\Controllers\Supervisor\SupervisorQuizzesController;
use App\Http\Controllers\Supervisor\SupervisorRecordedCoursesController;
use App\Http\Controllers\Supervisor\SupervisorSessionReportsController;
use App\Http\Controllers\Supervisor\SupervisorSessionsController;
use App\Http\Controllers\Supervisor\SupervisorStudentsController;
use App\Http\Controllers\Supervisor\SupervisorSubscriptionsController;
use App\Http\Controllers\Supervisor\SupervisorSupervisorsController;
use App\Http\Controllers\Supervisor\SupervisorTeacherEarningsController;
use App\Http\Controllers\Supervisor\SupervisorTeachersController;
use App\Http\Controllers\Supervisor\SupervisorTrialSessionsController;
use App\Http\Controllers\Teacher\GroupCircleReportController;
use App\Http\Controllers\Teacher\IndividualCircleReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {
    Route::middleware(['auth', 'role:supervisor,super_admin,admin'])->prefix('manage')->name('manage.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [SupervisorDashboardController::class, 'index'])->name('dashboard');

        // Teachers
        Route::get('/teachers', [SupervisorTeachersController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/create', [SupervisorTeachersController::class, 'create'])->name('teachers.create');
        Route::post('/teachers', [SupervisorTeachersController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{teacher}', [SupervisorTeachersController::class, 'show'])->name('teachers.show');
        Route::get('/teachers/{teacher}/edit', [SupervisorTeachersController::class, 'edit'])->name('teachers.edit');
        Route::put('/teachers/{teacher}', [SupervisorTeachersController::class, 'update'])->name('teachers.update');
        Route::post('/teachers/{teacher}/toggle-status', [SupervisorTeachersController::class, 'toggleStatus'])->name('teachers.toggle-status');
        Route::post('/teachers/{teacher}/reset-password', [SupervisorTeachersController::class, 'resetPassword'])->name('teachers.reset-password');
        Route::post('/teachers/{teacher}/verify-email', [SupervisorTeachersController::class, 'verifyEmail'])->name('teachers.verify-email');
        Route::delete('/teachers/{teacher}', [SupervisorTeachersController::class, 'destroy'])->name('teachers.destroy');

        // Students
        Route::get('/students', [SupervisorStudentsController::class, 'index'])->name('students.index');
        Route::get('/students/create', [SupervisorStudentsController::class, 'create'])->name('students.create');
        Route::post('/students', [SupervisorStudentsController::class, 'store'])->name('students.store');
        Route::get('/students/{student}', [SupervisorStudentsController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [SupervisorStudentsController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [SupervisorStudentsController::class, 'update'])->name('students.update');
        Route::post('/students/{student}/toggle-status', [SupervisorStudentsController::class, 'toggleStatus'])->name('students.toggle-status');
        Route::post('/students/{student}/reset-password', [SupervisorStudentsController::class, 'resetPassword'])->name('students.reset-password');
        Route::post('/students/{student}/verify-email', [SupervisorStudentsController::class, 'verifyEmail'])->name('students.verify-email');
        Route::delete('/students/{student}', [SupervisorStudentsController::class, 'destroy'])->name('students.destroy');

        // Parents
        Route::get('/parents', [SupervisorParentsController::class, 'index'])->name('parents.index');
        Route::get('/parents/create', [SupervisorParentsController::class, 'create'])->name('parents.create');
        Route::post('/parents', [SupervisorParentsController::class, 'store'])->name('parents.store');
        Route::post('/parents/{parent}/toggle-status', [SupervisorParentsController::class, 'toggleStatus'])->name('parents.toggle-status');
        Route::post('/parents/{parent}/reset-password', [SupervisorParentsController::class, 'resetPassword'])->name('parents.reset-password');
        Route::post('/parents/{parent}/verify-email', [SupervisorParentsController::class, 'verifyEmail'])->name('parents.verify-email');
        Route::delete('/parents/{parent}', [SupervisorParentsController::class, 'destroy'])->name('parents.destroy');

        // Supervisors (admin-only management)
        Route::get('/supervisors', [SupervisorSupervisorsController::class, 'index'])->name('supervisors.index');
        Route::get('/supervisors/create', [SupervisorSupervisorsController::class, 'create'])->name('supervisors.create');
        Route::post('/supervisors', [SupervisorSupervisorsController::class, 'store'])->name('supervisors.store');
        Route::get('/supervisors/{supervisor}/edit', [SupervisorSupervisorsController::class, 'edit'])->name('supervisors.edit');
        Route::put('/supervisors/{supervisor}', [SupervisorSupervisorsController::class, 'update'])->name('supervisors.update');
        Route::post('/supervisors/{supervisor}/toggle-status', [SupervisorSupervisorsController::class, 'toggleStatus'])->name('supervisors.toggle-status');
        Route::post('/supervisors/{supervisor}/reset-password', [SupervisorSupervisorsController::class, 'resetPassword'])->name('supervisors.reset-password');
        Route::delete('/supervisors/{supervisor}', [SupervisorSupervisorsController::class, 'destroy'])->name('supervisors.destroy');

        // Teacher Earnings
        Route::get('/teacher-earnings', [SupervisorTeacherEarningsController::class, 'index'])->name('teacher-earnings.index');
        Route::get('/teacher-earnings/teacher-summary', [SupervisorTeacherEarningsController::class, 'teacherSummary'])->name('teacher-earnings.teacher-summary');
        Route::post('/teacher-earnings/{earning}/dispute', [SupervisorTeacherEarningsController::class, 'dispute'])->name('teacher-earnings.dispute');
        Route::post('/teacher-earnings/{earning}/resolve', [SupervisorTeacherEarningsController::class, 'resolve'])->name('teacher-earnings.resolve');

        // Group Circles
        Route::get('/group-circles', [SupervisorGroupCirclesController::class, 'index'])->name('group-circles.index');
        Route::get('/group-circles/{circle}', [SupervisorGroupCirclesController::class, 'show'])->name('group-circles.show');
        Route::put('/group-circles/{circle}', [SupervisorGroupCirclesController::class, 'update'])->name('group-circles.update');

        // Individual Circles
        Route::get('/individual-circles', [SupervisorIndividualCirclesController::class, 'index'])->name('individual-circles.index');
        Route::get('/individual-circles/{circle}', [SupervisorIndividualCirclesController::class, 'show'])->name('individual-circles.show');
        Route::put('/individual-circles/{circle}', [SupervisorIndividualCirclesController::class, 'update'])->name('individual-circles.update');

        // Trial Sessions
        Route::get('/trial-sessions', [SupervisorTrialSessionsController::class, 'index'])->name('trial-sessions.index');
        Route::get('/trial-sessions/{trialRequest}', [SupervisorTrialSessionsController::class, 'show'])->name('trial-sessions.show');
        Route::post('/trial-sessions/{trialRequest}/cancel', [SupervisorTrialSessionsController::class, 'cancel'])->name('trial-sessions.cancel');

        // Academic Lessons
        Route::get('/academic-lessons', [SupervisorAcademicLessonsController::class, 'index'])->name('academic-lessons.index');
        Route::get('/academic-lessons/{subscription}', [SupervisorAcademicLessonsController::class, 'show'])->name('academic-lessons.show');
        Route::put('/academic-lessons/{subscription}', [SupervisorAcademicLessonsController::class, 'update'])->name('academic-lessons.update');

        // Interactive Courses
        Route::get('/interactive-courses', [SupervisorInteractiveCoursesController::class, 'index'])->name('interactive-courses.index');
        Route::get('/interactive-courses/{course}', [SupervisorInteractiveCoursesController::class, 'show'])->name('interactive-courses.show');
        Route::post('/interactive-courses/{course}/enrollments', [SupervisorInteractiveCoursesController::class, 'addEnrollment'])->name('interactive-courses.add-enrollment');
        Route::delete('/interactive-courses/{course}/enrollments/{enrollment}', [SupervisorInteractiveCoursesController::class, 'removeEnrollment'])->name('interactive-courses.remove-enrollment');

        // Subscriptions
        Route::get('/subscriptions', [SupervisorSubscriptionsController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/create', \App\Livewire\Supervisor\CreateFullSubscription::class)->name('subscriptions.create');
        Route::get('/subscriptions/{type}/{subscription}', [SupervisorSubscriptionsController::class, 'show'])->name('subscriptions.show')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/activate', [SupervisorSubscriptionsController::class, 'activate'])->name('subscriptions.activate')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/pause', [SupervisorSubscriptionsController::class, 'pause'])->name('subscriptions.pause')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/resume', [SupervisorSubscriptionsController::class, 'resume'])->name('subscriptions.resume')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/extend', [SupervisorSubscriptionsController::class, 'extend'])->name('subscriptions.extend')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/cancel-extension', [SupervisorSubscriptionsController::class, 'cancelExtension'])->name('subscriptions.cancel-extension')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/cancel', [SupervisorSubscriptionsController::class, 'cancel'])->name('subscriptions.cancel')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/confirm-payment', [SupervisorSubscriptionsController::class, 'confirmPayment'])->name('subscriptions.confirm-payment')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/renew', [SupervisorSubscriptionsController::class, 'renew'])->name('subscriptions.renew')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/resubscribe', [SupervisorSubscriptionsController::class, 'resubscribe'])->name('subscriptions.resubscribe')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/cancel-pending', [SupervisorSubscriptionsController::class, 'cancelPending'])->name('subscriptions.cancel-pending')->whereIn('type', ['quran', 'academic']);
        Route::delete('/subscriptions/{type}/{subscription}', [SupervisorSubscriptionsController::class, 'destroy'])->name('subscriptions.destroy')->whereIn('type', ['quran', 'academic']);
        Route::post('/subscriptions/{type}/{subscription}/create-circle', [SupervisorSubscriptionsController::class, 'createCircle'])->name('subscriptions.create-circle')->whereIn('type', ['quran']);

        // Payments (admin-only)
        Route::get('/payments', [SupervisorPaymentsController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [SupervisorPaymentsController::class, 'show'])->name('payments.show');
        Route::post('/payments/{payment}/mark-completed', [SupervisorPaymentsController::class, 'markCompleted'])->name('payments.mark-completed');

        // Homework
        Route::get('/homework', [SupervisorHomeworkController::class, 'index'])->name('homework.index');
        Route::get('/homework/{type}/{id}/submissions', [SupervisorHomeworkController::class, 'submissions'])->name('homework.submissions');
        Route::post('/homework/submissions/{submission}/grade', [SupervisorHomeworkController::class, 'grade'])->name('homework.grade');

        // Attendance
        Route::get('/attendance', [SupervisorAttendanceController::class, 'index'])->name('attendance.index');

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
        Route::post('/sessions/{sessionType}/{sessionId}/forgive', [SupervisorSessionsController::class, 'forgive'])
            ->name('sessions.forgive')->whereIn('sessionType', ['quran', 'academic']);

        // Redirect old monitoring route
        Route::get('/sessions-monitoring', fn (Request $request, $subdomain) => redirect()->route('manage.sessions.index', ['subdomain' => $subdomain] + $request->query()))->name('sessions-monitoring.index');

        // Quizzes
        Route::get('/quizzes', [SupervisorQuizzesController::class, 'index'])->name('quizzes.index');
        Route::get('/quizzes/{quiz}', [SupervisorQuizzesController::class, 'show'])->name('quizzes.show');

        // Session Reports
        Route::get('/session-reports', [SupervisorSessionReportsController::class, 'index'])->name('session-reports.index');
        Route::get('/session-reports/{type}/{id}', [SessionReportShowController::class, 'show'])->name('session-reports.show')->whereIn('type', ['quran', 'academic', 'interactive']);

        // Report Detail Pages (reuse teacher report controllers with supervisor layout)
        Route::get('/individual-circles/{circle}/report', [IndividualCircleReportController::class, 'show'])->name('individual-circles.report');
        Route::get('/group-circles/{circle}/students/{student}/report', [GroupCircleReportController::class, 'studentReport'])->name('group-circles.student-report');
        Route::get('/academic-subscriptions/{subscription}/report', [AcademicSessionController::class, 'subscriptionReport'])->name('academic-subscriptions.report');
        Route::get('/interactive-courses/{course}/students/{student}/report', [StudentInteractiveCourseController::class, 'interactiveCourseStudentReport'])->name('interactive-courses.student-report');

        // Recorded Courses (admin-only)
        Route::get('/recorded-courses', [SupervisorRecordedCoursesController::class, 'index'])->name('recorded-courses.index');
        Route::get('/recorded-courses/{course}', [SupervisorRecordedCoursesController::class, 'show'])->name('recorded-courses.show');
        Route::post('/recorded-courses/{course}/toggle-publish', [SupervisorRecordedCoursesController::class, 'togglePublish'])->name('recorded-courses.toggle-publish');

        // Certificates
        Route::get('/certificates', [SupervisorCertificatesController::class, 'index'])->name('certificates.index');
        Route::get('/certificates/issue', [SupervisorCertificatesController::class, 'issue'])->name('certificates.issue');
        Route::post('/certificates', [SupervisorCertificatesController::class, 'store'])->name('certificates.store');
        Route::get('/certificates/{certificate}', [SupervisorCertificatesController::class, 'show'])->name('certificates.show');

        // Profile
        Route::get('/profile', [SupervisorProfileController::class, 'index'])->name('profile');
    });
});
