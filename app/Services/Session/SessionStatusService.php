<?php

namespace App\Services\Session;

use Exception;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use App\Services\LiveKitService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling session status checks and eligibility
 */
class SessionStatusService
{
    private function getDefaultPreparationMinutes(): int
    {
        return config('business.meetings.preparation_minutes', 15);
    }

    private function getDefaultEndingBufferMinutes(): int
    {
        return config('business.sessions.buffer_time_minutes', 5);
    }

    private function getDefaultDurationMinutes(): int
    {
        return config('business.sessions.default_duration_minutes', 60);
    }

    public function __construct(
        private LiveKitService $liveKitService
    ) {}

    /**
     * Check if user can join a session
     */
    public function canUserJoinSession($session, string $userRole, ?Carbon $now = null): bool
    {
        $now = $now ?? AcademyContextService::nowInAcademyTimezone();

        if (! $session->scheduled_at) {
            return false;
        }

        [$preparationMinutes, $bufferMinutes] = $this->getSessionConfiguration($session);

        // Check if session has expired
        if ($this->hasSessionExpired($session, $bufferMinutes, $now)) {
            return false;
        }

        $isTeacher = in_array($userRole, [UserType::ACADEMIC_TEACHER->value, UserType::QURAN_TEACHER->value]);

        if (in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return true;
        }

        if (in_array($session->status, [SessionStatus::ABSENT, SessionStatus::SCHEDULED])) {
            $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(
                ($session->duration_minutes ?? $this->getDefaultDurationMinutes()) + $bufferMinutes
            );

            // Teachers can join during preparation time
            if ($isTeacher && $now->gte($preparationStart) && $now->lt($sessionEnd)) {
                return true;
            }

            // Students can only join from scheduled time
            if (! $isTeacher && $now->gte($session->scheduled_at) && $now->lt($sessionEnd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get session status display information
     */
    public function getStatusDisplay($session, string $userRole, ?int $preparationMinutes = null): array
    {
        $preparationMinutes = $preparationMinutes ?? $this->getDefaultPreparationMinutes();
        $canJoin = $this->canUserJoinSession($session, $userRole);

        return match ($session->status) {
            SessionStatus::READY => [
                'message' => __('sessions.status_display.ready_message'),
                'button_text' => __('sessions.status_display.ready_button'),
                'button_class' => 'bg-green-600 hover:bg-green-700',
                'can_join' => $canJoin,
            ],

            SessionStatus::ONGOING => [
                'message' => __('sessions.status_display.ongoing_message'),
                'button_text' => __('sessions.status_display.ongoing_button'),
                'button_class' => 'bg-orange-600 hover:bg-orange-700 animate-pulse',
                'can_join' => $canJoin,
            ],

            SessionStatus::SCHEDULED => $this->getScheduledStatusDisplay($session, $userRole, $canJoin, $preparationMinutes),

            SessionStatus::ABSENT => $this->getAbsentStatusDisplay($userRole, $canJoin),

            SessionStatus::COMPLETED => [
                'message' => __('sessions.status_display.completed_message'),
                'button_text' => __('sessions.status_display.completed_button'),
                'button_class' => 'bg-gray-400 cursor-not-allowed',
                'can_join' => false,
            ],

            SessionStatus::CANCELLED => [
                'message' => __('sessions.status_display.cancelled_message'),
                'button_text' => __('sessions.status_display.cancelled_button'),
                'button_class' => 'bg-red-400 cursor-not-allowed',
                'can_join' => false,
            ],

            SessionStatus::UNSCHEDULED => [
                'message' => __('sessions.status_display.unscheduled_message'),
                'button_text' => __('sessions.status_display.unscheduled_button'),
                'button_class' => 'bg-gray-400 cursor-not-allowed',
                'can_join' => false,
            ],

            default => [
                'message' => __('sessions.status_display.default_message', ['status' => $session->status->label() ?? __('sessions.status_display.default_unknown')]),
                'button_text' => __('sessions.status_display.default_button'),
                'button_class' => 'bg-gray-400 cursor-not-allowed',
                'can_join' => false,
            ],
        };
    }

    /**
     * Auto-complete session if expired
     */
    public function autoCompleteIfExpired($session, ?int $bufferMinutes = null): bool
    {
        $bufferMinutes = $bufferMinutes ?? $this->getDefaultEndingBufferMinutes();

        if (! $this->hasSessionExpired($session, $bufferMinutes)) {
            return false;
        }

        if (! in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return false;
        }

        $meetingRoomName = null;

        $completed = DB::transaction(function () use ($session, $bufferMinutes, &$meetingRoomName) {
            // Lock the session row to prevent concurrent updates
            $lockedSession = $session::lockForUpdate()->find($session->id);

            if (! $lockedSession) {
                Log::warning('Cannot auto-complete: session not found', [
                    'session_id' => $session->id,
                ]);

                return false;
            }

            // Re-check status after locking (another process may have updated it)
            if (! in_array($lockedSession->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
                return false;
            }

            $sessionEndTime = $lockedSession->scheduled_at->copy()->addMinutes(
                ($lockedSession->duration_minutes ?? $this->getDefaultDurationMinutes()) + $bufferMinutes
            );

            $lockedSession->update([
                'status' => SessionStatus::COMPLETED,
                'ended_at' => $sessionEndTime,
                'actual_duration_minutes' => $lockedSession->duration_minutes ?? $this->getDefaultDurationMinutes(),
            ]);

            // Refresh the original session instance and capture room name before commit
            $session->refresh();
            $meetingRoomName = $session->meeting_room_name;

            Log::info('Session auto-completed due to time expiration', [
                'session_id' => $session->id,
                'session_type' => get_class($session),
            ]);

            return true;
        });

        // End LiveKit room outside the transaction to avoid holding the row lock
        // during a network call that could time out
        if ($completed && $meetingRoomName) {
            try {
                $this->liveKitService->endMeeting($meetingRoomName);
                Log::info('LiveKit room closed on session auto-complete', [
                    'session_id' => $session->id,
                    'room_name' => $meetingRoomName,
                ]);
            } catch (Exception $e) {
                Log::warning('Failed to close LiveKit room on auto-complete', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return (bool) $completed;
    }

    /**
     * Get session configuration (preparation and buffer times)
     */
    public function getSessionConfiguration($session): array
    {
        if ($session instanceof AcademicSession) {
            return [$this->getDefaultPreparationMinutes(), $this->getDefaultEndingBufferMinutes()];
        }

        if ($session instanceof InteractiveCourseSession) {
            return [
                $session->course?->preparation_minutes ?? $this->getDefaultPreparationMinutes(),
                $session->course?->buffer_minutes ?? $this->getDefaultEndingBufferMinutes(),
            ];
        }

        // Quran sessions
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;

        return [
            $circle?->preparation_minutes ?? $this->getDefaultPreparationMinutes(),
            $circle?->ending_buffer_minutes ?? $this->getDefaultEndingBufferMinutes(),
        ];
    }

    /**
     * Resolve session from ID across all session types.
     *
     * Always scopes by the user's academy_id to prevent cross-tenant session access.
     *
     * @param  string|null  $sessionType  'quran'|'academic'|'interactive' — avoids cross-type ID collision
     */
    public function resolveSession(int $sessionId, $user, ?string $sessionType = null)
    {
        $academyId = $user?->academy_id;

        if ($sessionType === 'interactive') {
            $q = InteractiveCourseSession::query();
            if ($academyId) {
                $q->whereHas('course', fn ($sub) => $sub->where('academy_id', $academyId));
            }

            return $q->find($sessionId);
        }

        if ($sessionType === 'academic') {
            $q = AcademicSession::query();
            if ($academyId) {
                $q->where('academy_id', $academyId);
            }

            return $q->find($sessionId);
        }

        if ($sessionType === 'quran') {
            $q = QuranSession::query();
            if ($academyId) {
                $q->where('academy_id', $academyId);
            }

            return $q->find($sessionId);
        }

        // Fallback without type hint — query each model separately to avoid
        // returning the wrong session when integer IDs collide across tables.
        $iq = InteractiveCourseSession::query();
        if ($academyId) {
            $iq->whereHas('course', fn ($sub) => $sub->where('academy_id', $academyId));
        }
        $interactiveSession = $iq->find($sessionId);
        if ($interactiveSession) {
            return $interactiveSession;
        }

        $aq = AcademicSession::query();
        if ($academyId) {
            $aq->where('academy_id', $academyId);
        }
        $academicSession = $aq->find($sessionId);
        if ($academicSession) {
            return $academicSession;
        }

        $qq = QuranSession::query();
        if ($academyId) {
            $qq->where('academy_id', $academyId);
        }

        return $qq->find($sessionId);
    }

    /**
     * Check if session has expired
     */
    private function hasSessionExpired($session, int $bufferMinutes, ?Carbon $now = null): bool
    {
        $now = $now ?? AcademyContextService::nowInAcademyTimezone();

        if (! $session->scheduled_at) {
            return false;
        }

        $sessionEndTime = $session->scheduled_at->copy()->addMinutes(
            ($session->duration_minutes ?? $this->getDefaultDurationMinutes()) + $bufferMinutes
        );

        return $now->gte($sessionEndTime);
    }

    /**
     * Get scheduled status display
     */
    private function getScheduledStatusDisplay($session, string $userRole, bool $canJoin, int $preparationMinutes): array
    {
        if ($canJoin) {
            return [
                'message' => __('sessions.status_display.preparing_can_join'),
                'button_text' => __('sessions.status_display.ready_button'),
                'button_class' => 'bg-blue-600 hover:bg-blue-700',
                'can_join' => true,
            ];
        }

        $message = $session->scheduled_at
            ? $this->getWaitingMessage($session->scheduled_at, $preparationMinutes)
            : __('sessions.status_display.scheduled_no_time');

        return [
            'message' => $message,
            'button_text' => __('sessions.status_display.waiting_preparation'),
            'button_class' => 'bg-gray-400 cursor-not-allowed',
            'can_join' => false,
        ];
    }

    /**
     * Get absent status display
     */
    private function getAbsentStatusDisplay(string $userRole, bool $canJoin): array
    {
        if ($canJoin) {
            $isTeacher = in_array($userRole, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);

            if ($isTeacher) {
                return [
                    'message' => __('sessions.status_display.absent_teacher_can_join'),
                    'button_text' => __('sessions.status_display.ready_button'),
                    'button_class' => 'bg-green-600 hover:bg-green-700',
                    'can_join' => true,
                ];
            }

            return [
                'message' => __('sessions.status_display.absent_student_can_join'),
                'button_text' => __('sessions.status_display.absent_student_button'),
                'button_class' => 'bg-yellow-600 hover:bg-yellow-700',
                'can_join' => true,
            ];
        }

        $isTeacher = in_array($userRole, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);

        if ($isTeacher) {
            return [
                'message' => __('sessions.status_display.absent_teacher_expired'),
                'button_text' => __('sessions.status_display.completed_button'),
                'button_class' => 'bg-gray-400 cursor-not-allowed',
                'can_join' => false,
            ];
        }

        return [
            'message' => __('sessions.status_display.absent_student_recorded'),
            'button_text' => __('sessions.status_display.absent_student_button_text'),
            'button_class' => 'bg-red-400 cursor-not-allowed',
            'can_join' => false,
        ];
    }

    /**
     * Get waiting message for scheduled sessions
     */
    private function getWaitingMessage(Carbon $scheduledAt, int $preparationMinutes): string
    {
        $preparationTime = $scheduledAt->copy()->subMinutes($preparationMinutes);
        $timeData = formatTimeRemaining($preparationTime);

        return ! $timeData['is_past']
            ? __('sessions.status_display.will_prepare_in', ['time' => $timeData['formatted']])
            : __('sessions.status_display.preparing_now');
    }
}
