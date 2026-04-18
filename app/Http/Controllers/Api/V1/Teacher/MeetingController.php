<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected LiveKitService $liveKitService
    ) {}

    /**
     * Create a meeting for a session.
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

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Check if meeting already exists
        if ($session->meeting_link) {
            return $this->success([
                'meeting_url' => $session->meeting_link,
                'room_name' => $session->meeting_room_name,
            ], __('Meeting already exists'));
        }

        try {
            // Create LiveKit room
            $roomName = $this->generateRoomName($request->session_type, $session->id);

            $meetingData = $this->liveKitService->createMeeting(
                $session->academy,
                $request->session_type,
                $session->id,
                $session->scheduled_at ?? now(),
                [
                    'empty_timeout' => 600,
                    'max_participants' => $request->session_type === 'interactive' ? 50 : 10,
                ]
            );

            // Update session with meeting info
            $session->update([
                'meeting_link' => $meetingData['meeting_url'] ?? config('livekit.url').'/room/'.$roomName,
                'meeting_room_name' => $meetingData['room_name'] ?? $roomName,
            ]);

            return $this->created([
                'meeting_url' => $session->meeting_link,
                'room_name' => $session->meeting_room_name,
            ], __('Meeting created successfully'));
        } catch (Exception $e) {
            return $this->error(__('Failed to create meeting.'), 500, 'MEETING_CREATE_FAILED');
        }
    }

    /**
     * Get session based on type and verify teacher access.
     */
    protected function getSession($user, string $type, string $id)
    {
        // API-003: Add explicit academy_id verification for defense in depth
        $userAcademyId = $user->academy_id;

        if ($type === 'quran') {
            if (! $user->quranTeacherProfile) {
                return null;
            }

            $quranTeacherId = $user->id;

            return QuranSession::where('id', $id)
                ->where('quran_teacher_id', $quranTeacherId)
                ->where('academy_id', $userAcademyId)
                ->first();
        }

        if ($type === 'academic') {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if (! $academicTeacherId) {
                return null;
            }

            return AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->where('academy_id', $userAcademyId)
                ->first();
        }

        if ($type === 'interactive') {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if (! $academicTeacherId) {
                return null;
            }

            $courseIds = $user->academicTeacherProfile?->assignedCourses()
                ?->pluck('id') ?? collect();

            return InteractiveCourseSession::where('id', $id)
                ->whereIn('course_id', $courseIds)
                ->first();
        }

        return null;
    }

    /**
     * Generate room name.
     * API-004: Uses consistent prefix pattern matching LiveKitService convention
     * so webhook controller can parse the prefix for optimized session lookup.
     */
    protected function generateRoomName(string $type, string $id): string
    {
        $prefix = match ($type) {
            'quran' => 'quran',
            'academic' => 'academic',
            'interactive' => 'interactive',
            default => 'session',
        };

        return "{$prefix}-session-{$id}-".time();
    }
}
