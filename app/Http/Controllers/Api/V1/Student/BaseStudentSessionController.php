<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\PaginatesResults;

/**
 * Base controller for student session operations
 *
 * Provides shared functionality for session listing and viewing
 */
abstract class BaseStudentSessionController extends Controller
{
    use ApiResponses, PaginatesResults;

    /**
     * Format a session for the API response.
     */
    protected function formatSession($session, string $type): array
    {
        // All session types now use scheduled_at
        $scheduledAt = $session->scheduled_at?->toISOString();

        $teacher = match ($type) {
            'quran' => $session->quranTeacher, // QuranSession::quranTeacher returns User directly
            'academic' => $session->academicTeacher?->user,
            'interactive' => $session->course?->assignedTeacher?->user,
            default => null,
        };

        $title = match ($type) {
            'quran' => $session->title ?? 'جلسة قرآنية',
            'academic' => $session->title ?? $session->academicSubscription?->subject_name ?? 'جلسة أكاديمية',
            'interactive' => $session->title ?? $session->course?->title ?? 'جلسة تفاعلية',
            default => 'جلسة',
        };

        return [
            'id' => $session->id,
            'type' => $type,
            'title' => $title,
            'status' => $session->status->value ?? $session->status,
            'status_label' => $session->status->label ?? $session->status,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $session->duration_minutes ?? 45,
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
            ] : null,
            'can_join' => $this->canJoin($session, $type),
            'has_meeting' => $session->meeting !== null,
        ];
    }

    /**
     * Check if session can be joined.
     */
    protected function canJoin($session, string $type): bool
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
}
