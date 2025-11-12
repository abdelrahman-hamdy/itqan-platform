<?php

use App\Http\Controllers\Api\ProgressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Progress tracking routes - use web middleware for session-based auth
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/courses/{courseId}/progress', [ProgressController::class, 'getCourseProgress']);
    Route::get('/courses/{courseId}/lessons/{lessonId}/progress', [ProgressController::class, 'getLessonProgress']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/progress', [ProgressController::class, 'updateLessonProgress']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/complete', [ProgressController::class, 'markLessonComplete']);
    Route::post('/courses/{courseId}/lessons/{lessonId}/toggle', [ProgressController::class, 'toggleLessonCompletion']);
});

// Unified Meeting API routes - used by session detail pages
// These are NOT separate meeting routes but API endpoints for session pages
Route::middleware(['web', 'auth', 'verified'])->prefix('sessions')->group(function () {
    // Meeting management endpoints called from session detail pages
    Route::post('/meeting/create', [App\Http\Controllers\UnifiedMeetingController::class, 'createMeeting'])
        ->name('api.sessions.meeting.create');

    Route::post('/meeting/token', [App\Http\Controllers\UnifiedMeetingController::class, 'getParticipantToken'])
        ->name('api.sessions.meeting.token');

    Route::get('/meeting/info', [App\Http\Controllers\UnifiedMeetingController::class, 'getRoomInfo'])
        ->name('api.sessions.meeting.info');

    Route::post('/meeting/end', [App\Http\Controllers\UnifiedMeetingController::class, 'endMeeting'])
        ->name('api.sessions.meeting.end');

    Route::post('/meeting/leave', [App\Http\Controllers\UnifiedMeetingController::class, 'recordLeave'])
        ->name('api.sessions.meeting.leave');
});

// Server time endpoint for session timer synchronization
Route::get('/server-time', function () {
    return response()->json([
        'timestamp' => now()->toISOString(),
        'unix_timestamp' => now()->getTimestamp(),
        'timezone' => \App\Services\AcademyContextService::getTimezone(),
    ]);
})->name('api.server-time');
