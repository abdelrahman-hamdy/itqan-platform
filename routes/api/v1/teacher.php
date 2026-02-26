<?php

use App\Http\Controllers\Api\V1\Teacher\Academic\CourseController as AcademicCourseController;
use App\Http\Controllers\Api\V1\Teacher\Academic\LessonController as AcademicLessonController;
use App\Http\Controllers\Api\V1\Teacher\Academic\SessionController as AcademicSessionController;
use App\Http\Controllers\Api\V1\Teacher\CertificateController;
use App\Http\Controllers\Api\V1\Teacher\DashboardController;
use App\Http\Controllers\Api\V1\Teacher\EarningsController;
use App\Http\Controllers\Api\V1\Teacher\HomeworkController;
use App\Http\Controllers\Api\V1\Teacher\MeetingController;
use App\Http\Controllers\Api\V1\Teacher\ProfileController;
use App\Http\Controllers\Api\V1\Teacher\Quran\CircleController as QuranCircleController;
use App\Http\Controllers\Api\V1\Teacher\ReportController;
use App\Http\Controllers\Api\V1\Teacher\Quran\SessionController as QuranSessionController;
use App\Http\Controllers\Api\V1\Teacher\ScheduleController;
use App\Http\Controllers\Api\V1\Teacher\StudentController;
use App\Http\Controllers\Api\V1\Teacher\TrialRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Teacher Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/teacher
| Requires: auth:sanctum, api.resolve.academy, api.academy.active, api.user.academy
|
*/

