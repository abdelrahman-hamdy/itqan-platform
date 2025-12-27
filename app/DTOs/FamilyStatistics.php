<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * Data Transfer Object for Family/Parent Dashboard Statistics
 *
 * Aggregates statistics across all children for parent dashboard display.
 */
class FamilyStatistics
{
    public function __construct(
        public readonly int $totalChildren,
        public readonly int $activeSubscriptions,
        public readonly int $upcomingSessions,
        public readonly int $completedSessions,
        public readonly int $totalCertificates,
        public readonly float $averageAttendanceRate,
        public readonly float $totalPaymentsThisMonth,
        public readonly float $pendingPayments,
        public readonly array $childrenStats = [],
        public readonly array $recentActivities = [],
    ) {}

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
            completedSessions: $sessionCounts['completed'] ?? 0,
            totalCertificates: $sessionCounts['certificates'] ?? 0,
            averageAttendanceRate: $sessionCounts['attendance_rate'] ?? 0.0,
            totalPaymentsThisMonth: $paymentData['this_month'] ?? 0.0,
            pendingPayments: $paymentData['pending'] ?? 0.0,
            childrenStats: $childrenStats,
            recentActivities: $recentActivities,
        );
    }

    /**
     * Create empty statistics
     */
    public static function empty(): self
    {
        return new self(
            totalChildren: 0,
            activeSubscriptions: 0,
            upcomingSessions: 0,
            completedSessions: 0,
            totalCertificates: 0,
            averageAttendanceRate: 0.0,
            totalPaymentsThisMonth: 0.0,
            pendingPayments: 0.0,
        );
    }

    /**
     * Check if family has active content
     */
    public function hasActiveContent(): bool
    {
        return $this->activeSubscriptions > 0 || $this->upcomingSessions > 0;
    }

    /**
     * Get formatted attendance rate
     */
    public function getFormattedAttendanceRate(): string
    {
        return number_format($this->averageAttendanceRate, 1) . '%';
    }

    /**
     * Get formatted payments
     */
    public function getFormattedPaymentsThisMonth(): string
    {
        return number_format($this->totalPaymentsThisMonth, 2) . ' ر.س';
    }

    /**
     * Get formatted pending payments
     */
    public function getFormattedPendingPayments(): string
    {
        return number_format($this->pendingPayments, 2) . ' ر.س';
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'total_children' => $this->totalChildren,
            'active_subscriptions' => $this->activeSubscriptions,
            'upcoming_sessions' => $this->upcomingSessions,
            'completed_sessions' => $this->completedSessions,
            'total_certificates' => $this->totalCertificates,
            'average_attendance_rate' => $this->averageAttendanceRate,
            'average_attendance_rate_formatted' => $this->getFormattedAttendanceRate(),
            'total_payments_this_month' => $this->totalPaymentsThisMonth,
            'total_payments_this_month_formatted' => $this->getFormattedPaymentsThisMonth(),
            'pending_payments' => $this->pendingPayments,
            'pending_payments_formatted' => $this->getFormattedPendingPayments(),
            'has_active_content' => $this->hasActiveContent(),
            'children_stats' => $this->childrenStats,
            'recent_activities' => $this->recentActivities,
        ];
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
