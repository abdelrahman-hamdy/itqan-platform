<?php

namespace App\Enums;

/**
 * Support Ticket Status Enum
 *
 * @see \App\Models\SupportTicket
 */
enum SupportTicketStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    /**
     * Get the localized label for the status
     */
    public function label(): string
    {
        return __('enums.support_ticket_status.'.$this->value);
    }

    /**
     * Get the icon for the status
     */
    public function icon(): string
    {
        return match ($this) {
            self::OPEN => 'ri-chat-new-line',
            self::CLOSED => 'ri-check-double-line',
        };
    }

    /**
     * Get the Tailwind badge classes for the status
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::OPEN => 'bg-green-100 text-green-800',
            self::CLOSED => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options array for select dropdowns
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($status) => $status->label(), self::cases())
        );
    }
}
