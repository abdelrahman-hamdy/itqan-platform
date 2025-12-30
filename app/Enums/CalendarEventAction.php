<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Calendar Event Action Enum
 *
 * Defines all actions that can be performed on calendar events.
 * Used for action buttons and permission checks.
 *
 * @see \App\Filament\Shared\Widgets\UnifiedCalendarWidget
 */
enum CalendarEventAction: string
{
    case VIEW = 'view';
    case EDIT = 'edit';
    case RESCHEDULE = 'reschedule';
    case RESIZE = 'resize';
    case CANCEL = 'cancel';
    case START = 'start';
    case JOIN = 'join';

    /**
     * Get the Arabic label for this action.
     */
    public function label(): string
    {
        return match ($this) {
            self::VIEW => 'عرض التفاصيل',
            self::EDIT => 'تعديل',
            self::RESCHEDULE => 'إعادة الجدولة',
            self::RESIZE => 'تغيير المدة',
            self::CANCEL => 'إلغاء الجلسة',
            self::START => 'بدء الجلسة',
            self::JOIN => 'الانضمام للجلسة',
        };
    }

    /**
     * Get the Heroicon name for this action.
     */
    public function icon(): string
    {
        return match ($this) {
            self::VIEW => 'heroicon-o-eye',
            self::EDIT => 'heroicon-o-pencil',
            self::RESCHEDULE => 'heroicon-o-calendar',
            self::RESIZE => 'heroicon-o-arrows-pointing-out',
            self::CANCEL => 'heroicon-o-x-circle',
            self::START => 'heroicon-o-play',
            self::JOIN => 'heroicon-o-video-camera',
        };
    }

    /**
     * Get the Filament color for this action.
     */
    public function color(): string
    {
        return match ($this) {
            self::VIEW => 'info',
            self::EDIT => 'warning',
            self::RESCHEDULE => 'primary',
            self::RESIZE => 'gray',
            self::CANCEL => 'danger',
            self::START => 'success',
            self::JOIN => 'success',
        };
    }

    /**
     * Check if this action requires confirmation.
     */
    public function requiresConfirmation(): bool
    {
        return match ($this) {
            self::CANCEL => true,
            default => false,
        };
    }

    /**
     * Check if this action is destructive.
     */
    public function isDestructive(): bool
    {
        return $this === self::CANCEL;
    }

    /**
     * Check if this action requires the session to be in a specific status.
     *
     * @return array<SessionStatus>|null Allowed statuses, or null if no restriction
     */
    public function allowedStatuses(): ?array
    {
        return match ($this) {
            self::VIEW => null, // Can view any status
            self::EDIT => [SessionStatus::UNSCHEDULED, SessionStatus::SCHEDULED, SessionStatus::READY],
            self::RESCHEDULE => [SessionStatus::SCHEDULED, SessionStatus::READY],
            self::RESIZE => [SessionStatus::SCHEDULED, SessionStatus::READY],
            self::CANCEL => [SessionStatus::SCHEDULED, SessionStatus::READY, SessionStatus::ONGOING],
            self::START => [SessionStatus::SCHEDULED, SessionStatus::READY],
            self::JOIN => [SessionStatus::READY, SessionStatus::ONGOING],
        };
    }

    /**
     * Check if this action is allowed for the given session status.
     */
    public function isAllowedForStatus(SessionStatus $status): bool
    {
        $allowed = $this->allowedStatuses();

        if ($allowed === null) {
            return true;
        }

        return in_array($status, $allowed, true);
    }

    /**
     * Get actions available for a session based on status.
     *
     * @return array<self>
     */
    public static function forStatus(SessionStatus $status): array
    {
        return array_filter(
            self::cases(),
            fn (self $action) => $action->isAllowedForStatus($status)
        );
    }

    /**
     * Get all view-related actions.
     *
     * @return array<self>
     */
    public static function viewActions(): array
    {
        return [self::VIEW];
    }

    /**
     * Get all edit-related actions.
     *
     * @return array<self>
     */
    public static function editActions(): array
    {
        return [self::EDIT, self::RESCHEDULE, self::RESIZE, self::CANCEL];
    }

    /**
     * Get all meeting-related actions.
     *
     * @return array<self>
     */
    public static function meetingActions(): array
    {
        return [self::START, self::JOIN];
    }
}
