<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LiveKitMeetingController extends Controller
{
    use ApiResponses;

    private LiveKitService $livekitService;

    public function __construct(LiveKitService $livekitService)
    {
        $this->livekitService = $livekitService;
    }

    /**
     * Create a new meeting room
     */
    public function createMeeting(Request $request, $subdomain = null, $sessionId = null): JsonResponse
    {
        try {
            // Use sessionId from route if provided, otherwise from request body
            $actualSessionId = $sessionId ?? $request->input('session_id');

            if (! $actualSessionId) {
                return $this->error('Session ID is required', 400);
            }

            $validator = Validator::make(array_merge($request->all(), ['session_id' => $actualSessionId]), [
                'session_id' => 'required|exists:quran_sessions,id',
                'max_participants' => 'nullable|integer|min:2|max:100',
                'recording_enabled' => 'nullable|boolean',
                'max_duration' => 'nullable|integer|min:15|max:480', // 15 minutes to 8 hours
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $session = QuranSession::findOrFail($actualSessionId);

            // Check if user has permission to create meeting for this session
            $user = $request->user();

            // Debug logging to understand the authorization issue
            Log::info('Meeting creation authorization debug', [
                'user_id' => $user->id,
                'user_type' => $user->user_type,
                'session_id' => $session->id,
                'session_quran_teacher_id' => $session->quran_teacher_id,
                'session_student_id' => $session->student_id,
                'can_manage' => $this->canManageSession($user, $session),
            ]);

            if (! $this->canManageSession($user, $session)) {
                return $this->error('Unauthorized to manage this session', 403, [
                    'debug' => [
                        'user_type' => $user->user_type,
                        'user_id' => $user->id,
                        'session_teacher_id' => $session->quran_teacher_id,
                    ],
                ]);
            }

            // Check if meeting already exists
            if ($session->meeting_room_name) {
                return $this->error('Meeting already exists for this session', 409, [
                    'meeting_info' => [
                        'meeting_url' => $session->meeting_link,
                        'room_name' => $session->meeting_room_name,
                    ],
                ]);
            }

            // Generate meeting
            $meetingUrl = $session->generateMeetingLink([
                'max_participants' => $request->input('max_participants', 50),
                'recording_enabled' => $request->input('recording_enabled', false),
                'max_duration' => $request->input('max_duration', 120),
            ]);

            return $this->success([
                'session_id' => $session->id,
                'meeting_url' => $meetingUrl,
                'room_name' => $session->meeting_room_name,
                'meeting_id' => $session->meeting_id,
                'platform' => 'livekit',
                'created_at' => now()->toISOString(),
            ], 'Meeting created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create LiveKit meeting', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return $this->serverError('Failed to create meeting: '.$e->getMessage());
        }
    }

    /**
     * Get participant access token for joining a meeting
     */
    public function getParticipantToken(Request $request, int $sessionId): JsonResponse
    {
        try {
            // Get session type from request or try to detect it
            $sessionType = $request->input('session_type', 'quran');

            // Load the appropriate session model based on type
            $session = $this->getSessionByType($sessionType, $sessionId);
            $user = $request->user();

            // Check if user has permission to join this session
            if (! $this->canJoinSession($user, $session)) {
                return $this->forbidden('Unauthorized to join this session');
            }

            // Check if meeting exists
            if (! $session->meeting_room_name) {
                return $this->notFound('Meeting not created yet');
            }

            // Get custom permissions from request
            $permissions = [
                'can_publish' => $request->input('can_publish', true),
                'can_subscribe' => $request->input('can_subscribe', true),
            ];

            // Generate access token
            $token = $session->generateParticipantToken($user, $permissions);

            return $this->success([
                'access_token' => $token,
                'server_url' => config('livekit.server_url'),
                'room_name' => $session->meeting_room_name,
                'participant_identity' => $user->id.'_'.\Illuminate\Support\Str::slug($user->first_name.'_'.$user->last_name),
                'permissions' => $permissions,
                'expires_at' => now()->addHours(3)->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate LiveKit participant token', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $request->user()->id,
            ]);

            return $this->serverError('Failed to generate access token: '.$e->getMessage());
        }
    }

    /**
     * Get room information for a session
     */
    public function getRoomInfo(Request $request, int $sessionId): JsonResponse
    {
        try {
            $session = QuranSession::findOrFail($sessionId);
            $user = $request->user();

            // Check if user has permission to access this session
            if (! $this->canJoinSession($user, $session) && ! $this->canManageSession($user, $session)) {
                return $this->forbidden('Unauthorized to access this session');
            }

            // Check if meeting exists
            if (! $session->meeting_room_name) {
                return $this->notFound('Meeting not created yet');
            }

            // Get room information from LiveKit service
            $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);

            if (! $roomInfo) {
                Log::warning('Room not found on LiveKit server, attempting to recreate', [
                    'session_id' => $sessionId,
                    'room_name' => $session->meeting_room_name,
                ]);

                // Try to recreate the room since it exists in database but not on LiveKit server
                try {
                    $session->generateMeetingLink();

                    // Try to get room info again after recreation
                    $roomInfo = $this->livekitService->getRoomInfo($session->meeting_room_name);

                    if (! $roomInfo) {
                        return $this->notFound('Unable to get room information after recreation attempt');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to recreate room', [
                        'session_id' => $sessionId,
                        'room_name' => $session->meeting_room_name,
                        'error' => $e->getMessage(),
                    ]);

                    // As a final fallback, provide basic room info from database
                    Log::info('Providing fallback room info from database', [
                        'session_id' => $sessionId,
                        'room_name' => $session->meeting_room_name,
                    ]);

                    return $this->success([
                        'room_name' => $session->meeting_room_name,
                        'room_sid' => $session->meeting_id ?? $session->meeting_room_name,
                        'participant_count' => 0,
                        'created_at' => $session->meeting_created_at ?? $session->created_at,
                        'participants' => [],
                        'is_active' => false,
                        'fallback_mode' => true,
                        'server_url' => config('livekit.server_url'),
                    ]);
                }
            }

            return $this->success([
                'room_name' => $session->meeting_room_name,
                'server_url' => config('livekit.server_url'),
                'data' => $roomInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get LiveKit room info', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $request->user()->id ?? null,
            ]);

            return $this->serverError('Failed to get room information: '.$e->getMessage());
        }
    }

    private function canManageSession($user, QuranSession $session): bool
    {
        // Super admin can manage any session
        if ($user->user_type === 'super_admin') {
            return true;
        }

        // Academy admin can manage sessions in their academy
        if ($user->user_type === 'admin' && $session->academy_id === $user->academy_id) {
            return true;
        }

        // Supervisor can manage sessions in their academy
        if ($user->user_type === 'supervisor' && $session->academy_id === $user->academy_id) {
            return true;
        }

        // Teachers can manage their own sessions
        if (in_array($user->user_type, ['quran_teacher', 'academic_teacher'])) {
            return $session->quran_teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Get session by type and ID
     */
    private function getSessionByType(string $sessionType, int $sessionId)
    {
        return match ($sessionType) {
            'quran' => QuranSession::findOrFail($sessionId),
            'academic' => AcademicSession::findOrFail($sessionId),
            default => throw new \InvalidArgumentException("Invalid session type: {$sessionType}")
        };
    }

    private function canJoinSession($user, $session): bool
    {
        // Super admin, admin, and teachers can join any session
        if (in_array($user->user_type, ['super_admin', 'admin', 'quran_teacher', 'academic_teacher'])) {
            return true;
        }

        if ($user->user_type === 'student') {
            // Check if this is an individual session (direct student assignment)
            if ($session->student_id === $user->id) {
                return true;
            }

            // Handle QuranSession specific checks
            if ($session instanceof QuranSession) {
                // Check if this is a group/circle session and user is enrolled in the circle
                if ($session->circle_id && $session->circle) {
                    return $session->circle->students()->where('student_id', $user->id)->exists();
                }

                // Check if this is a subscription session
                if ($session->quran_subscription_id && $session->subscription) {
                    return $session->subscription->student_id === $user->id;
                }

                // Check if this is an individual circle session
                if ($session->individual_circle_id && $session->individualCircle) {
                    return $session->individualCircle->student_id === $user->id;
                }
            }

            // Handle AcademicSession specific checks
            if ($session instanceof AcademicSession) {
                // Check if this is an academic subscription session
                if ($session->academic_subscription_id && $session->academicSubscription) {
                    return $session->academicSubscription->student_id === $user->id;
                }

                // Check if this is an individual lesson session
                if ($session->academic_individual_lesson_id && $session->academicIndividualLesson) {
                    return $session->academicIndividualLesson->student_id === $user->id;
                }
            }
        }

        if ($user->user_type === 'parent') {
            $childrenIds = $user->children()->pluck('id');

            // Check individual sessions
            if (in_array($session->student_id, $childrenIds->toArray())) {
                return true;
            }

            // Handle QuranSession specific checks
            if ($session instanceof QuranSession) {
                // Check group/circle sessions
                if ($session->circle_id && $session->circle) {
                    return $session->circle->students()->whereIn('student_id', $childrenIds)->exists();
                }

                // Check subscription sessions
                if ($session->quran_subscription_id && $session->subscription) {
                    return in_array($session->subscription->student_id, $childrenIds->toArray());
                }

                // Check individual circle sessions
                if ($session->individual_circle_id && $session->individualCircle) {
                    return in_array($session->individualCircle->student_id, $childrenIds->toArray());
                }
            }

            // Handle AcademicSession specific checks
            if ($session instanceof AcademicSession) {
                // Check academic subscription sessions
                if ($session->academic_subscription_id && $session->academicSubscription) {
                    return in_array($session->academicSubscription->student_id, $childrenIds->toArray());
                }

                // Check individual lesson sessions
                if ($session->academic_individual_lesson_id && $session->academicIndividualLesson) {
                    return in_array($session->academicIndividualLesson->student_id, $childrenIds->toArray());
                }
            }
        }

        return false;
    }
}
