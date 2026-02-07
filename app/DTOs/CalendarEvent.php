<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for Calendar Events
 *
 * Represents a calendar event for display in the UI,
 * standardizing the structure across different session types.
 *
 * @property-read string|int $id Event unique identifier
 * @property-read string $title Event title/name
 * @property-read Carbon $start Event start date/time
 * @property-read Carbon $end Event end date/time
 * @property-read string $type Event type (session, meeting, homework, etc.)
 * @property-read string|null $status Event status (scheduled, live, completed, etc.)
 * @property-read string|null $color Event display color (hex or CSS color)
 * @property-read string|null $url Event detail page URL
 * @property-read array $metadata Additional event metadata
 */
readonly class CalendarEvent
{
    public function __construct(
        public string|int $id,
        public string $title,
        public Carbon $start,
        public Carbon $end,
        public string $type,
        public ?string $status = null,
        public ?string $color = null,
        public ?string $url = null,
        public ?int $teacherId = null,
        public ?string $teacherName = null,
        public ?int $studentId = null,
        public ?string $studentName = null,
        public array $metadata = [],
        public bool $allDay = false,
        public bool $editable = false,
    ) {}

    /**
     * Create from a Quran session
     */
    public static function fromQuranSession($session): self
    {
        return new self(
            id: 'quran-'.$session->id,
            title: $session->individualCircle?->name ?? $session->circle?->name ?? 'جلسة قرآن',
            start: Carbon::parse($session->scheduled_at),
            end: Carbon::parse($session->scheduled_at)->addMinutes($session->duration_minutes ?? 60),
            type: 'quran_session',
            status: $session->status instanceof \BackedEnum ? $session->status->value : $session->status,
            color: self::getStatusColor($session->status),
            url: "/teacher-panel/quran-sessions/{$session->id}",
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
            id: 'academic-'.$session->id,
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
            ? Carbon::parse($session->scheduled_date.' '.$session->scheduled_time)
            : Carbon::parse($session->scheduled_at ?? now());

        return new self(
            id: 'interactive-'.$session->id,
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
     * Create a session event
     */
    public static function forSession(
        string|int $id,
        string $title,
        Carbon $start,
        Carbon $end,
        string $sessionType,
        ?string $status = null,
        ?string $url = null,
        array $metadata = []
    ): self {
        $colors = [
            'quran' => '#10b981', // green
            'academic' => '#3b82f6', // blue
            'interactive' => '#8b5cf6', // purple
        ];

        return new self(
            id: $id,
            title: $title,
            start: $start,
            end: $end,
            type: $sessionType,
            status: $status,
            color: $colors[$sessionType] ?? '#6b7280',
            url: $url,
            metadata: $metadata,
        );
    }

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            start: $data['start'] instanceof Carbon ? $data['start'] : Carbon::parse($data['start']),
            end: $data['end'] instanceof Carbon ? $data['end'] : Carbon::parse($data['end']),
            type: $data['type'],
            status: $data['status'] ?? null,
            color: $data['color'] ?? null,
            url: $data['url'] ?? null,
            teacherId: $data['teacherId'] ?? $data['teacher_id'] ?? null,
            teacherName: $data['teacherName'] ?? $data['teacher_name'] ?? null,
            studentId: $data['studentId'] ?? $data['student_id'] ?? null,
            studentName: $data['studentName'] ?? $data['student_name'] ?? null,
            metadata: $data['metadata'] ?? [],
            allDay: (bool) ($data['allDay'] ?? $data['all_day'] ?? false),
            editable: (bool) ($data['editable'] ?? false),
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
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
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

    /**
     * Get event duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        return (int) $this->start->diffInMinutes($this->end);
    }

    /**
     * Check if event is in the past
     */
    public function isPast(): bool
    {
        return $this->end->isPast();
    }

    /**
     * Check if event is ongoing
     */
    public function isOngoing(): bool
    {
        $now = Carbon::now();

        return $this->start->isPast() && $this->end->isFuture();
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start->isFuture();
    }
}
