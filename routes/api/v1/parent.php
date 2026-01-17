<?php

use App\Http\Controllers\Api\V1\ParentApi\CertificateController;
use App\Http\Controllers\Api\V1\ParentApi\ChildrenController;
use App\Http\Controllers\Api\V1\ParentApi\DashboardController;
use App\Http\Controllers\Api\V1\ParentApi\HomeworkController;
use App\Http\Controllers\Api\V1\ParentApi\PaymentController;
use App\Http\Controllers\Api\V1\ParentApi\ProfileController;
use App\Http\Controllers\Api\V1\ParentApi\QuizController;
use App\Http\Controllers\Api\V1\ParentApi\Reports\ParentAcademicReportController;
// New refactored controllers
use App\Http\Controllers\Api\V1\ParentApi\Reports\ParentInteractiveReportController;
use App\Http\Controllers\Api\V1\ParentApi\Reports\ParentQuranReportController;
use App\Http\Controllers\Api\V1\ParentApi\Reports\ParentUnifiedReportController;
use App\Http\Controllers\Api\V1\ParentApi\Sessions\ParentAcademicSessionController;
use App\Http\Controllers\Api\V1\ParentApi\Sessions\ParentInteractiveSessionController;
use App\Http\Controllers\Api\V1\ParentApi\Sessions\ParentQuranSessionController;
use App\Http\Controllers\Api\V1\ParentApi\Sessions\ParentUnifiedSessionController;
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

        Route::get('/{childId}/homework', [HomeworkController::class, 'childHomework'])
            ->name('api.v1.parent.children.homework');
    });

    // Homework
    Route::prefix('homework')->group(function () {
        Route::get('/', [HomeworkController::class, 'index'])
            ->name('api.v1.parent.homework.index');

        Route::get('/{type}/{id}', [HomeworkController::class, 'show'])
            ->where('type', 'academic')
            ->name('api.v1.parent.homework.show');
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

    // Reports - Refactored with specialized controllers
    Route::prefix('reports')->group(function () {
        // Unified reports (all session types)
        Route::get('/progress', [ParentUnifiedReportController::class, 'progress'])
            ->name('api.v1.parent.reports.progress');

        Route::get('/progress/{childId}', [ParentUnifiedReportController::class, 'progress'])
            ->name('api.v1.parent.reports.child-progress');

        Route::get('/attendance', [ParentUnifiedReportController::class, 'attendance'])
            ->name('api.v1.parent.reports.attendance');

        Route::get('/attendance/{childId}', [ParentUnifiedReportController::class, 'attendance'])
            ->name('api.v1.parent.reports.child-attendance');

        // Quran-specific reports
        Route::prefix('quran')->group(function () {
            Route::get('/progress', [ParentQuranReportController::class, 'progress'])
                ->name('api.v1.parent.reports.quran.progress');

            Route::get('/progress/{childId}', [ParentQuranReportController::class, 'progress'])
                ->name('api.v1.parent.reports.quran.child-progress');

            Route::get('/attendance', [ParentQuranReportController::class, 'attendance'])
                ->name('api.v1.parent.reports.quran.attendance');

            Route::get('/attendance/{childId}', [ParentQuranReportController::class, 'attendance'])
                ->name('api.v1.parent.reports.quran.child-attendance');

            Route::get('/subscription/{id}', [ParentQuranReportController::class, 'subscription'])
                ->name('api.v1.parent.reports.quran.subscription');
        });

        // Academic-specific reports
        Route::prefix('academic')->group(function () {
            Route::get('/progress', [ParentAcademicReportController::class, 'progress'])
                ->name('api.v1.parent.reports.academic.progress');

            Route::get('/progress/{childId}', [ParentAcademicReportController::class, 'progress'])
                ->name('api.v1.parent.reports.academic.child-progress');

            Route::get('/attendance', [ParentAcademicReportController::class, 'attendance'])
                ->name('api.v1.parent.reports.academic.attendance');

            Route::get('/attendance/{childId}', [ParentAcademicReportController::class, 'attendance'])
                ->name('api.v1.parent.reports.academic.child-attendance');

            Route::get('/subscription/{id}', [ParentAcademicReportController::class, 'subscription'])
                ->name('api.v1.parent.reports.academic.subscription');
        });

        // Interactive course reports
        Route::prefix('interactive')->group(function () {
            Route::get('/progress', [ParentInteractiveReportController::class, 'progress'])
                ->name('api.v1.parent.reports.interactive.progress');

            Route::get('/progress/{childId}', [ParentInteractiveReportController::class, 'progress'])
                ->name('api.v1.parent.reports.interactive.child-progress');

            Route::get('/subscription/{id}', [ParentInteractiveReportController::class, 'subscription'])
                ->name('api.v1.parent.reports.interactive.subscription');
        });

        // Legacy routes for backward compatibility (deprecated)
        Route::get('/subscription/{type}/{id}', function ($type, $id) {
            return match ($type) {
                'quran' => app(ParentQuranReportController::class)->subscription(request(), $id),
                'academic' => app(ParentAcademicReportController::class)->subscription(request(), $id),
                default => response()->json(['error' => 'Invalid subscription type'], 400),
            };
        })->where('type', 'quran|academic')
            ->name('api.v1.parent.reports.subscription');
    });

    // Sessions - Refactored with specialized controllers
    Route::prefix('sessions')->group(function () {
        // Unified sessions (all types)
        Route::get('/', [ParentUnifiedSessionController::class, 'index'])
            ->name('api.v1.parent.sessions.index');

        Route::get('/today', [ParentUnifiedSessionController::class, 'today'])
            ->name('api.v1.parent.sessions.today');

        Route::get('/upcoming', [ParentUnifiedSessionController::class, 'upcoming'])
            ->name('api.v1.parent.sessions.upcoming');

        Route::get('/{type}/{id}', [ParentUnifiedSessionController::class, 'show'])
            ->where('type', 'quran|academic|interactive')
            ->name('api.v1.parent.sessions.show');

        // Quran sessions
        Route::prefix('quran')->group(function () {
            Route::get('/', [ParentQuranSessionController::class, 'index'])
                ->name('api.v1.parent.sessions.quran.index');

            Route::get('/today', [ParentQuranSessionController::class, 'today'])
                ->name('api.v1.parent.sessions.quran.today');

            Route::get('/upcoming', [ParentQuranSessionController::class, 'upcoming'])
                ->name('api.v1.parent.sessions.quran.upcoming');

            Route::get('/{id}', [ParentQuranSessionController::class, 'show'])
                ->name('api.v1.parent.sessions.quran.show');
        });

        // Academic sessions
        Route::prefix('academic')->group(function () {
            Route::get('/', [ParentAcademicSessionController::class, 'index'])
                ->name('api.v1.parent.sessions.academic.index');

            Route::get('/today', [ParentAcademicSessionController::class, 'today'])
                ->name('api.v1.parent.sessions.academic.today');

            Route::get('/upcoming', [ParentAcademicSessionController::class, 'upcoming'])
                ->name('api.v1.parent.sessions.academic.upcoming');

            Route::get('/{id}', [ParentAcademicSessionController::class, 'show'])
                ->name('api.v1.parent.sessions.academic.show');
        });

        // Interactive sessions
        Route::prefix('interactive')->group(function () {
            Route::get('/', [ParentInteractiveSessionController::class, 'index'])
                ->name('api.v1.parent.sessions.interactive.index');

            Route::get('/today', [ParentInteractiveSessionController::class, 'today'])
                ->name('api.v1.parent.sessions.interactive.today');

            Route::get('/upcoming', [ParentInteractiveSessionController::class, 'upcoming'])
                ->name('api.v1.parent.sessions.interactive.upcoming');

            Route::get('/{id}', [ParentInteractiveSessionController::class, 'show'])
                ->name('api.v1.parent.sessions.interactive.show');
        });
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
