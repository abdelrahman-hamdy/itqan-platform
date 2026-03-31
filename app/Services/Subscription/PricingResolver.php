<?php

namespace App\Services\Subscription;

use App\Enums\BillingCycle;

/**
 * Shared pricing resolution for packages across billing cycles.
 *
 * Eliminates duplication between SubscriptionRenewalService::resolvePricing(),
 * AdminSubscriptionWizardService::getPriceFromPackage(), and
 * CreateFullSubscription::calculateAmount().
 */
class PricingResolver
{
    /**
     * Resolve the price from a package for a given billing cycle.
     * Supports sale prices with fallback to regular prices.
     *
     * @param  object|array  $package  Package model or array with pricing fields
     * @param  bool  $useSalePrices  Whether to prefer sale prices over regular
     */
    public static function resolvePriceFromPackage(
        object|array $package,
        BillingCycle $billingCycle,
        bool $useSalePrices = true,
    ): float {
        $pkg = is_array($package) ? (object) $package : $package;

        return match ($billingCycle) {
            BillingCycle::MONTHLY => (float) (
                ($useSalePrices ? ($pkg->sale_monthly_price ?? null) : null)
                ?? $pkg->monthly_price
                ?? 0
            ),
            BillingCycle::QUARTERLY => (float) (
                ($useSalePrices ? ($pkg->sale_quarterly_price ?? null) : null)
                ?? $pkg->quarterly_price
                ?? (($pkg->monthly_price ?? 0) * 3)
            ),
            BillingCycle::YEARLY => (float) (
                ($useSalePrices ? ($pkg->sale_yearly_price ?? null) : null)
                ?? $pkg->yearly_price
                ?? (($pkg->monthly_price ?? 0) * 12)
            ),
            BillingCycle::LIFETIME => (float) ($pkg->final_price ?? $pkg->yearly_price ?? 0),
        };
    }
}
