<?php

namespace App\Services\Subscription;

use App\Enums\BillingCycle;
use App\Exceptions\Subscription\InvalidPricingOverride;
use App\Models\BaseSubscription;
use App\Models\SubscriptionCycle;
use App\Models\User;
use App\Services\Subscription\Concerns\RecordsSubscriptionAudit;
use App\Support\Subscriptions\SubscriptionLock;
use Illuminate\Support\Facades\DB;

/**
 * SubscriptionPricing — the SOLE writer of cycle pricing fields.
 *
 * Implements §7 of `docs/subscription-invariants.md` (pricing trust model):
 *
 *   - `pricing_source ∈ {'package','sale_price','manual_override'}`. NULL
 *     forbidden (INV-D1).
 *   - When `pricing_source == 'package'` the final_price MUST equal what
 *     PricingResolver derives from `package + billing_cycle - discount`.
 *     Otherwise `pricing_override_reason` (non-empty) and
 *     `pricing_override_actor_id` (non-null) MUST both be populated
 *     (INV-D2).
 *   - `final_price` MUST be ≥ 0 (INV-D3).
 *
 * Every public mutator follows the lock + reconciler + audit shape from §6
 * (`SubscriptionLifecycle`, `SubscriptionConsumption`, `SubscriptionPayment`
 * follow the same pattern). The reconciler is invoked even though the
 * pricing write does not mutate the subscription row directly — it
 * re-validates every invariant on the way out, which catches an override
 * that accidentally violates a downstream invariant (e.g. a follow-up
 * code change forgets to clear `pricing_override_reason` on a package
 * re-pricing).
 *
 * Source-hint values:
 *   - `'package'`         — final_price is derived from PricingResolver.
 *   - `'sale_price'`      — caller supplied a sale price (override).
 *   - `'manual_override'` — admin/supervisor entered a custom price.
 */
class SubscriptionPricing
{
    use RecordsSubscriptionAudit;

    public const SOURCE_PACKAGE = 'package';

    public const SOURCE_SALE_PRICE = 'sale_price';

    public const SOURCE_MANUAL_OVERRIDE = 'manual_override';

    private const VALID_SOURCES = [
        self::SOURCE_PACKAGE,
        self::SOURCE_SALE_PRICE,
        self::SOURCE_MANUAL_OVERRIDE,
    ];

    public function __construct(
        private readonly SubscriptionReconciler $reconciler,
    ) {}

    /**
     * Apply the §7 pricing contract to `$cycle`.
     *
     * Writes `final_price`, `pricing_source`, `pricing_override_reason`,
     * `pricing_override_actor_id` in a single locked + reconciled
     * transaction, and audit-logs the change.
     *
     * @param  object|array  $package  Package model or array (PricingResolver
     *                                 contract: needs the *_price fields).
     * @param  float  $discount  Discount applied AFTER package price (final =
     *                           packagePrice - discount when sourceHint =
     *                           'package').
     * @param  string  $sourceHint  One of SOURCE_PACKAGE | SOURCE_SALE_PRICE
     *                              | SOURCE_MANUAL_OVERRIDE.
     * @param  float|null  $manualPrice  REQUIRED for sale_price /
     *                                   manual_override (the caller's
     *                                   authoritative price). IGNORED for
     *                                   'package' (computed from package).
     *
     * @throws InvalidPricingOverride on contract violation.
     */
    public function priceCycle(
        SubscriptionCycle $cycle,
        object|array $package,
        BillingCycle $billingCycle,
        float $discount,
        string $sourceHint,
        User $actor,
        ?string $reason = null,
        ?float $manualPrice = null,
    ): SubscriptionCycle {
        $this->assertKnownSource($sourceHint);

        $sub = $this->resolveSubscription($cycle);

        return SubscriptionLock::for($sub, function () use (
            $cycle,
            $package,
            $billingCycle,
            $discount,
            $sourceHint,
            $actor,
            $reason,
            $manualPrice,
            $sub,
        ) {
            return DB::transaction(function () use (
                $cycle,
                $package,
                $billingCycle,
                $discount,
                $sourceHint,
                $actor,
                $reason,
                $manualPrice,
                $sub,
            ) {
                return $this->withAudit(
                    $sub,
                    'price_cycle',
                    $sourceHint,
                    $actor,
                    function () use (
                        $cycle,
                        $package,
                        $billingCycle,
                        $discount,
                        $sourceHint,
                        $actor,
                        $reason,
                        $manualPrice,
                        $sub,
                    ) {
                        /** @var SubscriptionCycle $locked */
                        $locked = SubscriptionCycle::query()
                            ->whereKey($cycle->getKey())
                            ->lockForUpdate()
                            ->first();

                        if ($locked === null) {
                            throw new \RuntimeException("SubscriptionCycle#{$cycle->getKey()} disappeared mid-price.");
                        }

                        [$finalPrice, $resolvedSource, $resolvedReason, $resolvedActorId] =
                            $this->computePricing(
                                $locked,
                                $package,
                                $billingCycle,
                                $discount,
                                $sourceHint,
                                $manualPrice,
                                $reason,
                                $actor,
                            );

                        $locked->fill([
                            'final_price' => $finalPrice,
                            'discount_amount' => $discount,
                            'pricing_source' => $resolvedSource,
                            'pricing_override_reason' => $resolvedReason,
                            'pricing_override_actor_id' => $resolvedActorId,
                        ]);
                        $locked->save();

                        $this->reconciler->sync($sub);

                        return $locked->fresh();
                    },
                );
            });
        });
    }

