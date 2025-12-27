<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Enums\SessionStatus;

class MeetingController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected LiveKitService $liveKitService
    ) {}

    /**
     * Create a meeting for a session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'session_type' => ['required', 'in:quran,academic,interactive'],
            'session_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $session = $this->getSession($user, $request->session_type, $request->session_id);

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        // Check if meeting already exists
        if ($session->meeting_link) {
            return $this->success([
                'meeting_link' => $session->meeting_link,
                'room_name' => $session->meeting_room_name,
            ], __('Meeting already exists'));
        }

        try {
            // Create LiveKit room
            $roomName = $this->generateRoomName($request->session_type, $session->id);

            $room = $this->liveKitService->createRoom($roomName, [
                'emptyTimeout' => 600, // 10 minutes
                'maxParticipants' => $request->session_type === 'interactive' ? 50 : 10,
            ]);

            // Update session with meeting info
            $session->update([
                'meeting_link' => $room['url'] ?? config('livekit.url') . '/room/' . $roomName,
                'meeting_room_name' => $roomName,
            ]);

            return $this->created([
                'meeting_link' => $session->meeting_link,
                'room_name' => $roomName,
            ], __('Meeting created successfully'));
        } catch (\Exception $e) {
            return $this->error(__('Failed to create meeting.'), 500, 'MEETING_CREATE_FAILED');
        }
    }

    /**
     * Get meeting token.
     *
     * @param Request $request
     * @param string $sessionType
     * @param int $sessionId
     * @return JsonResponse
     */
    public function token(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();

        $session = $this->getSession($user, $sessionType, $sessionId);

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        // Check if meeting exists
        if (!$session->meeting_room_name) {
            return $this->error(__('No meeting exists for this session.'), 400, 'NO_MEETING');
        }

        // Check if session is within join window
        $statusValue = $session->status->value ?? $session->status;
        $canJoin = in_array($statusValue, ['scheduled', 'live', 'in_progress']);

        if (!$canJoin) {
            return $this->error(__('This session is not available for joining.'), 400, 'SESSION_NOT_AVAILABLE');
        }

        try {
            // Generate token with teacher permissions
            $token = $this->liveKitService->generateToken(
                $session->meeting_room_name,
                $user->id,
                $user->name,
                [
                    'canPublish' => true,
                    'canSubscribe' => true,
                    'canPublishData' => true,
                    'hidden' => false,
                    'recorder' => false,
                ]
            );

            return $this->success([
                'token' => $token,
                'room_name' => $session->meeting_room_name,
                'server_url' => config('livekit.url'),
                'participant' => [
                    'identity' => (string) $user->id,
                    'name' => $user->name,
                    'is_teacher' => true,
                ],
            ], __('Token generated successfully'));
        } catch (\Exception $e) {
            return $this->error(__('Failed to generate token.'), 500, 'TOKEN_GENERATE_FAILED');
        }
    }

    /**
     * Get session based on type and verify teacher access.
     */
    protected function getSession($user, string $type, int $id)
    {
        if ($type === 'quran') {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if (!$quranTeacherId) {
                return null;
            }

            return QuranSession::where('id', $id)
                ->where('quran_teacher_id', $quranTeacherId)
                ->first();
        }

        if ($type === 'academic') {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if (!$academicTeacherId) {
                return null;
            }

            return AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();
        }

        if ($type === 'interactive') {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if (!$academicTeacherId) {
                return null;
            }

            $courseIds = $user->academicTeacherProfile->assignedCourses()
                ->pluck('id');

            return InteractiveCourseSession::where('id', $id)
                ->whereIn('course_id', $courseIds)
                ->first();
        }

        return null;
    }

    /**
     * Generate room name.
     */
    protected function generateRoomName(string $type, int $id): string
    {
        $prefix = match ($type) {
            'quran' => 'quran',
            'academic' => 'academic',
            'interactive' => 'interactive',
            default => 'session',
        };

        return "{$prefix}-{$id}-" . time();
    }
}
