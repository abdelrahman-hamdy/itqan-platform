<?php

namespace App\Enums;

use Carbon\Carbon;

/**
 * BillingCycle Enum
 *
 * Defines billing periods for subscriptions.
 * Used for:
 * - Calculating subscription end dates
 * - Determining next billing date
 * - Selecting package pricing tier
 */
enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case LIFETIME = 'lifetime';     // For one-time purchases (courses)

    /**
     * Get the Arabic label for the billing cycle
     */
    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'شهري',
            self::QUARTERLY => 'ربع سنوي',
            self::YEARLY => 'سنوي',
            self::LIFETIME => 'مدى الحياة',
        };
    }

    /**
     * Get the English label for the billing cycle
     */
    public function labelEn(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::YEARLY => 'Yearly',
            self::LIFETIME => 'Lifetime',
        };
    }

    /**
     * Get the number of months in this billing cycle
     */
    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::YEARLY => 12,
            self::LIFETIME => 0, // No recurring billing
        };
    }

    /**
     * Get the number of days in this billing cycle (approximate)
     */
    public function days(): int
    {
        return match ($this) {
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::YEARLY => 365,
            self::LIFETIME => 36500, // 100 years
        };
    }

    /**
     * Calculate the end date from a start date
     */
    public function calculateEndDate(Carbon $startDate): Carbon
    {
        return match ($this) {
            self::MONTHLY => $startDate->copy()->addMonth(),
            self::QUARTERLY => $startDate->copy()->addMonths(3),
            self::YEARLY => $startDate->copy()->addYear(),
            self::LIFETIME => $startDate->copy()->addYears(100), // Effectively unlimited
        };
    }

    /**
     * Calculate the next billing date from current date
     */
    public function nextBillingDate(Carbon $currentBillingDate): Carbon
    {
        return $this->calculateEndDate($currentBillingDate);
    }

    /**
     * Check if this is a recurring billing cycle
     */
    public function isRecurring(): bool
    {
        return $this !== self::LIFETIME;
    }

    /**
     * Check if this billing cycle supports auto-renewal
     */
    public function supportsAutoRenewal(): bool
    {
        return $this->isRecurring();
    }

    /**
     * Get the multiplier for calculating total sessions
     * (based on sessions_per_month from package)
     */
    public function sessionMultiplier(): int
    {
        return $this->months();
    }

    /**
     * Get discount percentage (if any) compared to monthly
     */
    public function discountPercentage(): int
    {
        return match ($this) {
            self::MONTHLY => 0,
            self::QUARTERLY => 10,  // 10% discount
            self::YEARLY => 20,     // 20% discount
            self::LIFETIME => 0,    // Special pricing
        };
    }

    /**
     * Get short code for subscription codes
     */
    public function shortCode(): string
    {
        return match ($this) {
            self::MONTHLY => 'M',
            self::QUARTERLY => 'Q',
            self::YEARLY => 'Y',
            self::LIFETIME => 'L',
        };
    }

    /**
     * Get all billing cycle values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get recurring billing cycles only
     */
    public static function recurringCycles(): array
    {
        return [self::MONTHLY, self::QUARTERLY, self::YEARLY];
    }

    /**
     * Get billing cycle options for forms (value => label)
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn ($cycle) => $cycle->label(), self::cases())
        );
    }

    /**
     * Get recurring billing cycle options for forms
     */
    public static function recurringOptions(): array
    {
        $cycles = self::recurringCycles();
        return array_combine(
            array_map(fn ($c) => $c->value, $cycles),
            array_map(fn ($c) => $c->label(), $cycles)
        );
    }
}
