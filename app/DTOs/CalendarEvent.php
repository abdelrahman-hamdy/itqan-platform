<?php

namespace App\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for Calendar Events
 *
 * Represents a calendar event for display in the UI,
 * standardizing the structure across different session types.
 */
class CalendarEvent
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $color = null,
        public readonly ?string $url = null,
        public readonly ?int $teacherId = null,
        public readonly ?string $teacherName = null,
        public readonly ?int $studentId = null,
        public readonly ?string $studentName = null,
        public readonly array $metadata = [],
        public readonly bool $allDay = false,
        public readonly bool $editable = false,
    ) {}

    /**
     * Create from a Quran session
     */
    public static function fromQuranSession($session): self
    {
        return new self(
            id: 'quran-' . $session->id,
            title: $session->individualCircle?->name ?? $session->circle?->name ?? 'جلسة قرآن',
            start: Carbon::parse($session->scheduled_at),
            end: Carbon::parse($session->scheduled_at)->addMinutes($session->duration_minutes ?? 60),
            type: 'quran_session',
            status: $session->status instanceof \BackedEnum ? $session->status->value : $session->status,
            color: self::getStatusColor($session->status),
            url: route('teacher.quran-sessions.show', $session),
            teacherId: $session->quran_teacher_id,
            teacherName: $session->quranTeacher?->user?->name,
            studentId: $session->student_id,
            studentName: $session->student?->name,
            metadata: [
                'session_type' => $session->session_type,
                'duration_minutes' => $session->duration_minutes,
            ],
        );
    }

    /**
     * Create from an Academic session
     */
    public static function fromAcademicSession($session): self
    {
        return new self(
            id: 'academic-' . $session->id,
            title: $session->academicIndividualLesson?->name ?? 'جلسة أكاديمية',
            start: Carbon::parse($session->scheduled_at),
            end: Carbon::parse($session->scheduled_at)->addMinutes($session->duration_minutes ?? 60),
            type: 'academic_session',
            status: $session->status instanceof \BackedEnum ? $session->status->value : $session->status,
            color: self::getStatusColor($session->status),
            url: route('teacher.academic-sessions.show', $session),
            teacherId: $session->academic_teacher_id,
            teacherName: $session->academicTeacher?->user?->name,
            studentId: $session->student_id,
            studentName: $session->student?->name,
            metadata: [
                'subject' => $session->subject?->name ?? null,
                'duration_minutes' => $session->duration_minutes,
            ],
        );
    }

    /**
     * Create from an Interactive Course session
     */
    public static function fromInteractiveCourseSession($session): self
    {
        $scheduledAt = $session->scheduled_date && $session->scheduled_time
            ? Carbon::parse($session->scheduled_date . ' ' . $session->scheduled_time)
            : Carbon::parse($session->scheduled_at ?? now());

        return new self(
            id: 'interactive-' . $session->id,
            title: $session->course?->title ?? 'جلسة دورة تفاعلية',
            start: $scheduledAt,
            end: $scheduledAt->copy()->addMinutes($session->duration_minutes ?? 60),
            type: 'interactive_course_session',
            status: $session->status instanceof \BackedEnum ? $session->status->value : $session->status,
            color: self::getStatusColor($session->status),
            url: route('interactive-courses.show', $session->course_id),
            teacherId: $session->course?->assignedTeacher?->id,
            teacherName: $session->course?->assignedTeacher?->user?->name,
            metadata: [
                'course_id' => $session->course_id,
                'session_number' => $session->session_number,
                'duration_minutes' => $session->duration_minutes,
            ],
        );
    }

    /**
     * Get color based on session status
     */
    private static function getStatusColor(mixed $status): string
    {
        $statusValue = $status instanceof \BackedEnum ? $status->value : $status;

        return match ($statusValue) {
            'scheduled' => '#3B82F6', // Blue
            'live', 'in_progress' => '#10B981', // Green
            'completed' => '#6B7280', // Gray
            'cancelled' => '#EF4444', // Red
            'rescheduled' => '#F59E0B', // Amber
            default => '#8B5CF6', // Purple
        };
    }

    /**
     * Convert to FullCalendar-compatible array
     */
    public function toFullCalendarFormat(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'allDay' => $this->allDay,
            'color' => $this->color,
            'url' => $this->url,
            'editable' => $this->editable,
            'extendedProps' => [
                'type' => $this->type,
                'status' => $this->status,
                'teacherId' => $this->teacherId,
                'teacherName' => $this->teacherName,
                'studentId' => $this->studentId,
                'studentName' => $this->studentName,
                ...$this->metadata,
            ],
        ];
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start->toDateTimeString(),
            'end' => $this->end->toDateTimeString(),
            'type' => $this->type,
            'status' => $this->status,
            'color' => $this->color,
            'url' => $this->url,
            'teacher_id' => $this->teacherId,
            'teacher_name' => $this->teacherName,
            'student_id' => $this->studentId,
            'student_name' => $this->studentName,
            'all_day' => $this->allDay,
            'editable' => $this->editable,
            'metadata' => $this->metadata,
        ];
    }
}