// Verify user is a teacher
Route::middleware(['api.is.teacher', 'ability:teacher:*'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('api.v1.teacher.dashboard');

    // Schedule
    Route::prefix('schedule')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])
            ->name('api.v1.teacher.schedule.index');

        Route::get('/{date}', [ScheduleController::class, 'day'])
            ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
            ->name('api.v1.teacher.schedule.day');
    });

    // Quran Teacher Routes
    Route::prefix('quran')->middleware('api.is.quran-teacher')->group(function () {

        // Circles
        Route::prefix('circles')->group(function () {
            Route::get('/individual', [QuranCircleController::class, 'individualIndex'])
                ->name('api.v1.teacher.quran.circles.individual.index');

            Route::get('/individual/{id}', [QuranCircleController::class, 'individualShow'])
                ->name('api.v1.teacher.quran.circles.individual.show');

            Route::get('/group', [QuranCircleController::class, 'groupIndex'])
                ->name('api.v1.teacher.quran.circles.group.index');

            Route::get('/group/{id}', [QuranCircleController::class, 'groupShow'])
                ->name('api.v1.teacher.quran.circles.group.show');

            Route::get('/group/{id}/students', [QuranCircleController::class, 'groupStudents'])
                ->name('api.v1.teacher.quran.circles.group.students');

            Route::get('/group/{id}/certificates', [QuranCircleController::class, 'groupCertificates'])
                ->name('api.v1.teacher.quran.circles.group.certificates');
        });

        // Sessions
        Route::prefix('sessions')->group(function () {
            Route::get('/', [QuranSessionController::class, 'index'])
                ->name('api.v1.teacher.quran.sessions.index');

            Route::get('/{id}', [QuranSessionController::class, 'show'])
                ->name('api.v1.teacher.quran.sessions.show');

            Route::post('/{id}/complete', [QuranSessionController::class, 'complete'])
                ->name('api.v1.teacher.quran.sessions.complete');

            Route::post('/{id}/cancel', [QuranSessionController::class, 'cancel'])
                ->name('api.v1.teacher.quran.sessions.cancel');

            Route::post('/{id}/reschedule', [QuranSessionController::class, 'reschedule'])
                ->name('api.v1.teacher.quran.sessions.reschedule');

            Route::post('/{id}/mark-absent', [QuranSessionController::class, 'markAbsent'])
                ->name('api.v1.teacher.quran.sessions.mark-absent');

            Route::post('/{id}/evaluate', [QuranSessionController::class, 'evaluate'])
                ->name('api.v1.teacher.quran.sessions.evaluate');

            Route::put('/{id}/notes', [QuranSessionController::class, 'updateNotes'])
                ->name('api.v1.teacher.quran.sessions.notes');

            Route::get('/{id}/attendance', [QuranSessionController::class, 'attendance'])
                ->name('api.v1.teacher.quran.sessions.attendance');

            Route::put('/{id}/attendance/{attendanceId}', [QuranSessionController::class, 'overrideAttendance'])
                ->name('api.v1.teacher.quran.sessions.attendance.override');
        });

    });

    // Trial Requests (Quran teachers only, but at /teacher/trial-requests to match mobile)
    Route::prefix('trial-requests')->middleware('api.is.quran-teacher')->group(function () {
        Route::get('/', [TrialRequestController::class, 'index'])
            ->name('api.v1.teacher.trial-requests.index');

        Route::get('/{id}', [TrialRequestController::class, 'show'])
            ->name('api.v1.teacher.trial-requests.show');

        Route::post('/{id}/approve', [TrialRequestController::class, 'approve'])
            ->name('api.v1.teacher.trial-requests.approve');

        Route::post('/{id}/schedule', [TrialRequestController::class, 'schedule'])
            ->name('api.v1.teacher.trial-requests.schedule');

        Route::post('/{id}/reject', [TrialRequestController::class, 'reject'])
            ->name('api.v1.teacher.trial-requests.reject');
    });

    // Academic Teacher Routes
    Route::prefix('academic')->middleware('api.is.academic-teacher')->group(function () {

        // Lessons (Individual)
        Route::prefix('lessons')->group(function () {
            Route::get('/', [AcademicLessonController::class, 'index'])
                ->name('api.v1.teacher.academic.lessons.index');

            Route::get('/{id}', [AcademicLessonController::class, 'show'])
                ->name('api.v1.teacher.academic.lessons.show');
        });

        // Courses (Interactive)
        Route::prefix('courses')->group(function () {
            Route::get('/', [AcademicCourseController::class, 'index'])
                ->name('api.v1.teacher.academic.courses.index');

            Route::get('/{id}', [AcademicCourseController::class, 'show'])
                ->name('api.v1.teacher.academic.courses.show');

            Route::get('/{id}/students', [AcademicCourseController::class, 'students'])
                ->name('api.v1.teacher.academic.courses.students');

            Route::get('/{id}/certificates', [AcademicCourseController::class, 'certificates'])
                ->name('api.v1.teacher.academic.courses.certificates');
        });

        // Sessions
        Route::prefix('sessions')->group(function () {
            Route::get('/', [AcademicSessionController::class, 'index'])
                ->name('api.v1.teacher.academic.sessions.index');

            Route::get('/{id}', [AcademicSessionController::class, 'show'])
                ->name('api.v1.teacher.academic.sessions.show');

            Route::post('/{id}/complete', [AcademicSessionController::class, 'complete'])
                ->name('api.v1.teacher.academic.sessions.complete');

            Route::post('/{id}/cancel', [AcademicSessionController::class, 'cancel'])
                ->name('api.v1.teacher.academic.sessions.cancel');

            Route::post('/{id}/reschedule', [AcademicSessionController::class, 'reschedule'])
                ->name('api.v1.teacher.academic.sessions.reschedule');

            Route::post('/{id}/mark-absent', [AcademicSessionController::class, 'markAbsent'])
                ->name('api.v1.teacher.academic.sessions.mark-absent');

            Route::put('/{id}/evaluation', [AcademicSessionController::class, 'updateEvaluation'])
                ->name('api.v1.teacher.academic.sessions.evaluation');

            Route::get('/{id}/attendance', [AcademicSessionController::class, 'attendance'])
                ->name('api.v1.teacher.academic.sessions.attendance');

            Route::put('/{id}/attendance/{attendanceId}', [AcademicSessionController::class, 'overrideAttendance'])
                ->name('api.v1.teacher.academic.sessions.attendance.override');
        });
    });

    // Students (common for both teacher types)
    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index'])
            ->name('api.v1.teacher.students.index');

        Route::get('/{id}', [StudentController::class, 'show'])
            ->name('api.v1.teacher.students.show');

        Route::post('/{id}/report', [StudentController::class, 'createReport'])
            ->name('api.v1.teacher.students.report');
    });

    // Homework
    Route::prefix('homework')->group(function () {
        Route::get('/', [HomeworkController::class, 'index'])
            ->name('api.v1.teacher.homework.index');

        Route::get('/{type}/{id}', [HomeworkController::class, 'show'])
            ->where('type', 'academic|interactive|quran')
            ->name('api.v1.teacher.homework.show');

        Route::post('/assign', [HomeworkController::class, 'assign'])
            ->name('api.v1.teacher.homework.assign');

        Route::put('/{type}/{id}', [HomeworkController::class, 'update'])
            ->where('type', 'academic|interactive|quran')
            ->name('api.v1.teacher.homework.update');

        Route::get('/{type}/{id}/submissions', [HomeworkController::class, 'submissions'])
            ->where('type', 'academic|interactive|quran')
            ->name('api.v1.teacher.homework.submissions');

        Route::post('/submissions/{submissionId}/grade', [HomeworkController::class, 'grade'])
            ->name('api.v1.teacher.homework.grade');

        Route::post('/submissions/{submissionId}/request-revision', [HomeworkController::class, 'requestRevision'])
            ->name('api.v1.teacher.homework.request-revision');
    });

    // Meetings
    Route::prefix('meetings')->group(function () {
        Route::post('/create', [MeetingController::class, 'create'])
            ->name('api.v1.teacher.meetings.create');

        Route::get('/{sessionType}/{sessionId}/token', [MeetingController::class, 'token'])
            ->where('sessionType', 'quran|academic|interactive')
            ->name('api.v1.teacher.meetings.token');
    });

    // Earnings
    Route::prefix('earnings')->group(function () {
        Route::get('/', [EarningsController::class, 'summary'])
            ->name('api.v1.teacher.earnings.summary');

        Route::get('/history', [EarningsController::class, 'history'])
            ->name('api.v1.teacher.earnings.history');
    });

    Route::get('/payouts', [EarningsController::class, 'payouts'])
        ->name('api.v1.teacher.payouts');

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])
            ->name('api.v1.teacher.profile.show');

        Route::put('/', [ProfileController::class, 'update'])
            ->name('api.v1.teacher.profile.update');

        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
            ->name('api.v1.teacher.profile.avatar');

        Route::post('/change-password', [ProfileController::class, 'changePassword'])
            ->middleware('throttle:5,1')
            ->name('api.v1.teacher.profile.change-password');

        Route::delete('/', [ProfileController::class, 'deleteAccount'])
            ->middleware('throttle:3,1')
            ->name('api.v1.teacher.profile.delete');
    });

    // Reports (circle report data for teacher view)
    Route::prefix('reports')->group(function () {
        Route::get('/quran/individual/{id}', [ReportController::class, 'quranIndividualReport'])
            ->name('api.v1.teacher.reports.quran.individual');

        Route::get('/quran/individual/{id}/sessions', [ReportController::class, 'quranIndividualSessions'])
            ->name('api.v1.teacher.reports.quran.individual.sessions');

        Route::get('/quran/group/{id}', [ReportController::class, 'quranGroupReport'])
            ->name('api.v1.teacher.reports.quran.group');

        Route::get('/quran/group/{id}/sessions', [ReportController::class, 'quranGroupSessions'])
            ->name('api.v1.teacher.reports.quran.group.sessions');
    });

    // Certificates (common for both teacher types)
    Route::prefix('certificates')->group(function () {
        Route::post('/issue', [CertificateController::class, 'issue'])
            ->name('api.v1.teacher.certificates.issue');
    });
});
