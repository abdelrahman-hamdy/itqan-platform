<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Enums\CalendarSessionType;
use App\Models\User;

/**
 * Calendar Configuration Class
 *
 * Provides role-based configuration for the unified calendar widget.
 * Determines which session types are visible and what actions are allowed.
 *
 * This class supports three modes:
 * 1. Quran Teacher - sees Quran individual/group/trial sessions
 * 2. Academic Teacher - sees academic private lessons and interactive courses
 * 3. Supervisor (future) - can manage any teacher's calendar
 *
 * @see \App\Filament\Shared\Widgets\UnifiedCalendarWidget
 */
final readonly class CalendarConfiguration
{
    /**
     * @param  array<CalendarSessionType>  $sessionTypes  Session types visible on calendar
     * @param  bool  $allowDragDrop  Whether drag-and-drop rescheduling is enabled
     * @param  bool  $allowResize  Whether resize (duration change) is enabled
     * @param  bool  $showColorLegend  Whether to show the color legend widget
     * @param  bool  $showDaySummary  Whether clicking a day shows session summary
     * @param  CalendarSessionType|null  $filterSessionType  Filter to show only specific type
     * @param  int|null  $filterCircleId  Filter to show only specific circle
     * @param  bool  $supervisorMode  Whether in supervisor mode
     * @param  int|null  $targetTeacherId  Teacher ID when in supervisor mode
     */
    public function __construct(
        public array $sessionTypes,
        public bool $allowDragDrop = true,
        public bool $allowResize = true,
        public bool $showColorLegend = true,
        public bool $showDaySummary = true,
        public ?CalendarSessionType $filterSessionType = null,
        public ?int $filterCircleId = null,
        public bool $supervisorMode = false,
        public ?int $targetTeacherId = null,
    ) {}

    /**
     * Create configuration for a Quran teacher.
     */
    public static function forQuranTeacher(): self
    {
        return new self(
            sessionTypes: CalendarSessionType::forQuranTeacher(),
            allowDragDrop: true,
            allowResize: true,
            showColorLegend: true,
            showDaySummary: true,
        );
    }

    /**
     * Create configuration for an Academic teacher.
     */
    public static function forAcademicTeacher(): self
    {
        return new self(
            sessionTypes: CalendarSessionType::forAcademicTeacher(),
            allowDragDrop: true,
            allowResize: true,
            showColorLegend: true,
            showDaySummary: true,
        );
    }

    /**
     * Create configuration for a dual-role teacher (both Quran and Academic).
     */
    public static function forDualTeacher(): self
    {
        return new self(
            sessionTypes: CalendarSessionType::all(),
            allowDragDrop: true,
            allowResize: true,
            showColorLegend: true,
            showDaySummary: true,
        );
    }

    /**
     * Create configuration for a supervisor managing a specific teacher.
     * Future feature - prepared for when supervisor scheduling is enabled.
     *
     * @param  int  $targetTeacherId  The teacher whose calendar to manage
     * @param  array<CalendarSessionType>  $sessionTypes  Session types the supervisor can manage
     */
    public static function forSupervisor(int $targetTeacherId, array $sessionTypes): self
    {
        return new self(
            sessionTypes: $sessionTypes,
            allowDragDrop: true,
            allowResize: true,
            showColorLegend: true,
            showDaySummary: true,
            supervisorMode: true,
            targetTeacherId: $targetTeacherId,
        );
    }

    /**
     * Create configuration based on a user's roles.
     *
     * @throws \InvalidArgumentException If user is not a teacher
     */
    public static function forUser(User $user): self
    {
        $isQuranTeacher = $user->isQuranTeacher();
        $isAcademicTeacher = $user->isAcademicTeacher();

        // Check if user is a dual-role teacher
        if ($isQuranTeacher && $isAcademicTeacher) {
            return self::forDualTeacher();
        }

        if ($isQuranTeacher) {
            return self::forQuranTeacher();
        }

        if ($isAcademicTeacher) {
            return self::forAcademicTeacher();
        }

        throw new \InvalidArgumentException(
            'User must be a Quran teacher or Academic teacher to access the calendar'
        );
    }

    /**
     * Check if a specific session type is enabled.
     */
    public function hasSessionType(CalendarSessionType $type): bool
    {
        if ($this->filterSessionType !== null) {
            return $this->filterSessionType === $type;
        }

        return in_array($type, $this->sessionTypes, true);
    }

    /**
     * Get all enabled session types.
     *
     * @return array<CalendarSessionType>
     */
    public function getSessionTypes(): array
    {
        if ($this->filterSessionType !== null) {
            return [$this->filterSessionType];
        }

        return $this->sessionTypes;
    }

    /**
     * Check if this is a Quran-only configuration.
     */
    public function isQuranOnly(): bool
    {
        foreach ($this->sessionTypes as $type) {
            if (! $type->isQuran()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this is an Academic-only configuration.
     */
    public function isAcademicOnly(): bool
    {
        foreach ($this->sessionTypes as $type) {
            if (! $type->isAcademic()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if in supervisor mode.
     */
    public function isSupervisorMode(): bool
    {
        return $this->supervisorMode && $this->targetTeacherId !== null;
    }

    /**
     * Get the target teacher ID (for supervisor mode).
     */
    public function getTargetTeacherId(): ?int
    {
        return $this->targetTeacherId;
    }

    /**
     * Create a new configuration with a session type filter applied.
     */
    public function withSessionTypeFilter(CalendarSessionType $type): self
    {
        return new self(
            sessionTypes: $this->sessionTypes,
            allowDragDrop: $this->allowDragDrop,
            allowResize: $this->allowResize,
            showColorLegend: $this->showColorLegend,
            showDaySummary: $this->showDaySummary,
            filterSessionType: $type,
            filterCircleId: $this->filterCircleId,
            supervisorMode: $this->supervisorMode,
            targetTeacherId: $this->targetTeacherId,
        );
    }

    /**
     * Create a new configuration with a circle filter applied.
     */
    public function withCircleFilter(int $circleId): self
    {
        return new self(
            sessionTypes: $this->sessionTypes,
            allowDragDrop: $this->allowDragDrop,
            allowResize: $this->allowResize,
            showColorLegend: $this->showColorLegend,
            showDaySummary: $this->showDaySummary,
            filterSessionType: $this->filterSessionType,
            filterCircleId: $circleId,
            supervisorMode: $this->supervisorMode,
            targetTeacherId: $this->targetTeacherId,
        );
    }

    /**
     * Create a new configuration with read-only mode (no drag/drop/resize).
     */
    public function asReadOnly(): self
    {
        return new self(
            sessionTypes: $this->sessionTypes,
            allowDragDrop: false,
            allowResize: false,
            showColorLegend: $this->showColorLegend,
            showDaySummary: $this->showDaySummary,
            filterSessionType: $this->filterSessionType,
            filterCircleId: $this->filterCircleId,
            supervisorMode: $this->supervisorMode,
            targetTeacherId: $this->targetTeacherId,
        );
    }

    /**
     * Convert to array for debugging/logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sessionTypes' => array_map(fn ($t) => $t->value, $this->sessionTypes),
            'allowDragDrop' => $this->allowDragDrop,
            'allowResize' => $this->allowResize,
            'showColorLegend' => $this->showColorLegend,
            'showDaySummary' => $this->showDaySummary,
            'filterSessionType' => $this->filterSessionType?->value,
            'filterCircleId' => $this->filterCircleId,
            'supervisorMode' => $this->supervisorMode,
            'targetTeacherId' => $this->targetTeacherId,
        ];
    }
}
