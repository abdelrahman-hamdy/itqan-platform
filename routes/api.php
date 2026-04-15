<?php

use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\ServerTimeController;
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
// API-001: Explicitly whitelist returned fields instead of exposing full user object
Route::middleware(['api.locale', 'auth:sanctum'])->get('/user', function () {
    $user = request()->user();

    return response()->json([
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'user_type' => $user->user_type,
        'academy_id' => $user->academy_id,
        'avatar' => $user->avatar,
        'email_verified_at' => $user->email_verified_at,
        'active_status' => $user->active_status,
        'created_at' => $user->created_at,
    ]);
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
// MEETING MANAGEMENT — Moved to routes/web/meetings.php
// These use web session auth, not API tokens. Kept in web routes to avoid
// the api middleware group's throttle:api (60/min) which caused 429 errors.
// ============================================================================

// ============================================================================
// PAYMENT API ROUTES (Web Auth - Checkout Pages)
// ============================================================================
Route::middleware(['web', 'auth', 'throttle:10,1'])
    ->prefix('v1/payments')
    ->group(function () {
        Route::post('/create-intent', [\App\Http\Controllers\Api\PaymentApiController::class, 'createIntent'])
            ->name('api.v1.payments.create-intent');
    });

// ============================================================================
// MOBILE API V1 ROUTES
// ============================================================================
require __DIR__.'/api/v1.php';
