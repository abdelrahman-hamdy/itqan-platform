<?php

namespace App\Enums;

/**
 * Date Period Enum
 *
 * Defines time period options for reporting and filtering.
 */
enum DatePeriod: string
{
    case CUSTOM = 'custom';
    case WEEK = 'week';
    case MONTH = 'month';
    case QUARTER = 'quarter';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    /**
     * Get the localized label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CUSTOM => __('enums.date_period.custom'),
            self::WEEK => __('enums.date_period.week'),
            self::MONTH => __('enums.date_period.month'),
            self::QUARTER => __('enums.date_period.quarter'),
            self::MONTHLY => __('enums.date_period.monthly'),
            self::QUARTERLY => __('enums.date_period.quarterly'),
            self::YEARLY => __('enums.date_period.yearly'),
        };
    }

    /**
     * Get valid aggregation periods for reports.
     */
    public static function aggregationPeriods(): array
    {
        return [self::MONTHLY, self::QUARTERLY, self::YEARLY];
    }
}
