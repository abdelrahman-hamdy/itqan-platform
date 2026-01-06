<?php

/*
|--------------------------------------------------------------------------
| Parent Routes
|--------------------------------------------------------------------------
| Parent portal routes for viewing children's data (subscriptions,
| sessions, payments, certificates, reports). Uses child-switching pattern.
*/

use App\Http\Controllers\ParentCalendarController;
use App\Http\Controllers\ParentCertificateController;
use App\Http\Controllers\ParentChildrenController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\ParentHomeworkController;
use App\Http\Controllers\ParentPaymentController;
use App\Http\Controllers\ParentProfileController;
use App\Http\Controllers\ParentQuizController;
use App\Http\Controllers\ParentReportController;
use App\Http\Controllers\ParentSessionController;
use App\Http\Controllers\ParentSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    Route::middleware(['auth', 'role:parent', 'child.selection'])->prefix('parent')->name('parent.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Child Selection API
        |--------------------------------------------------------------------------
        */

        Route::post('/select-child', [ParentDashboardController::class, 'selectChildSession'])->name('select-child');

        /*
        |--------------------------------------------------------------------------
        | Profile (Main Dashboard)
        |--------------------------------------------------------------------------
        */

        Route::get('/', [ParentProfileController::class, 'index'])->name('dashboard');
        Route::get('/profile', [ParentProfileController::class, 'index'])->name('profile');
        Route::get('/profile/edit', [ParentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ParentProfileController::class, 'update'])->name('profile.update');

        /*
        |--------------------------------------------------------------------------
        | Children Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('children')->name('children.')->group(function () {
            Route::get('/', [ParentChildrenController::class, 'index'])->name('index');
            Route::post('/', [ParentChildrenController::class, 'store'])->name('store');
            Route::delete('/{student}', [ParentChildrenController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Sessions
        |--------------------------------------------------------------------------
        */

        Route::prefix('sessions')->name('sessions.')->group(function () {
            Route::get('/upcoming', [ParentSessionController::class, 'upcoming'])->name('upcoming');
            Route::get('/history', [ParentSessionController::class, 'history'])->name('history');
            Route::get('/{sessionType}/{session}', [ParentSessionController::class, 'show'])->name('show');
        });

        /*
        |--------------------------------------------------------------------------
        | Calendar
        |--------------------------------------------------------------------------
        */

        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [ParentCalendarController::class, 'index'])->name('index');
            Route::get('/events', [ParentCalendarController::class, 'getEvents'])->name('events');
        });

        /*
        |--------------------------------------------------------------------------
        | Subscriptions
        |--------------------------------------------------------------------------
        */

        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [ParentSubscriptionController::class, 'index'])->name('index');
            Route::get('/{type}/{subscription}', [ParentSubscriptionController::class, 'show'])->name('show');
        });

        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        */

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [ParentPaymentController::class, 'index'])->name('index');
            Route::get('/{payment}', [ParentPaymentController::class, 'show'])->name('show');
            Route::get('/{payment}/receipt', [ParentPaymentController::class, 'downloadReceipt'])->name('receipt');
        });

        /*
        |--------------------------------------------------------------------------
        | Certificates
        |--------------------------------------------------------------------------
        */

        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/', [ParentCertificateController::class, 'index'])->name('index');
            Route::get('/{certificate}', [ParentCertificateController::class, 'show'])->name('show');
            Route::get('/{certificate}/download', [ParentCertificateController::class, 'download'])->name('download');
        });

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/progress', [ParentReportController::class, 'progressReport'])->name('progress');
            // Redirect old attendance route to unified progress report
            Route::get('/attendance', fn ($subdomain) => redirect()->route('parent.reports.progress', ['subdomain' => $subdomain]))->name('attendance');

            // Detailed reports for individual subscriptions
            Route::get('/quran/individual/{circle}', [ParentReportController::class, 'quranIndividualReport'])->name('quran.individual');
            Route::get('/academic/{subscription}', [ParentReportController::class, 'academicSubscriptionReport'])->name('academic.subscription');
            Route::get('/interactive/{course}', [ParentReportController::class, 'interactiveCourseReport'])->name('interactive.course');
        });

        /*
        |--------------------------------------------------------------------------
        | Homework (reuses student views with parent layout)
        |--------------------------------------------------------------------------
        */

        Route::prefix('homework')->name('homework.')->group(function () {
            Route::get('/', [ParentHomeworkController::class, 'index'])->name('index');
            Route::get('/{id}/{type?}', [ParentHomeworkController::class, 'view'])->name('view');
        });

        /*
        |--------------------------------------------------------------------------
        | Quizzes
        |--------------------------------------------------------------------------
        */

        Route::prefix('quizzes')->name('quizzes.')->group(function () {
            Route::get('/', [ParentQuizController::class, 'index'])->name('index');
            Route::get('/{quiz}/result', [ParentQuizController::class, 'result'])->name('result');
        });
    });
});
