<?php

namespace App\Http\Controllers;

use App\Contracts\MeetingCapable;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use App\Services\MeetingAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnifiedMeetingController extends Controller
{
    protected LiveKitService $liveKitService;

    protected MeetingAttendanceService $attendanceService;

    public function __construct(
        LiveKitService $liveKitService,
        MeetingAttendanceService $attendanceService
    ) {
        $this->liveKitService = $liveKitService;
        $this->attendanceService = $attendanceService;
    }

    /**
     * Create or get meeting for a session (polymorphic)
     * Used by session detail pages to initialize meetings
     */
    public function createMeeting(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_type' => 'required|in:quran,academic',
                'session_id' => 'required|integer',
                'max_participants' => 'sometimes|integer|min:2|max:50',
                'recording_enabled' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة',
                ], 404);
            }

            // Check if user can manage this meeting
            if (! $session->canUserManageMeeting($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإدارة هذه الجلسة',
                ], 403);
            }

            // Check if meeting already exists and is valid
            if ($session->meeting_room_name && $session->isMeetingValid()) {
                return response()->json([
                    'success' => true,
                    'message' => 'الاجتماع موجود بالفعل',
                    'data' => [
                        'meeting_url' => $session->meeting_link,
                        'room_name' => $session->meeting_room_name,
                        'meeting_id' => $session->meeting_id,
                        'platform' => $session->meeting_platform,
                        'expires_at' => $session->meeting_expires_at,
                        'session_type' => $sessionType,
                        'session_id' => $session->id,
                    ],
                ]);
            }

            // Create new meeting with session-specific options
            $options = [
                'max_participants' => $request->input('max_participants'),
                'recording_enabled' => $request->input('recording_enabled'),
            ];

            // Generate meeting link using the session's method
            $meetingUrl = $session->generateMeetingLink($options);

            Log::info('Unified meeting created', [
                'session_type' => $sessionType,
                'session_id' => $session->id,
                'user_id' => $user->id,
                'meeting_url' => $meetingUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الاجتماع بنجاح',
                'data' => [
                    'meeting_url' => $meetingUrl,
                    'room_name' => $session->meeting_room_name,
                    'meeting_id' => $session->meeting_id,
                    'platform' => $session->meeting_platform,
                    'expires_at' => $session->meeting_expires_at,
                    'session_type' => $sessionType,
                    'session_id' => $session->id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create unified meeting', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الاجتماع',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Generate participant token for joining a meeting
     * Used by session detail pages when users click "Join Meeting"
     */
    public function getParticipantToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_type' => 'required|in:quran,academic',
                'session_id' => 'required|integer',
                'permissions' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة',
                ], 404);
            }

            // Check if user can join this meeting
            if (! $session->canUserJoinMeeting($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالانضمام إلى هذه الجلسة',
                ], 403);
            }

            // Check if meeting exists
            if (! $session->meeting_room_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إنشاء الاجتماع بعد',
                ], 404);
            }

            // Generate participant token with custom permissions if provided
            $permissions = $request->input('permissions', []);
            $token = $session->generateParticipantToken($user, $permissions);

            // 🔥 FIX: Only update session status, NOT attendance
            // Attendance will be recorded by LiveKit webhooks (source of truth)
            if ($session->status->value === 'ready' || $session->status->value === 'scheduled') {
                $session->update(['status' => 'live']);
                Log::info('Session status updated to live on participant join', [
                    'session_id' => $session->id,
                    'session_type' => $sessionType,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء رمز الوصول بنجاح',
                'data' => [
                    'access_token' => $token,
                    'server_url' => config('livekit.server_url'),
                    'room_name' => $session->meeting_room_name,
                    'session_type' => $sessionType,
                    'session_id' => $session->id,
                    'user_identity' => $user->id.'_'.str_replace(' ', '_', trim($user->first_name.'_'.$user->last_name)),
                    'user_name' => trim($user->first_name.' '.$user->last_name),
                    'meeting_config' => $session->getMeetingConfiguration(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate participant token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء رمز الوصول',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get room information and participants
     * Used by session detail pages to show meeting status
     */
    public function getRoomInfo(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_type' => 'required|in:quran,academic',
                'session_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة',
                ], 404);
            }

            // Check if user can view this meeting info
            if (! $session->canUserJoinMeeting($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بعرض معلومات هذه الجلسة',
                ], 403);
            }

            // Get room info
            $roomInfo = $session->getRoomInfo();

            if (! $roomInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'الاجتماع غير موجود أو غير نشط',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $roomInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get room info', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب معلومات الاجتماع',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * End a meeting
     * Used by session detail pages when teachers end the meeting
     */
    public function endMeeting(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_type' => 'required|in:quran,academic',
                'session_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة',
                ], 404);
            }

            // Check if user can manage this meeting
            if (! $session->canUserManageMeeting($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإنهاء هذه الجلسة',
                ], 403);
            }

            // End the meeting
            $success = $session->endMeeting();

            if ($success) {
                // Calculate final attendance for all participants
                $this->attendanceService->calculateFinalAttendance($session);

                Log::info('Unified meeting ended', [
                    'session_type' => $sessionType,
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إنهاء الاجتماع بنجاح',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في إنهاء الاجتماع',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to end meeting', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنهاء الاجتماع',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get session by type polymorphically
     */
    protected function getSessionByType(string $sessionType, int $sessionId): ?MeetingCapable
    {
        switch ($sessionType) {
            case 'quran':
                return QuranSession::find($sessionId);
            case 'academic':
                return AcademicSession::find($sessionId);
            default:
                return null;
        }
    }

    /**
     * Record user leave event
     * Called when users leave the meeting
     */
    public function recordLeave(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_type' => 'required|in:quran,academic',
                'session_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'message' => 'الجلسة غير موجودة',
                ], 404);
            }

            // 🔥 FIX: Don't record leave from UI
            // Attendance will be recorded by LiveKit webhooks (source of truth)
            Log::info('User left meeting (attendance will be recorded by webhook)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record leave', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
