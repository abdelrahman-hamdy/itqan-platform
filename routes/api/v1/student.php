<?php

use App\Http\Controllers\Api\V1\Student\CalendarController;
use App\Http\Controllers\Api\V1\Student\CertificateController;
use App\Http\Controllers\Api\V1\Student\DashboardController;
use App\Http\Controllers\Api\V1\Student\HomeworkController;
use App\Http\Controllers\Api\V1\Student\PaymentController;
use App\Http\Controllers\Api\V1\Student\ProfileController;
use App\Http\Controllers\Api\V1\Student\QuizController;
use App\Http\Controllers\Api\V1\Student\SessionController;
use App\Http\Controllers\Api\V1\Student\SubscriptionController;
use App\Http\Controllers\Api\V1\Student\TeacherController;
use App\Http\Controllers\Api\V1\Student\CourseController;
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

    // Sessions
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index'])
            ->name('api.v1.student.sessions.index');

        Route::get('/upcoming', [SessionController::class, 'upcoming'])
            ->name('api.v1.student.sessions.upcoming');

        Route::get('/today', [SessionController::class, 'today'])
            ->name('api.v1.student.sessions.today');

        Route::get('/{type}/{id}', [SessionController::class, 'show'])
            ->where('type', 'quran|academic|interactive')
            ->name('api.v1.student.sessions.show');

        Route::post('/{type}/{id}/feedback', [SessionController::class, 'submitFeedback'])
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
});
