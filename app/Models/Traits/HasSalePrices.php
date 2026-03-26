<?php

namespace App\Models\Traits;

trait HasSalePrices
{
    public function getPriceForBillingCycle(string $billingCycle): ?float
    {
        return match ($billingCycle) {
            'monthly' => $this->monthly_price,
            'quarterly' => $this->quarterly_price,
            'yearly' => $this->yearly_price,
            default => null,
        };
    }

    public function hasSalePrice(string $billingCycle): bool
    {
        return $this->getSalePriceForBillingCycle($billingCycle) !== null;
    }

    public function getSalePriceForBillingCycle(string $billingCycle): ?float
    {
        $price = match ($billingCycle) {
            'monthly' => $this->sale_monthly_price,
            'quarterly' => $this->sale_quarterly_price,
            'yearly' => $this->sale_yearly_price,
            default => null,
        };

        return $price !== null ? (float) $price : null;
    }

    public function getEffectivePriceForBillingCycle(string $billingCycle): ?float
    {
        return $this->getSalePriceForBillingCycle($billingCycle)
            ?? $this->getPriceForBillingCycle($billingCycle);
    }

    public function hasAnySalePrice(): bool
    {
        return $this->sale_monthly_price !== null
            || $this->sale_quarterly_price !== null
            || $this->sale_yearly_price !== null;
    }

    public function getDiscountPercentage(string $billingCycle): int
    {
        $original = $this->getPriceForBillingCycle($billingCycle);
        $sale = $this->getSalePriceForBillingCycle($billingCycle);

        if (! $original || ! $sale || $original <= 0 || $sale >= $original) {
            return 0;
        }

        return (int) round((($original - $sale) / $original) * 100);
    }
}
