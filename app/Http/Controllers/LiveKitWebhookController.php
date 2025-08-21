<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\QuranSession;
use App\Services\SessionMeetingService;
use App\Enums\SessionStatus;

class LiveKitWebhookController extends Controller
{
    private SessionMeetingService $sessionMeetingService;

    public function __construct(SessionMeetingService $sessionMeetingService)
    {
        $this->sessionMeetingService = $sessionMeetingService;
    }

    /**
     * Handle webhooks from LiveKit server
     */
    public function handleWebhook(Request $request): Response
    {
        try {
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
            // Update session status to ongoing
            $session->update([
                'status' => SessionStatus::ONGOING,
                'meeting_started_at' => now(),
            ]);

            // Ensure session persistence
            $this->sessionMeetingService->markSessionPersistent($session);

            Log::info('Session meeting started', [
                'session_id' => $session->id,
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
     */
    private function handleParticipantJoined(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        $participantIdentity = $data['participant']['identity'] ?? null;
        
        if (!$roomName || !$participantIdentity) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        try {
            // Extract user ID from participant identity
            $userId = $this->extractUserIdFromIdentity($participantIdentity);
            
            Log::info('Participant joined session', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'user_id' => $userId,
                'participant_count' => $data['room']['num_participants'] ?? 0,
            ]);

            // TODO: Could implement attendance tracking here
            // $this->trackAttendance($session, $userId, 'joined');

        } catch (\Exception $e) {
            Log::error('Failed to handle participant joined event', [
                'session_id' => $session->id ?? 'unknown',
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle participant left event
     */
    private function handleParticipantLeft(array $data): void
    {
        $roomName = $data['room']['name'] ?? null;
        $participantIdentity = $data['participant']['identity'] ?? null;
        
        if (!$roomName || !$participantIdentity) return;

        $session = $this->findSessionByRoomName($roomName);
        if (!$session) return;

        try {
            $userId = $this->extractUserIdFromIdentity($participantIdentity);
            $remainingParticipants = $data['room']['num_participants'] ?? 0;
            
            Log::info('Participant left session', [
                'session_id' => $session->id,
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'user_id' => $userId,
                'remaining_participants' => $remainingParticipants,
            ]);

            // Check if room is now empty
            if ($remainingParticipants === 0) {
                $this->handleEmptyRoom($session, $roomName);
            }

            // TODO: Could implement attendance tracking here
            // $this->trackAttendance($session, $userId, 'left');

        } catch (\Exception $e) {
            Log::error('Failed to handle participant left event', [
                'session_id' => $session->id ?? 'unknown',
                'room_name' => $roomName,
                'participant_identity' => $participantIdentity,
                'error' => $e->getMessage(),
            ]);
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
}