<?php

namespace App\Filament\Shared\Traits;

use App\Enums\SessionStatus;

/**
 * Trait FormatsCalendarData
 *
 * Provides color schemes, status formatting, and event formatting
 * for calendar implementations.
 */
trait FormatsCalendarData
{
    /**
     * Color scheme for different session types (non-status based)
     */
    public const SESSION_TYPE_COLORS = [
        // Quran session types
        'trial' => '#eab308',           // yellow-500
        'group' => '#22c55e',           // green-500
        'quran_individual' => '#6366f1', // indigo-500

        // Academic session types
        'academic_individual' => '#3B82F6',  // blue-500
        'interactive_course' => '#10B981',   // emerald-500
    ];

    /**
     * Get color for a session based on type and status
     * Uses enum hexColor() for status-based colors
     *
     * @param  string  $sessionType  Session type (trial, group, individual, etc.)
     * @param  SessionStatus|string  $status  Session status
     * @param  bool  $isAcademic  Whether this is an academic session
     * @return string Hex color code
     */
    protected function getSessionColor(string $sessionType, SessionStatus|string $status, bool $isAcademic = false): string
    {
        // Convert string to enum if needed
        $statusEnum = $status instanceof SessionStatus
            ? $status
            : SessionStatus::tryFrom($status);

        // Status-based colors take precedence for certain statuses
        if ($statusEnum) {
            if (in_array($statusEnum, [SessionStatus::CANCELLED, SessionStatus::ONGOING, SessionStatus::ABSENT])) {
                return $statusEnum->hexColor();
            }
        }

        // Type-based colors
        return match ($sessionType) {
            'trial' => self::SESSION_TYPE_COLORS['trial'],
            'group' => self::SESSION_TYPE_COLORS['group'],
            'individual' => $isAcademic
                ? self::SESSION_TYPE_COLORS['academic_individual']
                : self::SESSION_TYPE_COLORS['quran_individual'],
            'interactive_course' => self::SESSION_TYPE_COLORS['interactive_course'],
            default => self::SESSION_TYPE_COLORS['group'],
        };
    }

    /**
     * Get hex color directly from session status enum
     */
    protected function getStatusHexColor(SessionStatus|string $status): string
    {
        $statusEnum = $status instanceof SessionStatus
            ? $status
            : SessionStatus::tryFrom($status);

        return $statusEnum?->hexColor() ?? '#6B7280';
    }

    /**
     * Get color scheme configuration for a teacher type
     *
     * @param  string  $teacherType  Teacher type ('quran_teacher' or 'academic_teacher')
     * @return array Color scheme configuration
     */
    protected function getColorScheme(string $teacherType): array
    {
        if ($teacherType === 'academic_teacher') {
            return [
                'private_lesson' => [
                    'color' => self::SESSION_TYPE_COLORS['academic_individual'],
                    'label' => 'درس خاص',
                    'icon' => '👤',
                ],
                'interactive_course' => [
                    'color' => self::SESSION_TYPE_COLORS['interactive_course'],
                    'label' => 'دورة تفاعلية',
                    'icon' => '👥',
                ],
            ];
        }

        // Quran teacher
        return [
            'trial' => [
                'color' => self::SESSION_TYPE_COLORS['trial'],
                'label' => 'جلسة تجريبية',
                'icon' => '🎯',
            ],
            'group' => [
                'color' => self::SESSION_TYPE_COLORS['group'],
                'label' => 'حلقة جماعية',
                'icon' => '👥',
            ],
            'individual' => [
                'color' => self::SESSION_TYPE_COLORS['quran_individual'],
                'label' => 'حلقة فردية',
                'icon' => '👤',
            ],
        ];
    }

    /**
     * Get status color indicators for calendar legend
     * Uses enum for consistent colors and labels
     */
    protected function getStatusColorIndicators(): array
    {
        return [
            [
                'status' => SessionStatus::SCHEDULED,
                'color' => SessionStatus::SCHEDULED->hexColor(),
                'label' => SessionStatus::SCHEDULED->label(),
            ],
            [
                'status' => SessionStatus::READY,
                'color' => SessionStatus::READY->hexColor(),
                'label' => SessionStatus::READY->label(),
            ],
            [
                'status' => SessionStatus::ONGOING,
                'color' => SessionStatus::ONGOING->hexColor(),
                'label' => SessionStatus::ONGOING->label(),
            ],
            [
                'status' => SessionStatus::COMPLETED,
                'color' => SessionStatus::COMPLETED->hexColor(),
                'label' => SessionStatus::COMPLETED->label(),
            ],
            [
                'status' => SessionStatus::CANCELLED,
                'color' => SessionStatus::CANCELLED->hexColor(),
                'label' => SessionStatus::CANCELLED->label(),
            ],
            [
                'status' => SessionStatus::ABSENT,
                'color' => SessionStatus::ABSENT->hexColor(),
                'label' => SessionStatus::ABSENT->label(),
            ],
        ];
    }

    /**
     * Format status badge for display using enum
     *
     * @param  SessionStatus|string  $status  Session status
     * @return array Badge configuration [color, label]
     */
    protected function formatStatusBadge(SessionStatus|string $status): array
    {
        $statusEnum = $status instanceof SessionStatus
            ? $status
            : SessionStatus::tryFrom($status);

        if ($statusEnum) {
            return [$statusEnum->color(), $statusEnum->label()];
        }

        // Fallback for non-standard statuses
        return match ($status) {
            'teacher_absent' => ['danger', 'غياب المعلم'],
            default => ['gray', $status],
        };
    }

    /**
     * Format schedule status for display
     *
     * @param  string  $status  Schedule status
     * @return array Status configuration [label, color, icon]
     */
    protected function formatScheduleStatus(string $status): array
    {
        return match ($status) {
            'not_scheduled' => [
                'label' => 'غير مجدولة',
                'color' => 'text-red-600',
                'icon' => '❌',
                'bgColor' => 'bg-red-50',
            ],
            'partially_scheduled' => [
                'label' => 'مجدولة جزئياً',
                'color' => 'text-yellow-600',
                'icon' => '⏳',
                'bgColor' => 'bg-yellow-50',
            ],
            'fully_scheduled' => [
                'label' => 'مجدولة بالكامل',
                'color' => 'text-green-600',
                'icon' => '✅',
                'bgColor' => 'bg-green-50',
            ],
            SessionStatus::SCHEDULED->value => [
                'label' => 'مجدولة',
                'color' => 'text-green-600',
                'icon' => '✅',
                'bgColor' => 'bg-green-50',
            ],
            default => [
                'label' => $status,
                'color' => 'text-gray-600',
                'icon' => '❓',
                'bgColor' => 'bg-gray-50',
            ],
        };
    }
}
