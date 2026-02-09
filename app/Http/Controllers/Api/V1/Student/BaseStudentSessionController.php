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

        return [
            'id' => $session->id,
            'type' => $type,
            'title' => $this->resolveSessionTitle($session, $type),
            'status' => $session->status->value ?? $session->status,
            'status_label' => $session->status->label ?? $session->status,
            'scheduled_at' => $session->scheduled_at?->toISOString(),
            'duration_minutes' => $session->duration_minutes ?? 45,
            'teacher' => $this->formatTeacherData($teacher),
            'can_join' => $this->canJoinSession($session),
            'has_meeting' => $session->meeting !== null,
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

        $base['description'] = $session->description;
        $base['notes'] = $session->notes ?? $session->teacher_notes ?? null;
        $base['student_rating'] = $session->student_rating;
        $base['student_feedback'] = $session->student_feedback;

        $meetingData = $this->formatMeetingData($session);
        if ($meetingData) {
            $base['meeting'] = $meetingData;
        }

        $attendanceData = $this->formatAttendanceData($session);
        if ($attendanceData) {
            $base['attendance'] = $attendanceData;
        }

        return $base;
    }
}
