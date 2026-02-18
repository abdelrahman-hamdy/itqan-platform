<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Http\Traits\Api\PaginatesResults;
use App\Http\Traits\Api\SessionViewerTrait;

/**
 * Base controller for student session operations
 *
 * Provides shared functionality for session listing and viewing
 */
abstract class BaseStudentSessionController extends Controller
{
    use ApiResponses, PaginatesResults, SessionViewerTrait;

    /**
     * Format a session for the API response.
     */
    protected function formatSession($session, string $type): array
    {
        $teacher = $this->resolveSessionTeacher($session, $type);

        // session_mode applies to quran/academic only (individual, group, trial)
        $sessionMode = in_array($type, ['quran', 'academic']) ? ($session->session_type ?? null) : null;

        // Get attendance status if attendances are loaded
        $attendanceStatus = null;
        if (isset($session->attendances) && $session->attendances->isNotEmpty()) {
            $attendanceStatus = $session->attendances->first()->attendance_status ?? null;
        }

        return [
            'id' => $session->id,
            'type' => $type,
            'title' => $this->resolveSessionTitle($session, $type),
            'session_code' => $session->session_code ?? null,
            'session_mode' => $sessionMode,
            'status' => $session->status->value ?? $session->status,
            'status_label' => $session->status->label ?? $session->status,
            'scheduled_at' => $session->scheduled_at?->toISOString(),
            'duration_minutes' => $session->duration_minutes ?? 45,
            'teacher' => $this->formatTeacherData($teacher),
            'meeting_url' => $session->meeting_link ?? null,
            'can_join' => $this->canJoinSession($session),
            'has_meeting' => ! empty($session->meeting_link),
            'attendance_status' => $attendanceStatus,
        ];
    }

    /**
     * Format common session detail fields shared by all session types.
     *
     * Adds description, notes, feedback, meeting, and attendance to the
     * base format. Child controllers should call this and then append
     * their type-specific details.
     */
    protected function formatCommonSessionDetails($session, string $type): array
    {
        $base = $this->formatSession($session, $type);

        // Ensure can_join and session_code are always present in detail views
        $base['can_join'] = $this->canJoinSession($session);
        $base['session_code'] = $session->session_code ?? null;

        $base['description'] = $session->description;
        $base['notes'] = $session->session_notes ?? null;
        $base['teacher_feedback'] = $session->teacher_feedback ?? null;

        // Meeting data is stored directly on the session model (no separate meeting relationship)
        if ($session->meeting_link || $session->meeting_room_name) {
            $base['meeting'] = [
                'meeting_url' => $session->meeting_link,
                'room_name' => $session->meeting_room_name ?? null,
            ];
        }

        $attendanceData = $this->formatAttendanceData($session);
        if ($attendanceData) {
            $base['attendance'] = $attendanceData;
        }

        return $base;
    }
}
