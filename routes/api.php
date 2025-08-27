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
