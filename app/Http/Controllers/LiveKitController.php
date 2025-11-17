<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveKitController extends Controller
{
    /**
     * Get LiveKit access token for a participant
     */
    public function getToken(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'room_name' => 'required|string',
                'participant_name' => 'required|string',
                'user_type' => 'required|string|in:quran_teacher,student',
            ]);

            $roomName = $request->input('room_name');
            $participantName = $request->input('participant_name');
            $userType = $request->input('user_type');

            // Get LiveKit configuration
            $apiKey = config('livekit.api_key');
            $apiSecret = config('livekit.api_secret');

            if (! $apiKey || ! $apiSecret) {
                return response()->json([
                    'error' => 'LiveKit configuration not found',
                ], 500);
            }

            // Create participant identity with user ID for uniqueness (consistent with other token generation)
            $user = auth()->user();
            $identity = $user->id.'_'.\Illuminate\Support\Str::slug($user->first_name.'_'.$user->last_name);

            // Create access token options with metadata
            $metadata = json_encode([
                'userType' => $userType,
                'displayName' => $participantName,
                'role' => $userType === 'quran_teacher' ? 'teacher' : 'student',
                'userId' => $user->id,
            ]);

            $options = new AccessTokenOptions([
                'identity' => $identity,
                'name' => $participantName,
                'metadata' => $metadata,
            ]);

            // Create access token
            $at = new AccessToken($apiKey, $apiSecret, $options);

            // Create video grant with properties
            $grantProperties = [
                'roomJoin' => true,
                'room' => $roomName,
            ];

            // Teachers get additional permissions
            if ($userType === 'quran_teacher') {
                $grantProperties['roomAdmin'] = true;
                $grantProperties['roomCreate'] = true;
                $grantProperties['canPublish'] = true;
                $grantProperties['canSubscribe'] = true;
            } else {
                // Students get basic permissions
                $grantProperties['canPublish'] = true;
                $grantProperties['canSubscribe'] = true;
            }

            // Create video grant
            $grant = new VideoGrant($grantProperties);

            // Set the video grant
            $at->setGrant($grant);

            // Generate token
            $token = $at->toJwt();

            return response()->json([
                'token' => $token,
                'room_name' => $roomName,
                'participant_name' => $participantName,
                'identity' => $identity,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate token: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mute/unmute participant's microphone (Admin only)
     */
    public function muteParticipant(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'room_name' => 'required|string',
                'participant_identity' => 'required|string',
                'track_sid' => 'required|string',
                'muted' => 'required|boolean',
            ]);

            // Check if user is a teacher
            if (! in_array(auth()->user()->user_type, ['quran_teacher', 'academic_teacher'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $roomName = $request->input('room_name');
            $participantIdentity = $request->input('participant_identity');
            $trackSid = $request->input('track_sid');
            $muted = $request->input('muted');

            // Get LiveKit service
            $liveKitService = app(\App\Services\LiveKitService::class);

            if (! $liveKitService->isConfigured()) {
                return response()->json(['error' => 'LiveKit service not configured'], 500);
            }

            // Use room service client to mute/unmute
            $roomService = new \Agence104\LiveKit\RoomServiceClient(
                config('livekit.api_url'),
                config('livekit.api_key'),
                config('livekit.api_secret')
            );

            $result = $roomService->mutePublishedTrack(
                $roomName,
                $participantIdentity,
                $trackSid,
                $muted
            );

            Log::info('Participant mute/unmute action', [
                'room' => $roomName,
                'participant' => $participantIdentity,
                'track_sid' => $trackSid,
                'muted' => $muted,
                'teacher' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'muted' => $muted,
                'participant_identity' => $participantIdentity,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mute/unmute participant', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to mute/unmute participant: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get room participants with their tracks
     */
    public function getRoomParticipants(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'room_name' => 'required|string',
            ]);

            // Check if user is a teacher
            if (! in_array(auth()->user()->user_type, ['quran_teacher', 'academic_teacher'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $roomName = $request->input('room_name');

            // Get LiveKit service
            $liveKitService = app(\App\Services\LiveKitService::class);
            $roomInfo = $liveKitService->getRoomInfo($roomName);

            if (! $roomInfo) {
                return response()->json(['error' => 'Room not found'], 404);
            }

            // Get detailed participant info with tracks
            $roomService = new \Agence104\LiveKit\RoomServiceClient(
                config('livekit.api_url'),
                config('livekit.api_key'),
                config('livekit.api_secret')
            );

            $participantsResponse = $roomService->listParticipants($roomName);
            $participants = [];

            if ($participantsResponse && method_exists($participantsResponse, 'getParticipants')) {
                foreach ($participantsResponse->getParticipants() as $participant) {
                    $tracks = [];

                    // Get tracks for this participant
                    foreach ($participant->getTracks() as $track) {
                        $tracks[] = [
                            'sid' => $track->getSid(),
                            'name' => $track->getName(),
                            'type' => $track->getType(),
                            'muted' => $track->getMuted(),
                            'source' => $track->getSource(),
                        ];
                    }

                    $participants[] = [
                        'identity' => $participant->getIdentity(),
                        'name' => $participant->getName() ?: $participant->getIdentity(),
                        'sid' => $participant->getSid(),
                        'tracks' => $tracks,
                        'joined_at' => $participant->getJoinedAt(),
                        'metadata' => $participant->getMetadata(),
                    ];
                }
            }

            return response()->json([
                'room_name' => $roomName,
                'participants' => $participants,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get room participants', [
                'error' => $e->getMessage(),
                'room_name' => $request->input('room_name'),
            ]);

            return response()->json([
                'error' => 'Failed to get participants: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get room permissions (microphone and camera allowed state)
     */
    public function getRoomPermissions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'room_name' => 'required|string',
            ]);

            $roomName = $request->input('room_name');

            // Get permission service
            $permissionService = app(\App\Services\RoomPermissionService::class);
            $permissions = $permissionService->getRoomPermissions($roomName);

            return response()->json([
                'success' => true,
                'permissions' => [
                    'microphone_allowed' => $permissions['microphone_allowed'] ?? true,
                    'camera_allowed' => $permissions['camera_allowed'] ?? true,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get room permissions', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to get room permissions: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mute all students in a room (Teacher only)
     */
    public function muteAllStudents(Request $request): JsonResponse
    {
        try {
            // Enhanced debugging at controller level
            \Log::info('LiveKitController::muteAllStudents - Start', [
                'auth_check' => auth()->check(),
                'user_id' => auth()->check() ? auth()->user()->id : null,
                'user_type' => auth()->check() ? auth()->user()->user_type : null,
                'request_data' => $request->all(),
                'session_id' => $request->session()->getId() ?? 'no-session',
            ]);

            // Check if user is a teacher
            if (! auth()->check()) {
                \Log::warning('LiveKitController::muteAllStudents - User not authenticated');

                return response()->json(['error' => 'Authentication required'], 401);
            }

            $allowedTypes = ['quran_teacher', 'academic_teacher', 'admin', 'super_admin'];
            if (! in_array(auth()->user()->user_type, $allowedTypes)) {
                \Log::warning('LiveKitController::muteAllStudents - Unauthorized user type', [
                    'user_type' => auth()->user()->user_type,
                    'allowed_types' => $allowedTypes,
                ]);

                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $roomName = $request->input('room_name');
            $muted = $request->input('muted');

            \Log::info('LiveKitController::muteAllStudents - Processing request', [
                'room_name' => $roomName,
                'muted' => $muted,
            ]);

            // Store permission state in RoomPermissionService
            $permissionService = app(\App\Services\RoomPermissionService::class);
            $allowed = ! $muted; // Inverted: muted=true means NOT allowed
            $permissionService->setMicrophonePermission($roomName, $allowed, auth()->id());

            // Get room participants
            $roomService = new \Agence104\LiveKit\RoomServiceClient(
                config('livekit.api_url'),
                config('livekit.api_key'),
                config('livekit.api_secret')
            );

            try {
                $participantsResponse = $roomService->listParticipants($roomName);
            } catch (\Exception $e) {
                Log::error('Failed to list participants', [
                    'room' => $roomName,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Room not found or LiveKit server unavailable',
                ], 404);
            }

            $mutedCount = 0;

            if ($participantsResponse && method_exists($participantsResponse, 'getParticipants')) {
                foreach ($participantsResponse->getParticipants() as $participant) {
                    // Parse metadata to check if participant is student
                    $metadata = $participant->getMetadata() ? json_decode($participant->getMetadata(), true) : [];

                    // Check if participant is student based on identity or metadata
                    $identity = $participant->getIdentity();
                    $isStudent = (isset($metadata['role']) && $metadata['role'] === 'student') ||
                                 (! str_contains($identity, 'teacher') && ! str_contains($identity, 'admin'));

                    if ($isStudent) {
                        // Find audio tracks and mute them
                        foreach ($participant->getTracks() as $track) {
                            if ($track->getType() === \Livekit\TrackType::AUDIO) { // Audio type = 0
                                try {
                                    $roomService->mutePublishedTrack(
                                        $roomName,
                                        $participant->getIdentity(),
                                        $track->getSid(),
                                        $muted
                                    );
                                    $mutedCount++;
                                } catch (\Exception $e) {
                                    Log::warning('Failed to mute individual student', [
                                        'participant' => $participant->getIdentity(),
                                        'track_sid' => $track->getSid(),
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            Log::info('Bulk mute/unmute students action', [
                'room' => $roomName,
                'muted' => $muted,
                'affected_tracks' => $mutedCount,
                'teacher' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'muted' => $muted,
                'affected_participants' => $mutedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mute all students', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to mute all students: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disable/enable all students' cameras in a room (Teacher only)
     */
    public function disableAllStudentsCamera(Request $request): JsonResponse
    {
        try {
            \Log::info('LiveKitController::disableAllStudentsCamera - Start', [
                'auth_check' => auth()->check(),
                'user_id' => auth()->check() ? auth()->user()->id : null,
                'user_type' => auth()->check() ? auth()->user()->user_type : null,
                'request_data' => $request->all(),
            ]);

            if (! auth()->check()) {
                return response()->json(['error' => 'Authentication required'], 401);
            }

            $allowedTypes = ['quran_teacher', 'academic_teacher', 'admin', 'super_admin'];
            if (! in_array(auth()->user()->user_type, $allowedTypes)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $roomName = $request->input('room_name');
            $disabled = $request->input('disabled');

            \Log::info('LiveKitController::disableAllStudentsCamera - Processing', [
                'room_name' => $roomName,
                'disabled' => $disabled,
            ]);

            // Store permission state in RoomPermissionService
            $permissionService = app(\App\Services\RoomPermissionService::class);
            $allowed = ! $disabled; // Inverted: disabled=true means NOT allowed
            $permissionService->setCameraPermission($roomName, $allowed, auth()->id());

            $roomService = new \Agence104\LiveKit\RoomServiceClient(
                config('livekit.api_url'),
                config('livekit.api_key'),
                config('livekit.api_secret')
            );

            try {
                $participantsResponse = $roomService->listParticipants($roomName);
            } catch (\Exception $e) {
                Log::error('Failed to list participants for camera control', [
                    'room' => $roomName,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'error' => 'Room not found or LiveKit server unavailable',
                ], 404);
            }

            $affectedCount = 0;

            if ($participantsResponse && method_exists($participantsResponse, 'getParticipants')) {
                foreach ($participantsResponse->getParticipants() as $participant) {
                    $metadata = $participant->getMetadata() ? json_decode($participant->getMetadata(), true) : [];
                    $identity = $participant->getIdentity();
                    $isStudent = (isset($metadata['role']) && $metadata['role'] === 'student') ||
                                 (! str_contains($identity, 'teacher') && ! str_contains($identity, 'admin'));

                    if ($isStudent) {
                        // Find video tracks and disable/enable them
                        foreach ($participant->getTracks() as $track) {
                            if ($track->getType() === \Livekit\TrackType::VIDEO) { // Video type = 1
                                try {
                                    $roomService->mutePublishedTrack(
                                        $roomName,
                                        $participant->getIdentity(),
                                        $track->getSid(),
                                        $disabled
                                    );
                                    $affectedCount++;
                                } catch (\Exception $e) {
                                    Log::warning('Failed to control student camera', [
                                        'participant' => $participant->getIdentity(),
                                        'track_sid' => $track->getSid(),
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            Log::info('Bulk camera control action', [
                'room' => $roomName,
                'disabled' => $disabled,
                'affected_tracks' => $affectedCount,
                'teacher' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'disabled' => $disabled,
                'affected_participants' => $affectedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to control students cameras', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Failed to control cameras: '.$e->getMessage(),
            ], 500);
        }
    }
}
