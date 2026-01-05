<?php

namespace App\Http\Controllers;

use App\Contracts\MeetingCapable;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use App\Services\MeetingAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Enums\SessionStatus;

class UnifiedMeetingController extends Controller
{
    use ApiResponses;
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
                'session_type' => 'required|in:quran,academic,interactive',
                'session_id' => 'required|integer',
                'max_participants' => 'sometimes|integer|min:2|max:50',
                'recording_enabled' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return $this->notFound('الجلسة غير موجودة');
            }

            // Check if user can manage this meeting
            if (! $session->canUserManageMeeting($user)) {
                return $this->forbidden('غير مصرح لك بإدارة هذه الجلسة');
            }

            // Check if meeting already exists and is valid
            if ($session->meeting_room_name && $session->isMeetingValid()) {
                return $this->success([
                    'meeting_url' => $session->meeting_link,
                    'room_name' => $session->meeting_room_name,
                    'meeting_id' => $session->meeting_id,
                    'platform' => $session->meeting_platform,
                    'expires_at' => $session->meeting_expires_at,
                    'session_type' => $sessionType,
                    'session_id' => $session->id,
                ], 'الاجتماع موجود بالفعل');
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

            return $this->success([
                'meeting_url' => $meetingUrl,
                'room_name' => $session->meeting_room_name,
                'meeting_id' => $session->meeting_id,
                'platform' => $session->meeting_platform,
                'expires_at' => $session->meeting_expires_at,
                'session_type' => $sessionType,
                'session_id' => $session->id,
            ], 'تم إنشاء الاجتماع بنجاح');

        } catch (\Exception $e) {
            Log::error('Failed to create unified meeting', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إنشاء الاجتماع' . (config('app.debug') ? ': ' . $e->getMessage() : ''),
                500
            );
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
                'session_type' => 'required|in:quran,academic,interactive',
                'session_id' => 'required|integer',
                'permissions' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return $this->notFound('الجلسة غير موجودة');
            }

            // Check if user can join this meeting
            if (! $session->canUserJoinMeeting($user)) {
                return $this->forbidden('غير مصرح لك بالانضمام إلى هذه الجلسة');
            }

            // Check if meeting exists
            if (! $session->meeting_room_name) {
                return $this->notFound('لم يتم إنشاء الاجتماع بعد');
            }

            // Generate participant token with custom permissions if provided
            $permissions = $request->input('permissions', []);
            $token = $session->generateParticipantToken($user, $permissions);

            // 🔥 FIX: Only update session status, NOT attendance
            // Attendance will be recorded by LiveKit webhooks (source of truth)
            if ($session->status->value === SessionStatus::READY->value || $session->status->value === SessionStatus::SCHEDULED->value) {
                $session->update(['status' => SessionStatus::ONGOING]);
                Log::info('Session status updated to ongoing on participant join', [
                    'session_id' => $session->id,
                    'session_type' => $sessionType,
                ]);
            }

            return $this->success([
                'access_token' => $token,
                'server_url' => config('livekit.server_url'),
                'room_name' => $session->meeting_room_name,
                'session_type' => $sessionType,
                'session_id' => $session->id,
                'user_identity' => $user->id.'_'.str_replace(' ', '_', trim($user->first_name.'_'.$user->last_name)),
                'user_name' => trim($user->first_name.' '.$user->last_name),
                'meeting_config' => $session->getMeetingConfiguration(),
            ], 'تم إنشاء رمز الوصول بنجاح');

        } catch (\Exception $e) {
            Log::error('Failed to generate participant token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إنشاء رمز الوصول' . (config('app.debug') ? ': ' . $e->getMessage() : ''),
                500
            );
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
                'session_type' => 'required|in:quran,academic,interactive',
                'session_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return $this->notFound('الجلسة غير موجودة');
            }

            // Check if user can view this meeting info
            if (! $session->canUserJoinMeeting($user)) {
                return $this->forbidden('غير مصرح لك بعرض معلومات هذه الجلسة');
            }

            // Get room info
            $roomInfo = $session->getRoomInfo();

            if (! $roomInfo) {
                return $this->notFound('الاجتماع غير موجود أو غير نشط');
            }

            return $this->success($roomInfo);

        } catch (\Exception $e) {
            Log::error('Failed to get room info', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return $this->error(
                'حدث خطأ أثناء جلب معلومات الاجتماع' . (config('app.debug') ? ': ' . $e->getMessage() : ''),
                500
            );
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
                'session_type' => 'required|in:quran,academic,interactive',
                'session_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return $this->notFound('الجلسة غير موجودة');
            }

            // Check if user can manage this meeting
            if (! $session->canUserManageMeeting($user)) {
                return $this->forbidden('غير مصرح لك بإنهاء هذه الجلسة');
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

                return $this->success(null, 'تم إنهاء الاجتماع بنجاح');
            } else {
                return $this->serverError('فشل في إنهاء الاجتماع');
            }

        } catch (\Exception $e) {
            Log::error('Failed to end meeting', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إنهاء الاجتماع' . (config('app.debug') ? ': ' . $e->getMessage() : ''),
                500
            );
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
            case 'interactive':
                return InteractiveCourseSession::find($sessionId);
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
                'session_type' => 'required|in:quran,academic,interactive',
                'session_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $user = Auth::user();
            $sessionType = $request->input('session_type');
            $sessionId = $request->input('session_id');

            // Get the session polymorphically
            $session = $this->getSessionByType($sessionType, $sessionId);

            if (! $session) {
                return $this->notFound('الجلسة غير موجودة');
            }

            // 🔥 FIX: Don't record leave from UI
            // Attendance will be recorded by LiveKit webhooks (source of truth)
            Log::info('User left meeting (attendance will be recorded by webhook)', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'session_type' => $sessionType,
            ]);

            return $this->success(null, 'تم تسجيل الخروج بنجاح');

        } catch (\Exception $e) {
            Log::error('Failed to record leave', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تسجيل الخروج' . (config('app.debug') ? ': ' . $e->getMessage() : ''),
                500
            );
        }
    }
}
