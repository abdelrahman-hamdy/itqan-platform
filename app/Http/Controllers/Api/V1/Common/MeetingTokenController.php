<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\StudentProfile;
use App\Services\LiveKitService;
use App\Services\MeetingAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class MeetingTokenController extends Controller
{
    use ApiResponses;

    protected LiveKitService $liveKitService;

    protected MeetingAttendanceService $attendanceService;

    public function __construct(LiveKitService $liveKitService, MeetingAttendanceService $attendanceService)
    {
        $this->liveKitService = $liveKitService;
        $this->attendanceService = $attendanceService;
    }

    /**
     * Get meeting token for a session.
     */
    public function getToken(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (! $session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        // Check if session can be joined
        if (! $this->canJoinSession($session, $sessionType)) {
            return $this->error(
                __('Cannot join session at this time.'),
                400,
                'SESSION_NOT_JOINABLE'
            );
        }

        // Get or create meeting
        $meeting = $session->meeting;

        if (! $meeting) {
            return $this->error(
                __('Meeting not available yet.'),
                400,
                'MEETING_NOT_AVAILABLE'
            );
        }

        // Determine participant role
        $isTeacher = $this->isTeacher($session, $sessionType, $user->id);
        $role = $isTeacher ? 'teacher' : 'student';

        try {
            // Generate LiveKit token
            $token = $this->liveKitService->generateParticipantToken(
                $meeting->room_name,
                $user,
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
                'room_name' => $meeting->room_name,
                'livekit_url' => config('livekit.server_url'),
                'participant' => [
                    'identity' => (string) $user->id,
                    'name' => $user->name,
                    'role' => $role,
                ],
                'session' => [
                    'id' => $session->id,
                    'type' => $sessionType,
                    'title' => $session->title,
                    'duration_minutes' => $session->duration_minutes ?? 45,
                ],
                'expires_in' => 7200,
            ], __('Meeting token generated'));

        } catch (InvalidArgumentException $e) {
            Log::error('Invalid meeting token parameters', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                __('Invalid parameters for token generation.'),
                400,
                'INVALID_PARAMETERS'
            );
        } catch (RuntimeException $e) {
            Log::error('LiveKit service error', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                __('Service unavailable.'),
                503,
                'SERVICE_UNAVAILABLE'
            );
        } catch (Throwable $e) {
            Log::critical('Unexpected error generating meeting token', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            report($e);

            return $this->error(
                __('Failed to generate meeting token.'),
                500,
                'TOKEN_GENERATION_FAILED'
            );
        }
    }

    /**
     * Get meeting info without token.
     */
    public function getInfo(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (! $session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        $meeting = $session->meeting;
        $canJoin = $this->canJoinSession($session, $sessionType);
        $isTeacher = $this->isTeacher($session, $sessionType, $user->id);

        return $this->success([
            'session' => [
                'id' => $session->id,
                'type' => $sessionType,
                'title' => $session->title,
                'status' => $session->status->value ?? $session->status,
                'scheduled_at' => $this->getScheduledAt($session, $sessionType)?->toISOString(),
                'duration_minutes' => $session->duration_minutes ?? 45,
            ],
            'meeting' => $meeting ? [
                'id' => $meeting->id,
                'room_name' => $meeting->room_name,
                'status' => $meeting->status,
                'is_active' => $meeting->status === 'active',
            ] : null,
            'participant' => [
                'role' => $isTeacher ? 'teacher' : 'student',
                'can_join' => $canJoin,
            ],
            'join_window' => [
                'opens_at' => $this->getJoinWindowStart($session, $sessionType)?->toISOString(),
                'closes_at' => $this->getJoinWindowEnd($session, $sessionType)?->toISOString(),
            ],
        ], __('Meeting info retrieved'));
    }

    /**
     * Start or create a meeting for a session (teacher only).
     */
    public function startMeeting(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (! $session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        if (! $this->isTeacher($session, $sessionType, $user->id)) {
            return $this->forbidden(__('Only teachers can start meetings.'));
        }

        // If meeting already exists and has a room name, return it
        if ($session->meeting_room_name) {
            return $this->success([
                'meeting_room_name' => $session->meeting_room_name,
                'meeting_url' => $session->meeting_link,
                'meeting_id' => $session->meeting_id,
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'livekit_url' => config('livekit.server_url'),
                'already_exists' => true,
            ], __('Meeting already exists.'));
        }

        try {
            $meetingUrl = $session->generateMeetingLink([
                'max_participants' => $request->input('max_participants', 50),
                'recording_enabled' => $request->input('recording_enabled', false),
            ]);

            // Refresh model to get updated meeting fields
            $session->refresh();

            // Update session status to READY if it's still scheduled
            $status = $session->status->value ?? $session->status;
            if ($status === SessionStatus::SCHEDULED->value) {
                $session->update(['status' => SessionStatus::READY]);
            }

            Log::info('Meeting started via mobile API', [
                'session_type' => $sessionType,
                'session_id' => $session->id,
                'user_id' => $user->id,
                'room_name' => $session->meeting_room_name,
            ]);

            return $this->success([
                'meeting_room_name' => $session->meeting_room_name,
                'meeting_url' => $session->meeting_link,
                'meeting_id' => $session->meeting_id,
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'livekit_url' => config('livekit.server_url'),
                'already_exists' => false,
            ], __('Meeting created successfully.'));

        } catch (Throwable $e) {
            Log::error('Failed to start meeting via mobile API', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                __('Failed to create meeting.'),
                500,
                'MEETING_CREATION_FAILED'
            );
        }
    }

    /**
     * End a meeting (teacher only).
     */
    public function endMeeting(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (! $session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        if (! $this->isTeacher($session, $sessionType, $user->id)) {
            return $this->forbidden(__('Only teachers can end meetings.'));
        }

        if (! $session->meeting_room_name) {
            return $this->error(
                __('No active meeting to end.'),
                400,
                'NO_ACTIVE_MEETING'
            );
        }

        try {
            $success = $session->endMeeting();

            if ($success) {
                $this->attendanceService->calculateFinalAttendance($session);

                // Update session status to completed
                $session->update(['status' => SessionStatus::COMPLETED]);

                Log::info('Meeting ended via mobile API', [
                    'session_type' => $sessionType,
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                ]);

                return $this->success(null, __('Meeting ended successfully.'));
            }

            return $this->serverError(__('Failed to end meeting.'));

        } catch (Throwable $e) {
            Log::error('Failed to end meeting via mobile API', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                __('Failed to end meeting.'),
                500,
                'MEETING_END_FAILED'
            );
        }
    }

    /**
     * Get participants currently in the meeting.
     */
    public function getParticipants(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (! $session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        if (! $session->meeting_room_name) {
            return $this->success([
                'participants' => [],
                'count' => 0,
            ], __('No active meeting.'));
        }

        try {
            $roomInfo = $this->liveKitService->getRoomInfo($session->meeting_room_name);

            if (! $roomInfo) {
                return $this->success([
                    'participants' => [],
                    'count' => 0,
                ], __('Room not found or empty.'));
            }

            return $this->success([
                'participants' => $roomInfo['participants'] ?? [],
                'count' => $roomInfo['participant_count'] ?? 0,
                'room_name' => $roomInfo['room_name'],
                'is_active' => $roomInfo['is_active'] ?? false,
            ], __('Participants retrieved.'));

        } catch (Throwable $e) {
            Log::error('Failed to get meeting participants', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                __('Failed to get participants.'),
                500,
                'PARTICIPANTS_FETCH_FAILED'
            );
        }
    }

    /**
     * Remove a participant from the meeting (teacher only).
     */
    public function kickParticipant(Request $request, string $sessionType, int $sessionId): JsonResponse
    {
        $user = $request->user();
        $session = $this->getSession($sessionType, $sessionId, $user->id);

        if (! $session) {
            return $this->notFound(__('Session not found or access denied.'));
        }

        if (! $this->isTeacher($session, $sessionType, $user->id)) {
            return $this->forbidden(__('Only teachers can remove participants.'));
        }

        $participantIdentity = $request->input('participant_identity');
        if (! $participantIdentity) {
            return $this->error(
                __('Participant identity is required.'),
                422,
                'MISSING_PARTICIPANT_IDENTITY'
            );
        }

        if (! $session->meeting_room_name) {
            return $this->error(
                __('No active meeting.'),
                400,
                'NO_ACTIVE_MEETING'
            );
        }

        try {
            $removed = $this->liveKitService->roomManager()->removeParticipant(
                $session->meeting_room_name,
                $participantIdentity
            );

            if ($removed) {
                Log::info('Participant kicked via mobile API', [
                    'session_type' => $sessionType,
                    'session_id' => $session->id,
                    'kicked_by' => $user->id,
                    'participant_identity' => $participantIdentity,
                ]);

                return $this->success(null, __('Participant removed.'));
            }

            return $this->error(
                __('Failed to remove participant.'),
                500,
                'KICK_FAILED'
            );

        } catch (Throwable $e) {
            Log::error('Failed to kick participant', [
                'session_id' => $session->id,
                'session_type' => $sessionType,
                'participant_identity' => $participantIdentity,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                __('Failed to remove participant.'),
                500,
                'KICK_FAILED'
            );
        }
    }

    /**
     * Get session by type and ID.
     */
    protected function getSession(string $type, int $id, int $userId)
    {
        return match ($type) {
            'quran' => QuranSession::where('id', $id)
                ->where(function ($q) use ($userId) {
                    $q->where('student_id', $userId)
                        ->orWhereHas('quranTeacher', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->with(['meeting', 'quranTeacher'])
                ->first(),

            'academic' => AcademicSession::where('id', $id)
                ->where(function ($q) use ($userId) {
                    $q->where('student_id', $userId)
                        ->orWhereHas('academicTeacher', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->with(['meeting', 'academicTeacher'])
                ->first(),

            // interactive_course_enrollments.student_id references StudentProfile.id
            // We need to get user's studentProfile first for enrollment check
            'interactive' => InteractiveCourseSession::where('id', $id)
                ->where(function ($q) use ($userId) {
                    // Get the student profile ID for this user
                    $studentProfile = StudentProfile::where('user_id', $userId)->first();
                    $studentProfileId = $studentProfile?->id;

                    if ($studentProfileId) {
                        $q->whereHas('course.enrollments', function ($q) use ($studentProfileId) {
                            $q->where('student_id', $studentProfileId);
                        });
                    }

                    // Or check if user is the teacher
                    $q->orWhereHas('course.assignedTeacher', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                })
                ->with(['meeting', 'course.assignedTeacher'])
                ->first(),

            default => null,
        };
    }

    /**
     * Check if session can be joined.
     */
    protected function canJoinSession($session, string $type): bool
    {
        $scheduledAt = $this->getScheduledAt($session, $type);

        if (! $scheduledAt) {
            return false;
        }

        $now = now();
        $joinStart = $scheduledAt->copy()->subMinutes(10);
        $duration = $session->duration_minutes ?? 45;
        $joinEnd = $scheduledAt->copy()->addMinutes($duration + 15); // 15 min grace after

        $status = $session->status->value ?? $session->status;

        return $now->between($joinStart, $joinEnd)
            && ! in_array($status, [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value]);
    }

    /**
     * Check if user is the teacher.
     */
    protected function isTeacher($session, string $type, int $userId): bool
    {
        return match ($type) {
            'quran' => $session->quranTeacher?->id === $userId, // QuranSession::quranTeacher returns User directly
            'academic' => $session->academicTeacher?->user_id === $userId,
            'interactive' => $session->course?->assignedTeacher?->user_id === $userId,
            default => false,
        };
    }

    /**
     * Get scheduled at time.
     */
    protected function getScheduledAt($session, string $type)
    {
        // All session types now use scheduled_at
        return $session->scheduled_at;
    }

    /**
     * Get join window start time.
     */
    protected function getJoinWindowStart($session, string $type)
    {
        $scheduledAt = $this->getScheduledAt($session, $type);

        return $scheduledAt?->copy()->subMinutes(10);
    }

    /**
     * Get join window end time.
     */
    protected function getJoinWindowEnd($session, string $type)
    {
        $scheduledAt = $this->getScheduledAt($session, $type);
        $duration = $session->duration_minutes ?? 45;

        return $scheduledAt?->copy()->addMinutes($duration + 15);
    }
}
