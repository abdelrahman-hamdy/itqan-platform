<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Exception;
use Agence104\LiveKit\RoomServiceClient;
use Illuminate\Validation\ValidationException;
use Livekit\TrackType;
use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Enums\UserType;
use App\Http\Requests\GetLiveKitTokenRequest;
use App\Http\Requests\GetRoomParticipantsRequest;
use App\Http\Requests\GetRoomPermissionsRequest;
use App\Http\Requests\MuteParticipantRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Services\LiveKitService;
use App\Services\RoomPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveKitController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected LiveKitService $liveKitService,
        protected RoomPermissionService $roomPermissionService
    ) {}

    /**
     * Get LiveKit access token for a participant
     */
    public function getToken(GetLiveKitTokenRequest $request): JsonResponse
    {
        try {

            $roomName = $request->input('room_name');
            $participantName = $request->input('participant_name');

            // Get LiveKit configuration
            $apiKey = config('livekit.api_key');
            $apiSecret = config('livekit.api_secret');

            if (! $apiKey || ! $apiSecret) {
                return $this->serverError('LiveKit configuration not found');
            }

            // Create participant identity with user ID for uniqueness (consistent with other token generation)
            $user = auth()->user();
            // Derive user type from the authenticated user's actual role (never trust client input)
            $userType = $user->user_type;
            $identity = $user->id.'_'.Str::slug($user->first_name.'_'.$user->last_name);

            // Create access token options with metadata
            $metadata = json_encode([
                'userType' => $userType,
                'displayName' => $participantName,
                'role' => $userType === UserType::QURAN_TEACHER->value ? 'teacher' : 'student',
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
            if ($userType === UserType::QURAN_TEACHER->value) {
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

            return $this->success([
                'token' => $token,
                'room_name' => $roomName,
                'participant_name' => $participantName,
                'identity' => $identity,
            ]);

        } catch (Exception $e) {
            return $this->serverError('Failed to generate token: '.$e->getMessage());
        }
    }

    /**
     * Mute/unmute participant's microphone (Admin only)
     */
    public function muteParticipant(MuteParticipantRequest $request): JsonResponse
    {
        try {

            $roomName = $request->input('room_name');
            $participantIdentity = $request->input('participant_identity');
            $trackSid = $request->input('track_sid');
            $muted = $request->input('muted');

            if (! $this->liveKitService->isConfigured()) {
                return $this->serverError('LiveKit service not configured');
            }

            // Use room service client to mute/unmute
            $roomService = new RoomServiceClient(
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

            return $this->success([
                'muted' => $muted,
                'participant_identity' => $participantIdentity,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to mute/unmute participant', [
                'error' => $e->getMessage(),
                'room_name' => $request->input('room_name'),
                'user_id' => auth()->id(),
            ]);

            return $this->serverError('Failed to mute/unmute participant: '.$e->getMessage());
        }
    }

    /**
     * Get room participants with their tracks
     */
    public function getRoomParticipants(GetRoomParticipantsRequest $request): JsonResponse
    {
        try {
            $roomName = $request->input('room_name');

            // Return empty participants if no room specified
            if (empty($roomName)) {
                return $this->success([
                    'room_name' => null,
                    'participants' => [],
                ]);
            }

            $roomInfo = $this->liveKitService->getRoomInfo($roomName);

            if (! $roomInfo) {
                return $this->notFound('Room not found');
            }

            // Get detailed participant info with tracks
            $roomService = new RoomServiceClient(
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

            return $this->success([
                'room_name' => $roomName,
                'participants' => $participants,
            ]);

        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            Log::error('Failed to get room participants', [
                'error' => $e->getMessage(),
                'room_name' => $request->input('room_name'),
            ]);

            return $this->serverError('Failed to get participants: '.$e->getMessage());
        }
    }

    /**
     * Get room permissions (microphone and camera allowed state)
     */
    public function getRoomPermissions(GetRoomPermissionsRequest $request): JsonResponse
    {
        try {
            $roomName = $request->input('room_name');

            // Return default permissions if no room specified
            if (empty($roomName)) {
                return $this->success([
                    'permissions' => [
                        'microphone_allowed' => true,
                        'camera_allowed' => true,
                    ],
                ]);
            }

            $permissions = $this->roomPermissionService->getRoomPermissions($roomName);

            return $this->success([
                'permissions' => [
                    'microphone_allowed' => $permissions['microphone_allowed'] ?? true,
                    'camera_allowed' => $permissions['camera_allowed'] ?? true,
                ],
            ]);

        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed');
        } catch (Exception $e) {
            Log::error('Failed to get room permissions', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->serverError('Failed to get room permissions: '.$e->getMessage());
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

                return $this->unauthorized('Authentication required');
            }

            $allowedTypes = [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::ADMIN->value, UserType::SUPER_ADMIN->value];
            if (! in_array(auth()->user()->user_type, $allowedTypes)) {
                \Log::warning('LiveKitController::muteAllStudents - Unauthorized user type', [
                    'user_type' => auth()->user()->user_type,
                    'allowed_types' => $allowedTypes,
                ]);

                return $this->forbidden('Unauthorized');
            }

            $roomName = $request->input('room_name');
            $muted = $request->input('muted');

            \Log::info('LiveKitController::muteAllStudents - Processing request', [
                'room_name' => $roomName,
                'muted' => $muted,
            ]);

            // Store permission state
            $allowed = ! $muted; // Inverted: muted=true means NOT allowed
            $this->roomPermissionService->setMicrophonePermission($roomName, $allowed, auth()->id());

            // Get room participants
            $roomService = new RoomServiceClient(
                config('livekit.api_url'),
                config('livekit.api_key'),
                config('livekit.api_secret')
            );

            try {
                $participantsResponse = $roomService->listParticipants($roomName);
            } catch (Exception $e) {
                Log::error('Failed to list participants', [
                    'room' => $roomName,
                    'error' => $e->getMessage(),
                ]);

                return $this->notFound('Room not found or LiveKit server unavailable');
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
                            if ($track->getType() === TrackType::AUDIO) { // Audio type = 0
                                try {
                                    $roomService->mutePublishedTrack(
                                        $roomName,
                                        $participant->getIdentity(),
                                        $track->getSid(),
                                        $muted
                                    );
                                    $mutedCount++;
                                } catch (Exception $e) {
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

            return $this->success([
                'muted' => $muted,
                'affected_participants' => $mutedCount,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to mute all students', [
                'error' => $e->getMessage(),
                'room_name' => $request->input('room_name'),
                'user_id' => auth()->id(),
            ]);

            return $this->serverError('Failed to mute all students: '.$e->getMessage());
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
                return $this->unauthorized('Authentication required');
            }

            $allowedTypes = [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value, UserType::ADMIN->value, UserType::SUPER_ADMIN->value];
            if (! in_array(auth()->user()->user_type, $allowedTypes)) {
                return $this->forbidden('Unauthorized');
            }

            $roomName = $request->input('room_name');
            $disabled = $request->input('disabled');

            \Log::info('LiveKitController::disableAllStudentsCamera - Processing', [
                'room_name' => $roomName,
                'disabled' => $disabled,
            ]);

            // Store permission state
            $allowed = ! $disabled; // Inverted: disabled=true means NOT allowed
            $this->roomPermissionService->setCameraPermission($roomName, $allowed, auth()->id());

            $roomService = new RoomServiceClient(
                config('livekit.api_url'),
                config('livekit.api_key'),
                config('livekit.api_secret')
            );

            try {
                $participantsResponse = $roomService->listParticipants($roomName);
            } catch (Exception $e) {
                Log::error('Failed to list participants for camera control', [
                    'room' => $roomName,
                    'error' => $e->getMessage(),
                ]);

                return $this->notFound('Room not found or LiveKit server unavailable');
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
                            if ($track->getType() === TrackType::VIDEO) { // Video type = 1
                                try {
                                    $roomService->mutePublishedTrack(
                                        $roomName,
                                        $participant->getIdentity(),
                                        $track->getSid(),
                                        $disabled
                                    );
                                    $affectedCount++;
                                } catch (Exception $e) {
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

            return $this->success([
                'disabled' => $disabled,
                'affected_participants' => $affectedCount,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to control students cameras', [
                'error' => $e->getMessage(),
                'room_name' => $request->input('room_name'),
                'user_id' => auth()->id(),
            ]);

            return $this->serverError('Failed to control cameras: '.$e->getMessage());
        }
    }
}