    /**
     * Compute (but do NOT persist) the price for a hypothetical next cycle.
     *
     * Pure: no writes. Used by the renew flow to surface the upcoming
     * price to the user before they commit.
     *
     * @param  object|array  $newPackage  Package the user is about to
     *                                    switch to (or the current package
     *                                    if unchanged).
     */
    public function recomputeNextCyclePrice(
        BaseSubscription $sub,
        object|array $newPackage,
        BillingCycle $newBillingCycle,
    ): float {
        // Per INV-D2 / §7: package source uses PricingResolver-only. Discount
        // carries over from the subscription's snapshot (admins override it
        // explicitly via the renew form when needed).
        $packagePrice = PricingResolver::resolvePriceFromPackage(
            $newPackage,
            $newBillingCycle,
        );

        $discount = (float) ($sub->discount_amount ?? 0);

        return max(0.0, $packagePrice - $discount);
    }

    /**
     * Convenience wrapper around `priceCycle()` for the manual-override
     * path. The price is supplied by the caller, source is locked to
     * `manual_override`, and `reason` is mandatory.
     */
    public function applyOverride(
        SubscriptionCycle $cycle,
        float $newPrice,
        User $actor,
        string $reason,
    ): SubscriptionCycle {
        $package = $cycle->package_snapshot ?? new \stdClass;
        $billingCycle = BillingCycle::tryFrom((string) $cycle->billing_cycle)
            ?? BillingCycle::MONTHLY;

        return $this->priceCycle(
            $cycle,
            $package,
            $billingCycle,
            (float) ($cycle->discount_amount ?? 0),
            self::SOURCE_MANUAL_OVERRIDE,
            $actor,
            $reason,
            $newPrice,
        );
    }

    // ========================================================================
    // Helpers (private)
    // ========================================================================

    /**
     * Compute the final_price + the persisted (source / reason / actor)
     * tuple for the requested write. Raises on contract violation BEFORE
     * any DB write happens (so the audit row carries the violations and
     * the transaction never starts).
     *
     * @return array{0: float, 1: string, 2: ?string, 3: ?int}
     */
    private function computePricing(
        SubscriptionCycle $cycle,
        object|array $package,
        BillingCycle $billingCycle,
        float $discount,
        string $sourceHint,
        ?float $manualPrice,
        ?string $reason,
        User $actor,
    ): array {
        $violations = [];

        if ($sourceHint === self::SOURCE_PACKAGE) {
            $packagePrice = PricingResolver::resolvePriceFromPackage(
                $package,
                $billingCycle,
            );

            $expected = max(0.0, $packagePrice - $discount);

            if ($expected < 0.0) {
                $violations[] = 'INV-D3: final_price would be negative.';
            }

            if (! empty($violations)) {
                throw new InvalidPricingOverride($cycle, $violations);
            }

            return [$expected, self::SOURCE_PACKAGE, null, null];
        }

        // sale_price / manual_override: caller supplies the price; both
        // reason + actor MUST be populated (INV-D2).
        if ($manualPrice === null) {
            $violations[] = sprintf(
                'INV-D2: pricing_source=%s requires an explicit manualPrice.',
                $sourceHint,
            );
        }

        if ($reason === null || trim($reason) === '') {
            $violations[] = sprintf(
                'INV-D2: pricing_source=%s requires a non-empty pricing_override_reason.',
                $sourceHint,
            );
        }

        if ($actor->getKey() === null) {
            $violations[] = sprintf(
                'INV-D2: pricing_source=%s requires a non-null pricing_override_actor_id.',
                $sourceHint,
            );
        }

        $price = (float) ($manualPrice ?? 0);

        if ($price < 0.0) {
            $violations[] = 'INV-D3: final_price MUST be ≥ 0.';
        }

        if (! empty($violations)) {
            throw new InvalidPricingOverride($cycle, $violations);
        }

        return [$price, $sourceHint, trim((string) $reason), (int) $actor->getKey()];
    }

    /**
     * Resolve the subscribable that owns this cycle. The lock is keyed on
     * the subscription, never the cycle.
     */
    private function resolveSubscription(SubscriptionCycle $cycle): BaseSubscription
    {
        $sub = $cycle->subscribable;

        if (! $sub instanceof BaseSubscription) {
            throw new \RuntimeException(sprintf(
                'SubscriptionCycle#%d has no resolvable subscribable (type=%s, id=%d).',
                $cycle->getKey(),
                $cycle->subscribable_type,
                $cycle->subscribable_id,
            ));
        }

        return $sub;
    }

    private function assertKnownSource(string $source): void
    {
        if (! in_array($source, self::VALID_SOURCES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown pricing source "%s". Expected one of: %s.',
                $source,
                implode(', ', self::VALID_SOURCES),
            ));
        }
    }
}
