<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\SessionMeetingService;
use App\Services\MeetingAttendanceService;
use App\Services\SessionStatusService;
use App\Services\AttendanceEventService;
use App\Enums\SessionStatus;

class LiveKitWebhookController extends Controller
{
    private SessionMeetingService $sessionMeetingService;
    private MeetingAttendanceService $attendanceService;
    private SessionStatusService $statusService;
    private AttendanceEventService $eventService;

    public function __construct(
        SessionMeetingService $sessionMeetingService,
        MeetingAttendanceService $attendanceService,
        SessionStatusService $statusService,
        AttendanceEventService $eventService
    ) {
        $this->sessionMeetingService = $sessionMeetingService;
        $this->attendanceService = $attendanceService;
        $this->statusService = $statusService;
        $this->eventService = $eventService;
    }

    /**
     * Handle webhooks from LiveKit server
     */
    public function handleWebhook(Request $request): Response
    {
        // 🔥 CRITICAL DEBUG: Log EVERY incoming request to this endpoint
        Log::info('🔔 WEBHOOK ENDPOINT HIT - Request received', [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'body_size' => strlen($request->getContent()),
            'has_event' => $request->has('event'),
            'event_value' => $request->input('event'),
        ]);

        try {
            // ENHANCEMENT: Validate webhook signature
            if (! $this->validateWebhookSignature($request)) {
                Log::warning('Invalid LiveKit webhook signature', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response('Unauthorized', 401);
            }

            $event = $request->input('event');
            $data = $request->all();

            Log::info('LiveKit webhook received', [
                'event' => $event,
                'room' => $data['room']['name'] ?? 'unknown',
                'participant_count' => $data['room']['num_participants'] ?? 0,
            ]);

            switch ($event) {
                case 'room_started':
                    $this->handleRoomStarted($data);
                    break;
                    
                case 'room_finished':
                    $this->handleRoomFinished($data);
                    break;
                    
                case 'participant_joined':
                    $this->handleParticipantJoined($data);
                    break;
                    
                case 'participant_left':
                    $this->handleParticipantLeft($data);
                    break;
                    
                case 'recording_started':
                    $this->handleRecordingStarted($data);
                    break;
                    
                case 'recording_finished':
                    $this->handleRecordingFinished($data);
                    break;

                case 'track_published':
                    $this->handleTrackPublished($data);
                    break;

                default:
                    Log::info('Unhandled LiveKit webhook event', ['event' => $event]);
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Failed to handle LiveKit webhook', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return response('Error processing webhook', 500);
        }
    }

    /**
     * Health check endpoint for LiveKit webhooks
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'livekit-webhooks',
        ]);
    }

    /**
     * Handle room started event
     */
    private function handleRoomStarted(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        if (!$roomName) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        try {
            // CRITICAL FIX: Only transition to ONGOING if session is READY
            // This prevents premature status changes for sessions that haven't reached preparation time
            if ($session->status !== SessionStatus::READY) {
                Log::warning('Room started but session not READY - skipping status transition', [
                    'session_id' => $session->id,
                    'session_type' => $session->session_type,
                    'current_status' => $session->status->value,
                    'room_name' => $roomName,
                    'scheduled_at' => $session->scheduled_at,
                    'current_time' => now(),
                ]);
                return;
            }

            // Update session status to ongoing
            $session->update([
                'status' => SessionStatus::ONGOING,
                'meeting_started_at' => now(),
            ]);

            // Ensure session persistence
            $this->sessionMeetingService->markSessionPersistent($session);

            Log::info('Session meeting started', [
                'session_id' => $session->id,
                'session_type' => $session->session_type,
                'room_name' => $roomName,
                'started_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle room started event', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle room finished event
     */
    private function handleRoomFinished(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        if (!$roomName) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        try {
            $participantCount = $data['room']['num_participants'] ?? 0;
            $duration = $data['room']['duration_seconds'] ?? 0;

            // Only mark as completed if the session was actually used
            if ($duration > 60) { // At least 1 minute of activity
                $session->update([
                    'status' => SessionStatus::COMPLETED,
                    'meeting_ended_at' => now(),
                    'actual_duration_minutes' => round($duration / 60),
                ]);
            } else {
                // Very short session, just mark as ended
                $session->update([
                    'meeting_ended_at' => now(),
                ]);
            }

            // Remove persistence since room is finished
            $this->sessionMeetingService->removeSessionPersistence($session);

            Log::info('Session meeting finished', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'duration_seconds' => $duration,
                'participant_count' => $participantCount,
                'ended_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle room finished event', [
                'session_id' => $session->id ?? 'unknown',
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle participant joined event
     * Creates immutable event log entry with LiveKit's exact timestamp
     */
    private function handleParticipantJoined(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        $participantData = $data['participant'] ?? [];
        $participantIdentity = $participantData['identity'] ?? null;

        if (!$roomName || !$participantIdentity) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        // 🔧 FIX: Set tenant context for multi-tenancy support in queued jobs
        if (isset($session->tenant_id)) {
            $tenant = \App\Models\Academy::find($session->tenant_id);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        try {
            // Extract user ID from participant identity
            $userId = $this->extractUserIdFromIdentity($participantIdentity);

            if (!$userId) {
                Log::warning('Could not extract user ID from participant identity', [
                    'participant_identity' => $participantIdentity,
                ]);
                return;
            }

            $user = User::find($userId);
            if (!$user) {
                Log::warning('User not found', ['user_id' => $userId]);
                return;
            }

            // 🔥 NEW: Use LiveKit's exact join timestamp (source of truth)
            $joinedAt = isset($participantData['joined_at'])
                ? \Carbon\Carbon::createFromTimestamp($participantData['joined_at'])
                : now();

            // 🔥 NEW: Create immutable event log entry
            $event = \App\Models\MeetingAttendanceEvent::create([
                'event_id' => $data['id'],  // Webhook UUID for idempotency
                'event_type' => 'join',
                'event_timestamp' => $joinedAt,  // From LiveKit, not Carbon::now()
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $session->academy_id ?? null,
                'participant_sid' => $participantData['sid'] ?? null,
                'participant_identity' => $participantIdentity,
                'participant_name' => $participantData['name'] ?? $user->full_name,
                'raw_webhook_data' => $data,  // Store full payload for debugging
            ]);

            // 🎯 NEW: Update MeetingAttendance record (aggregated state)
            $this->eventService->recordJoin($session, $user, [
                'timestamp' => $joinedAt,
                'event_id' => $data['id'],
                'participant_sid' => $participantData['sid'] ?? null,
            ]);

            // 🐛 Enhanced logging for debugging
            Log::info('✅ [WEBHOOK] JOIN event processed', [
                'webhook_id' => $data['id'] ?? null,
                'event_db_id' => $event->id,
                'session_id' => $session->id,
                'session_name' => $session->name ?? 'Unknown',
                'user_id' => $userId,
                'user_name' => $user->full_name,
                'participant_sid' => $participantData['sid'] ?? null,
                'joined_at' => $joinedAt->toISOString(),
                'room_name' => $roomName,
                'participant_count' => $data['room']['num_participants'] ?? 0,
            ]);

            // Clear any cached attendance status
            \Cache::forget("attendance_status_{$session->id}_{$userId}");

        } catch (\Illuminate\Database\UniqueConstraintException $e) {
            // Duplicate webhook - safely ignore
            Log::info('Duplicate join webhook ignored', [
                'event_id' => $data['id'],
                'session_id' => $session->id,
                'user_id' => $userId ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle participant joined event', [
                'session_id' => $session->id ?? 'unknown',
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle participant left event
     * Closes the attendance cycle and calculates duration from LiveKit timestamps
     */
    private function handleParticipantLeft(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        $participantData = $data['participant'] ?? [];
        $participantIdentity = $participantData['identity'] ?? null;
        $participantSid = $participantData['sid'] ?? null;

        if (!$roomName || !$participantIdentity) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        // 🔧 FIX: Set tenant context for multi-tenancy support in queued jobs
        if (isset($session->tenant_id)) {
            $tenant = \App\Models\Academy::find($session->tenant_id);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        try {
            $userId = $this->extractUserIdFromIdentity($participantIdentity);

            if (!$userId) {
                Log::warning('Could not extract user ID from participant identity', [
                    'participant_identity' => $participantIdentity,
                ]);
                return;
            }

            // 🔥 NEW: Use webhook creation time as leave timestamp (source of truth)
            $leftAt = isset($data['createdAt'])
                ? \Carbon\Carbon::createFromTimestamp($data['createdAt'])
                : now();

            // 🔥 NEW: Find the matching join event by participant_sid
            $joinEvent = \App\Models\MeetingAttendanceEvent::where('session_id', $session->id)
                ->where('session_type', get_class($session))
                ->where('user_id', $userId)
                ->where('participant_sid', $participantSid)
                ->where('event_type', 'join')
                ->whereNull('left_at')
                ->latest('event_timestamp')
                ->first();

            if (!$joinEvent) {
                Log::warning('No matching join event found for leave', [
                    'session_id' => $session->id,
                    'user_id' => $userId,
                    'participant_sid' => $participantSid,
                    'participant_identity' => $participantIdentity,
                ]);

                // Retry synchronously - leave might have arrived before join (rare race condition)
                // Execute immediately without dispatching to avoid tenant context issues
                \Log::info('[WEBHOOK] Leave arrived before join - will retry in 5 seconds');
                sleep(5); // Give join webhook time to process

                $joinEvent = \App\Models\MeetingAttendanceEvent::where('session_id', $session->id)
                    ->where('session_type', get_class($session))
                    ->where('user_id', $userId)
                    ->where('participant_sid', $participantSid)
                    ->where('event_type', 'join')
                    ->whereNull('left_at')
                    ->latest('event_timestamp')
                    ->first();

                if ($joinEvent) {
                    $this->closeJoinEvent($joinEvent, $leftAt, $data['id']);

                    $user = User::find($userId);
                    if ($user) {
                        // Also update MeetingAttendance
                        $this->eventService->recordLeave($session, $user, [
                            'timestamp' => $leftAt,
                            'event_id' => $data['id'],
                            'participant_sid' => $participantSid,
                            'duration_minutes' => $joinEvent->duration_minutes,
                        ]);
                    }
                    \Log::info('[WEBHOOK] Retry successful - leave event processed after join arrived');
                } else {
                    \Log::warning('[WEBHOOK] Retry failed - join event still not found after 5 seconds');
                }

                return;
            }

            // 🔥 NEW: Close the join event with calculated duration
            $this->closeJoinEvent($joinEvent, $leftAt, $data['id']);

            // 🎯 NEW: Update MeetingAttendance record (aggregated state)
            $user = User::find($userId);
            if ($user) {
                $this->eventService->recordLeave($session, $user, [
                    'timestamp' => $leftAt,
                    'event_id' => $data['id'],
                    'participant_sid' => $participantSid,
                    'duration_minutes' => $joinEvent->duration_minutes,
                ]);
            }

            $remainingParticipants = $data['room']['num_participants'] ?? 0;

            // 🐛 Enhanced logging for debugging
            Log::info('✅ [WEBHOOK] LEAVE event processed', [
                'webhook_id' => $data['id'] ?? null,
                'event_db_id' => $joinEvent->id,
                'session_id' => $session->id,
                'session_name' => $session->name ?? 'Unknown',
                'user_id' => $userId,
                'user_name' => $user->full_name ?? 'Unknown',
                'participant_sid' => $participantSid,
                'joined_at' => $joinEvent->event_timestamp->toISOString(),
                'left_at' => $leftAt->toISOString(),
                'duration_minutes' => $joinEvent->duration_minutes,
                'room_name' => $roomName,
                'remaining_participants' => $remainingParticipants,
            ]);

            // Clear any cached attendance status
            \Cache::forget("attendance_status_{$session->id}_{$userId}");

            // Check if room is now empty
            if ($remainingParticipants === 0) {
                $this->handleEmptyRoom($session, $roomName);
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle participant left event', [
                'session_id' => $session->id ?? 'unknown',
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'error' => $e->getMessage(),
            ]);

            // ENHANCEMENT: Queue retry job for failed operation
            if ($userId && $session) {
                $sessionType = $session instanceof \App\Models\AcademicSession ? 'academic' : 'quran';

                \App\Jobs\RetryAttendanceOperation::dispatch(
                    $session->id,
                    $sessionType,
                    $userId,
                    'leave'
                )->delay(now()->addMinutes(1));

                Log::info('Queued attendance leave retry job', [
                    'session_id' => $session->id,
                    'user_id' => $userId,
                ]);
            }
        }
    }

    /**
     * Handle recording started event
     */
    private function handleRecordingStarted(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        if (!$roomName) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        try {
            $session->update([
                'recording_started_at' => now(),
                'is_being_recorded' => true,
            ]);

            Log::info('Session recording started', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'recording_id' => $data['egress']['egress_id'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle recording started event', [
                'session_id' => $session->id ?? 'unknown',
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle recording finished event
     */
    private function handleRecordingFinished(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        if (!$roomName) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        try {
            $downloadUrl = $data['egress']['file']['download_url'] ?? null;
            $fileSize = $data['egress']['file']['size'] ?? 0;
            
            $session->update([
                'recording_ended_at' => now(),
                'is_being_recorded' => false,
                'recording_url' => $downloadUrl,
                'recording_size_bytes' => $fileSize,
            ]);

            Log::info('Session recording finished', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'recording_id' => $data['egress']['egress_id'] ?? 'unknown',
                'download_url' => $downloadUrl,
                'file_size' => $fileSize,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle recording finished event', [
                'session_id' => $session->id ?? 'unknown',
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Close a join event with leave timestamp and calculate duration
     */
    private function closeJoinEvent(\App\Models\MeetingAttendanceEvent $joinEvent, \Carbon\Carbon $leftAt, string $leaveEventId): void
    {
        $durationMinutes = $joinEvent->event_timestamp->diffInMinutes($leftAt);

        $joinEvent->update([
            'left_at' => $leftAt,
            'duration_minutes' => $durationMinutes,
            'leave_event_id' => $leaveEventId,
        ]);

        Log::info('Join event closed', [
            'join_event_id' => $joinEvent->id,
            'joined_at' => $joinEvent->event_timestamp->toISOString(),
            'left_at' => $leftAt->toISOString(),
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Handle empty room scenario
     */
    private function handleEmptyRoom(QuranSession $session, string $roomName): void
    {
        try {
            // Check if session should persist even when empty
            if ($this->sessionMeetingService->shouldSessionPersist($session)) {
                Log::info('Room is empty but session marked as persistent', [
                    'session_id' => $session->id,
                    'room_name' => $roomName,
                ]);
                return;
            }

            // Check if session is scheduled and still active
            $sessionTiming = $this->sessionMeetingService->getSessionTiming($session);
            
            if ($sessionTiming['status'] === 'active' || $sessionTiming['status'] === 'pre_session') {
                Log::info('Room is empty but session is still active, keeping room alive', [
                    'session_id' => $session->id,
                    'room_name' => $roomName,
                    'session_status' => $sessionTiming['status'],
                ]);
                return;
            }

            // Room can be cleaned up
            Log::info('Room is empty and session not active, room may be cleaned up by LiveKit', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'session_status' => $sessionTiming['status'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle empty room', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle track published event - enforce permissions for students
     */
    private function handleTrackPublished(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        $participantIdentity = $data['participant']['identity'] ?? null;
        $track = $data['track'] ?? null;

        if (!$roomName || !$participantIdentity || !$track) {
            Log::warning('Incomplete track_published webhook data', [
                'room' => $roomName,
                'participant' => $participantIdentity,
                'has_track' => isset($track),
            ]);
            return;
        }

        $trackSid = $track['sid'] ?? null;
        $trackType = $track['type'] ?? null; // 'AUDIO' or 'VIDEO'
        $trackSource = $track['source'] ?? null;

        // Get participant metadata to check role
        $metadata = json_decode($data['participant']['metadata'] ?? '{}', true);
        $role = $metadata['role'] ?? '';

        // Only enforce permissions on students
        $isStudent = ($role === 'student') ||
                     (!str_contains($participantIdentity, 'teacher') && !str_contains($participantIdentity, 'admin'));

        if (!$isStudent) {
            Log::debug('Track published by non-student, skipping permission check', [
                'participant' => $participantIdentity,
                'role' => $role,
                'track_type' => $trackType,
            ]);
            return;
        }

        // Check room permissions
        $permissionService = app(\App\Services\RoomPermissionService::class);
        $permissions = $permissionService->getRoomPermissions($roomName);

        $shouldMute = false;
        $reason = null;

        // Check if track type is allowed
        if ($trackType === 'AUDIO' && !($permissions['microphone_allowed'] ?? true)) {
            $shouldMute = true;
            $reason = 'Microphone not allowed by teacher';
        } elseif ($trackType === 'VIDEO' && !($permissions['camera_allowed'] ?? true)) {
            $shouldMute = true;
            $reason = 'Camera not allowed by teacher';
        }

        if ($shouldMute) {
            Log::info('🚫 Enforcing permission: Muting student track', [
                'room' => $roomName,
                'participant' => $participantIdentity,
                'track_sid' => $trackSid,
                'track_type' => $trackType,
                'reason' => $reason,
            ]);

            // Immediately mute the track server-side
            try {
                $roomService = new \Agence104\LiveKit\RoomServiceClient(
                    config('livekit.api_url'),
                    config('livekit.api_key'),
                    config('livekit.api_secret')
                );

                $roomService->mutePublishedTrack(
                    $roomName,
                    $participantIdentity,
                    $trackSid,
                    true
                );

                Log::info('✅ Student track muted successfully by permission enforcement', [
                    'participant' => $participantIdentity,
                    'track_type' => $trackType,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to enforce permission by muting track', [
                    'participant' => $participantIdentity,
                    'track_sid' => $trackSid,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::debug('Track published and allowed', [
                'room' => $roomName,
                'participant' => $participantIdentity,
                'track_type' => $trackType,
                'mic_allowed' => $permissions['microphone_allowed'] ?? true,
                'camera_allowed' => $permissions['camera_allowed'] ?? true,
            ]);
        }
    }

    /**
     * Find session by room name
     */
    private function findSessionByRoomName(string $roomName): ?QuranSession
    {
        return QuranSession::where('meeting_room_name', $roomName)->first();
    }

    /**
     * Extract user ID from LiveKit participant identity
     */
    private function extractUserIdFromIdentity(string $identity): ?int
    {
        // Identity format is usually "userId_firstName_lastName"
        $parts = explode('_', $identity);

        if (count($parts) > 0 && is_numeric($parts[0])) {
            return (int) $parts[0];
        }

        return null;
    }

    /**
     * 🔥 NEW: Validate webhook using LiveKit's JWT-based verification
     * Based on: https://docs.livekit.io/home/server/webhooks
     */
    private function validateWebhookSignature(Request $request): bool
    {
        // Allow webhooks in development mode without strict validation
        if (app()->environment('local', 'development')) {
            Log::debug('Development mode - skipping webhook validation');
            return true;
        }

        $apiKey = config('livekit.api_key');
        $apiSecret = config('livekit.api_secret');

        if (! $apiKey || ! $apiSecret) {
            Log::warning('LiveKit API credentials not configured - allowing webhook anyway');
            return true;
        }

        // Get Authorization header (JWT token)
        $authHeader = $request->header('Authorization');
        if (! $authHeader) {
            Log::error('LiveKit webhook missing Authorization header');
            return false;
        }

        // Remove "Bearer " prefix if present
        $token = str_starts_with($authHeader, 'Bearer ')
            ? substr($authHeader, 7)
            : $authHeader;

        try {
            // Decode JWT token without verification first to get claims
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                Log::error('Invalid JWT token format');
                return false;
            }

            // Decode header and payload
            $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (! $payload || ! isset($payload['sha256'])) {
                Log::error('JWT payload missing sha256 claim');
                return false;
            }

            // Verify JWT signature
            $signature = $parts[2];
            $dataToSign = $parts[0] . '.' . $parts[1];
            $expectedSignature = rtrim(strtr(base64_encode(hash_hmac('sha256', $dataToSign, $apiSecret, true)), '+/', '-_'), '=');

            if (! hash_equals($expectedSignature, $signature)) {
                Log::error('JWT signature verification failed');
                return false;
            }

            // Verify token expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Log::error('JWT token expired');
                return false;
            }

            // Verify issuer matches API key
            if (isset($payload['iss']) && $payload['iss'] !== $apiKey) {
                Log::warning('JWT issuer does not match API key', [
                    'expected' => $apiKey,
                    'received' => $payload['iss'],
                ]);
            }

            // Verify request body hash
            $body = $request->getContent();
            $bodyHash = hash('sha256', $body, true);
            $claimsHash = base64_decode($payload['sha256']);

            if (! hash_equals($bodyHash, $claimsHash)) {
                Log::error('Request body hash mismatch');
                return false;
            }

            Log::info('✅ LiveKit webhook signature verified successfully');
            return true;

        } catch (\Exception $e) {
            Log::error('Error validating LiveKit webhook signature', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}