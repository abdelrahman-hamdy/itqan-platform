<?php

namespace App\Services\Subscription;

use App\Enums\BillingCycle;
use App\Enums\PaymentStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\AcademicPackage;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\SubscriptionCycle;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles subscription renewal and resubscription logic — mutate-in-place.
 *
 * A subscription is a persistent thread. Renewal advances the thread into a new
 * cycle (row in `subscription_cycles`) instead of creating a new subscription row.
 *
 * Two renewal paths:
 *   - Replace: current cycle is exhausted or past its end → archive and create a
 *     fresh active cycle starting now. Subscription columns sync to the new cycle.
 *   - Queue: current cycle still has sessions and time → create a queued cycle
 *     starting at current.ends_at. The current cycle stays active; the queued one
 *     is promoted by `subscriptions:advance-cycles` when the current ends.
 *
 * Renewal always activates. There is no "renew with pending payment" path — the
 * user-initiated "pay later but keep scheduling" scenario is handled via the
 * Extend action (grace period on the current cycle).
 */
class SubscriptionRenewalService
{
    /**
     * Renew an existing subscription into a new cycle.
     *
     * Options:
     *   - billing_cycle:       monthly/quarterly/yearly for the new cycle
     *   - package_id:          optional package switch (type-aware)
     *   - teacher_id:          optional teacher switch (type-aware)
     *   - discount_amount:     per-cycle discount
     *   - is_recurring_discount: whether the discount persists into future cycles
     *   - actor_id:            who triggered the renewal (for audit)
     */
    public function renew(BaseSubscription $old, array $options = []): BaseSubscription
    {
        if (! $this->canRenew($old)) {
            throw new Exception(__('subscriptions.cannot_renew'));
        }

        return DB::transaction(function () use ($old, $options) {
            /** @var BaseSubscription $subscription */
            $subscription = $old::lockForUpdate()->find($old->id);

            // Ensure we always have a current cycle row to work with.
            // Legacy subscriptions created before the cycle refactor won't have one;
            // in that case we materialize one from the subscription's current columns.
            $currentCycle = $this->ensureCurrentCycle($subscription);

            // Guard against rapid duplicate renewals (e.g., double form submission)
            $recentRenewalExists = SubscriptionCycle::where('subscribable_type', $subscription->getMorphClass())
                ->where('subscribable_id', $subscription->id)
                ->where('created_at', '>=', now()->subMinute())
                ->whereJsonContains('metadata->created_by_renewal', true)
                ->exists();

            if ($recentRenewalExists) {
                Log::warning('Duplicate renewal attempt blocked — cycle created <60s ago', [
                    'subscription_id' => $subscription->id,
                    'actor' => auth()->id(),
                ]);

                return $subscription;
            }

            // Decide whether the new cycle REPLACES current immediately, or is QUEUED
            // to start when the current cycle ends.
            $replaceNow = $this->shouldReplaceImmediately($subscription, $currentCycle);

            // Reconcile any pre-existing queued cycle:
            //   - If abandoned (unpaid), delete it on either path so a new cycle
            //     can be created in its place.
            //   - On the queue path: a paid queued cycle blocks queueing a second
            //     one — surface the error to the user.
            //   - On the replace-now path: a paid queued cycle is real money;
            //     leave it for the advance-cycles cron to promote later.
            $existingQueued = $subscription->queuedCycle()->first();
            if ($existingQueued !== null) {
                if ($existingQueued->deleteIfAbandoned()) {
                    Log::info('Replaced abandoned unpaid queued cycle on retry', [
                        'subscription_id' => $subscription->id,
                        'old_cycle_id' => $existingQueued->id,
                        'old_payment_id' => $existingQueued->payment_id,
                        'actor_id' => auth()->id(),
                        'replace_now' => $replaceNow,
                    ]);
                } elseif (! $replaceNow) {
                    throw new Exception(__('subscriptions.errors.queued_cycle_exists'));
                } else {
                    Log::warning('Paid queued cycle preserved alongside immediate replacement', [
                        'subscription_id' => $subscription->id,
                        'queued_cycle_id' => $existingQueued->id,
                        'queued_payment_id' => $existingQueued->payment_id,
                        'actor_id' => auth()->id(),
                    ]);
                }
            }

            // Build per-cycle pricing & session counts (uses current or new package)
            $billingCycle = isset($options['billing_cycle'])
                ? BillingCycle::from($options['billing_cycle'])
                : $subscription->billing_cycle;

            $pricing = $this->resolvePricing($subscription, $options, $billingCycle);

            $sessionsPerMonth = $pricing['sessions_per_month'] ?? $subscription->sessions_per_month ?? 8;
            $totalSessions = $sessionsPerMonth * max(1, $billingCycle->months());

            // Compute window for the new cycle
            if ($replaceNow) {
                $newStartsAt = Carbon::now();
            } else {
                $newStartsAt = $currentCycle->ends_at ?? $subscription->ends_at ?? Carbon::now();
                if ($newStartsAt->isPast()) {
                    $newStartsAt = Carbon::now();
                }
            }
            $newEndsAt = $billingCycle->calculateEndDate($newStartsAt);

            // Carry over only when the cycle has unused sessions AND the
            // subscription view doesn't already say the student is exhausted.
            // The exhausted flag covers admin-preset pre-platform usage that
            // the cycle counter alone cannot see.
            $carryover = $replaceNow && ! $subscription->is_sessions_exhausted
                ? max(0, $this->remainingSessionsOnCycle($currentCycle))
                : 0;

            $totalWithCarryover = $totalSessions + $carryover;

            // Archive the current cycle if replacing
            if ($replaceNow) {
                $currentCycle->update([
                    'cycle_state' => SubscriptionCycle::STATE_ARCHIVED,
                    'archived_at' => now(),
                ]);
            }

            // Create the new cycle row
            $newCycleNumber = ((int) $subscription->cycle_count) + ($subscription->current_cycle_id ? 1 : 0);
            $newCycleNumber = max(1, $newCycleNumber);

            $newCycle = SubscriptionCycle::create([
                'subscribable_type' => $subscription->getMorphClass(),
                'subscribable_id' => $subscription->id,
                'academy_id' => $subscription->academy_id,
                'cycle_number' => $newCycleNumber,
                'cycle_state' => $replaceNow
                    ? SubscriptionCycle::STATE_ACTIVE
                    : SubscriptionCycle::STATE_QUEUED,
                'billing_cycle' => $billingCycle->value,
                'starts_at' => $newStartsAt,
                'ends_at' => $newEndsAt,
                'total_sessions' => $totalWithCarryover,
                'sessions_used' => 0,
                'sessions_completed' => 0,
                'sessions_missed' => 0,
                'carryover_sessions' => $carryover,
                'total_price' => $pricing['price_fields']['total_price'] ?? 0,
                'discount_amount' => $pricing['price_fields']['discount_amount'] ?? 0,
                'final_price' => $pricing['price_fields']['final_price'] ?? 0,
                'currency' => $subscription->currency ?? 'SAR',
                'package_id' => $pricing['package_id'] ?? null,
                'package_snapshot' => $pricing['package_snapshot'] ?? null,
                'payment_status' => ($options['payment_mode'] ?? 'paid') === 'unpaid'
                    ? SubscriptionCycle::PAYMENT_PENDING
                    : SubscriptionCycle::PAYMENT_PAID,
                'metadata' => [
                    'created_by_renewal' => true,
                    'payment_mode' => $options['payment_mode'] ?? 'paid',
                    'actor_id' => $options['actor_id'] ?? auth()->id(),
                    'previous_cycle_id' => $currentCycle->id ?? null,
                ],
            ]);

            // Sync subscription columns to the new cycle if we're replacing.
            // If we're queuing, the subscription row stays on the current cycle;
            // it will sync when AdvanceSubscriptionCycles promotes the queued cycle.
            if ($replaceNow) {
                $this->syncSubscriptionToCycle($subscription, $newCycle, $pricing, $options);
            } else {
                // Queued only: just bump the cycle count and keep the subscription on its current state
                $subscription->update([
                    'cycle_count' => $newCycleNumber,
                ]);
            }

            // Create a COMPLETED payment row linked to this cycle (Renew asserts the cycle is paid)
            $payment = $this->createRenewalPayment($subscription, $newCycle, $pricing, $options);
            $newCycle->update(['payment_id' => $payment->id]);

            // Academic: create the next batch of UNSCHEDULED sessions for this cycle
            if ($replaceNow && $subscription instanceof AcademicSubscription
                && method_exists($subscription, 'createLessonAndSessionsForCycle')) {
                $subscription->createLessonAndSessionsForCycle($newCycle);
            }

            Log::info('Subscription renewed (cycle-based)', [
                'subscription_id' => $subscription->id,
                'new_cycle_id' => $newCycle->id,
                'cycle_state' => $newCycle->cycle_state,
                'replace_now' => $replaceNow,
                'carryover' => $carryover,
                'total_sessions' => $totalWithCarryover,
            ]);

            return $subscription->fresh(['currentCycle', 'cycles']);
        });
    }

