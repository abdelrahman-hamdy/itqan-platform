<?php

namespace App\Services\Session;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Services\AcademyContextService;
use App\Services\LiveKitService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling session status checks and eligibility
 */
class SessionStatusService
{
    private const DEFAULT_PREPARATION_MINUTES = 15;
    private const DEFAULT_ENDING_BUFFER_MINUTES = 5;
    private const DEFAULT_DURATION_MINUTES = 60;

    public function __construct(
        private LiveKitService $liveKitService
    ) {}

    /**
     * Check if user can join a session
     */
    public function canUserJoinSession($session, string $userRole, ?Carbon $now = null): bool
    {
        $now = $now ?? AcademyContextService::nowInAcademyTimezone();

        if (!$session->scheduled_at) {
            return false;
        }

        [$preparationMinutes, $bufferMinutes] = $this->getSessionConfiguration($session);

        // Check if session has expired
        if ($this->hasSessionExpired($session, $bufferMinutes, $now)) {
            return false;
        }

        $isTeacher = in_array($userRole, ['academic_teacher', 'quran_teacher']);

        if (in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return true;
        }

        if (in_array($session->status, [SessionStatus::ABSENT, SessionStatus::SCHEDULED])) {
            $preparationStart = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
            $sessionEnd = $session->scheduled_at->copy()->addMinutes(
                ($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES) + $bufferMinutes
            );

            // Teachers can join during preparation time
            if ($isTeacher && $now->gte($preparationStart) && $now->lt($sessionEnd)) {
                return true;
            }

            // Students can only join from scheduled time
            if (!$isTeacher && $now->gte($session->scheduled_at) && $now->lt($sessionEnd)) {
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
        $preparationMinutes = $preparationMinutes ?? self::DEFAULT_PREPARATION_MINUTES;
        $canJoin = $this->canUserJoinSession($session, $userRole);

        return match ($session->status) {
            SessionStatus::READY => [
                'message' => 'الجلسة جاهزة - يمكنك الانضمام الآن',
                'button_text' => 'انضم للجلسة',
                'button_class' => 'bg-green-600 hover:bg-green-700',
                'can_join' => $canJoin,
            ],

            SessionStatus::ONGOING => [
                'message' => 'الجلسة جارية الآن - انضم للمشاركة',
                'button_text' => 'انضمام للجلسة الجارية',
                'button_class' => 'bg-orange-600 hover:bg-orange-700 animate-pulse',
                'can_join' => $canJoin,
            ],

            SessionStatus::SCHEDULED => $this->getScheduledStatusDisplay($session, $userRole, $canJoin, $preparationMinutes),

            SessionStatus::ABSENT => $this->getAbsentStatusDisplay($userRole, $canJoin),

            SessionStatus::COMPLETED => [
                'message' => 'تم إنهاء الجلسة بنجاح',
                'button_text' => 'الجلسة منتهية',
                'button_class' => 'bg-gray-400 cursor-not-allowed',
                'can_join' => false,
            ],

            SessionStatus::CANCELLED => [
                'message' => 'تم إلغاء الجلسة',
                'button_text' => 'الجلسة ملغية',
                'button_class' => 'bg-red-400 cursor-not-allowed',
                'can_join' => false,
            ],

            SessionStatus::UNSCHEDULED => [
                'message' => 'الجلسة غير مجدولة بعد',
                'button_text' => 'في انتظار الجدولة',
                'button_class' => 'bg-gray-400 cursor-not-allowed',
                'can_join' => false,
            ],

            default => [
                'message' => 'حالة الجلسة: ' . ($session->status->label() ?? 'غير معروفة'),
                'button_text' => 'غير متاح',
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
        $bufferMinutes = $bufferMinutes ?? self::DEFAULT_ENDING_BUFFER_MINUTES;

        if (!$this->hasSessionExpired($session, $bufferMinutes)) {
            return false;
        }

        if (!in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING])) {
            return false;
        }

        $sessionEndTime = $session->scheduled_at->copy()->addMinutes(
            ($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES) + $bufferMinutes
        );

        $session->update([
            'status' => SessionStatus::COMPLETED,
            'ended_at' => $sessionEndTime,
            'actual_duration_minutes' => $session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES,
        ]);

        if ($session->meeting_room_name) {
            try {
                $this->liveKitService->endMeeting($session->meeting_room_name);
                Log::info('LiveKit room closed on session auto-complete', [
                    'session_id' => $session->id,
                    'room_name' => $session->meeting_room_name,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to close LiveKit room on auto-complete', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Session auto-completed due to time expiration', [
            'session_id' => $session->id,
            'session_type' => get_class($session),
        ]);

        return true;
    }

    /**
     * Get session configuration (preparation and buffer times)
     */
    public function getSessionConfiguration($session): array
    {
        if ($session instanceof AcademicSession) {
            return [self::DEFAULT_PREPARATION_MINUTES, self::DEFAULT_ENDING_BUFFER_MINUTES];
        }

        if ($session instanceof InteractiveCourseSession) {
            return [
                $session->course?->preparation_minutes ?? self::DEFAULT_PREPARATION_MINUTES,
                $session->course?->buffer_minutes ?? self::DEFAULT_ENDING_BUFFER_MINUTES,
            ];
        }

        // Quran sessions
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;

        return [
            $circle?->preparation_minutes ?? self::DEFAULT_PREPARATION_MINUTES,
            $circle?->ending_buffer_minutes ?? self::DEFAULT_ENDING_BUFFER_MINUTES,
        ];
    }

    /**
     * Resolve session from ID across all session types
     */
    public function resolveSession(int $sessionId, $user)
    {
        $academicSession = AcademicSession::find($sessionId);
        $quranSession = QuranSession::find($sessionId);
        $interactiveSession = InteractiveCourseSession::find($sessionId);

        if ($interactiveSession) {
            return $interactiveSession;
        }

        if ($academicSession && $quranSession) {
            if ($user->hasRole('academic_teacher') || $academicSession->student_id === $user->id) {
                return $academicSession;
            }
            return $quranSession;
        }

        return $academicSession ?: $quranSession;
    }

    /**
     * Check if session has expired
     */
    private function hasSessionExpired($session, int $bufferMinutes, ?Carbon $now = null): bool
    {
        $now = $now ?? AcademyContextService::nowInAcademyTimezone();

        if (!$session->scheduled_at) {
            return false;
        }

        $sessionEndTime = $session->scheduled_at->copy()->addMinutes(
            ($session->duration_minutes ?? self::DEFAULT_DURATION_MINUTES) + $bufferMinutes
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
                'message' => 'جاري تحضير الاجتماع - يمكنك الانضمام الآن',
                'button_text' => 'انضم للجلسة',
                'button_class' => 'bg-blue-600 hover:bg-blue-700',
                'can_join' => true,
            ];
        }

        $message = $session->scheduled_at
            ? $this->getWaitingMessage($session->scheduled_at, $preparationMinutes)
            : 'الجلسة محجوزة ولكن لم يتم تحديد موعد';

        return [
            'message' => $message,
            'button_text' => 'في انتظار تحضير الاجتماع',
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
            $isTeacher = in_array($userRole, ['quran_teacher', 'academic_teacher']);

            if ($isTeacher) {
                return [
                    'message' => 'الجلسة نشطة - يمكنك بدء أو الانضمام للاجتماع',
                    'button_text' => 'انضم للجلسة',
                    'button_class' => 'bg-green-600 hover:bg-green-700',
                    'can_join' => true,
                ];
            }

            return [
                'message' => 'تم تسجيل غيابك ولكن يمكنك الانضمام الآن',
                'button_text' => 'انضم للجلسة (غائب)',
                'button_class' => 'bg-yellow-600 hover:bg-yellow-700',
                'can_join' => true,
            ];
        }

        $isTeacher = in_array($userRole, ['quran_teacher', 'academic_teacher']);

        if ($isTeacher) {
            return [
                'message' => 'انتهت فترة الجلسة',
                'button_text' => 'الجلسة منتهية',
                'button_class' => 'bg-gray-400 cursor-not-allowed',
                'can_join' => false,
            ];
        }

        return [
            'message' => 'تم تسجيل غياب الطالب',
            'button_text' => 'غياب الطالب',
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

        return !$timeData['is_past']
            ? 'سيتم تحضير الاجتماع خلال ' . $timeData['formatted']
            : 'جاري تحضير الاجتماع...';
    }
}
