<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\QuranSession;
use App\Services\LiveKitService;

class LiveKitWebhookController extends Controller
{
    private LiveKitService $livekitService;

    public function __construct(LiveKitService $livekitService)
    {
        $this->livekitService = $livekitService;
    }

    /**
     * Handle incoming LiveKit webhooks
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature if configured
            if (config('livekit.webhooks.secret')) {
                if (!$this->verifyWebhookSignature($request)) {
                    Log::warning('Invalid LiveKit webhook signature', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
            }

            $payload = $request->json()->all();
            
            Log::info('LiveKit webhook received', [
                'event' => $payload['event'] ?? 'unknown',
                'room' => $payload['room']['name'] ?? 'unknown',
                'payload_keys' => array_keys($payload),
            ]);

            // Process the webhook
            $this->livekitService->handleWebhook($payload);

            // Handle specific events that affect our session management
            $this->handleSessionEvents($payload);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Failed to process LiveKit webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->json()->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle events that affect our session management
     */
    private function handleSessionEvents(array $payload): void
    {
        $event = $payload['event'] ?? '';
        $roomName = $payload['room']['name'] ?? '';

        if (empty($roomName)) {
            return;
        }

        // Find the session associated with this room
        $session = QuranSession::where('meeting_room_name', $roomName)->first();

        if (!$session) {
            Log::warning('Received webhook for unknown room', [
                'room_name' => $roomName,
                'event' => $event,
            ]);
            return;
        }

        switch ($event) {
            case 'room_started':
                $this->handleRoomStarted($session, $payload);
                break;

            case 'room_finished':
                $this->handleRoomFinished($session, $payload);
                break;

            case 'participant_joined':
                $this->handleParticipantJoined($session, $payload);
                break;

            case 'participant_left':
                $this->handleParticipantLeft($session, $payload);
                break;

            case 'recording_started':
                $this->handleRecordingStarted($session, $payload);
                break;

            case 'recording_finished':
                $this->handleRecordingFinished($session, $payload);
                break;

            case 'egress_ended':
                $this->handleEgressEnded($session, $payload);
                break;

            default:
                Log::debug('Unhandled LiveKit webhook event', [
                    'event' => $event,
                    'room_name' => $roomName,
                    'session_id' => $session->id,
                ]);
        }
    }

    /**
     * Handle room started event
     */
    private function handleRoomStarted(QuranSession $session, array $payload): void
    {
        Log::info('LiveKit room started', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
        ]);

        $session->update([
            'started_at' => now(),
            'status' => 'ongoing',
        ]);