    /**
     * Resubscribe from a dormant (cancelled/expired/paused) subscription.
     *
     * Always replaces (never queues) — the subscription had no active coverage,
     * so the new cycle starts now. Clears cancellation fields and reactivates
     * linked circles/lessons.
     */
    public function resubscribe(BaseSubscription $old, array $options = []): BaseSubscription
    {
        if (! $this->canResubscribe($old)) {
            throw new Exception(__('subscriptions.cannot_resubscribe'));
        }

        // Teacher availability check
        if ($old instanceof QuranSubscription || $old instanceof AcademicSubscription) {
            $this->validateTeacherAvailability($old, $options);
        }

        return DB::transaction(function () use ($old, $options) {
            /** @var BaseSubscription $subscription */
            $subscription = $old::lockForUpdate()->find($old->id);

            // Clear cancellation state and flip back to ACTIVE so the shared
            // `renew()` path naturally triggers immediate replacement
            // (exhausted/past-end branch) rather than queuing a future cycle.
            $subscription->update([
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'status' => SessionSubscriptionStatus::ACTIVE,
            ]);

            return $this->renew($subscription, $options);
        });
    }

    /**
     * Check if a subscription can be renewed.
     */
    public function canRenew(BaseSubscription $subscription): bool
    {
        return $subscription->status->canRenew()
            || $subscription->is_sessions_exhausted;
    }

