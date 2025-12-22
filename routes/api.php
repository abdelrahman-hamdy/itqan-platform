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

// Session Status API routes
Route::middleware('auth:sanctum')->prefix('sessions')->group(function () {
    // Academic session status and attendance
    Route::get('/academic/{sessionId}/status', [App\Http\Controllers\Api\SessionStatusApiController::class, 'academicSessionStatus'])
        ->name('api.sessions.academic.status');
    Route::get('/academic/{sessionId}/attendance', [App\Http\Controllers\Api\SessionStatusApiController::class, 'academicAttendanceStatus'])
        ->name('api.sessions.academic.attendance');

    // Quran session status and attendance
    Route::get('/quran/{sessionId}/status', [App\Http\Controllers\Api\SessionStatusApiController::class, 'quranSessionStatus'])
        ->name('api.sessions.quran.status');
    Route::get('/quran/{sessionId}/attendance', [App\Http\Controllers\Api\SessionStatusApiController::class, 'quranAttendanceStatus'])
        ->name('api.sessions.quran.attendance');
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

    // 🔥 DEVELOPMENT FALLBACK: Manual attendance tracking for local testing
    // Production uses LiveKit webhooks - these endpoints only work in local/development
    if (app()->environment('local', 'development')) {
        Route::post('/meeting/join-dev', function (Request $request) {
            $user = $request->user();
            $sessionId = $request->input('session_id');

            if (!$user || !$sessionId) {
                return response()->json(['error' => 'Missing user or session_id'], 400);
            }

            // Find session
            $session = \App\Models\AcademicSession::find($sessionId)
                ?? \App\Models\QuranSession::find($sessionId);

            if (!$session) {
                return response()->json(['error' => 'Session not found'], 404);
            }

            // Check if already has open event
            $hasOpenEvent = \App\Models\MeetingAttendanceEvent::where('session_id', $session->id)
                ->where('session_type', get_class($session))
                ->where('user_id', $user->id)
                ->where('event_type', 'join')
                ->whereNull('left_at')
                ->exists();

            if ($hasOpenEvent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Already in meeting',
                    'is_currently_in_meeting' => true,
                ]);
            }

            // Create join event (simulating webhook)
            $event = \App\Models\MeetingAttendanceEvent::create([
                'event_id' => 'DEV_JOIN_' . uniqid(),
                'event_type' => 'join',
                'event_timestamp' => now(),
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $session->academy_id ?? null,
                'participant_sid' => 'PA_DEV_' . uniqid(),
                'participant_identity' => 'user-' . $user->id,
                'participant_name' => $user->full_name,
                'raw_webhook_data' => ['dev_mode' => true],
            ]);

            \Cache::forget("attendance_status_{$session->id}_{$user->id}");

            \Log::info('🔧 DEV: Manual join event created', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Join recorded (dev mode)',
                'is_currently_in_meeting' => true,
            ]);
        })->name('api.sessions.meeting.join-dev');

        // Production meeting leave endpoint (used by LiveKit integration)
        Route::post('/meeting/leave', function (Request $request) {
            $user = $request->user();
            $sessionId = $request->input('session_id');

            if (!$user || !$sessionId) {
                return response()->json(['error' => 'Missing user or session_id'], 400);
            }

            // Find open event
            $event = \App\Models\MeetingAttendanceEvent::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->where('event_type', 'join')
                ->whereNull('left_at')
                ->latest('event_timestamp')
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => true,
                    'message' => 'No open event to close',
                    'is_currently_in_meeting' => false,
                ]);
            }

            // Close event
            $durationMinutes = $event->event_timestamp->diffInMinutes(now());
            $event->update([
                'left_at' => now(),
                'duration_minutes' => $durationMinutes,
                'leave_event_id' => 'LEAVE_' . uniqid(),
            ]);

            \Cache::forget("attendance_status_{$sessionId}_{$user->id}");

            \Log::info('✅ Meeting leave recorded via API', [
                'event_id' => $event->id,
                'duration_minutes' => $durationMinutes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave recorded',
                'is_currently_in_meeting' => false,
                'duration_minutes' => $durationMinutes,
            ]);
        })->name('api.sessions.meeting.leave');

        Route::post('/meeting/leave-dev', function (Request $request) {
            $user = $request->user();
            $sessionId = $request->input('session_id');

            if (!$user || !$sessionId) {
                return response()->json(['error' => 'Missing user or session_id'], 400);
            }

            // Find open event
            $event = \App\Models\MeetingAttendanceEvent::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->where('event_type', 'join')
                ->whereNull('left_at')
                ->latest('event_timestamp')
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => true,
                    'message' => 'No open event to close',
                    'is_currently_in_meeting' => false,
                ]);
            }

            // Close event
            $durationMinutes = $event->event_timestamp->diffInMinutes(now());
            $event->update([
                'left_at' => now(),
                'duration_minutes' => $durationMinutes,
                'leave_event_id' => 'DEV_LEAVE_' . uniqid(),
            ]);

            \Cache::forget("attendance_status_{$sessionId}_{$user->id}");

            \Log::info('🔧 DEV: Manual leave event recorded', [
                'event_id' => $event->id,
                'duration_minutes' => $durationMinutes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave recorded (dev mode)',
                'is_currently_in_meeting' => false,
                'duration_minutes' => $durationMinutes,
            ]);
        })->name('api.sessions.meeting.leave-dev');
    }
});

// Server time endpoint for session timer synchronization
Route::get('/server-time', function () {
    return response()->json([
        'timestamp' => now()->toISOString(),
        'unix_timestamp' => now()->getTimestamp(),
        'timezone' => \App\Services\AcademyContextService::getTimezone(),
    ]);
})->name('api.server-time');

/*
|--------------------------------------------------------------------------
| Mobile API V1 Routes
|--------------------------------------------------------------------------
|
| Include the versioned API routes for mobile app integration.
| All routes are prefixed with /api/v1
|
*/
require __DIR__ . '/api/v1.php';
