<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Helpers\ImageHelper;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight session summary for list views
 *
 * @mixin BaseSession
 */
class SessionSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->getSessionType();

        return [
            'id' => $this->id,
            'type' => $type,
            'session_code' => $this->getSessionCode(),
            'status' => $this->status->value ?? $this->status,
            'status_label' => $this->status->label() ?? null,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'teacher' => $this->getTeacherData(),
            'student' => $this->getStudentData($type),
            'meeting' => $this->getMeetingData(),
            'can_observe' => $this->canBeObserved(),
        ];
    }

    /**
     * Get session type string
     */
    protected function getSessionType(): string
    {
        return match (get_class($this->resource)) {
            QuranSession::class => 'quran',
            AcademicSession::class => 'academic',
            InteractiveCourseSession::class => 'interactive',
            default => 'unknown',
        };
    }

    /**
     * Get session code
     */
    protected function getSessionCode(): ?string
    {
        if ($this->resource instanceof QuranSession) {
            return $this->session_code ?? $this->circle?->circle_code ?? $this->individualCircle?->circle_code;
        }

        if ($this->resource instanceof AcademicSession) {
            return $this->session_code;
        }

        if ($this->resource instanceof InteractiveCourseSession) {
            return $this->course?->course_code;
        }

        return null;
    }

    /**
     * Get teacher data
     */
    protected function getTeacherData(): array
    {
        $teacher = null;

        if ($this->resource instanceof QuranSession) {
            $teacher = $this->quranTeacher;
        } elseif ($this->resource instanceof AcademicSession) {
            $teacher = $this->academicTeacher?->user;
        } elseif ($this->resource instanceof InteractiveCourseSession) {
            $teacher = $this->course?->assignedTeacher?->user;
        }

        if (! $teacher) {
            return [];
        }

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => ImageHelper::getAvatarUrls($teacher->avatar, $teacher->name),
        ];
    }

    /**
     * Get student data
     */
    protected function getStudentData(string $type): array
    {
        if ($type === 'interactive') {
            // Interactive courses have multiple students
            $enrollmentCount = $this->course?->enrollments?->count() ?? 0;

            return [
                'type' => 'group',
                'count' => $enrollmentCount,
                'label' => __('admin.sessions.students_count', ['count' => $enrollmentCount]),
            ];
        }

        // Individual sessions (Quran or Academic)
        $student = $this->student;

        if (! $student) {
            return [];
        }

        return [
            'id' => $student->id,
            'name' => $student->name,
        ];
    }

    /**
     * Get meeting data
     */
    protected function getMeetingData(): ?array
    {
        if (! $this->meeting_room_name) {
            return null;
        }

        return [
            'room_name' => $this->meeting_room_name,
            'is_active' => in_array($this->status->value ?? $this->status, ['ready', 'ongoing']),
            'participant_count' => $this->meeting?->participant_count ?? 0,
        ];
    }

    /**
     * Check if session can be observed
     */
    protected function canBeObserved(): bool
    {
        if (! $this->meeting_room_name) {
            return false;
        }

        $status = is_string($this->status) ? $this->status : $this->status->value;

        return in_array($status, ['ready', 'ongoing']);
    }
}
