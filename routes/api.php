<?php

use App\Http\Controllers\Api\MeetingDataChannelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:web')->get('/user', function (Request $request) {
    return $request->user();
});

// Interactive Course Recording API Routes
Route::middleware(['auth:web', 'role:academic_teacher'])->prefix('interactive-courses')->group(function () {
    Route::post('/recording/start', [App\Http\Controllers\InteractiveCourseRecordingController::class, 'startRecording']);
    Route::post('/recording/stop', [App\Http\Controllers\InteractiveCourseRecordingController::class, 'stopRecording']);
    Route::get('/session/{sessionId}/recordings', [App\Http\Controllers\InteractiveCourseRecordingController::class, 'getSessionRecordings']);
    Route::delete('/recording/{recordingId}', [App\Http\Controllers\InteractiveCourseRecordingController::class, 'deleteRecording']);
    Route::get('/recording/{recordingId}/download', [App\Http\Controllers\InteractiveCourseRecordingController::class, 'downloadRecording']);
});

// Server time endpoint for session timer synchronization
Route::get('/server-time', function () {
    return response()->json([
        'timestamp' => now()->toISOString(),
        'unix_timestamp' => now()->timestamp,
        'timezone' => config('app.timezone'),
    ]);
});

// CSRF token refresh endpoint
Route::get('/csrf-token', function () {
    return response()->json([
        'token' => csrf_token(),
    ]);
});

// Removed LiveKit routes - moved to web.php for proper session authentication

// Meeting Data Channel API Routes
Route::middleware(['auth:web'])->group(function () {
    // Session-specific data channel routes
    Route::prefix('sessions/{session}')->group(function () {
        // Teacher control commands
        Route::post('commands/send', [MeetingDataChannelController::class, 'sendTeacherCommand']);
        Route::post('commands/mute-all', [MeetingDataChannelController::class, 'muteAllStudents']);
        Route::post('commands/allow-microphones', [MeetingDataChannelController::class, 'allowStudentMicrophones']);
        Route::post('commands/clear-hand-raises', [MeetingDataChannelController::class, 'clearAllHandRaises']);
        Route::post('commands/grant-microphone', [MeetingDataChannelController::class, 'grantMicrophoneToStudent']);

        // Participant interaction
        Route::post('acknowledge', [MeetingDataChannelController::class, 'acknowledgeMessage']);
        Route::get('state', [MeetingDataChannelController::class, 'getMeetingState']);
        Route::get('commands', [MeetingDataChannelController::class, 'getPendingCommands']);

        // Real-time communication
        Route::get('events', [MeetingDataChannelController::class, 'streamEvents']);

        // Delivery tracking
        Route::get('commands/{messageId}/status', [MeetingDataChannelController::class, 'getCommandDeliveryStatus']);

        // Testing
        Route::post('test-connectivity', [MeetingDataChannelController::class, 'testConnectivity']);
    });
});
