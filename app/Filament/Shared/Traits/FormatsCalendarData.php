<?php

namespace App\Filament\Shared\Traits;

/**
 * Trait FormatsCalendarData
 *
 * Provides color schemes, status formatting, and event formatting
 * for calendar implementations.
 */
trait FormatsCalendarData
{
    /**
     * Color scheme for different session types
     */
    public const SESSION_COLORS = [
        // Quran session types
        'trial' => '#eab308',      // yellow-500
        'group' => '#22c55e',      // green-500
        'quran_individual' => '#6366f1',  // indigo-500

        // Academic session types
        'academic_individual' => '#3B82F6',  // blue-500
        'interactive_course' => '#10B981',   // green-500

        // Status overrides
        'cancelled' => '#ef4444',  // red-500
        'ongoing' => '#3b82f6',    // blue-500
    ];

    /**
     * Get color for a session based on type and status
     *
     * @param string $sessionType Session type (trial, group, individual, etc.)
     * @param string $status Session status
     * @param bool $isAcademic Whether this is an academic session
     * @return string Hex color code
     */
    protected function getSessionColor(string $sessionType, string $status, bool $isAcademic = false): string
    {
        // Status-based colors take precedence
        if (in_array($status, ['cancelled', 'ongoing'])) {
            return self::SESSION_COLORS[$status];
        }

        // Type-based colors
        if ($sessionType === 'trial') {
            return self::SESSION_COLORS['trial'];
        }

        if ($sessionType === 'group') {
            return self::SESSION_COLORS['group'];
        }

        if ($sessionType === 'individual') {
            return $isAcademic
                ? self::SESSION_COLORS['academic_individual']
                : self::SESSION_COLORS['quran_individual'];
        }

        if ($sessionType === 'interactive_course') {
            return self::SESSION_COLORS['interactive_course'];
        }

        // Default fallback
        return self::SESSION_COLORS['group'];
    }

    /**
     * Get color scheme configuration for a teacher type
     *
     * @param string $teacherType Teacher type ('quran_teacher' or 'academic_teacher')
     * @return array Color scheme configuration
     */
    protected function getColorScheme(string $teacherType): array
    {
        if ($teacherType === 'academic_teacher') {
            return [
                'private_lesson' => [
                    'color' => self::SESSION_COLORS['academic_individual'],
                    'label' => 'درس خاص',
                    'icon' => '👤',
                ],
                'interactive_course' => [
                    'color' => self::SESSION_COLORS['interactive_course'],
                    'label' => 'دورة تفاعلية',
                    'icon' => '👥',
                ],
            ];
        }

        // Quran teacher
        return [
            'trial' => [
                'color' => self::SESSION_COLORS['trial'],
                'label' => 'جلسة تجريبية',
                'icon' => '🎯',
            ],
            'group' => [
                'color' => self::SESSION_COLORS['group'],
                'label' => 'حلقة جماعية',
                'icon' => '👥',
            ],
            'individual' => [
                'color' => self::SESSION_COLORS['quran_individual'],
                'label' => 'حلقة فردية',
                'icon' => '👤',
            ],
        ];
    }

    /**
     * Format status badge for display
     *
     * @param string $status Session status
     * @return array Badge configuration [color, label]
     */
    protected function formatStatusBadge(string $status): array
    {
        return match ($status) {
            'unscheduled' => ['gray', 'غير مجدولة'],
            'scheduled' => ['warning', 'مجدولة'],
            'ready' => ['info', 'جاهزة للبدء'],
            'ongoing' => ['primary', 'جارية'],
            'completed' => ['success', 'مكتملة'],
            'cancelled' => ['danger', 'ملغية'],
            'absent' => ['warning', 'غياب الطالب'],
            'teacher_absent' => ['danger', 'غياب المعلم'],
            default => ['gray', $status],
        };
    }

    /**
     * Format schedule status for display
     *
     * @param string $status Schedule status
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
            'scheduled' => [
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
