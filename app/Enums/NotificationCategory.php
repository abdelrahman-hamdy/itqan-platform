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

    /**
     * Get localized label
     */
    public function label(): string
    {
        return __('enums.notification_category.' . $this->value);
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
        };
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
        };
    }
}