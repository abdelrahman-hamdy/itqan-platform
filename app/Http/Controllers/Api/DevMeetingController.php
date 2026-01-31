<?php

namespace App\Http\Controllers\Api;

use App\Enums\MeetingEventType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\MeetingAttendanceEvent;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Development Meeting Controller
 *
 * IMPORTANT: This controller is ONLY loaded in local/development environments.
 * It provides manual attendance tracking endpoints for testing when LiveKit
 * webhooks are not available.
 *
 * Production uses LiveKit webhooks for real-time attendance tracking.
 *
 * @internal Development use only
 */
class DevMeetingController extends Controller
{
    /**
     * Manually record a meeting join event (dev mode only).
     *
     * Simulates a LiveKit webhook for joining a meeting.
     */
    public function joinDev(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');

        if (! $user || ! $sessionId) {
            return response()->json(['error' => 'Missing user or session_id'], 400);
        }

        $session = AcademicSession::find($sessionId)
            ?? QuranSession::find($sessionId);

        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        // Check if already has open event
        $hasOpenEvent = MeetingAttendanceEvent::where('session_id', $session->id)
            ->where('session_type', get_class($session))
            ->where('user_id', $user->id)
            ->where('event_type', MeetingEventType::JOINED)
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
        $event = MeetingAttendanceEvent::create([
            'event_id' => 'DEV_JOIN_'.uniqid(),
            'event_type' => MeetingEventType::JOINED,
            'event_timestamp' => now(),
            'session_id' => $session->id,
            'session_type' => get_class($session),
            'user_id' => $user->id,
            'academy_id' => $session->academy_id ?? null,
            'participant_sid' => 'PA_DEV_'.uniqid(),
            'participant_identity' => 'user-'.$user->id,
            'participant_name' => $user->full_name,
            'raw_webhook_data' => ['dev_mode' => true],
        ]);

        Cache::forget("attendance_status_{$session->id}_{$user->id}");

        Log::info('DEV: Manual join event created', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'session_id' => $session->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Join recorded (dev mode)',
            'is_currently_in_meeting' => true,
        ]);
    }

    /**
     * Manually record a meeting leave event (dev mode only).
     *
     * Simulates a LiveKit webhook for leaving a meeting.
     */
    public function leaveDev(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');

        if (! $user || ! $sessionId) {
            return response()->json(['error' => 'Missing user or session_id'], 400);
        }

        $event = MeetingAttendanceEvent::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->where('event_type', MeetingEventType::JOINED)
            ->whereNull('left_at')
            ->latest('event_timestamp')
            ->first();

        if (! $event) {
            return response()->json([
                'success' => true,
                'message' => 'No open event to close',
                'is_currently_in_meeting' => false,
            ]);
        }

        $durationMinutes = $event->event_timestamp->diffInMinutes(now());
        $event->update([
            'left_at' => now(),
            'duration_minutes' => $durationMinutes,
            'leave_event_id' => 'DEV_LEAVE_'.uniqid(),
        ]);

        Cache::forget("attendance_status_{$sessionId}_{$user->id}");

        Log::info('DEV: Manual leave event recorded', [
            'event_id' => $event->id,
            'duration_minutes' => $durationMinutes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave recorded (dev mode)',
            'is_currently_in_meeting' => false,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Production-compatible leave endpoint.
     *
     * Used by the frontend when LiveKit integration closes.
     */
    public function leave(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');

        if (! $user || ! $sessionId) {
            return response()->json(['error' => 'Missing user or session_id'], 400);
        }

        $event = MeetingAttendanceEvent::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->where('event_type', MeetingEventType::JOINED)
            ->whereNull('left_at')
            ->latest('event_timestamp')
            ->first();

        if (! $event) {
            return response()->json([
                'success' => true,
                'message' => 'No open event to close',
                'is_currently_in_meeting' => false,
            ]);
        }

        $durationMinutes = $event->event_timestamp->diffInMinutes(now());
        $event->update([
            'left_at' => now(),
            'duration_minutes' => $durationMinutes,
            'leave_event_id' => 'LEAVE_'.uniqid(),
        ]);

        Cache::forget("attendance_status_{$sessionId}_{$user->id}");

        Log::info('Meeting leave recorded via API', [
            'event_id' => $event->id,
            'duration_minutes' => $durationMinutes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave recorded',
            'is_currently_in_meeting' => false,
            'duration_minutes' => $durationMinutes,
        ]);
    }
}
