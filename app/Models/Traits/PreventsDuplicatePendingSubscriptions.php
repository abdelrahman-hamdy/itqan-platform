<?php

namespace App\Models\Traits;

use App\Enums\SubscriptionPaymentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * PreventsDuplicatePendingSubscriptions Trait
 *
 * Provides methods for checking and handling duplicate pending subscriptions.
 * Used by QuranSubscription, AcademicSubscription, and CourseSubscription.
 *
 * Classes using this trait must implement:
 * - getDuplicateKeyFields() - Fields that identify a unique subscription combination
 * - getPendingStatus() - The status enum value representing "pending"
 * - getActiveStatus() - The status enum value representing "active" (or "enrolled" for courses)
 * - getCancelledStatus() - The status enum value representing "cancelled"
 */
trait PreventsDuplicatePendingSubscriptions
{
    /**
     * Get the fields that identify a unique subscription combination.
     * Must be implemented by child classes.
     *
     * @return array<string> Field names used for duplicate detection
     */
    abstract protected function getDuplicateKeyFields(): array;

    /**
     * Get the "pending" status value for this subscription type.
     * Must be implemented by child classes.
     *
     * @return mixed The pending status enum case
     */
    abstract protected function getPendingStatus(): mixed;

    /**
     * Get the "active" status value for this subscription type.
     * For CourseSubscription, this returns ENROLLED.
     * Must be implemented by child classes.
     *
     * @return mixed The active status enum case
     */
    abstract protected function getActiveStatus(): mixed;

    /**
     * Get the "cancelled" status value for this subscription type.
     * Must be implemented by child classes.
     *
     * @return mixed The cancelled status enum case
     */
    abstract protected function getCancelledStatus(): mixed;

    /**
     * Scope: Get pending subscriptions that can be auto-cancelled.
     */
    public function scopePendingForAutoCancel(Builder $query): Builder
    {
        return $query->where('status', $this->getPendingStatus())
            ->where(function ($q) {
                $q->where('payment_status', SubscriptionPaymentStatus::PENDING)
                    ->orWhere('payment_status', 'pending');
            });
    }

    /**
     * Scope: Get expired pending subscriptions (older than configured hours).
     *
     * @param  int|null  $hoursOld  Hours after which subscription is considered expired
     */
    public function scopeExpiredPending(Builder $query, ?int $hoursOld = null): Builder
    {
        $hours = $hoursOld ?? config('subscriptions.pending.expires_after_hours', 48);

        return $query->where('status', $this->getPendingStatus())
            ->where(function ($q) {
                $q->where('payment_status', SubscriptionPaymentStatus::PENDING)
                    ->orWhere('payment_status', 'pending');
            })
            ->where('created_at', '<', now()->subHours($hours))
            // Exclude subscriptions with an admin-granted grace period that
            // is still in the future. Past grace dates are treated as
            // expired — those subs are eligible for cleanup again.
            ->where(function ($q) {
                $q->whereNull('metadata')
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.grace_period_ends_at') IS NULL")
                    ->orWhereRaw(
                        "STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.grace_period_ends_at')), '%Y-%m-%d %H:%i:%s') < ?",
                        [now()->toDateTimeString()]
                    );
            });
    }

