<?php

use App\Http\Controllers\Api\DevMeetingController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Controllers\UnifiedMeetingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file contains API routes that are not part of the versioned mobile API.
| For mobile app API endpoints, see routes/api/v1.php
|
| Route Groups:
| - /user - Authenticated user info
| - /courses - Course progress tracking (web auth)
| - /sessions - Session status and meeting management
| - /server-time - Server time synchronization
|
*/

// ============================================================================
// AUTHENTICATED USER INFO
// ============================================================================
Route::middleware(['api.locale', 'auth:sanctum'])->get('/user', function () {
    return request()->user();
})->name('api.user');

// ============================================================================
// SERVER TIME SYNCHRONIZATION
// ============================================================================
Route::get('/server-time', [ServerTimeController::class, 'index'])
    ->name('api.server-time');

// ============================================================================
// COURSE PROGRESS TRACKING (Web Auth)
// ============================================================================
Route::middleware(['web', 'auth'])->prefix('courses')->group(function () {
    Route::get('/{courseId}/progress', [ProgressController::class, 'getCourseProgress']);
    Route::get('/{courseId}/lessons/{lessonId}/progress', [ProgressController::class, 'getLessonProgress']);
    Route::post('/{courseId}/lessons/{lessonId}/progress', [ProgressController::class, 'updateLessonProgress']);
    Route::post('/{courseId}/lessons/{lessonId}/complete', [ProgressController::class, 'markLessonComplete']);
    Route::post('/{courseId}/lessons/{lessonId}/toggle', [ProgressController::class, 'toggleLessonCompletion']);
});

// ============================================================================
// MEETING MANAGEMENT (Web Auth - Session Pages)
// These endpoints are called from session detail pages, not mobile app.
// ============================================================================
Route::middleware(['web', 'auth', 'verified'])
    ->prefix('sessions/meeting')
    ->group(function () {
        Route::post('/create', [UnifiedMeetingController::class, 'createMeeting'])
            ->name('api.sessions.meeting.create');
        Route::post('/token', [UnifiedMeetingController::class, 'getParticipantToken'])
            ->name('api.sessions.meeting.token');
        Route::get('/info', [UnifiedMeetingController::class, 'getRoomInfo'])
            ->name('api.sessions.meeting.info');
        Route::post('/end', [UnifiedMeetingController::class, 'endMeeting'])
            ->name('api.sessions.meeting.end');
        Route::post('/leave', [DevMeetingController::class, 'leave'])
            ->name('api.sessions.meeting.leave');
    });

// ============================================================================
// DEVELOPMENT ROUTES (Local/Development Only)
// These routes are NOT available in production.
// ============================================================================
if (app()->environment('local', 'development')) {
    Route::middleware(['web', 'auth', 'verified'])
        ->prefix('sessions/meeting')
        ->group(function () {
            Route::post('/join-dev', [DevMeetingController::class, 'joinDev'])
                ->name('api.sessions.meeting.join-dev');
            Route::post('/leave-dev', [DevMeetingController::class, 'leaveDev'])
                ->name('api.sessions.meeting.leave-dev');
        });
}

// ============================================================================
// PAYMENT API ROUTES (Web Auth - Checkout Pages)
// These endpoints support Paymob integration and saved payment methods.
// ============================================================================
Route::middleware(['web', 'auth'])
    ->prefix('v1/payments')
    ->group(function () {
        Route::post('/create-intent', [\App\Http\Controllers\Api\PaymentApiController::class, 'createIntent'])
            ->name('api.v1.payments.create-intent');

        Route::post('/charge-saved', [\App\Http\Controllers\Api\PaymentApiController::class, 'chargeSaved'])
            ->name('api.v1.payments.charge-saved');

        Route::get('/saved-methods', [\App\Http\Controllers\Api\PaymentApiController::class, 'getSavedMethods'])
            ->name('api.v1.payments.saved-methods');

        Route::delete('/saved-methods/{id}', [\App\Http\Controllers\Api\PaymentApiController::class, 'deleteSavedMethod'])
            ->name('api.v1.payments.delete-saved-method');

        Route::post('/saved-methods/{id}/default', [\App\Http\Controllers\Api\PaymentApiController::class, 'setDefaultMethod'])
            ->name('api.v1.payments.set-default-method');
    });

// ============================================================================
// MOBILE API V1 ROUTES
// ============================================================================
require __DIR__.'/api/v1.php';
