<?php

namespace App\Enums;

/**
 * Notification Category Enum
 *
 * Categorizes notifications for filtering and display.
 *
 * Categories:
 * - SESSION: Session-related notifications
 * - ATTENDANCE: Attendance tracking notifications
 * - HOMEWORK: Homework assignments and submissions
 * - PAYMENT: Payment and billing notifications
 * - MEETING: Video meeting notifications
 * - PROGRESS: Student progress updates
 * - SYSTEM: System announcements
 *
 * @see \App\Models\Notification
 * @see \App\Services\NotificationService
 */
enum NotificationCategory: string
{
    case SESSION = 'session';
    case ATTENDANCE = 'attendance';
    case HOMEWORK = 'homework';
    case PAYMENT = 'payment';
    case MEETING = 'meeting';
    case PROGRESS = 'progress';
    case SYSTEM = 'system';
    case REVIEW = 'review';       // Yellow with star - for reviews
    case TRIAL = 'trial';         // Orange with gift - for trial sessions
    case ALERT = 'alert';         // Red - for urgent/negative notifications

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.notification_category.'.$this->value);
    }

    /**
     * Get the icon for the notification category
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::SESSION => 'heroicon-o-academic-cap',
            self::ATTENDANCE => 'heroicon-o-check-circle',
            self::HOMEWORK => 'heroicon-o-document-text',
            self::PAYMENT => 'heroicon-o-credit-card',
            self::MEETING => 'heroicon-o-video-camera',
            self::PROGRESS => 'heroicon-o-chart-bar',
            self::SYSTEM => 'heroicon-o-cog',
            self::REVIEW => 'heroicon-o-star',
            self::TRIAL => 'heroicon-o-gift',
            self::ALERT => 'heroicon-o-exclamation-triangle',
        };
    }

    /**
     * Get the color for the notification category
     */
    public function getColor(): string
    {
        return match ($this) {
            self::SESSION => 'primary',
            self::ATTENDANCE => 'success',
            self::HOMEWORK => 'warning',
            self::PAYMENT => 'info',
            self::MEETING => 'purple',
            self::PROGRESS => 'indigo',
            self::SYSTEM => 'gray',
            self::REVIEW => 'warning',
            self::TRIAL => 'orange',
            self::ALERT => 'danger',
        };
    }

    /**
     * Get the Filament-compatible color name for this category.
     * Used for Filament database notification rendering (iconColor).
     */
    public function getFilamentColor(): string
    {
        return $this->getColor();
    }

    /**
     * Get the Tailwind color class for the notification category
     */
    public function getTailwindColor(): string
    {
        return match ($this) {
            self::SESSION => 'bg-blue-100 text-blue-800',
            self::ATTENDANCE => 'bg-green-100 text-green-800',
            self::HOMEWORK => 'bg-yellow-100 text-yellow-800',
            self::PAYMENT => 'bg-cyan-100 text-cyan-800',
            self::MEETING => 'bg-purple-100 text-purple-800',
            self::PROGRESS => 'bg-indigo-100 text-indigo-800',
            self::SYSTEM => 'bg-gray-100 text-gray-800',
            self::REVIEW => 'bg-yellow-100 text-yellow-800',
            self::TRIAL => 'bg-orange-100 text-orange-800',
            self::ALERT => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get the filter tabs for the notification UI.
     * Each tab groups related categories together.
     *
     * @return array<int, array{key: string, label_key: string, categories: list<self>}>
     */
    public static function filterTabs(): array
    {
        return [
            [
                'key' => 'sessions',
                'label_key' => 'enums.notification_filter.sessions',
                'categories' => [self::SESSION, self::ATTENDANCE, self::MEETING, self::TRIAL],
            ],
            [
                'key' => 'homework',
                'label_key' => 'enums.notification_filter.homework',
                'categories' => [self::HOMEWORK],
            ],
            [
                'key' => 'payments',
                'label_key' => 'enums.notification_filter.payments',
                'categories' => [self::PAYMENT],
            ],
            [
                'key' => 'progress',
                'label_key' => 'enums.notification_filter.progress',
                'categories' => [self::PROGRESS, self::REVIEW],
            ],
        ];
    }

    /**
     * Resolve a filter tab key to the corresponding DB category values.
     *
     * @return list<string>|null  null means "show all"
     */
    public static function resolveCategoriesForTab(?string $tabKey): ?array
    {
        if ($tabKey === null) {
            return null;
        }

        foreach (self::filterTabs() as $tab) {
            if ($tab['key'] === $tabKey) {
                return array_map(fn (self $c) => $c->value, $tab['categories']);
            }
        }

        return null;
    }
}
