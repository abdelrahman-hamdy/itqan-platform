<?php

namespace App\Exceptions\Subscription;

use App\Models\SubscriptionCycle;
use RuntimeException;

/**
 * Raised by {@see \App\Services\Subscription\SubscriptionPricing::priceCycle()}
 * when the writer contract from `docs/subscription-invariants.md §7` is
 * violated:
 *
 *   - `pricing_source == 'package'` AND the supplied final_price does not
 *     match what PricingResolver derives from the package+billing_cycle
 *     (snapshot integrity per INV-D2).
 *   - `pricing_source ∈ {'sale_price','manual_override'}` AND either
 *     `pricing_override_reason` is empty or `pricing_override_actor_id` is
 *     null — overrides MUST carry both an actor and a reason for audit.
 *   - final_price is negative (INV-D3).
 *
 * The exception captures every violation as a list so the audit-log entry
 * persists the full diagnostic alongside the failed write.
 */
class InvalidPricingOverride extends RuntimeException
{
    /**
     * @param  list<string>  $violations  Human-readable invariant violations.
     */
    public function __construct(
        public readonly SubscriptionCycle $cycle,
        public readonly array $violations,
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? sprintf(
                'Invalid pricing override on cycle #%d: %s',
                $cycle->getKey(),
                implode(' | ', $violations),
            ),
        );
    }

    /**
     * Full list of violations recorded against this attempt.
     *
     * @return list<string>
     */
    public function violations(): array
    {
        return $this->violations;
    }
}
