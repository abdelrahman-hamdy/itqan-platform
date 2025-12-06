<?php

namespace App\Enums;

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

    /**
     * Get the label for the notification category
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SESSION => __('notifications.categories.session'),
            self::ATTENDANCE => __('notifications.categories.attendance'),
            self::HOMEWORK => __('notifications.categories.homework'),
            self::PAYMENT => __('notifications.categories.payment'),
            self::MEETING => __('notifications.categories.meeting'),
            self::PROGRESS => __('notifications.categories.progress'),
            self::SYSTEM => __('notifications.categories.system'),
        };
    }
}