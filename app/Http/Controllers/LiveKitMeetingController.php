<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\QuranSession;
use App\Services\LiveKitService;

class LiveKitMeetingController extends Controller
{
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
            
            if (!$actualSessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID is required'
                ], 400);
            }
            
            $validator = Validator::make(array_merge($request->all(), ['session_id' => $actualSessionId]), [
                'session_id' => 'required|exists:quran_sessions,id',
                'max_participants' => 'nullable|integer|min:2|max:100',
                'recording_enabled' => 'nullable|boolean',
                'max_duration' => 'nullable|integer|min:15|max:480', // 15 minutes to 8 hours
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
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
                'can_manage' => $this->canManageSession($user, $session)
            ]);
            
            if (!$this->canManageSession($user, $session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to manage this session',
                    'debug' => [
                        'user_type' => $user->user_type,
                        'user_id' => $user->id,
                        'session_teacher_id' => $session->quran_teacher_id
                    ]
                ], 403);
            }

            // Check if meeting already exists
            if ($session->meeting_room_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting already exists for this session',
                    'meeting_info' => [
                        'meeting_url' => $session->meeting_link,
                        'room_name' => $session->meeting_room_name,
                    ]
                ], 409);
            }

            // Generate meeting
            $meetingUrl = $session->generateMeetingLink([
                'max_participants' => $request->input('max_participants', 50),
                'recording_enabled' => $request->input('recording_enabled', false),
                'max_duration' => $request->input('max_duration', 120),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Meeting created successfully',
                'data' => [
                    'session_id' => $session->id,
                    'meeting_url' => $meetingUrl,
                    'room_name' => $session->meeting_room_name,
                    'meeting_id' => $session->meeting_id,
                    'platform' => 'livekit',
                    'created_at' => now()->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create LiveKit meeting', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create meeting: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get participant access token for joining a meeting
     */
    public function getParticipantToken(Request $request, int $sessionId): JsonResponse
    {
        try {
            $session = QuranSession::findOrFail($sessionId);
            $user = $request->user();

            // Check if user has permission to join this session
            if (!$this->canJoinSession($user, $session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to join this session'
                ], 403);
            }

            // Check if meeting exists
            if (!$session->meeting_room_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meeting not created yet'
                ], 404);
            }

            // Get custom permissions from request
            $permissions = [
                'can_publish' => $request->input('can_publish', true),
                'can_subscribe' => $request->input('can_subscribe', true),
            ];

            // Generate access token
            $token = $session->generateParticipantToken($user, $permissions);

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $token,
                    'server_url' => config('livekit.server_url'),
                    'room_name' => $session->meeting_room_name,
                    'participant_identity' => $user->id . '_' . \Illuminate\Support\Str::slug($user->first_name . '_' . $user->last_name),
                    'permissions' => $permissions,
                    'expires_at' => now()->addHours(3)->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate LiveKit participant token', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate access token: ' . $e->getMessage()
            ], 500);
        }
    }

    // Additional methods for recording, room info, etc. will be added next
    
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

    private function canJoinSession($user, QuranSession $session): bool
    {
        if (in_array($user->user_type, ['super_admin', 'admin', 'quran_teacher', 'academic_teacher'])) {
            return true;
        }

        if ($user->user_type === 'student') {
            return $session->students()->where('user_id', $user->id)->exists();
        }

        if ($user->user_type === 'parent') {
            $childrenIds = $user->children()->pluck('id');
            return $session->students()->whereIn('user_id', $childrenIds)->exists();
        }

        return false;
    }
}