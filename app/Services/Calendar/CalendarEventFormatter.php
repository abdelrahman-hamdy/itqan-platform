<?php

namespace App\Services\Calendar;

use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service for formatting calendar events for display.
 *
 * Transforms session models into standardized calendar event arrays
 * suitable for FullCalendar or other calendar displays.
 */
class CalendarEventFormatter
{
    /**
     * Surah names for Quran sessions.
     */
    private const SURAH_NAMES = [
        1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
        5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
        9 => 'التوبة', 10 => 'يونس', 11 => 'هود', 12 => 'يوسف',
        // ... more surahs can be added
    ];

    /**
     * Format a collection of sessions into calendar events.
     *
     * @param Collection $sessions The sessions to format
     * @param User $user The current user (for perspective)
     * @param string $sessionType The session type identifier
     * @return Collection
     */
    public function formatSessions(Collection $sessions, User $user, string $sessionType): Collection
    {
        return match ($sessionType) {
            'quran' => $this->formatQuranSessions($sessions, $user),
            'academic' => $this->formatAcademicSessions($sessions, $user),
            'interactive' => $this->formatInteractiveCourseSessions($sessions, $user),
            'circle' => $this->formatCircleSessions($sessions, $user),
            default => collect(),
        };
    }

    /**
     * Format Quran sessions for calendar display.
     */
    public function formatQuranSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            return $this->createCalendarEvent($session, $user, 'quran');
        });
    }

    /**
     * Format Academic sessions for calendar display.
     */
    public function formatAcademicSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            return $this->createCalendarEvent($session, $user, 'academic');
        });
    }

    /**
     * Format Interactive Course sessions for calendar display.
     */
    public function formatInteractiveCourseSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            return $this->createCalendarEvent($session, $user, 'interactive');
        });
    }

    /**
     * Format Circle sessions for calendar display.
     */
    public function formatCircleSessions(Collection $sessions, User $user): Collection
    {
        return $sessions->map(function ($session) use ($user) {
            return $this->createCalendarEvent($session, $user, 'circle');
        });
    }

    /**
     * Create a standardized calendar event array.
     */
    private function createCalendarEvent($session, User $user, string $type): array
    {
        $perspective = $this->getUserPerspective($user);

        return [
            'id' => "{$type}_{$session->id}",
            'session_id' => $session->id,
            'type' => $type,
            'title' => $this->getSessionTitle($session, $perspective, $type),
            'start' => $this->getSessionStartTime($session)->toIso8601String(),
            'end' => $this->getSessionEndTime($session)->toIso8601String(),
            'allDay' => false,
            'color' => $this->getSessionColor($session),
            'textColor' => '#ffffff',
            'borderColor' => $this->getSessionBorderColor($session),
            'extendedProps' => [
                'session_type' => $type,
                'status' => $this->getStatusValue($session),
                'status_label' => $this->getStatusLabel($session),
                'description' => $this->getSessionDescription($session, $perspective, $type),
                'url' => $this->getSessionUrl($session, $type),
                'teacher' => $this->getTeacherInfo($session, $type),
                'participants' => $this->getParticipants($session, $type),
                'can_join' => $this->canJoinSession($session),
                'is_cancelable' => $this->isCancelable($session, $user),
                'duration_minutes' => $session->duration_minutes ?? 60,
            ],
        ];
    }

    /**
     * Get user's perspective for session display.
     */
    private function getUserPerspective(User $user): string
    {
        if ($user->isQuranTeacher() || $user->isAcademicTeacher()) {
            return 'teacher';
        }
        if ($user->isParent()) {
            return 'parent';
        }
        return 'student';
    }

    /**
     * Get session title based on perspective.
     */
    private function getSessionTitle($session, string $perspective, string $type): string
    {
        if ($type === 'quran' || $type === 'circle') {
            if ($perspective === 'teacher') {
                return $session->student?->full_name ?? $session->circle?->name ?? 'جلسة قرآن';
            }
            return 'جلسة قرآن - ' . ($session->quranTeacher?->user?->full_name ?? 'المعلم');
        }

        if ($type === 'academic') {
            $subject = $session->academicSubscription?->academicSubject?->name ?? 'جلسة أكاديمية';
            if ($perspective === 'teacher') {
                return "{$subject} - " . ($session->student?->full_name ?? '');
            }
            return $subject;
        }

        if ($type === 'interactive') {
            return $session->course?->title ?? 'دورة تفاعلية';
        }

        return $session->title ?? 'جلسة';
    }

    /**
     * Get session description based on perspective.
     */
    private function getSessionDescription($session, string $perspective, string $type): string
    {
        $parts = [];

        if ($type === 'quran' || $type === 'circle') {
            if ($session->from_surah && $session->from_ayah) {
                $surahName = self::SURAH_NAMES[$session->from_surah] ?? "سورة {$session->from_surah}";
                $parts[] = "من: {$surahName} - الآية {$session->from_ayah}";
            }
            if ($session->to_surah && $session->to_ayah) {
                $surahName = self::SURAH_NAMES[$session->to_surah] ?? "سورة {$session->to_surah}";
                $parts[] = "إلى: {$surahName} - الآية {$session->to_ayah}";
            }
        }

        if ($type === 'academic' && $session->topics) {
            $parts[] = "المواضيع: {$session->topics}";
        }

        if ($type === 'interactive') {
            $parts[] = "الجلسة {$session->session_number}";
        }

        return implode(' | ', $parts);
    }

    /**
     * Get session start time.
     */
    private function getSessionStartTime($session): \Carbon\Carbon
    {
        if ($session instanceof InteractiveCourseSession) {
            if ($session->scheduled_at) {
                return $session->scheduled_at;
            }
            if ($session->scheduled_date && $session->scheduled_time) {
                return \Carbon\Carbon::parse($session->scheduled_date . ' ' . $session->scheduled_time);
            }
        }
        return $session->scheduled_at ?? now();
    }

    /**
     * Get session end time.
     */
    private function getSessionEndTime($session): \Carbon\Carbon
    {
        $startTime = $this->getSessionStartTime($session);
        $duration = $session->duration_minutes ?? 60;
        return $startTime->copy()->addMinutes($duration);
    }

    /**
     * Get color based on session status.
     */
    private function getSessionColor($session): string
    {
        $status = $this->getStatusValue($session);

        return match ($status) {
            SessionStatus::SCHEDULED->value, 'scheduled' => '#3b82f6', // Blue
            SessionStatus::ONGOING->value, 'ongoing', 'live' => '#22c55e', // Green
            SessionStatus::COMPLETED->value, 'completed' => '#6b7280', // Gray
            SessionStatus::CANCELLED->value, 'cancelled' => '#ef4444', // Red
            SessionStatus::ABSENT->value, 'absent' => '#f59e0b', // Amber
            default => '#3b82f6',
        };
    }

    /**
     * Get border color based on session status.
     */
    private function getSessionBorderColor($session): string
    {
        return $this->getSessionColor($session);
    }

    /**
     * Get session URL.
     */
    private function getSessionUrl($session, string $type): string
    {
        $subdomain = $session->academy?->subdomain ?? 'default';

        return match ($type) {
            'quran' => route('quran.sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]),
            'academic' => route('academic.sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]),
            'interactive' => route('interactive-courses.sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]),
            'circle' => route('quran.sessions.show', ['subdomain' => $subdomain, 'session' => $session->id]),
            default => '#',
        };
    }

    /**
     * Get teacher info.
     */
    private function getTeacherInfo($session, string $type): ?array
    {
        $teacher = match ($type) {
            'quran', 'circle' => $session->quranTeacher?->user,
            'academic' => $session->academicTeacher?->user,
            'interactive' => $session->course?->assignedTeacher?->user,
            default => null,
        };

        if (!$teacher) {
            return null;
        }

        return [
            'id' => $teacher->id,
            'name' => $teacher->full_name,
        ];
    }

    /**
     * Get participants.
     */
    private function getParticipants($session, string $type): array
    {
        if ($type === 'interactive') {
            return $session->course?->enrollments?->map(fn($e) => [
                'id' => $e->student_id,
                'name' => $e->student?->full_name ?? '',
            ])->toArray() ?? [];
        }

        if ($session->student) {
            return [[
                'id' => $session->student->id,
                'name' => $session->student->full_name,
            ]];
        }

        return [];
    }

    /**
     * Check if session can be joined.
     */
    private function canJoinSession($session): bool
    {
        $status = $this->getStatusValue($session);
        if (!in_array($status, [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, 'scheduled', 'ongoing'])) {
            return false;
        }

        $startTime = $this->getSessionStartTime($session);
        $prepTime = $startTime->copy()->subMinutes(10);

        return now()->gte($prepTime);
    }

    /**
     * Check if session is cancelable.
     */
    private function isCancelable($session, User $user): bool
    {
        $status = $this->getStatusValue($session);
        if (!in_array($status, [SessionStatus::SCHEDULED->value, 'scheduled'])) {
            return false;
        }

        // Can only cancel if more than 1 hour before start
        $startTime = $this->getSessionStartTime($session);
        return now()->lt($startTime->copy()->subHour());
    }

    /**
     * Get status value (handle both enum and string).
     */
    private function getStatusValue($session): string
    {
        $status = $session->status;
        return $status instanceof SessionStatus ? $status->value : (string) $status;
    }

    /**
     * Get status label.
     */
    private function getStatusLabel($session): string
    {
        $status = $session->status;
        if ($status instanceof SessionStatus) {
            return $status->label();
        }
        return SessionStatus::tryFrom($status)?->label() ?? $status;
    }
}
