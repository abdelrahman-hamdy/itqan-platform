<?php

namespace App\Http\Traits\Api;

use App\Enums\SessionStatus;
use App\Http\Helpers\PaginationHelper;

/**
 * Shared session viewing logic for both student and parent session controllers.
 *
 * Extracts common formatting, pagination, and helper methods used across
 * BaseStudentSessionController and BaseParentSessionController hierarchies.
 */
trait SessionViewerTrait
{
    /**
     * Resolve the display title for a session based on its type.
     */
    protected function resolveSessionTitle($session, string $type): string
    {
        return match ($type) {
            'quran' => $session->title ?? __('جلسة قرآنية'),
            'academic' => $session->title ?? $session->academicSubscription?->subject_name ?? __('جلسة أكاديمية'),
            'interactive' => $session->title ?? $session->course?->title ?? __('جلسة تفاعلية'),
            default => __('جلسة'),
        };
    }

    /**
     * Resolve the teacher User model from a session based on its type.
     *
     * @return \App\Models\User|null
     */
    protected function resolveSessionTeacher($session, string $type)
    {
        return match ($type) {
            'quran' => $session->quranTeacher,
            'academic' => $session->academicTeacher?->user,
            'interactive' => $session->course?->assignedTeacher?->user,
            default => null,
        };
    }

    /**
     * Format teacher data for API response.
     *
     * @param  \App\Models\User|null  $teacher
     */
    protected function formatTeacherData($teacher): ?array
    {
        if (! $teacher) {
            return null;
        }

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
        ];
    }

    /**
     * Format meeting data for API response.
     */
    protected function formatMeetingData($session): ?array
    {
        if (! $session->meeting) {
            return null;
        }

        return [
            'id' => $session->meeting->id,
            'room_name' => $session->meeting->room_name,
            'status' => $session->meeting->status,
        ];
    }

    /**
     * Format attendance data for API response.
     */
    protected function formatAttendanceData($session): ?array
    {
        if (! isset($session->attendances) || $session->attendances->isEmpty()) {
            return null;
        }

        $attendance = $session->attendances->first();

        return [
            'status' => $attendance->status,
            'attended_at' => $attendance->attended_at?->toISOString(),
            'left_at' => $attendance->left_at?->toISOString(),
            'duration_minutes' => $attendance->duration_minutes,
        ];
    }

    /**
     * Check if a session can currently be joined.
     *
     * A session is joinable if the current time falls within the join window
     * (10 minutes before start to end of duration) and the session is not
     * cancelled or completed.
     */
    protected function canJoinSession($session): bool
    {
        $now = now();
        $sessionTime = $session->scheduled_at;

        if (! $sessionTime) {
            return false;
        }

        $joinStart = $sessionTime->copy()->subMinutes(10);
        $duration = $session->duration_minutes ?? 45;
        $joinEnd = $sessionTime->copy()->addMinutes($duration);

        $status = $session->status->value ?? $session->status;

        return $now->between($joinStart, $joinEnd)
            && ! in_array($status, [SessionStatus::CANCELLED->value, SessionStatus::COMPLETED->value]);
    }

    /**
     * Sort an array of formatted sessions by scheduled time.
     */
    protected function sortSessionsByTime(array $sessions, bool $ascending = false): array
    {
        usort($sessions, function ($a, $b) use ($ascending) {
            $timeA = strtotime($a['scheduled_at'] ?? '0');
            $timeB = strtotime($b['scheduled_at'] ?? '0');

            return $ascending
                ? $timeA <=> $timeB
                : $timeB <=> $timeA;
        });

        return $sessions;
    }

    /**
     * Manually paginate an array of sessions.
     *
     * @return array{sessions: array, pagination: array}
     */
    protected function manualPaginateSessions(array $sessions, int $page = 1, int $perPage = 15): array
    {
        $total = count($sessions);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($sessions, $offset, $perPage);

        return [
            'sessions' => $items,
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ];
    }
}