    /**
     * Check if a subscription can be resubscribed (dormant renewal).
     */
    public function canResubscribe(BaseSubscription $subscription): bool
    {
        return in_array($subscription->status, [
            SessionSubscriptionStatus::CANCELLED,
            SessionSubscriptionStatus::EXPIRED,
            SessionSubscriptionStatus::PAUSED,
        ], true);
    }

    /**
     * Get available renewal options (packages, billing cycles, pricing).
     */
    public function getRenewalOptions(BaseSubscription $subscription): array
    {
        $type = $subscription->getSubscriptionType();
        $academyId = $subscription->academy_id;

        $packages = match ($type) {
            'quran' => QuranPackage::where('academy_id', $academyId)->active()->get(),
            'academic' => AcademicPackage::where('academy_id', $academyId)->active()->get(),
            default => collect(),
        };

        $billingCycles = collect(BillingCycle::cases())
            ->filter(fn (BillingCycle $cycle) => $cycle->supportsAutoRenewal())
            ->map(fn (BillingCycle $cycle) => [
                'value' => $cycle->value,
                'label' => $cycle->label(),
                'months' => $cycle->months(),
            ])
            ->values()
            ->all();

        return [
            'packages' => $packages->map(fn ($pkg) => [
                'id' => $pkg->id,
                'name' => $pkg->name,
                'sessions_per_month' => $pkg->sessions_per_month,
                'session_duration_minutes' => $pkg->session_duration_minutes,
                'monthly_price' => (float) $pkg->monthly_price,
                'quarterly_price' => (float) ($pkg->quarterly_price ?? $pkg->monthly_price * 3),
                'yearly_price' => (float) ($pkg->yearly_price ?? $pkg->monthly_price * 12),
                'sale_monthly_price' => $pkg->sale_monthly_price !== null ? (float) $pkg->sale_monthly_price : null,
                'sale_quarterly_price' => $pkg->sale_quarterly_price !== null ? (float) $pkg->sale_quarterly_price : null,
                'sale_yearly_price' => $pkg->sale_yearly_price !== null ? (float) $pkg->sale_yearly_price : null,
                'effective_monthly_price' => (float) PricingResolver::resolvePriceFromPackage($pkg, BillingCycle::MONTHLY),
                'effective_quarterly_price' => (float) PricingResolver::resolvePriceFromPackage($pkg, BillingCycle::QUARTERLY),
                'effective_yearly_price' => (float) PricingResolver::resolvePriceFromPackage($pkg, BillingCycle::YEARLY),
                'currency' => $pkg->currency ?? 'SAR',
            ])->all(),
            'billing_cycles' => $billingCycles,
            'current' => [
                'package_id' => $this->getPackageId($subscription),
                'billing_cycle' => $subscription->billing_cycle->value,
                'sessions_remaining' => method_exists($subscription, 'getSessionsRemaining')
                    ? $subscription->getSessionsRemaining()
                    : 0,
            ],
        ];
    }

