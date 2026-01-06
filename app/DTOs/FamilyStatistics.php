<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * Data Transfer Object for family/parent statistics
 *
 * Used by ParentDashboardService to return aggregated statistics about
 * a parent's children, subscriptions, sessions, and payments.
 *
 * @property-read int $totalChildren Number of children registered
 * @property-read int $activeSubscriptions Number of active subscriptions across all children
 * @property-read int $upcomingSessions Number of upcoming sessions scheduled
 * @property-read float $totalPayments Total amount paid (in SAR)
 * @property-read float $attendanceRate Overall attendance rate (0-100)
 */
readonly class FamilyStatistics
{
    public function __construct(
        public int $totalChildren,
        public int $activeSubscriptions,
        public int $upcomingSessions,
        public float $totalPayments,
        public float $attendanceRate,
        public int $completedSessions = 0,
        public int $totalCertificates = 0,
        public array $childrenStats = [],
        public array $recentActivities = [],
    ) {}

    /**
     * Create instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            totalChildren: (int) ($data['totalChildren'] ?? $data['total_children'] ?? 0),
            activeSubscriptions: (int) ($data['activeSubscriptions'] ?? $data['active_subscriptions'] ?? 0),
            upcomingSessions: (int) ($data['upcomingSessions'] ?? $data['upcoming_sessions'] ?? 0),
            totalPayments: (float) ($data['totalPayments'] ?? $data['total_payments'] ?? 0),
            attendanceRate: (float) ($data['attendanceRate'] ?? $data['attendance_rate'] ?? 0),
            completedSessions: (int) ($data['completedSessions'] ?? $data['completed_sessions'] ?? 0),
            totalCertificates: (int) ($data['totalCertificates'] ?? $data['total_certificates'] ?? 0),
            childrenStats: $data['childrenStats'] ?? $data['children_stats'] ?? [],
            recentActivities: $data['recentActivities'] ?? $data['recent_activities'] ?? [],
        );
    }

    /**
     * Create from parent data
     */
    public static function fromParentData(
        Collection $children,
        array $subscriptionCounts,
        array $sessionCounts,
        array $paymentData,
        array $childrenStats = [],
        array $recentActivities = []
    ): self {
        return new self(
            totalChildren: $children->count(),
            activeSubscriptions: $subscriptionCounts['active'] ?? 0,
            upcomingSessions: $sessionCounts['upcoming'] ?? 0,
            totalPayments: $paymentData['total'] ?? 0.0,
            attendanceRate: $sessionCounts['attendance_rate'] ?? 0.0,
            completedSessions: $sessionCounts['completed'] ?? 0,
            totalCertificates: $sessionCounts['certificates'] ?? 0,
            childrenStats: $childrenStats,
            recentActivities: $recentActivities,
        );
    }

    /**
     * Create empty statistics (for new parents)
     */
    public static function empty(): self
    {
        return new self(
            totalChildren: 0,
            activeSubscriptions: 0,
            upcomingSessions: 0,
            totalPayments: 0.0,
            attendanceRate: 0.0,
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'total_children' => $this->totalChildren,
            'active_subscriptions' => $this->activeSubscriptions,
            'upcoming_sessions' => $this->upcomingSessions,
            'total_payments' => $this->totalPayments,
            'attendance_rate' => $this->attendanceRate,
            'completed_sessions' => $this->completedSessions,
            'total_certificates' => $this->totalCertificates,
            'children_stats' => $this->childrenStats,
            'recent_activities' => $this->recentActivities,
        ];
    }

    /**
     * Get formatted payment amount with currency
     */
    public function getFormattedPayments(): string
    {
        return number_format($this->totalPayments, 2).' SAR';
    }

    /**
     * Get formatted attendance rate as percentage
     */
    public function getFormattedAttendanceRate(): string
    {
        return number_format($this->attendanceRate, 1).'%';
    }

    /**
     * Check if family has any active engagement
     */
    public function hasActiveEngagement(): bool
    {
        return $this->activeSubscriptions > 0 || $this->upcomingSessions > 0;
    }

    /**
     * Get average subscriptions per child
     */
    public function getAverageSubscriptionsPerChild(): float
    {
        if ($this->totalChildren === 0) {
            return 0.0;
        }

        return round($this->activeSubscriptions / $this->totalChildren, 2);
    }

    /**
     * Get attendance status based on rate
     */
    public function getAttendanceStatus(): string
    {
        return match (true) {
            $this->attendanceRate >= 90 => 'excellent',
            $this->attendanceRate >= 75 => 'good',
            $this->attendanceRate >= 60 => 'fair',
            default => 'needs_improvement',
        };
    }

    /**
     * Convert to dashboard widget array
     */
    public function toWidgetArray(): array
    {
        return [
            [
                'label' => 'الأبناء',
                'value' => $this->totalChildren,
                'icon' => 'users',
            ],
            [
                'label' => 'الاشتراكات النشطة',
                'value' => $this->activeSubscriptions,
                'icon' => 'credit-card',
            ],
            [
                'label' => 'الجلسات القادمة',
                'value' => $this->upcomingSessions,
                'icon' => 'calendar',
            ],
            [
                'label' => 'نسبة الحضور',
                'value' => $this->getFormattedAttendanceRate(),
                'icon' => 'check-circle',
            ],
            [
                'label' => 'الشهادات',
                'value' => $this->totalCertificates,
                'icon' => 'award',
            ],
        ];
    }
}
