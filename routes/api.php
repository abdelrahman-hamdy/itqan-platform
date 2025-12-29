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
Route::middleware('auth:sanctum')->get('/user', function () {
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
// SESSION STATUS API (DEPRECATED)
// Migrate to /api/v1/student/sessions or /api/v1/teacher/sessions
// Sunset: June 30, 2025
// ============================================================================
Route::middleware(['auth:sanctum', 'api.deprecated:2025-06-30,/api/v1/student/sessions/{id}'])
    ->prefix('sessions')
    ->group(function () {
        // Academic session status
        Route::get('/academic/{sessionId}/status', [App\Http\Controllers\Api\AcademicSessionStatusApiController::class, 'status'])
            ->name('api.sessions.academic.status');
        Route::get('/academic/{sessionId}/attendance', [App\Http\Controllers\Api\AcademicSessionStatusApiController::class, 'attendance'])
            ->name('api.sessions.academic.attendance');

        // Quran session status
        Route::get('/quran/{sessionId}/status', [App\Http\Controllers\Api\QuranSessionStatusApiController::class, 'status'])
            ->name('api.sessions.quran.status');
        Route::get('/quran/{sessionId}/attendance', [App\Http\Controllers\Api\QuranSessionStatusApiController::class, 'attendance'])
            ->name('api.sessions.quran.attendance');

        // Unified session status (auto-detects type)
        Route::get('/{sessionId}/status', [App\Http\Controllers\Api\UnifiedSessionStatusApiController::class, 'generalSessionStatus'])
            ->name('api.sessions.status');
        Route::get('/{sessionId}/attendance', [App\Http\Controllers\Api\UnifiedSessionStatusApiController::class, 'generalAttendanceStatus'])
            ->name('api.sessions.attendance');
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
// MOBILE API V1 ROUTES
// ============================================================================
require __DIR__ . '/api/v1.php';
