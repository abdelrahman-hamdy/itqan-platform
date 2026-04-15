<?php

/*
|--------------------------------------------------------------------------
| Meeting Routes
|--------------------------------------------------------------------------
| LiveKit video meeting integration, webhooks, and meeting management API.
| Note: Meetings are embedded in session pages, not separate routes.
*/

use App\Http\Controllers\Api\DevMeetingController;
use App\Http\Controllers\Api\MeetingTelemetryController;
use App\Http\Controllers\InteractiveCourseRecordingController;
use App\Http\Controllers\LiveKitController;
use App\Http\Controllers\LiveKitMeetingController;
use App\Http\Controllers\LiveKitWebhookController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingObserverController;
use App\Http\Controllers\UnifiedMeetingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LiveKit Control Routes (Tenant-Aware)
|--------------------------------------------------------------------------
*/

Route::prefix('livekit')->middleware(['auth'])->group(function () {
    // Basic participant endpoints available to authenticated users
    Route::get('participants', [LiveKitController::class, 'getRoomParticipants']);
    Route::get('rooms/permissions', [LiveKitController::class, 'getRoomPermissions']);

    // Teacher-only participant control endpoints with detailed debugging
    Route::middleware(['control-participants'])->group(function () {
        Route::post('participants/mute', [LiveKitController::class, 'muteParticipant']);
        Route::post('participants/mute-all-students', [LiveKitController::class, 'muteAllStudents']);
        Route::post('participants/disable-all-students-camera', [LiveKitController::class, 'disableAllStudentsCamera']);
        Route::get('rooms/{room_name}/participants', [LiveKitController::class, 'getRoomParticipants']);
    });
});

/*
|--------------------------------------------------------------------------
| LiveKit Webhooks (Global - No Authentication)
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Meeting Management API (Session Pages)
|--------------------------------------------------------------------------
| Called from session detail pages via fetch/sendBeacon, not mobile app.
| These are web routes (not api/) to avoid the api middleware group's
| throttle:api (60/min) which caused 429 errors during meetings.
| CSRF exempted for telemetry/leave in bootstrap/app.php (sendBeacon can't set headers).
*/

Route::middleware(['auth', 'verified'])
    ->prefix('api/sessions/meeting')
    ->group(function () {
        Route::post('/create', [UnifiedMeetingController::class, 'createMeeting'])->name('api.sessions.meeting.create');
        Route::post('/token', [UnifiedMeetingController::class, 'getParticipantToken'])->name('api.sessions.meeting.token');
        Route::get('/info', [UnifiedMeetingController::class, 'getRoomInfo'])->name('api.sessions.meeting.info');
        Route::post('/end', [UnifiedMeetingController::class, 'endMeeting'])->name('api.sessions.meeting.end');
        Route::post('/leave', [DevMeetingController::class, 'leave'])->name('api.sessions.meeting.leave');
        Route::post('/telemetry', [MeetingTelemetryController::class, 'store'])
            ->middleware('throttle:1500,1')
            ->name('api.sessions.meeting.telemetry');
    });

// Dev-only meeting routes (not available in production)
if (app()->environment('local', 'development')) {
    Route::middleware(['auth', 'verified'])
        ->prefix('api/sessions/meeting')
        ->group(function () {
            Route::post('/join-dev', [DevMeetingController::class, 'joinDev'])->name('api.sessions.meeting.join-dev');
            Route::post('/leave-dev', [DevMeetingController::class, 'leaveDev'])->name('api.sessions.meeting.leave-dev');
        });
}

/*
|--------------------------------------------------------------------------
| LiveKit Webhooks (Global - No Authentication)
|--------------------------------------------------------------------------
*/

// Webhooks (no authentication required - validated via signatures)
// Rate limited to prevent abuse, CSRF excluded since webhooks use signature validation
Route::prefix('webhooks')->middleware(['throttle:60,1'])->group(function () {
    // LiveKit webhooks
    // CSRF exempted via validateCsrfTokens(except: [...]) in bootstrap/app.php
    Route::post('livekit', [LiveKitWebhookController::class, 'handleWebhook'])->name('webhooks.livekit');
    Route::get('livekit/health', [LiveKitWebhookController::class, 'health'])->name('webhooks.livekit.health');
});

/*
|--------------------------------------------------------------------------
| Meeting API Routes (Authenticated)
|--------------------------------------------------------------------------
*/

// Meeting API Routes (no separate UI routes - embedded in sessions)
Route::middleware(['auth'])->group(function () {
    Route::post('meetings/{session}/create-or-get', [MeetingController::class, 'createOrGet'])->name('meetings.create-or-get');
});

// LiveKit Meeting API routes (requires authentication)
Route::middleware(['auth'])->prefix('api/meetings')->group(function () {
    Route::post('create', [LiveKitMeetingController::class, 'createMeeting'])->name('api.meetings.create');
    Route::get('{sessionId}/token', [LiveKitMeetingController::class, 'getParticipantToken'])->name('api.meetings.token');
    Route::get('{sessionId}/info', [LiveKitMeetingController::class, 'getRoomInfo'])->name('api.meetings.info');
    Route::post('{sessionId}/end', [LiveKitMeetingController::class, 'endMeeting'])->name('api.meetings.end');

    // LiveKit Token API
    Route::post('livekit/token', [LiveKitController::class, 'getToken'])->name('api.livekit.token');

    // Observer token for supervisor/admin meeting observation (no attendance tracking)
    Route::get('observer/{sessionType}/{sessionId}/token', [MeetingObserverController::class, 'getObserverToken'])
        ->name('api.meetings.observer-token');
});

/*
|--------------------------------------------------------------------------
| Interactive Course Recording API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('api/recordings')->group(function () {
    // Recording control (start/stop)
    Route::post('start', [InteractiveCourseRecordingController::class, 'startRecording'])->name('api.recordings.start');
    Route::post('stop', [InteractiveCourseRecordingController::class, 'stopRecording'])->name('api.recordings.stop');

    // Recording management
    Route::get('session/{sessionId}', [InteractiveCourseRecordingController::class, 'getSessionRecordings'])->name('api.recordings.session');
    Route::delete('{recordingId}', [InteractiveCourseRecordingController::class, 'deleteRecording'])->name('api.recordings.delete');

    // Recording access (download/stream)
    Route::get('{recordingId}/download', [InteractiveCourseRecordingController::class, 'downloadRecording'])->name('recordings.download');
    Route::get('{recordingId}/stream', [InteractiveCourseRecordingController::class, 'streamRecording'])->name('recordings.stream');
});
