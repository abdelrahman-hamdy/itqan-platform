<?php

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
| All teacher-facing routes (both Quran and Academic teachers) including
| session management, homework grading, reports, and student management.
*/

use App\Http\Controllers\AcademicIndividualLessonController;
use App\Http\Controllers\AcademicSessionController;
use App\Http\Controllers\LiveKitMeetingController;
use App\Http\Controllers\QuranGroupCircleScheduleController;
use App\Http\Controllers\QuranIndividualCircleController;
use App\Http\Controllers\QuranSessionController;
use App\Http\Controllers\StudentInteractiveCourseController;
use App\Http\Controllers\StudentReportController;
use App\Http\Controllers\Teacher\GroupCircleReportController;
use App\Http\Controllers\Teacher\HomeworkGradingController;
use App\Http\Controllers\Teacher\IndividualCircleReportController;
use App\Http\Controllers\Teacher\SessionHomeworkController;
use App\Http\Controllers\Teacher\TrialSessionController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Course Management Routes (Admin/Teacher Only)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:admin,teacher,quran_teacher,academic_teacher'])->group(function () {
        Route::get('/courses/create', [\App\Http\Controllers\RecordedCourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [\App\Http\Controllers\RecordedCourseController::class, 'store'])->name('courses.store');

        // Certificate Preview (Teachers/Admins) - accepts GET for iframe and POST for form
        Route::match(['get', 'post'], '/certificates/preview', [\App\Http\Controllers\CertificateController::class, 'preview'])->name('certificates.preview');
    });

    /*
    |--------------------------------------------------------------------------
    | Homework Grading Routes (All Teachers)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:quran_teacher,academic_teacher'])->group(function () {
        Route::prefix('teacher/homework')->name('teacher.homework.')->group(function () {
            Route::get('/', [HomeworkGradingController::class, 'index'])->name('index');
            Route::get('/{submissionId}/grade', [HomeworkGradingController::class, 'grade'])->name('grade');
            Route::post('/{submissionId}/grade', [HomeworkGradingController::class, 'gradeProcess'])->name('grade.process');
            Route::post('/{submissionId}/revision', [HomeworkGradingController::class, 'requestRevision'])->name('request-revision');
            Route::get('/statistics', [HomeworkGradingController::class, 'statistics'])->name('statistics');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Academic Teacher Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:academic_teacher'])->prefix('teacher')->name('teacher.')->group(function () {

        // Academic sessions list
        Route::get('/academic-sessions', [AcademicSessionController::class, 'index'])->name('academic-sessions.index');

        // Academic subscription comprehensive report
        Route::get('/academic-subscriptions/{subscription}/report', [AcademicSessionController::class, 'subscriptionReport'])->name('academic-subscriptions.report');

        Route::prefix('academic-sessions/{session}')->name('academic-sessions.')->group(function () {
            // Session view
            Route::get('/', [AcademicSessionController::class, 'show'])->name('show');

            // Session management
            Route::put('/evaluation', [AcademicSessionController::class, 'updateEvaluation'])->name('evaluation');
            Route::put('/status', [AcademicSessionController::class, 'updateStatus'])->name('status');
            Route::put('/reschedule', [AcademicSessionController::class, 'reschedule'])->name('reschedule');
            Route::put('/cancel', [AcademicSessionController::class, 'cancel'])->name('cancel');

            // Homework management
            Route::post('/homework/assign', [AcademicSessionController::class, 'assignHomework'])->name('assign-homework');
            Route::put('/homework/update', [AcademicSessionController::class, 'updateHomework'])->name('update-homework');
            Route::post('/reports/{reportId}/homework/grade', [AcademicSessionController::class, 'gradeHomework'])->name('grade-homework');
        });

        // Interactive courses listing for teachers
        Route::get('/interactive-courses', [AcademicIndividualLessonController::class, 'interactiveCoursesIndex'])->name('interactive-courses.index');

        // Interactive course comprehensive report
        Route::get('/interactive-courses/{course}/report', [StudentInteractiveCourseController::class, 'interactiveCourseReport'])->name('interactive-courses.report');
        // Interactive course individual student report
        Route::get('/interactive-courses/{course}/students/{student}/report', [StudentInteractiveCourseController::class, 'interactiveCourseStudentReport'])->name('interactive-courses.student-report');

        Route::prefix('interactive-sessions/{session}')->name('interactive-sessions.')->group(function () {
            // Session view for teachers
            Route::get('/', [StudentInteractiveCourseController::class, 'showInteractiveCourseSession'])->name('show');
            // Update session content
            Route::put('/content', [StudentInteractiveCourseController::class, 'updateInteractiveSessionContent'])->name('content');
            // Assign homework
            Route::post('/assign-homework', [StudentInteractiveCourseController::class, 'assignInteractiveSessionHomework'])->name('assign-homework');
            // Update homework
            Route::put('/update-homework', [StudentInteractiveCourseController::class, 'updateInteractiveSessionHomework'])->name('update-homework');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Teacher Student Report Management Routes (AJAX)
    |--------------------------------------------------------------------------
    */

    // Quran teacher reports - specific route
    Route::middleware(['auth', 'role:quran_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::post('/quran-reports/{type}', [StudentReportController::class, 'store'])->name('quran-reports.store');
        Route::put('/quran-reports/{type}/{report}', [StudentReportController::class, 'update'])->name('quran-reports.update');
    });

    // Academic teacher reports - specific route
    Route::middleware(['auth', 'role:academic_teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::post('/academic-reports/{type}', [StudentReportController::class, 'store'])->name('academic-reports.store');
        Route::put('/academic-reports/{type}/{report}', [StudentReportController::class, 'update'])->name('academic-reports.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Quran Teacher Individual Circles Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:quran_teacher'])->prefix('teacher')->name('teacher.')->group(function () {

        // Trial Sessions Management
        Route::get('/trial-sessions', [TrialSessionController::class, 'index'])->name('trial-sessions.index');
        Route::get('/trial-sessions/{trialRequest}', [TrialSessionController::class, 'show'])->name('trial-sessions.show');
        Route::put('/trial-sessions/{trialRequest}/evaluate', [TrialSessionController::class, 'evaluate'])->name('trial-sessions.evaluate');

        // Individual Circles Management
        Route::get('/individual-circles', [QuranIndividualCircleController::class, 'index'])->name('individual-circles.index');
        Route::get('/individual-circles/{circle}/progress', [QuranIndividualCircleController::class, 'progressReport'])->name('individual-circles.progress');
        Route::get('/individual-circles/{circle}/report', [IndividualCircleReportController::class, 'show'])->name('individual-circles.report');

        // AJAX routes for individual circles
        Route::get('/individual-circles/{circle}/template-sessions', [QuranIndividualCircleController::class, 'getTemplateSessions'])->name('individual-circles.template-sessions');
        Route::put('/individual-circles/{circle}/settings', [QuranIndividualCircleController::class, 'updateSettings'])->name('individual-circles.update-settings');

        // Student Reports API Routes
        Route::prefix('student-reports')->name('student-reports.')->group(function () {
            Route::get('{reportId}', [\App\Http\Controllers\Teacher\StudentReportController::class, 'show'])->name('show');
            Route::post('update', [\App\Http\Controllers\Teacher\StudentReportController::class, 'updateEvaluation'])->name('update');
            Route::post('sessions/{sessionId}/generate', [\App\Http\Controllers\Teacher\StudentReportController::class, 'generateSessionReports'])->name('generate-session');
            Route::get('sessions/{sessionId}/stats', [\App\Http\Controllers\Teacher\StudentReportController::class, 'getSessionStats'])->name('session-stats');
        });

        // Student basic info API
        Route::get('students/{studentId}/basic-info', [\App\Http\Controllers\Teacher\StudentReportController::class, 'getStudentBasicInfo'])->name('students.basic-info');

        // Session Homework Management Routes
        Route::prefix('sessions/{sessionId}/homework')->name('sessions.homework.')->group(function () {
            Route::get('', [SessionHomeworkController::class, 'show'])->name('show');
            Route::post('', [SessionHomeworkController::class, 'createOrUpdate'])->name('create-or-update');
            Route::delete('', [SessionHomeworkController::class, 'destroy'])->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Quran Teacher Group Circles Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:quran_teacher,admin,super_admin'])->prefix('teacher')->name('teacher.')->group(function () {
        // Group Circles Management
        Route::get('/group-circles', [QuranGroupCircleScheduleController::class, 'index'])->name('group-circles.index');
        Route::get('/group-circles/{circle}', [QuranGroupCircleScheduleController::class, 'show'])->name('group-circles.show');
        Route::get('/group-circles/{circle}/progress', [QuranGroupCircleScheduleController::class, 'progressReport'])->name('group-circles.progress');
        Route::get('/group-circles/{circle}/students/{student}/progress', [QuranGroupCircleScheduleController::class, 'studentProgressReport'])->name('group-circles.student-progress');

        // Group Circle Reports
        Route::get('/group-circles/{circle}/report', [GroupCircleReportController::class, 'show'])->name('group-circles.report');
        Route::get('/group-circles/{circle}/students/{student}/report', [GroupCircleReportController::class, 'studentReport'])->name('group-circles.student-report');

        // Session management routes
        Route::get('/sessions/{sessionId}', [QuranSessionController::class, 'showForTeacher'])->name('sessions.show');
        Route::put('/sessions/{sessionId}/notes', [QuranSessionController::class, 'updateNotes'])->name('sessions.update-notes');
        Route::put('/sessions/{sessionId}/complete', [QuranSessionController::class, 'markCompleted'])->name('sessions.complete');
        Route::put('/sessions/{sessionId}/cancel', [QuranSessionController::class, 'markCancelled'])->name('sessions.cancel');
        Route::put('/sessions/{sessionId}/absent', [QuranSessionController::class, 'markAbsent'])->name('sessions.absent');
        Route::get('/sessions/{sessionId}/actions', [QuranSessionController::class, 'getStatusActions'])->name('sessions.actions');
        Route::post('/sessions/{sessionId}/create-meeting', [LiveKitMeetingController::class, 'createMeeting'])->name('sessions.create-meeting');
    });
});
