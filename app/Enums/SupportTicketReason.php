<?php

namespace App\Enums;

/**
 * Support Ticket Reason Enum
 *
 * Defines the possible reasons for a support ticket.
 *
 * @see \App\Models\SupportTicket
 */
enum SupportTicketReason: string
{
    case SESSION_ISSUE = 'session_issue';
    case PAYMENT_ISSUE = 'payment_issue';
    case TECHNICAL_ISSUE = 'technical_issue';
    case ACCOUNT_ISSUE = 'account_issue';
    case HOMEWORK_ISSUE = 'homework_issue';
    case GENERAL = 'general';

    /**
     * Get the localized label for the reason
     */
    public function label(): string
    {
        return __('enums.support_ticket_reason.'.$this->value);
    }

    /**
     * Get the icon for the reason
     */
    public function icon(): string
    {
        return match ($this) {
            self::SESSION_ISSUE => 'ri-calendar-line',
            self::PAYMENT_ISSUE => 'ri-bank-card-line',
            self::TECHNICAL_ISSUE => 'ri-bug-line',
            self::ACCOUNT_ISSUE => 'ri-user-settings-line',
            self::HOMEWORK_ISSUE => 'ri-file-list-3-line',
            self::GENERAL => 'ri-question-line',
        };
    }

    /**
     * Get the Tailwind color classes for the reason badge
     */
    public function color(): string
    {
        return match ($this) {
            self::SESSION_ISSUE => 'bg-blue-100 text-blue-800',
            self::PAYMENT_ISSUE => 'bg-yellow-100 text-yellow-800',
            self::TECHNICAL_ISSUE => 'bg-red-100 text-red-800',
            self::ACCOUNT_ISSUE => 'bg-purple-100 text-purple-800',
            self::HOMEWORK_ISSUE => 'bg-orange-100 text-orange-800',
            self::GENERAL => 'bg-gray-100 text-gray-800',
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
            array_map(fn ($reason) => $reason->label(), self::cases())
        );
    }
}
