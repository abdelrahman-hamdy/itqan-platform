<?php

namespace App\Services\LiveKit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveKitRecordingManager
{
    private ?string $apiUrl;

    private LiveKitTokenGenerator $tokenGenerator;

    public function __construct(LiveKitTokenGenerator $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
        $serverUrl = config('livekit.server_url', 'wss://localhost');
        $this->apiUrl = config('livekit.api_url', str_replace('wss://', 'http://', $serverUrl));
    }

    /**
     * Check if recording manager is properly configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiUrl) && $this->tokenGenerator->isConfigured();
    }

    /**
     * Start recording for a room using LiveKit Egress
     */
    public function startRecording(string $roomName, array $options = []): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit recording manager not configured properly');
            }

            // Build file path for local storage on LiveKit server
            $filename = $options['filename'] ?? sprintf('recording-%s-%s', $roomName, now()->timestamp);
            $filepath = sprintf(
                '%s/%s.mp4',
                rtrim($options['storage_path'] ?? '/recordings', '/'),
                $filename
            );

            // Prepare Egress request payload for room composite recording
            $payload = [
                'room_name' => $roomName,
                'file' => [
                    'filepath' => $filepath,
                ],
                'options' => [
                    'preset' => $options['preset'] ?? 'HD',
                    'layout' => $options['layout'] ?? 'grid',
                ],
            ];

            // Add metadata if provided
            if (! empty($options['metadata'])) {
                $payload['metadata'] = json_encode($options['metadata']);
            }

            Log::info('Starting LiveKit Egress recording', [
                'room_name' => $roomName,
                'filepath' => $filepath,
                'api_url' => $this->apiUrl,
            ]);

            // Generate token for Egress API
            $token = $this->tokenGenerator->generateEgressToken();

            // Call LiveKit Egress Twirp API (StartRoomCompositeEgress)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/StartRoomCompositeEgress', $payload);

            if (! $response->successful()) {
                throw new \Exception('Egress API error: '.$response->body());
            }

            $responseData = $response->json();

            Log::info('Recording started successfully', [
                'room_name' => $roomName,
                'egress_id' => $responseData['egressId'] ?? null,
            ]);

            return [
                'egress_id' => $responseData['egressId'] ?? $responseData['egress_id'] ?? null,
                'room_name' => $roomName,
                'filepath' => $filepath,
                'response' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to start recording', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
                'options' => $options,
            ]);

            throw new \Exception('Failed to start recording: '.$e->getMessage());
        }
    }

    /**
     * Stop an active recording
     */
    public function stopRecording(string $egressId): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit recording manager not configured properly');
            }

            Log::info('Stopping LiveKit Egress recording', [
                'egress_id' => $egressId,
                'api_url' => $this->apiUrl,
            ]);

            // Generate token for Egress API
            $token = $this->tokenGenerator->generateEgressToken();

            // Call LiveKit Egress Twirp API (StopEgress)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/StopEgress', [
                'egress_id' => $egressId,
            ]);

            if (! $response->successful()) {
                throw new \Exception('Egress API error: '.$response->body());
            }

            Log::info('Recording stopped successfully', [
                'egress_id' => $egressId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to stop recording', [
                'error' => $e->getMessage(),
                'egress_id' => $egressId,
            ]);

            throw new \Exception('Failed to stop recording: '.$e->getMessage());
        }
    }

    /**
     * Get recording information
     */
    public function getRecording(string $egressId): ?array
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit recording manager not configured properly');
            }

            // Generate token for Egress API
            $token = $this->tokenGenerator->generateEgressToken();

            // Call LiveKit Egress API to get egress info
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/ListEgress', [
                'egress_id' => $egressId,
            ]);

            if (! $response->successful()) {
                throw new \Exception('Egress API error: '.$response->body());
            }

            $responseData = $response->json();

            return $responseData;

        } catch (\Exception $e) {
            Log::error('Failed to get recording info', [
                'error' => $e->getMessage(),
                'egress_id' => $egressId,
            ]);

            return null;
        }
    }

    /**
     * List all recordings for a room
     */
    public function listRecordings(string $roomName): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit recording manager not configured properly');
            }

            // Generate token for Egress API
            $token = $this->tokenGenerator->generateEgressToken();

            // Call LiveKit Egress API to list all egresses
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/ListEgress', [
                'room_name' => $roomName,
            ]);

            if (! $response->successful()) {
                throw new \Exception('Egress API error: '.$response->body());
            }

            $responseData = $response->json();

            return $responseData['items'] ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to list recordings', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            return [];
        }
    }

    /**
     * Start track composite recording (record individual tracks)
     */
    public function startTrackRecording(string $roomName, array $trackOptions = []): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit recording manager not configured properly');
            }

            $filename = $trackOptions['filename'] ?? sprintf('track-recording-%s-%s', $roomName, now()->timestamp);
            $filepath = sprintf(
                '%s/%s',
                rtrim($trackOptions['storage_path'] ?? '/recordings', '/'),
                $filename
            );

            $payload = [
                'room_name' => $roomName,
                'audio_only' => $trackOptions['audio_only'] ?? false,
                'video_only' => $trackOptions['video_only'] ?? false,
                'file_outputs' => [
                    [
                        'filepath' => $filepath,
                    ],
                ],
            ];

            Log::info('Starting LiveKit track recording', [
                'room_name' => $roomName,
                'filepath' => $filepath,
            ]);

            $token = $this->tokenGenerator->generateEgressToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/StartTrackCompositeEgress', $payload);

            if (! $response->successful()) {
                throw new \Exception('Egress API error: '.$response->body());
            }

            $responseData = $response->json();

            Log::info('Track recording started successfully', [
                'room_name' => $roomName,
                'egress_id' => $responseData['egressId'] ?? null,
            ]);

            return [
                'egress_id' => $responseData['egressId'] ?? $responseData['egress_id'] ?? null,
                'room_name' => $roomName,
                'filepath' => $filepath,
                'response' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to start track recording', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            throw new \Exception('Failed to start track recording: '.$e->getMessage());
        }
    }

    /**
     * Update egress (e.g., add stream output)
     */
    public function updateEgress(string $egressId, array $updates): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new \Exception('LiveKit recording manager not configured properly');
            }

            $payload = array_merge(['egress_id' => $egressId], $updates);

            $token = $this->tokenGenerator->generateEgressToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/UpdateLayout', $payload);

            if (! $response->successful()) {
                throw new \Exception('Egress API error: '.$response->body());
            }

            Log::info('Egress updated successfully', [
                'egress_id' => $egressId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update egress', [
                'error' => $e->getMessage(),
                'egress_id' => $egressId,
            ]);

            return false;
        }
    }

    /**
     * Get recording status
     */
    public function getRecordingStatus(string $egressId): ?string
    {
        $recording = $this->getRecording($egressId);

        return $recording['status'] ?? null;
    }

    /**
     * Check if recording is active
     */
    public function isRecordingActive(string $egressId): bool
    {
        $status = $this->getRecordingStatus($egressId);

        return in_array($status, ['EGRESS_STARTING', 'EGRESS_ACTIVE']);
    }
}