    // ========================================================================
    // Cycle management helpers
    // ========================================================================

    /**
     * Ensure the subscription has a `current_cycle_id` row.
     *
     * Delegates to the model-level `BaseSubscription::ensureCurrentCycle()` which
     * is the single source of truth for first-cycle materialization.
     */
    private function ensureCurrentCycle(BaseSubscription $subscription): SubscriptionCycle
    {
        return $subscription->ensureCurrentCycle();
    }

    /**
     * Decide whether the new cycle should replace the current cycle immediately
     * (archive + activate now) or be queued (created as queued, starts when
     * current ends).
     *
     * Per the user's model:
     *   - If current cycle is exhausted OR its end date has passed, replace now.
     *   - Otherwise, queue.
     */
    private function shouldReplaceImmediately(
        BaseSubscription $subscription,
        ?SubscriptionCycle $currentCycle,
    ): bool {
        if (! $currentCycle) {
            return true;
        }

        if (! empty($subscription->status) && in_array($subscription->status, [
            SessionSubscriptionStatus::CANCELLED,
            SessionSubscriptionStatus::EXPIRED,
            SessionSubscriptionStatus::PAUSED,
        ], true)) {
            return true;
        }

        // Trust the subscription-level exhaustion flag too: cycle.sessions_used
        // only tracks in-platform consumption since materialization, while
        // subscription.sessions_used can include admin-preset pre-platform usage.
        // When the two disagree, the subscription view wins for renewal eligibility.
        $remaining = $this->remainingSessionsOnCycle($currentCycle);
        if ($remaining <= 0 || $subscription->is_sessions_exhausted) {
            return true;
        }

        if ($currentCycle->ends_at && $currentCycle->ends_at->isPast()) {
            return true;
        }

        return false;
    }

    private function remainingSessionsOnCycle(SubscriptionCycle $cycle): int
    {
        return max(0, ((int) $cycle->total_sessions) - ((int) $cycle->sessions_used));
    }

    /**
     * Copy the new cycle's column values onto the subscription row so reads
     * via `$sub->starts_at`, `$sub->total_sessions`, etc. stay accurate.
     */
    private function syncSubscriptionToCycle(
        BaseSubscription $subscription,
        SubscriptionCycle $cycle,
        array $pricing,
        array $options,
    ): void {
        $isPaid = ($options['payment_mode'] ?? 'paid') !== 'unpaid';

        $updateData = [
            'status' => SessionSubscriptionStatus::ACTIVE,
            'payment_status' => $isPaid
                ? SubscriptionPaymentStatus::PAID
                : SubscriptionPaymentStatus::PENDING,
            'starts_at' => $cycle->starts_at,
            'ends_at' => $cycle->ends_at,
            'next_billing_date' => $cycle->ends_at,
            'last_payment_date' => $isPaid ? now() : $subscription->last_payment_date,
            'billing_cycle' => BillingCycle::from($cycle->billing_cycle),
            'total_sessions' => $cycle->total_sessions,
            'sessions_remaining' => $cycle->total_sessions,
            'sessions_used' => 0,
            'total_sessions_completed' => 0,
            'total_sessions_missed' => 0,
            'progress_percentage' => 0,
            'current_cycle_id' => $cycle->id,
            'cycle_count' => $cycle->cycle_number,
            'auto_renew' => BillingCycle::from($cycle->billing_cycle)->supportsAutoRenewal(),
            'total_price' => $pricing['price_fields']['total_price'] ?? $subscription->total_price,
            'discount_amount' => $pricing['price_fields']['discount_amount'] ?? $subscription->discount_amount,
            'final_price' => $pricing['price_fields']['final_price'] ?? $subscription->final_price,
            'is_recurring_discount' => $options['is_recurring_discount'] ?? $subscription->is_recurring_discount,
            'package_name_ar' => $pricing['price_fields']['package_name_ar'] ?? $subscription->package_name_ar,
            'package_name_en' => $pricing['price_fields']['package_name_en'] ?? $subscription->package_name_en,
            'package_price_monthly' => $pricing['price_fields']['package_price_monthly'] ?? $subscription->package_price_monthly,
            'package_price_quarterly' => $pricing['price_fields']['package_price_quarterly'] ?? $subscription->package_price_quarterly,
            'package_price_yearly' => $pricing['price_fields']['package_price_yearly'] ?? $subscription->package_price_yearly,
        ];

        // Clear subscription-level grace period metadata (cycle is freshly paid)
        $metadata = $subscription->metadata ?? [];
        unset(
            $metadata['grace_period_ends_at'],
            $metadata['grace_period_expires_at'],
            $metadata['grace_period_started_at'],
            $metadata['sessions_exhausted'],
            $metadata['sessions_exhausted_at']
        );
        $updateData['metadata'] = $metadata ?: null;

        // Type-specific field preservation
        if ($subscription instanceof QuranSubscription) {
            if (isset($options['package_id'])) {
                $updateData['package_id'] = $options['package_id'];
            }
            if (isset($options['teacher_id'])) {
                $updateData['quran_teacher_id'] = $options['teacher_id'];
            }
        } elseif ($subscription instanceof AcademicSubscription) {
            if (isset($options['package_id'])) {
                $updateData['academic_package_id'] = $options['package_id'];
            }
            if (isset($options['teacher_id'])) {
                $updateData['teacher_id'] = $options['teacher_id'];
            }
            $updateData['start_date'] = $cycle->starts_at;
            $updateData['end_date'] = $cycle->ends_at;
        }

        $subscription->update($updateData);

        // Restore any suspended future sessions
        if (method_exists($subscription, 'restoreSuspendedSessions')) {
            $subscription->restoreSuspendedSessions();
        }
    }