    /**
     * Scope: Find pending subscriptions for a specific combination.
     *
     * @param  int  $academyId  Academy ID
     * @param  int  $studentId  Student ID
     * @param  array<string, mixed>  $keyValues  Key field values for duplicate detection
     */
    public function scopePendingForCombination(
        Builder $query,
        int $academyId,
        int $studentId,
        array $keyValues
    ): Builder {
        $query->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', $this->getPendingStatus())
            ->where(function ($q) {
                $q->where('payment_status', SubscriptionPaymentStatus::PENDING)
                    ->orWhere('payment_status', 'pending');
            });

        foreach ($keyValues as $field => $value) {
            if ($value !== null) {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Scope: Find active subscriptions for a specific combination.
     *
     * @param  int  $academyId  Academy ID
     * @param  int  $studentId  Student ID
     * @param  array<string, mixed>  $keyValues  Key field values for duplicate detection
     */
    public function scopeActiveForCombination(
        Builder $query,
        int $academyId,
        int $studentId,
        array $keyValues
    ): Builder {
        $query->where('academy_id', $academyId)
            ->where('student_id', $studentId)
            ->where('status', $this->getActiveStatus());

        foreach ($keyValues as $field => $value) {
            if ($value !== null) {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Cancel this subscription as duplicate or expired.
     *
     * Also clears payment_status to FAILED so the student-facing payable list
     * (which keys off payment_status=PENDING) drops this row immediately,
     * and so a fresh gateway payment cannot resurrect the cancelled thread
     * through QuranSubscriptionPaymentController::getPendingSubscription().
     * See followup_payment_routing_to_cancelled_sub.md, defect 1.
     *
     * @param  string|null  $reason  Cancellation reason (uses config default if null)
     */
    public function cancelAsDuplicateOrExpired(?string $reason = null): void
    {
        $now = now();
        // Collapse ends_at to the cancel time. Otherwise the UI's
        // "ends in X days" countdown ticks against the original 30-day
        // projection — wrong for a cancelled sub.
        $endsAt = $this->ends_at !== null && $this->ends_at->lt($now)
            ? $this->ends_at
            : $now;

        $this->update([
            'status' => $this->getCancelledStatus(),
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'cancelled_at' => $now,
            'ends_at' => $endsAt,
            'cancellation_reason' => $reason ?? config('subscriptions.cancellation_reasons.expired'),
            'auto_renew' => false,
        ]);

        $this->assertCancelInvariants(__METHOD__);
    }

    /**
     * Cancel this subscription due to payment failure.
     */
    public function cancelDueToPaymentFailure(): void
    {
        $now = now();
        $endsAt = $this->ends_at !== null && $this->ends_at->lt($now)
            ? $this->ends_at
            : $now;

        $this->update([
            'status' => $this->getCancelledStatus(),
            'payment_status' => SubscriptionPaymentStatus::FAILED,
            'cancelled_at' => $now,
            'ends_at' => $endsAt,
            'cancellation_reason' => config('subscriptions.cancellation_reasons.payment_failed'),
            'auto_renew' => false,
        ]);

        $this->assertCancelInvariants(__METHOD__);
    }

    /**
     * Defensive post-cancel guard: the cancel paths above should never leave
     * a row where `cancelled_at` is set but `ends_at`, `cancellation_reason`,
     * or `payment_status` are NULL/inconsistent. Future regressions get
     * caught here instead of surfacing weeks later via the "ends in 7 days"
     * supervisor complaint.
     *
     * Logs the violation to the `subscriptions` channel so the issue is
     * traceable in production observability. Throws so the calling code
     * fails fast.
     */
    protected function assertCancelInvariants(string $caller): void
    {
        $this->refresh();

        $violations = [];
        if ($this->cancelled_at === null) {
            $violations[] = 'cancelled_at is null';
        }
        if ($this->ends_at === null) {
            $violations[] = 'ends_at is null';
        }
        if (empty($this->cancellation_reason)) {
            $violations[] = 'cancellation_reason is empty';
        }

        if ($violations === []) {
            return;
        }

        \Illuminate\Support\Facades\Log::warning('subscription.cancel_invariant_failed', [
            'subscription_id' => $this->getKey(),
            'subscription_type' => $this->getMorphClass(),
            'caller' => $caller,
            'violations' => $violations,
        ]);

        throw new \RuntimeException(sprintf(
            'cancel invariant failed on %s#%d after %s: %s',
            $this->getMorphClass(),
            $this->getKey(),
            $caller,
            implode(', ', $violations),
        ));
    }

    /**
     * Find duplicate pending subscriptions for the same combination.
     * Excludes the current subscription if it has an ID.
     *
     * @return static|null The duplicate pending subscription, if found
     */
    public function findDuplicatePending(): ?static
    {
        $query = static::where('academy_id', $this->academy_id)
            ->where('student_id', $this->student_id)
            ->where('status', $this->getPendingStatus())
            ->where(function ($q) {
                $q->where('payment_status', SubscriptionPaymentStatus::PENDING)
                    ->orWhere('payment_status', 'pending');
            });

        // Exclude current subscription if it exists
        if ($this->id) {
            $query->where('id', '!=', $this->id);
        }

        // Add duplicate key field conditions
        foreach ($this->getDuplicateKeyFields() as $field) {
            if ($this->$field !== null) {
                $query->where($field, $this->$field);
            }
        }

        return $query->first();
    }

    /**
     * Find a recently-cancelled subscription matching the same combination,
     * suitable for reuse by a gateway-retry flow.
     *
     * Window matches the per-payment retry envelope (default 60 minutes):
     * when a student bounces between Paymob and Tap because the first
     * gateway expired, we want to un-cancel the prior row instead of
     * minting a sibling that produces a ghost activation.
     *
     * @param  int  $windowMinutes  Max age of the cancelled row to consider
     */
    public function findRecentCancelledForRetry(int $windowMinutes = 60): ?static
    {
        $query = static::where('academy_id', $this->academy_id)
            ->where('student_id', $this->student_id)
            ->where('status', $this->getCancelledStatus())
            ->where('cancelled_at', '>=', now()->subMinutes($windowMinutes));

        if ($this->id) {
            $query->where('id', '!=', $this->id);
        }

        foreach ($this->getDuplicateKeyFields() as $field) {
            if ($this->$field !== null) {
                $query->where($field, $this->$field);
            }
        }

        return $query->orderByDesc('cancelled_at')->first();
    }

    /**
     * Check if a duplicate active subscription exists.
     *
     * @return static|null The active subscription, if found
     */
    public function findDuplicateActive(): ?static
    {
        $query = static::where('academy_id', $this->academy_id)
            ->where('student_id', $this->student_id)
            ->where('status', $this->getActiveStatus());

        // Exclude current subscription if it exists
        if ($this->id) {
            $query->where('id', '!=', $this->id);
        }

        // Add duplicate key field conditions
        foreach ($this->getDuplicateKeyFields() as $field) {
            if ($this->$field !== null) {
                $query->where($field, $this->$field);
            }
        }

        return $query->first();
    }

    /**
     * Check if this subscription is pending and expired.
     */
    public function isPendingAndExpired(): bool
    {
        if ($this->status !== $this->getPendingStatus()) {
            return false;
        }

        $hours = config('subscriptions.pending.expires_after_hours', 48);

        return $this->created_at->lt(now()->subHours($hours));
    }

    /**
     * Check if this subscription can be paid (is pending and not expired).
     */
    public function canBePaid(): bool
    {
        return $this->status === $this->getPendingStatus()
            && ! $this->isPendingAndExpired();
    }

    /**
     * Get the duplicate key values for this subscription.
     *
     * @return array<string, mixed>
     */
    public function getDuplicateKeyValues(): array
    {
        $values = [];
        foreach ($this->getDuplicateKeyFields() as $field) {
            $values[$field] = $this->$field;
        }

        return $values;
    }
}
