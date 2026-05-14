<?php

namespace App\Services\LiveKit;

use App\Enums\LiveKitEgressStatus;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveKitRecordingManager
{
    private ?string $apiUrl;

    private LiveKitTokenGenerator $tokenGenerator;

    public function __construct(LiveKitTokenGenerator $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
        $serverUrl = config('livekit.server_url');
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
     * Dispatches audio-only recordings to TrackComposite egress (no Chrome
     * compositor, ~5x lower CPU per recording) when the
     * use_track_egress_for_audio_only flag is enabled; everything else still
     * uses RoomComposite egress.
     */
    public function startRecording(string $roomName, array $options = []): array
    {
        $audioOnly = $options['audio_only'] ?? false;
        $useTrackEgress = config('livekit.use_track_egress_for_audio_only', false);

        if ($audioOnly && $useTrackEgress) {
            return $this->startTrackRecording($roomName, $options);
        }

        return $this->startRoomCompositeRecording($roomName, $options);
    }

    /**
     * RoomComposite egress (Chrome-based compositor). Used when TrackComposite
     * routing is disabled or video is present.
     */
    private function startRoomCompositeRecording(string $roomName, array $options = []): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new Exception('LiveKit recording manager not configured properly');
            }

            // Audio-only AAC is always written into an MP4 container by LiveKit
            // egress (gstreamer's mp4mux). Use .mp4 to avoid the .m4a.mp4 double
            // extension LiveKit appends to non-recognized extensions.
            $audioOnly = $options['audio_only'] ?? false;
            $extension = '.mp4';
            $filename = $options['filename'] ?? sprintf('recording-%s-%s', $roomName, now()->timestamp);
            $filepath = sprintf(
                '%s/%s%s',
                rtrim($options['storage_path'] ?? '/recordings', '/'),
                $filename,
                $extension
            );

            $payload = [
                'room_name' => $roomName,
                'audio_only' => $audioOnly,
                'file' => [
                    'filepath' => $filepath,
                ],
            ];

            if ($audioOnly) {
                $payload['advanced'] = [
                    'audio_codec' => 'AAC',
                    'audio_bitrate' => $options['audio_bitrate'] ?? config('livekit.audio.recording_bitrate_kbps', 128),
                    'audio_frequency' => $options['audio_frequency'] ?? config('livekit.audio.recording_frequency', 48000),
                ];
            } else {
                $payload['options'] = [
                    'preset' => $options['preset'] ?? 'HD',
                    'layout' => $options['layout'] ?? 'grid',
                ];
            }

            if (! empty($options['metadata'])) {
                $payload['metadata'] = json_encode($options['metadata']);
            }

            Log::info('Starting LiveKit Egress recording', [
                'room_name' => $roomName,
                'filepath' => $filepath,
                'api_url' => $this->apiUrl,
            ]);

            $token = $this->tokenGenerator->generateEgressToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/StartRoomCompositeEgress', $payload);

            if (! $response->successful()) {
                throw new Exception('Egress API error: '.$response->body());
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

        } catch (Exception $e) {
            Log::error('Failed to start recording', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
                'options' => $options,
            ]);

            throw new Exception('Failed to start recording: '.$e->getMessage());
        }
    }

    /**
     * Stop an active recording
     */
    public function stopRecording(string $egressId): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new Exception('LiveKit recording manager not configured properly');
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
                throw new Exception('Egress API error: '.$response->body());
            }

            Log::info('Recording stopped successfully', [
                'egress_id' => $egressId,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to stop recording', [
                'error' => $e->getMessage(),
                'egress_id' => $egressId,
            ]);

            throw new Exception('Failed to stop recording: '.$e->getMessage());
        }
    }

    /**
     * Look up a single egress by ID. Returns the raw item, with its 'items'
     * envelope unwrapped so callers don't have to repeat the dance.
     */
    public function getRecording(string $egressId): ?array
    {
        try {
            if (! $this->isConfigured()) {
                throw new Exception('LiveKit recording manager not configured properly');
            }

            $token = $this->tokenGenerator->generateEgressToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/ListEgress', [
                'egress_id' => $egressId,
            ]);

            if (! $response->successful()) {
                throw new Exception('Egress API error: '.$response->body());
            }

            $items = $response->json('items') ?? [];

            return $items[0] ?? null;
        } catch (Exception $e) {
            Log::error('Failed to get recording info', [
                'error' => $e->getMessage(),
                'egress_id' => $egressId,
            ]);

            return null;
        }
    }

    /**
     * Fetch every active egress on the LiveKit server in one ListEgress call.
     * Used by the reconciliation cron to avoid one HTTP roundtrip per stuck row.
     *
     * Returns items keyed by egress_id, or empty array on API failure.
     */
    public function listAllActiveEgresses(): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new Exception('LiveKit recording manager not configured properly');
            }

            $token = $this->tokenGenerator->generateEgressToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/ListEgress', (object) []);

            if (! $response->successful()) {
                throw new Exception('Egress API error: '.$response->body());
            }

            $keyed = [];
            foreach ($response->json('items') ?? [] as $item) {
                $id = $item['egressId'] ?? $item['egress_id'] ?? null;
                if ($id) {
                    $keyed[$id] = $item;
                }
            }

            return $keyed;
        } catch (Exception $e) {
            Log::error('Failed to list active egresses', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * List all recordings for a room
     */
    public function listRecordings(string $roomName): array
    {
        try {
            if (! $this->isConfigured()) {
                throw new Exception('LiveKit recording manager not configured properly');
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
                throw new Exception('Egress API error: '.$response->body());
            }

            $responseData = $response->json();

            return $responseData['items'] ?? [];

        } catch (Exception $e) {
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
                throw new Exception('LiveKit recording manager not configured properly');
            }

            $audioOnly = $trackOptions['audio_only'] ?? false;
            $filename = $trackOptions['filename'] ?? sprintf('track-recording-%s-%s', $roomName, now()->timestamp);
            $extension = '.mp4';
            $filepath = sprintf(
                '%s/%s%s',
                rtrim($trackOptions['storage_path'] ?? '/recordings', '/'),
                $filename,
                $extension
            );

            $fileOutput = ['filepath' => $filepath];

            if ($audioOnly) {
                $fileOutput['audio_bitrate'] = $trackOptions['audio_bitrate'] ?? config('livekit.audio.recording_bitrate_kbps', 128);
                $fileOutput['audio_frequency'] = $trackOptions['audio_frequency'] ?? config('livekit.audio.recording_frequency', 48000);
            }

            $payload = [
                'room_name' => $roomName,
                'audio_only' => $audioOnly,
                'video_only' => $trackOptions['video_only'] ?? false,
                'file_outputs' => [$fileOutput],
            ];

            if (! empty($trackOptions['metadata'])) {
                $payload['metadata'] = json_encode($trackOptions['metadata']);
            }

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
                throw new Exception('Egress API error: '.$response->body());
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

        } catch (Exception $e) {
            Log::error('Failed to start track recording', [
                'error' => $e->getMessage(),
                'room_name' => $roomName,
            ]);

            throw new Exception('Failed to start track recording: '.$e->getMessage());
        }
    }

    /**
     * Update egress (e.g., add stream output)
     */
    public function updateEgress(string $egressId, array $updates): bool
    {
        try {
            if (! $this->isConfigured()) {
                throw new Exception('LiveKit recording manager not configured properly');
            }

            $payload = array_merge(['egress_id' => $egressId], $updates);

            $token = $this->tokenGenerator->generateEgressToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'/twirp/livekit.Egress/UpdateLayout', $payload);

            if (! $response->successful()) {
                throw new Exception('Egress API error: '.$response->body());
            }

            Log::info('Egress updated successfully', [
                'egress_id' => $egressId,
            ]);

            return true;

        } catch (Exception $e) {
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
        $status = LiveKitEgressStatus::tryFrom($this->getRecordingStatus($egressId) ?? '');

        return $status === LiveKitEgressStatus::STARTING || $status === LiveKitEgressStatus::ACTIVE;
    }
}
