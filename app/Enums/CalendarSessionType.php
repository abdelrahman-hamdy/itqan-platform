<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;

/**
 * Calendar Session Type Enum
 *
 * Defines all session types that can be displayed on the teacher calendar.
 * Used for type-safe event identification, color coding, and role-based filtering.
 *
 * @see \App\Filament\Shared\Widgets\UnifiedCalendarWidget
 * @see \App\ValueObjects\CalendarEventId
 */
enum CalendarSessionType: string
{
    case QURAN_INDIVIDUAL = 'quran_individual';
    case QURAN_GROUP = 'quran_group';
    case QURAN_TRIAL = 'quran_trial';
    case ACADEMIC_PRIVATE = 'academic_private';
    case INTERACTIVE_COURSE = 'interactive_course';

    /**
     * Get the event ID prefix for this session type.
     * Used to create unique, type-safe event IDs.
     *
     * Format: {prefix}-{id} (e.g., 'qi-123', 'ap-456')
     */
    public function eventIdPrefix(): string
    {
        return match ($this) {
            self::QURAN_INDIVIDUAL => 'qi',
            self::QURAN_GROUP => 'qg',
            self::QURAN_TRIAL => 'qt',
            self::ACADEMIC_PRIVATE => 'ap',
            self::INTERACTIVE_COURSE => 'ic',
        };
    }

    /**
     * Get the Arabic label for this session type.
     */
    public function label(): string
    {
        return match ($this) {
            self::QURAN_INDIVIDUAL => __('calendar.session_types.quran_individual'),
            self::QURAN_GROUP => __('calendar.session_types.quran_group'),
            self::QURAN_TRIAL => __('calendar.session_types.quran_trial'),
            self::ACADEMIC_PRIVATE => __('calendar.session_types.academic_private'),
            self::INTERACTIVE_COURSE => __('calendar.session_types.interactive_course'),
        };
    }

    /**
     * Get the fallback Arabic label (for when translations aren't available).
     */
    public function fallbackLabel(): string
    {
        return match ($this) {
            self::QURAN_INDIVIDUAL => 'حلقة فردية',
            self::QURAN_GROUP => 'حلقة جماعية',
            self::QURAN_TRIAL => 'جلسة تجريبية',
            self::ACADEMIC_PRIVATE => 'درس خاص',
            self::INTERACTIVE_COURSE => 'دورة تفاعلية',
        };
    }

    /**
     * Get the hex color for this session type on the calendar.
     */
    public function hexColor(): string
    {
        return match ($this) {
            self::QURAN_INDIVIDUAL => '#6366f1',  // indigo-500
            self::QURAN_GROUP => '#22c55e',       // green-500
            self::QURAN_TRIAL => '#eab308',       // yellow-500
            self::ACADEMIC_PRIVATE => '#3B82F6',  // blue-500
            self::INTERACTIVE_COURSE => '#10B981', // emerald-500
        };
    }

    /**
     * Get the Tailwind color class for this session type.
     */
    public function tailwindColor(): string
    {
        return match ($this) {
            self::QURAN_INDIVIDUAL => 'indigo',
            self::QURAN_GROUP => 'green',
            self::QURAN_TRIAL => 'yellow',
            self::ACADEMIC_PRIVATE => 'blue',
            self::INTERACTIVE_COURSE => 'emerald',
        };
    }

    /**
     * Get the Heroicon name for this session type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::QURAN_INDIVIDUAL => 'heroicon-m-user',
            self::QURAN_GROUP => 'heroicon-m-user-group',
            self::QURAN_TRIAL => 'heroicon-m-clock',
            self::ACADEMIC_PRIVATE => 'heroicon-m-academic-cap',
            self::INTERACTIVE_COURSE => 'heroicon-m-play-circle',
        };
    }

    /**
     * Check if this session type allows drag-and-drop rescheduling.
     */
    public function isMovable(): bool
    {
        return true;
    }

    /**
     * Check if this session type allows resize (duration change).
     */
    public function isResizable(): bool
    {
        // Trial sessions typically have fixed duration
        return $this !== self::QURAN_TRIAL;
    }

    /**
     * Get the Eloquent model class for this session type.
     *
     * @return class-string
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::QURAN_INDIVIDUAL,
            self::QURAN_GROUP,
            self::QURAN_TRIAL => QuranSession::class,
            self::ACADEMIC_PRIVATE => AcademicSession::class,
            self::INTERACTIVE_COURSE => InteractiveCourseSession::class,
        };
    }

    /**
     * Check if this is a Quran session type.
     */
    public function isQuran(): bool
    {
        return in_array($this, [
            self::QURAN_INDIVIDUAL,
            self::QURAN_GROUP,
            self::QURAN_TRIAL,
        ], true);
    }

    /**
     * Check if this is an Academic session type.
     */
    public function isAcademic(): bool
    {
        return in_array($this, [
            self::ACADEMIC_PRIVATE,
            self::INTERACTIVE_COURSE,
        ], true);
    }

    /**
     * Get session types available for Quran teachers.
     *
     * @return array<self>
     */
    public static function forQuranTeacher(): array
    {
        return [
            self::QURAN_INDIVIDUAL,
            self::QURAN_GROUP,
            self::QURAN_TRIAL,
        ];
    }

    /**
     * Get session types available for Academic teachers.
     *
     * @return array<self>
     */
    public static function forAcademicTeacher(): array
    {
        return [
            self::ACADEMIC_PRIVATE,
            self::INTERACTIVE_COURSE,
        ];
    }

    /**
     * Get all session types.
     *
     * @return array<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Parse a session type from an event ID prefix.
     *
     * @throws \InvalidArgumentException If prefix is unknown
     */
    public static function fromEventIdPrefix(string $prefix): self
    {
        return match ($prefix) {
            'qi' => self::QURAN_INDIVIDUAL,
            'qg' => self::QURAN_GROUP,
            'qt' => self::QURAN_TRIAL,
            'ap' => self::ACADEMIC_PRIVATE,
            'ic' => self::INTERACTIVE_COURSE,
            default => throw new \InvalidArgumentException("Unknown event prefix: {$prefix}"),
        };
    }

    /**
     * Determine the session type from a QuranSession model.
     */
    public static function fromQuranSession(QuranSession $session): self
    {
        if ($session->trial_request_id) {
            return self::QURAN_TRIAL;
        }

        return match ($session->session_type) {
            'individual' => self::QURAN_INDIVIDUAL,
            'circle', 'group' => self::QURAN_GROUP,
            default => self::QURAN_INDIVIDUAL,
        };
    }

    /**
     * Get form select options for filtering.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[$type->value] = $type->fallbackLabel();
        }

        return $options;
    }

    /**
     * Get options for Quran teacher filter.
     *
     * @return array<string, string>
     */
    public static function quranOptions(): array
    {
        $options = [];
        foreach (self::forQuranTeacher() as $type) {
            $options[$type->value] = $type->fallbackLabel();
        }

        return $options;
    }

    /**
     * Get options for Academic teacher filter.
     *
     * @return array<string, string>
     */
    public static function academicOptions(): array
    {
        $options = [];
        foreach (self::forAcademicTeacher() as $type) {
            $options[$type->value] = $type->fallbackLabel();
        }

        return $options;
    }
}
