<?php

use App\Http\Controllers\Api\V1\Student\AcademicSessionController;
use App\Http\Controllers\Api\V1\Student\CalendarController;
use App\Http\Controllers\Api\V1\Student\CertificateController;
use App\Http\Controllers\Api\V1\Student\CourseController;
use App\Http\Controllers\Api\V1\Student\DashboardController;
use App\Http\Controllers\Api\V1\Student\HomeworkController;
use App\Http\Controllers\Api\V1\Student\InteractiveSessionController;
use App\Http\Controllers\Api\V1\Student\PaymentController;
use App\Http\Controllers\Api\V1\Student\ProfileController;
use App\Http\Controllers\Api\V1\Student\QuizController;
use App\Http\Controllers\Api\V1\Student\QuranSessionController;
use App\Http\Controllers\Api\V1\Student\RecordedCourseController;
use App\Http\Controllers\Api\V1\Student\SubscriptionController;
use App\Http\Controllers\Api\V1\Student\TeacherController;
use App\Http\Controllers\Api\V1\Student\UnifiedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Student Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/student
| Requires: auth:sanctum, api.resolve.academy, api.academy.active, api.user.academy
|
*/

// Verify user is a student
Route::middleware('api.is.student')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('api.v1.student.dashboard');

    // Sessions (unified - aggregates all types)
    Route::prefix('sessions')->group(function () {
        Route::get('/', [UnifiedSessionController::class, 'index'])
            ->name('api.v1.student.sessions.index');

        Route::get('/upcoming', [UnifiedSessionController::class, 'upcoming'])
            ->name('api.v1.student.sessions.upcoming');

        Route::get('/today', [UnifiedSessionController::class, 'today'])
            ->name('api.v1.student.sessions.today');

        // Type-specific routes for detailed operations
        Route::prefix('quran')->group(function () {
            Route::get('/', [QuranSessionController::class, 'index'])
                ->name('api.v1.student.sessions.quran.index');
            Route::get('/{id}', [QuranSessionController::class, 'show'])
                ->name('api.v1.student.sessions.quran.show');
            Route::post('/{id}/feedback', [QuranSessionController::class, 'submitFeedback'])
                ->name('api.v1.student.sessions.quran.feedback');
        });

        Route::prefix('academic')->group(function () {
            Route::get('/', [AcademicSessionController::class, 'index'])
                ->name('api.v1.student.sessions.academic.index');
            Route::get('/{id}', [AcademicSessionController::class, 'show'])
                ->name('api.v1.student.sessions.academic.show');
            Route::post('/{id}/feedback', [AcademicSessionController::class, 'submitFeedback'])
                ->name('api.v1.student.sessions.academic.feedback');
        });

        Route::prefix('interactive')->group(function () {
            Route::get('/', [InteractiveSessionController::class, 'index'])
                ->name('api.v1.student.sessions.interactive.index');
            Route::get('/{id}', [InteractiveSessionController::class, 'show'])
                ->name('api.v1.student.sessions.interactive.show');
            Route::post('/{id}/feedback', [InteractiveSessionController::class, 'submitFeedback'])
                ->name('api.v1.student.sessions.interactive.feedback');
        });

        // Legacy routes for backward compatibility (delegate to type-specific controllers)
        Route::get('/{type}/{id}', function (string $type, int $id) {
            $controllerClass = match ($type) {
                'quran' => QuranSessionController::class,
                'academic' => AcademicSessionController::class,
                'interactive' => InteractiveSessionController::class,
                default => null,
            };

            if (! $controllerClass) {
                abort(404);
            }

            return app($controllerClass)->show(request(), $id);
        })
            ->where('type', 'quran|academic|interactive')
            ->name('api.v1.student.sessions.show');

        Route::post('/{type}/{id}/feedback', function (string $type, int $id) {
            $controllerClass = match ($type) {
                'quran' => QuranSessionController::class,
                'academic' => AcademicSessionController::class,
                'interactive' => InteractiveSessionController::class,
                default => null,
            };

            if (! $controllerClass) {
                abort(404);
            }

            return app($controllerClass)->submitFeedback(request(), $id);
        })
            ->where('type', 'quran|academic|interactive')
            ->name('api.v1.student.sessions.feedback');
    });

    // Subscriptions
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])
            ->name('api.v1.student.subscriptions.index');

        Route::get('/{type}/{id}', [SubscriptionController::class, 'show'])
            ->where('type', 'quran|academic|course')
            ->name('api.v1.student.subscriptions.show');

        Route::get('/{type}/{id}/sessions', [SubscriptionController::class, 'sessions'])
            ->where('type', 'quran|academic|course')
            ->name('api.v1.student.subscriptions.sessions');

        Route::patch('/{type}/{id}/toggle-auto-renew', [SubscriptionController::class, 'toggleAutoRenew'])
            ->where('type', 'quran|academic')
            ->name('api.v1.student.subscriptions.toggle-auto-renew');

        Route::patch('/{type}/{id}/cancel', [SubscriptionController::class, 'cancel'])
            ->where('type', 'quran|academic|course')
            ->name('api.v1.student.subscriptions.cancel');
    });

    // Homework
    Route::prefix('homework')->group(function () {
        Route::get('/', [HomeworkController::class, 'index'])
            ->name('api.v1.student.homework.index');

        Route::get('/{type}/{id}', [HomeworkController::class, 'show'])
            ->where('type', 'academic|interactive')
            ->name('api.v1.student.homework.show');

        Route::post('/{type}/{id}/submit', [HomeworkController::class, 'submit'])
            ->where('type', 'academic|interactive')
            ->name('api.v1.student.homework.submit');

        Route::post('/{type}/{id}/draft', [HomeworkController::class, 'saveDraft'])
            ->where('type', 'academic|interactive')
            ->name('api.v1.student.homework.draft');

        Route::post('/{type}/{id}/revision', [HomeworkController::class, 'submitRevision'])
            ->where('type', 'academic|interactive')
            ->name('api.v1.student.homework.revision');
    });

    // Quizzes
    Route::prefix('quizzes')->group(function () {
        Route::get('/', [QuizController::class, 'index'])
            ->name('api.v1.student.quizzes.index');

        Route::get('/{id}', [QuizController::class, 'show'])
            ->name('api.v1.student.quizzes.show');

        Route::post('/{id}/start', [QuizController::class, 'start'])
            ->name('api.v1.student.quizzes.start');

        Route::post('/{id}/submit', [QuizController::class, 'submit'])
            ->name('api.v1.student.quizzes.submit');

        Route::get('/{id}/result', [QuizController::class, 'result'])
            ->name('api.v1.student.quizzes.result');
    });

    // Certificates
    Route::prefix('certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'index'])
            ->name('api.v1.student.certificates.index');

        Route::get('/{id}', [CertificateController::class, 'show'])
            ->name('api.v1.student.certificates.show');

        Route::get('/{id}/download', [CertificateController::class, 'download'])
            ->name('api.v1.student.certificates.download');

        Route::post('/request', [CertificateController::class, 'request'])
            ->name('api.v1.student.certificates.request');
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])
            ->name('api.v1.student.payments.index');

        Route::get('/{id}', [PaymentController::class, 'show'])
            ->name('api.v1.student.payments.show');

        Route::get('/{id}/receipt', [PaymentController::class, 'receipt'])
            ->name('api.v1.student.payments.receipt');
    });

    // Calendar
    Route::prefix('calendar')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])
            ->name('api.v1.student.calendar.index');

        Route::get('/month/{year}/{month}', [CalendarController::class, 'month'])
            ->where(['year' => '[0-9]{4}', 'month' => '[0-9]{1,2}'])
            ->name('api.v1.student.calendar.month');
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])
            ->name('api.v1.student.profile.show');

        Route::put('/', [ProfileController::class, 'update'])
            ->name('api.v1.student.profile.update');

        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
            ->name('api.v1.student.profile.avatar');
    });

    // Teachers (browsing)
    Route::prefix('teachers')->group(function () {
        Route::get('/quran', [TeacherController::class, 'quranTeachers'])
            ->name('api.v1.student.teachers.quran.index');

        Route::get('/quran/{id}', [TeacherController::class, 'showQuranTeacher'])
            ->name('api.v1.student.teachers.quran.show');

        Route::get('/academic', [TeacherController::class, 'academicTeachers'])
            ->name('api.v1.student.teachers.academic.index');

        Route::get('/academic/{id}', [TeacherController::class, 'showAcademicTeacher'])
            ->name('api.v1.student.teachers.academic.show');
    });

    // Interactive Courses
    Route::prefix('courses/interactive')->group(function () {
        Route::get('/', [CourseController::class, 'index'])
            ->name('api.v1.student.courses.interactive.index');

        Route::get('/{id}', [CourseController::class, 'show'])
            ->name('api.v1.student.courses.interactive.show');
    });

    // Recorded Courses
    Route::prefix('courses/recorded')->group(function () {
        Route::get('/', [RecordedCourseController::class, 'index'])
            ->name('api.v1.student.courses.recorded.index');

        Route::get('/{id}', [RecordedCourseController::class, 'show'])
            ->name('api.v1.student.courses.recorded.show');

        Route::get('/{id}/lessons', [RecordedCourseController::class, 'lessons'])
            ->name('api.v1.student.courses.recorded.lessons');

        Route::get('/{id}/bookmarks', [RecordedCourseController::class, 'bookmarks'])
            ->name('api.v1.student.courses.recorded.bookmarks');

        Route::get('/{id}/lessons/{lessonId}', [RecordedCourseController::class, 'lesson'])
            ->name('api.v1.student.courses.recorded.lesson');

        Route::post('/{id}/lessons/{lessonId}/progress', [RecordedCourseController::class, 'updateProgress'])
            ->name('api.v1.student.courses.recorded.progress');

        Route::get('/{id}/lessons/{lessonId}/materials', [RecordedCourseController::class, 'materials'])
            ->name('api.v1.student.courses.recorded.materials');

        Route::post('/{id}/lessons/{lessonId}/notes', [RecordedCourseController::class, 'addNote'])
            ->name('api.v1.student.courses.recorded.notes');

        Route::post('/{id}/lessons/{lessonId}/rate', [RecordedCourseController::class, 'rate'])
            ->name('api.v1.student.courses.recorded.rate');

        Route::post('/{id}/lessons/{lessonId}/bookmark', [RecordedCourseController::class, 'toggleBookmark'])
            ->name('api.v1.student.courses.recorded.bookmark');
    });
});