    /**
     * Create a payment row for the renewal.
     *
     * When payment_mode = 'paid': creates a COMPLETED payment (admin confirmed
     * external payment or student paid in-platform).
     * When payment_mode = 'unpaid': creates a PENDING payment (student will pay later).
     */
    private function createRenewalPayment(
        BaseSubscription $subscription,
        SubscriptionCycle $cycle,
        array $pricing,
        array $options = [],
    ): Payment {
        $amount = (float) ($pricing['price_fields']['final_price']
            ?? $cycle->final_price
            ?? $cycle->total_price
            ?? 0);

        $isPaid = ($options['payment_mode'] ?? 'paid') !== 'unpaid';

        return Payment::createPayment([
            'academy_id' => $subscription->academy_id,
            'user_id' => $subscription->student_id,
            'payable_type' => $subscription::class,
            'payable_id' => $subscription->id,
            'subscription_cycle_id' => $cycle->id,
            'payment_method' => 'cash',
            'payment_gateway' => 'manual',
            'amount' => $amount,
            'currency' => $subscription->currency ?? 'SAR',
            'status' => $isPaid ? PaymentStatus::COMPLETED : PaymentStatus::PENDING,
            'payment_status' => $isPaid ? 'paid' : 'pending',
            'payment_date' => $isPaid ? now() : null,
            'paid_at' => $isPaid ? now() : null,
            'confirmed_at' => $isPaid ? now() : null,
            'notes' => $isPaid
                ? __('subscriptions.renewal_payment_recorded_by_admin')
                : __('subscriptions.renewal_payment_pending'),
        ]);
    }

    // ========================================================================
    // Pricing resolution (kept mostly intact from the previous implementation)
    // ========================================================================

    private function resolvePricing(
        BaseSubscription $subscription,
        array $options,
        BillingCycle $billingCycle,
    ): array {
        $packageId = $options['package_id'] ?? $this->getPackageId($subscription);
        $package = $packageId ? $this->findPackage($subscription, $packageId) : null;

        // The current package record is always authoritative for pricing —
        // reading from it honors any active sale_*_price that may have been set
        // after the original purchase.  The subscription's own snapshot is only
        // used as a fallback when the package row has been deleted.
        if ($package) {
            $finalPrice = $options['amount'] ?? PricingResolver::resolvePriceFromPackage($package, $billingCycle);
            $discount = $this->resolveDiscount($subscription, $options);

            return [
                'sessions_per_month' => $package->sessions_per_month,
                'session_duration_minutes' => $package->session_duration_minutes,
                'package_id' => $package->id,
                'package_snapshot' => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'sessions_per_month' => $package->sessions_per_month,
                    'session_duration_minutes' => $package->session_duration_minutes,
                    'currency' => $package->currency ?? 'SAR',
                ],
                'price_fields' => [
                    'package_name_ar' => $package->name,
                    'package_name_en' => $package->name,
                    'package_price_monthly' => (float) $package->monthly_price,
                    'package_price_quarterly' => (float) ($package->quarterly_price ?? $package->monthly_price * 3),
                    'package_price_yearly' => (float) ($package->yearly_price ?? $package->monthly_price * 12),
                    'total_price' => $finalPrice,
                    'discount_amount' => $discount,
                    'final_price' => max(0, $finalPrice - $discount),
                ],
            ];
        }

