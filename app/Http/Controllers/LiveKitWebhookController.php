<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Exceptions\WebhookValidationException;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AttendanceEventService;
use App\Services\MeetingAttendanceService;
use App\Services\RecordingService;
use App\Services\RoomPermissionService;
use App\Services\SessionMeetingService;
use App\Services\UnifiedSessionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LiveKitWebhookController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected SessionMeetingService $sessionMeetingService,
        protected MeetingAttendanceService $attendanceService,
        protected UnifiedSessionStatusService $statusService,
        protected AttendanceEventService $eventService,
        protected RecordingService $recordingService,
        protected RoomPermissionService $roomPermissionService
    ) {}

    /**
     * Handle webhooks from LiveKit server
     */
    public function handleWebhook(Request $request): Response
    {
        Log::debug('LiveKit webhook endpoint request received', [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'has_event' => $request->has('event'),
        ]);

        try {
            // Validate webhook signature (throws WebhookValidationException on failure)
            $this->validateWebhookSignature($request);

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

                case 'egress_ended':
                    $this->handleEgressEnded($data);
                    break;

                default:
                    Log::info('Unhandled LiveKit webhook event', ['event' => $event]);
            }

            return response('OK', 200);

        } catch (WebhookValidationException $e) {
            $e->report();

            return response($e->getMessage(), 401);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error handling LiveKit webhook', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'event' => $request->input('event'),
            ]);

            return response('Database error', 500);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Invalid webhook data from LiveKit', [
                'error' => $e->getMessage(),
                'event' => $request->input('event'),
            ]);

            return response('Invalid data', 400);
        } catch (\Throwable $e) {
            Log::error('Unexpected error handling LiveKit webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
            ]);
            report($e);

            return response('Error processing webhook', 500);
        }
    }

    /**
     * Health check endpoint for LiveKit webhooks
     */
    public function health(): JsonResponse
    {
        return $this->success([
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

        if (! $roomName) {
            Log::warning('Room started event missing room name');

            return;
        }

        $session = $this->findSessionByRoomName($roomName);

        if (! $session) {
            return;
        }

        try {
            // Handle status transition based on current status
            if ($session->status === SessionStatus::READY) {
                // Transition from READY to ONGOING
                $session->update([
                    'status' => SessionStatus::ONGOING,
                    'started_at' => now(),
                ]);

                // Ensure session persistence
                $this->sessionMeetingService->markSessionPersistent($session);

                Log::info('Session meeting started', [
                    'session_id' => $session->id,
                    'session_type' => $session->session_type ?? get_class($session),
                    'room_name' => $roomName,
                    'started_at' => now(),
                ]);
            } elseif ($session->status === SessionStatus::ONGOING) {
                // Session already ONGOING - just log and continue to auto-recording
                Log::info('Room started for already ONGOING session', [
                    'session_id' => $session->id,
                    'session_type' => $session->session_type ?? get_class($session),
                    'room_name' => $roomName,
                ]);
            } else {
                // Session not READY or ONGOING - skip all processing
                Log::warning('Room started but session not READY/ONGOING - skipping', [
                    'session_id' => $session->id,
                    'session_type' => $session->session_type ?? get_class($session),
                    'current_status' => $session->status->value,
                    'room_name' => $roomName,
                    'scheduled_at' => $session->scheduled_at,
                    'current_time' => now(),
                ]);

                return;
            }

            // NOTE: Auto-recording is now triggered on participant_joined, not room_started
            // This ensures recording starts only when someone actually joins AND session time has started

        } catch (\Exception $e) {
            Log::error('Failed to handle room started event', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Try to start auto-recording if session supports it and has it enabled
     */
    private function tryStartAutoRecording(\App\Models\BaseSession $session, string $roomName): void
    {
        try {
            // Check if session implements RecordingCapable interface
            if (! ($session instanceof \App\Contracts\RecordingCapable)) {
                Log::debug('Auto-recording skipped: Session does not implement RecordingCapable', [
                    'session_id' => $session->id,
                ]);

                return;
            }

            // Check if session's scheduled time has started
            if ($session->scheduled_at && now()->lt($session->scheduled_at)) {
                Log::debug('Auto-recording skipped: Session scheduled time not yet reached', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at,
                ]);

                return;
            }

            // Check if recording is enabled for this session
            if (! $session->isRecordingEnabled()) {
                Log::debug('Auto-recording skipped: Recording not enabled for this session', [
                    'session_id' => $session->id,
                ]);

                return;
            }

            // Check if session can be recorded (status, permissions, etc.)
            if (! $session->canBeRecorded()) {
                Log::debug('Auto-recording skipped: Session cannot be recorded at this time', [
                    'session_id' => $session->id,
                ]);

                return;
            }

            // Check if already recording
            if ($session->isRecording()) {
                Log::debug('Auto-recording skipped: Session is already being recorded', [
                    'session_id' => $session->id,
                ]);

                return;
            }

            // Start auto-recording
            $recording = $this->recordingService->startRecording($session);

            Log::info('Auto-recording started successfully', [
                'session_id' => $session->id,
                'recording_id' => $recording->id,
                'egress_id' => $recording->recording_id,
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the webhook - recording is optional
            Log::error('Failed to start auto-recording', [
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
        if (! $roomName) {
            return;
        }

        $session = $this->findSessionByRoomName($roomName);
        if (! $session) {
            return;
        }

        try {
            $participantCount = $data['room']['num_participants'] ?? 0;
            $duration = $data['room']['duration_seconds'] ?? 0;

            // Only mark as completed if the session was actually used
            if ($duration > 60) { // At least 1 minute of activity
                $session->update([
                    'status' => SessionStatus::COMPLETED,
                    'ended_at' => now(),
                    'actual_duration_minutes' => round($duration / 60),
                ]);
            } else {
                // Very short session, just mark as ended
                $session->update([
                    'ended_at' => now(),
                ]);
            }

            // Remove persistence since room is finished
            $this->sessionMeetingService->removeSessionPersistence($session);

            // Stop any active recording when room closes
            if ($session instanceof \App\Contracts\RecordingCapable && $session->isRecording()) {
                try {
                    $session->stopRecording();
                    Log::info('Recording stopped on room finished', ['session_id' => $session->id]);
                } catch (\Exception $e) {
                    Log::error('Failed to stop recording on room finished', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

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

        if (! $roomName || ! $participantIdentity) {
            return;
        }

        $session = $this->findSessionByRoomName($roomName);
        if (! $session) {
            return;
        }

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

            if (! $userId) {
                Log::warning('Could not extract user ID from participant identity', [
                    'participant_identity' => $participantIdentity,
                ]);

                return;
            }

            $user = User::find($userId);
            if (! $user) {
                Log::warning('User not found', ['user_id' => $userId]);

                return;
            }

            // Use LiveKit's exact join timestamp (source of truth)
            // LiveKit sends 'joinedAt' in camelCase, not 'joined_at'
            // Always use UTC for storage, convert to academy timezone only for display
            $joinedAt = isset($participantData['joinedAt'])
                ? \Carbon\Carbon::createFromTimestamp($participantData['joinedAt'], 'UTC')
                : now('UTC');

            // Create immutable event log entry
            $event = \App\Models\MeetingAttendanceEvent::create([
                'event_id' => $data['id'],
                'event_type' => 'join',
                'event_timestamp' => $joinedAt,
                'session_id' => $session->id,
                'session_type' => get_class($session),
                'user_id' => $user->id,
                'academy_id' => $session->academy_id ?? null,
                'participant_sid' => $participantData['sid'] ?? null,
                'participant_identity' => $participantIdentity,
                'participant_name' => $participantData['name'] ?? $user->full_name,
                'raw_webhook_data' => $data,
            ]);

            // Update MeetingAttendance record (aggregated state)
            $this->eventService->recordJoin($session, $user, [
                'timestamp' => $joinedAt,
                'event_id' => $data['id'],
                'participant_sid' => $participantData['sid'] ?? null,
            ]);

            Log::info('Participant join event processed', [
                'session_id' => $session->id,
                'user_id' => $userId,
                'participant_sid' => $participantData['sid'] ?? null,
            ]);

            // Clear any cached attendance status
            \Cache::forget("attendance_status_{$session->id}_{$userId}");

            // Try to start recording when first participant joins
            $participantCount = $data['room']['num_participants'] ?? 0;
            if ($participantCount == 1) {
                $this->tryStartAutoRecording($session, $roomName);
            }

        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate webhook - safely ignore
            Log::info('Duplicate join webhook ignored', [
                'event_id' => $data['id'],
                'session_id' => $session->id,
                'user_id' => $userId,
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

        if (! $roomName || ! $participantIdentity) {
            return;
        }

        $session = $this->findSessionByRoomName($roomName);
        if (! $session) {
            return;
        }

        // 🔧 FIX: Set tenant context for multi-tenancy support in queued jobs
        if (isset($session->tenant_id)) {
            $tenant = \App\Models\Academy::find($session->tenant_id);
            if ($tenant) {
                $tenant->makeCurrent();
            }
        }

        try {
            $userId = $this->extractUserIdFromIdentity($participantIdentity);

            if (! $userId) {
                Log::warning('Could not extract user ID from participant identity', [
                    'participant_identity' => $participantIdentity,
                ]);

                return;
            }

            // Use webhook creation time as leave timestamp (source of truth)
            // Always use UTC for storage, convert to academy timezone only for display
            $leftAt = isset($data['createdAt'])
                ? \Carbon\Carbon::createFromTimestamp($data['createdAt'], 'UTC')
                : now('UTC');

            // Find the matching join event by participant_sid
            $joinEvent = \App\Models\MeetingAttendanceEvent::where('session_id', $session->id)
                ->where('session_type', get_class($session))
                ->where('user_id', $userId)
                ->where('participant_sid', $participantSid)
                ->where('event_type', 'join')
                ->whereNull('left_at')
                ->latest('event_timestamp')
                ->first();

            if (! $joinEvent) {
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

            // Close the join event with calculated duration
            $this->closeJoinEvent($joinEvent, $leftAt, $data['id']);

            // Update MeetingAttendance record (aggregated state)
            $user = User::find($userId);
            if ($user) {
                $this->eventService->recordLeave($session, $user, [
                    'timestamp' => $leftAt,
                    'event_id' => $data['id'],
                    'participant_sid' => $participantSid,
                    'duration_minutes' => $joinEvent->duration_minutes,
                ]);
            }

            Log::info('Participant leave event processed', [
                'session_id' => $session->id,
                'user_id' => $userId,
                'duration_minutes' => $joinEvent->duration_minutes,
            ]);

            // Clear any cached attendance status
            \Cache::forget("attendance_status_{$session->id}_{$userId}");

            // Check if room is now empty
            $remainingParticipants = $data['room']['num_participants'] ?? 0;
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
        if (! $roomName) {
            return;
        }

        $session = $this->findSessionByRoomName($roomName);
        if (! $session) {
            return;
        }

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
        if (! $roomName) {
            return;
        }

        $session = $this->findSessionByRoomName($roomName);
        if (! $session) {
            return;
        }

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
    private function handleEmptyRoom(\App\Models\BaseSession $session, string $roomName): void
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

        if (! $roomName || ! $participantIdentity || ! $track) {
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
                     (! str_contains($participantIdentity, 'teacher') && ! str_contains($participantIdentity, 'admin'));

        if (! $isStudent) {
            Log::debug('Track published by non-student, skipping permission check', [
                'participant' => $participantIdentity,
                'role' => $role,
                'track_type' => $trackType,
            ]);

            return;
        }

        // Check room permissions
        $permissionService = $this->roomPermissionService;
        $permissions = $permissionService->getRoomPermissions($roomName);

        $shouldMute = false;
        $reason = null;

        // Check if track type is allowed
        if ($trackType === 'AUDIO' && ! ($permissions['microphone_allowed'] ?? true)) {
            $shouldMute = true;
            $reason = 'Microphone not allowed by teacher';
        } elseif ($trackType === 'VIDEO' && ! ($permissions['camera_allowed'] ?? true)) {
            $shouldMute = true;
            $reason = 'Camera not allowed by teacher';
        }

        if ($shouldMute) {
            Log::info('Enforcing permission: Muting student track', [
                'participant' => $participantIdentity,
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

                Log::info('Student track muted successfully', [
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
     * Find session by room name across all session types
     *
     * @param  string  $roomName  LiveKit room name
     * @return \App\Models\BaseSession|null The session model (QuranSession, InteractiveCourseSession, or AcademicSession)
     */
    private function findSessionByRoomName(string $roomName): ?\App\Models\BaseSession
    {
        // Search QuranSession first (most common)
        $session = QuranSession::where('meeting_room_name', $roomName)->first();
        if ($session) {
            return $session;
        }

        // Search InteractiveCourseSession
        $session = \App\Models\InteractiveCourseSession::where('meeting_room_name', $roomName)->first();
        if ($session) {
            return $session;
        }

        // Search AcademicSession
        $session = \App\Models\AcademicSession::where('meeting_room_name', $roomName)->first();
        if ($session) {
            return $session;
        }

        Log::warning('Session not found for room name', ['room_name' => $roomName]);

        return null;
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
     * Validate webhook using LiveKit's JWT-based verification.
     * Based on: https://docs.livekit.io/home/server/webhooks
     *
     * @throws WebhookValidationException When signature validation fails
     */
    private function validateWebhookSignature(Request $request): void
    {
        // Allow webhooks in development mode without strict validation
        if (app()->environment('local', 'development')) {
            Log::debug('Development mode - skipping webhook validation');

            return;
        }

        $apiKey = config('livekit.api_key');
        $apiSecret = config('livekit.api_secret');

        if (! $apiKey || ! $apiSecret) {
            Log::warning('LiveKit API credentials not configured - allowing webhook anyway');

            return;
        }

        // Get Authorization header (JWT token)
        $authHeader = $request->header('Authorization');
        if (! $authHeader) {
            throw WebhookValidationException::missingFields(
                'livekit',
                ['Authorization header'],
                $request->all()
            );
        }

        // Remove "Bearer " prefix if present
        $token = str_starts_with($authHeader, 'Bearer ')
            ? substr($authHeader, 7)
            : $authHeader;

        try {
            // Decode JWT token without verification first to get claims
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw WebhookValidationException::invalidFormat(
                    'livekit',
                    'Invalid JWT token format - expected 3 parts',
                    $request->all()
                );
            }

            // Decode header and payload
            $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (! $payload || ! isset($payload['sha256'])) {
                throw WebhookValidationException::missingFields(
                    'livekit',
                    ['sha256 claim in JWT payload'],
                    $request->all()
                );
            }

            // Verify JWT signature
            $signature = $parts[2];
            $dataToSign = $parts[0].'.'.$parts[1];
            $expectedSignature = rtrim(strtr(base64_encode(hash_hmac('sha256', $dataToSign, $apiSecret, true)), '+/', '-_'), '=');

            if (! hash_equals($expectedSignature, $signature)) {
                throw WebhookValidationException::invalidSignature(
                    'livekit',
                    substr($signature, 0, 20).'...',
                    $request->all()
                );
            }

            // Verify token expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw WebhookValidationException::expired(
                    'livekit',
                    date('Y-m-d H:i:s', $payload['exp']),
                    $request->all()
                );
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
                throw WebhookValidationException::invalidSignature(
                    'livekit',
                    'body_hash_mismatch',
                    $request->all()
                );
            }

            Log::debug('LiveKit webhook signature verified successfully');

        } catch (WebhookValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error validating LiveKit webhook signature', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw WebhookValidationException::invalidFormat(
                'livekit',
                'JWT validation error: '.$e->getMessage(),
                $request->all()
            );
        }
    }

    /**
     * Handle LiveKit Egress ended event (recording completed/failed)
     *
     * @param  array  $data  Webhook payload
     */
    private function handleEgressEnded(array $data): void
    {
        try {
            Log::info('Processing egress_ended webhook', [
                'egress_id' => $data['egressInfo']['egressId'] ?? null,
                'status' => $data['egressInfo']['status'] ?? null,
            ]);

            // Delegate to RecordingService for processing
            $this->recordingService->processEgressWebhook($data);

        } catch (\Exception $e) {
            Log::error('Failed to handle egress_ended webhook', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }
}
