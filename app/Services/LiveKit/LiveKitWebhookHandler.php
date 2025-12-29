<?php

namespace App\Services\LiveKit;

use Illuminate\Support\Facades\Log;

class LiveKitWebhookHandler
{
    private ?string $webhookSecret;

    public function __construct()
    {
        $this->webhookSecret = config('livekit.webhook_secret', '');
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('LiveKit webhook secret not configured');

            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse webhook event from payload
     */
    public function parseEvent(string $payload): ?array
    {
        try {
            $data = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse webhook payload', [
                    'error' => json_last_error_msg(),
                ]);

                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Exception parsing webhook payload', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Handle webhook event and dispatch to appropriate handler
     */
    public function handleEvent(array $webhookData): void
    {
        $event = $webhookData['event'] ?? '';

        Log::info('LiveKit webhook received', [
            'event' => $event,
            'data' => $webhookData,
        ]);

        switch ($event) {
            case 'room_started':
                $this->handleRoomStarted($webhookData);
                break;

            case 'room_finished':
                $this->handleRoomFinished($webhookData);
                break;

            case 'participant_joined':
                $this->handleParticipantJoined($webhookData);
                break;

            case 'participant_left':
                $this->handleParticipantLeft($webhookData);
                break;

            case 'recording_started':
                $this->handleRecordingStarted($webhookData);
                break;

            case 'recording_finished':
                $this->handleRecordingFinished($webhookData);
                break;

            case 'egress_started':
                $this->handleEgressStarted($webhookData);
                break;

            case 'egress_updated':
                $this->handleEgressUpdated($webhookData);
                break;

            case 'egress_ended':
                $this->handleEgressEnded($webhookData);
                break;

            default:
                Log::warning('Unknown LiveKit webhook event', [
                    'event' => $event,
                ]);
        }
    }

    /**
     * Handle room started event
     */
    protected function handleRoomStarted(array $data): void
    {
        Log::info('LiveKit room started', [
            'room_name' => $data['room']['name'] ?? null,
            'room_sid' => $data['room']['sid'] ?? null,
        ]);

        // Dispatch event or trigger service to update session status
        // This can be extended to fire Laravel events
    }

    /**
     * Handle room finished event
     */
    protected function handleRoomFinished(array $data): void
    {
        Log::info('LiveKit room finished', [
            'room_name' => $data['room']['name'] ?? null,
            'room_sid' => $data['room']['sid'] ?? null,
            'duration' => $data['room']['duration'] ?? null,
        ]);

        // Update session as completed, calculate duration, etc.
    }

    /**
     * Handle participant joined event
     */
    protected function handleParticipantJoined(array $data): void
    {
        Log::info('LiveKit participant joined', [
            'room_name' => $data['room']['name'] ?? null,
            'participant_identity' => $data['participant']['identity'] ?? null,
            'participant_name' => $data['participant']['name'] ?? null,
        ]);

        // Track attendance, send notifications, etc.
    }

    /**
     * Handle participant left event
     */
    protected function handleParticipantLeft(array $data): void
    {
        Log::info('LiveKit participant left', [
            'room_name' => $data['room']['name'] ?? null,
            'participant_identity' => $data['participant']['identity'] ?? null,
            'duration' => $data['participant']['duration'] ?? null,
        ]);

        // Update attendance records, handle early departures, etc.
    }

    /**
     * Handle recording started event
     */
    protected function handleRecordingStarted(array $data): void
    {
        Log::info('LiveKit recording started', [
            'room_name' => $data['room']['name'] ?? null,
            'recording_id' => $data['recording_id'] ?? null,
        ]);

        // Update database with recording status
    }

    /**
     * Handle recording finished event
     */
    protected function handleRecordingFinished(array $data): void
    {
        Log::info('LiveKit recording finished', [
            'room_name' => $data['room']['name'] ?? null,
            'recording_id' => $data['recording_id'] ?? null,
            'file_path' => $data['file_path'] ?? null,
        ]);

        // Process completed recordings, save to database, notify users, etc.
    }

    /**
     * Handle egress started event
     */
    protected function handleEgressStarted(array $data): void
    {
        Log::info('LiveKit egress started', [
            'egress_id' => $data['egress_id'] ?? null,
            'room_name' => $data['room_name'] ?? null,
        ]);

        // Update recording status in database
    }

    /**
     * Handle egress updated event
     */
    protected function handleEgressUpdated(array $data): void
    {
        Log::info('LiveKit egress updated', [
            'egress_id' => $data['egress_id'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        // Update recording progress
    }

    /**
     * Handle egress ended event
     */
    protected function handleEgressEnded(array $data): void
    {
        Log::info('LiveKit egress ended', [
            'egress_id' => $data['egress_id'] ?? null,
            'status' => $data['status'] ?? null,
            'error' => $data['error'] ?? null,
            'file_path' => $data['file_path'] ?? null,
        ]);

        // Finalize recording, update database, notify users
    }

    /**
     * Get webhook secret
     */
    public function getWebhookSecret(): ?string
    {
        return $this->webhookSecret;
    }

    /**
     * Check if webhook handler is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->webhookSecret);
    }
}