        // Package deleted — fall back to the subscription's frozen snapshot.
        $finalPrice = $options['amount'] ?? $subscription->getPriceForBillingCycle();
        $discount = $this->resolveDiscount($subscription, $options);

        return [
            'sessions_per_month' => $subscription->sessions_per_month,
            'session_duration_minutes' => $subscription->session_duration_minutes,
            'package_id' => $this->getPackageId($subscription),
            'package_snapshot' => $this->packageSnapshotFromSubscription($subscription),
            'price_fields' => [
                'package_name_ar' => $subscription->package_name_ar,
                'package_name_en' => $subscription->package_name_en,
                'package_price_monthly' => $subscription->package_price_monthly,
                'package_price_quarterly' => $subscription->package_price_quarterly,
                'package_price_yearly' => $subscription->package_price_yearly,
                'total_price' => $finalPrice,
                'discount_amount' => $discount,
                'final_price' => max(0, $finalPrice - $discount),
            ],
        ];
    }

    private function resolveDiscount(BaseSubscription $subscription, array $options): float
    {
        if (array_key_exists('discount_amount', $options)) {
            return (float) $options['discount_amount'];
        }

        return $subscription->is_recurring_discount ? (float) $subscription->discount_amount : 0;
    }

    private function packageSnapshotFromSubscription(BaseSubscription $subscription): ?array
    {
        return [
            'package_id' => $this->getPackageId($subscription),
            'name_ar' => $subscription->package_name_ar,
            'name_en' => $subscription->package_name_en,
            'sessions_per_month' => $subscription->sessions_per_month ?? null,
            'session_duration_minutes' => $subscription->session_duration_minutes,
            'monthly_price' => $subscription->package_price_monthly,
            'quarterly_price' => $subscription->package_price_quarterly,
            'yearly_price' => $subscription->package_price_yearly,
            'currency' => $subscription->currency ?? 'SAR',
        ];
    }

    private function validateTeacherAvailability(BaseSubscription $old, array $options): void
    {
        $teacherId = $options['teacher_id'] ?? null;

        if ($old instanceof QuranSubscription) {
            $currentTeacherId = $teacherId ?? $old->quran_teacher_id;
            if ($currentTeacherId) {
                $teacherProfile = QuranTeacherProfile::find($currentTeacherId);
                if (! $teacherProfile || ! $teacherProfile->user?->active_status) {
                    if (! $teacherId) {
                        throw new Exception(__('subscriptions.teacher_unavailable_select_new'));
                    }
                }
            }
        } elseif ($old instanceof AcademicSubscription) {
            $currentTeacherId = $teacherId ?? $old->teacher_id;
            if ($currentTeacherId) {
                $teacherProfile = AcademicTeacherProfile::find($currentTeacherId);
                if (! $teacherProfile) {
                    if (! $teacherId) {
                        throw new Exception(__('subscriptions.teacher_unavailable_select_new'));
                    }
                }
            }
        }
    }

    private function getPackageId(BaseSubscription $subscription): ?int
    {
        if ($subscription instanceof QuranSubscription) {
            return $subscription->package_id;
        }
        if ($subscription instanceof AcademicSubscription) {
            return $subscription->academic_package_id;
        }

        return null;
    }

    private function findPackage(BaseSubscription $subscription, int $packageId): QuranPackage|AcademicPackage|null
    {
        if ($subscription instanceof QuranSubscription) {
            return QuranPackage::find($packageId);
        }
        if ($subscription instanceof AcademicSubscription) {
            return AcademicPackage::find($packageId);
        }

        return null;
    }
}