        // You can add additional logic here:
        // - Send notifications to participants
        // - Update attendance tracking
        // - Start automatic recording if configured
    }

    /**
     * Handle room finished event
     */
    private function handleRoomFinished(QuranSession $session, array $payload): void
    {
        $duration = $payload['room']['duration'] ?? null;
        
        Log::info('LiveKit room finished', [
            'session_id' => $session->id,
            'room_name' => $session->meeting_room_name,
            'duration' => $duration,
        ]);

        $updateData = [
            'ended_at' => now(),
            'status' => 'completed',
        ];

        // Calculate actual duration if provided
        if ($duration) {
            $updateData['actual_duration_minutes'] = ceil($duration / 60);
        } elseif ($session->started_at) {
            $updateData['actual_duration_minutes'] = $session->started_at->diffInMinutes(now());
        }

        $session->update($updateData);

        // You can add additional logic here:
        // - Generate session summary
        // - Send completion notifications
        // - Update attendance records
        // - Process recordings
    }

    /**
     * Handle participant joined event
     */
    private function handleParticipantJoined(QuranSession $session, array $payload): void
    {
        $participant = $payload['participant'] ?? [];
        $identity = $participant['identity'] ?? 'unknown';

        Log::info('Participant joined LiveKit room', [
            'session_id' => $session->id,
            'participant_identity' => $identity,
            'room_name' => $session->meeting_room_name,
        ]);

        // Update participant count
        $session->increment('participants_count');

        // You can add additional logic here:
        // - Track attendance
        // - Send welcome messages
        // - Update UI for other participants
        // - Log participation for analytics
    }

    /**
     * Handle participant left event
     */
    private function handleParticipantLeft(QuranSession $session, array $payload): void
    {
        $participant = $payload['participant'] ?? [];
        $identity = $participant['identity'] ?? 'unknown';

        Log::info('Participant left LiveKit room', [
            'session_id' => $session->id,
            'participant_identity' => $identity,
            'room_name' => $session->meeting_room_name,
        ]);

        // Update participant count
        $session->decrement('participants_count');

        // You can add additional logic here:
        // - Update attendance records
        // - Handle early departures
        // - Send notifications if teacher leaves
    }

    /**
     * Handle recording started event
     */
    private function handleRecordingStarted(QuranSession $session, array $payload): void
    {
        $recordingId = $payload['egress_info']['egress_id'] ?? null;

        Log::info('Recording started for LiveKit room', [
            'session_id' => $session->id,
            'recording_id' => $recordingId,
            'room_name' => $session->meeting_room_name,
        ]);

        // Update session with recording info
        $meetingData = $session->meeting_data ?? [];
        $meetingData['recording'] = [
            'recording_id' => $recordingId,
            'status' => 'recording',
            'started_at' => now(),
        ];

        $session->update([
            'meeting_data' => $meetingData,
            'recording_enabled' => true,
        ]);
    }

    /**
     * Handle recording finished event
     */
    private function handleRecordingFinished(QuranSession $session, array $payload): void
    {
        $recordingId = $payload['egress_info']['egress_id'] ?? null;

        Log::info('Recording finished for LiveKit room', [
            'session_id' => $session->id,
            'recording_id' => $recordingId,
            'room_name' => $session->meeting_room_name,
        ]);

        // Update recording status
        $meetingData = $session->meeting_data ?? [];
        if (isset($meetingData['recording'])) {
            $meetingData['recording']['status'] = 'processing';
            $meetingData['recording']['finished_at'] = now();
            
            $session->update(['meeting_data' => $meetingData]);
        }
    }

    /**
     * Handle egress (recording/streaming) ended event
     */
    private function handleEgressEnded(QuranSession $session, array $payload): void
    {
        $egressInfo = $payload['egress_info'] ?? [];
        $recordingId = $egressInfo['egress_id'] ?? null;
        $fileOutputs = $egressInfo['file_results'] ?? [];

        Log::info('Egress ended for LiveKit room', [
            'session_id' => $session->id,
            'recording_id' => $recordingId,
            'file_count' => count($fileOutputs),
            'room_name' => $session->meeting_room_name,
        ]);

        // Update session with final recording file information
        $meetingData = $session->meeting_data ?? [];
        
        if (isset($meetingData['recording'])) {
            $meetingData['recording']['status'] = 'completed';
            $meetingData['recording']['completed_at'] = now();
            $meetingData['recording']['files'] = $fileOutputs;

            // Set the recording URL to the first file output
            $recordingUrl = null;
            if (!empty($fileOutputs)) {
                $recordingUrl = $fileOutputs[0]['download_url'] ?? $fileOutputs[0]['filename'] ?? null;
            }

            $session->update([
                'recording_url' => $recordingUrl,
                'meeting_data' => $meetingData,
            ]);

            // You can add additional logic here:
            // - Process the recording file
            // - Generate thumbnails
            // - Send notifications about available recording
            // - Update storage statistics
        }
    }

    /**
     * Verify webhook signature for security
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('livekit-signature');
        $secret = config('livekit.webhooks.secret');

        if (!$signature || !$secret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Health check endpoint for LiveKit webhooks
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'livekit-webhook',
            'timestamp' => now()->toISOString(),
        ]);
    }
}