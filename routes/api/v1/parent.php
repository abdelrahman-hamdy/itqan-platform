<?php

use App\Http\Controllers\Api\V1\ParentApi\CertificateController;
use App\Http\Controllers\Api\V1\ParentApi\ChildrenController;
use App\Http\Controllers\Api\V1\ParentApi\DashboardController;
use App\Http\Controllers\Api\V1\ParentApi\PaymentController;
use App\Http\Controllers\Api\V1\ParentApi\ProfileController;
use App\Http\Controllers\Api\V1\ParentApi\QuizController;
use App\Http\Controllers\Api\V1\ParentApi\ReportController;
use App\Http\Controllers\Api\V1\ParentApi\SessionController;
use App\Http\Controllers\Api\V1\ParentApi\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Parent Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/parent
| Requires: auth:sanctum, api.resolve.academy, api.academy.active, api.user.academy
|
*/

// Verify user is a parent
Route::middleware('api.is.parent')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('api.v1.parent.dashboard');

    // Children Management
    Route::prefix('children')->group(function () {
        Route::get('/', [ChildrenController::class, 'index'])
            ->name('api.v1.parent.children.index');

        Route::post('/link', [ChildrenController::class, 'link'])
            ->name('api.v1.parent.children.link');

        Route::get('/{id}', [ChildrenController::class, 'show'])
            ->name('api.v1.parent.children.show');

        Route::put('/{id}/active', [ChildrenController::class, 'setActive'])
            ->name('api.v1.parent.children.set-active');

        Route::delete('/{id}/unlink', [ChildrenController::class, 'unlink'])
            ->name('api.v1.parent.children.unlink');

        // Child-specific endpoints
        Route::get('/{childId}/quizzes', [QuizController::class, 'childQuizzes'])
            ->name('api.v1.parent.children.quizzes');

        Route::get('/{childId}/certificates', [CertificateController::class, 'childCertificates'])
            ->name('api.v1.parent.children.certificates');
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])
            ->name('api.v1.parent.payments.index');

        Route::get('/{id}', [PaymentController::class, 'show'])
            ->name('api.v1.parent.payments.show');

        Route::post('/initiate', [PaymentController::class, 'initiate'])
            ->name('api.v1.parent.payments.initiate');
    });

    // Subscriptions
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])
            ->name('api.v1.parent.subscriptions.index');

        Route::get('/{type}/{id}', [SubscriptionController::class, 'show'])
            ->where('type', 'quran|academic|course')
            ->name('api.v1.parent.subscriptions.show');
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/progress', [ReportController::class, 'progress'])
            ->name('api.v1.parent.reports.progress');

        Route::get('/progress/{childId}', [ReportController::class, 'progress'])
            ->name('api.v1.parent.reports.child-progress');

        Route::get('/attendance', [ReportController::class, 'attendance'])
            ->name('api.v1.parent.reports.attendance');

        Route::get('/attendance/{childId}', [ReportController::class, 'attendance'])
            ->name('api.v1.parent.reports.child-attendance');

        Route::get('/subscription/{type}/{id}', [ReportController::class, 'subscription'])
            ->where('type', 'quran|academic')
            ->name('api.v1.parent.reports.subscription');
    });

    // Sessions
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index'])
            ->name('api.v1.parent.sessions.index');

        Route::get('/today', [SessionController::class, 'today'])
            ->name('api.v1.parent.sessions.today');

        Route::get('/upcoming', [SessionController::class, 'upcoming'])
            ->name('api.v1.parent.sessions.upcoming');

        Route::get('/{type}/{id}', [SessionController::class, 'show'])
            ->where('type', 'quran|academic|interactive')
            ->name('api.v1.parent.sessions.show');
    });

    // Quizzes
    Route::prefix('quizzes')->group(function () {
        Route::get('/', [QuizController::class, 'index'])
            ->name('api.v1.parent.quizzes.index');

        Route::get('/{id}', [QuizController::class, 'show'])
            ->name('api.v1.parent.quizzes.show');
    });

    // Certificates
    Route::prefix('certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'index'])
            ->name('api.v1.parent.certificates.index');

        Route::get('/{id}', [CertificateController::class, 'show'])
            ->name('api.v1.parent.certificates.show');
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])
            ->name('api.v1.parent.profile.show');

        Route::put('/', [ProfileController::class, 'update'])
            ->name('api.v1.parent.profile.update');

        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
            ->name('api.v1.parent.profile.avatar');

        Route::post('/change-password', [ProfileController::class, 'changePassword'])
            ->name('api.v1.parent.profile.change-password');

        Route::delete('/', [ProfileController::class, 'deleteAccount'])
            ->name('api.v1.parent.profile.delete');
    });
});
