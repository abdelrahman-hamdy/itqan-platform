<?php

namespace App\Enums;

/**
 * SubscriptionViewState — the SINGLE canonical state model every UI/API/gate
 * consumes for a subscription (per `docs/subscription-invariants.md §1`).
 *
 * Eight exhaustive cases. No other state may be represented. The value is
 * derived deterministically from `subscription + currentCycle + queuedCycle`
 * by {@see \App\Services\Subscription\SubscriptionPresentation::viewStateFor()}.
 *
 * Surfaces (mobile-app subscription card, student blade, supervisor table,
 * Filament resources) MUST consume this enum's case + the matching primary
 * action key — they do NOT re-derive state from raw `status`/`payment_status`
 * combinations. Every fresh `if ($sub->status === ... && $cycle->payment === ...)`
 * branch is a regression (INV-J1 / INV-J2).
 */
enum SubscriptionViewState: string
{
    /** Sub created, first cycle never paid. No access. Action: Pay. */
    case PENDING_FIRST_PAYMENT = 'pending_first_payment';

    /** Current cycle PAID, within dates, quota remaining. Full access. */
    case ACTIVE_PAID = 'active_paid';

    /**
     * Current cycle ACTIVE in dates, payment_status PENDING (hybrid / lie-state
     * today). Access granted but payment owed. Action: Pay.
     */
    case ACTIVE_PAYMENT_DUE = 'active_payment_due';

    /** Sub past `ends_at` but `grace_period_ends_at` > now. Action: Renew. */
    case GRACE_ADMIN = 'grace_admin';

    /**
     * Admin/supervisor-paused. No new sessions schedulable; future scheduled
     * sessions in the pause window were cancelled (P6.b). Action: Resume
     * (admin/supervisor only).
     */
    case PAUSED_ADMIN = 'paused_admin';

    /**
     * Cycle quota exhausted before `ends_at`. Behaves same as active_paid for
     * date math but flagged so UI prompts renewal. Action: Renew.
     */
    case PAUSED_END_OF_PERIOD = 'paused_end_of_period';

    /**
     * Past `ends_at` AND past `grace_period_ends_at` (or grace was never
     * granted). No access. Action: Renew / Resubscribe.
     */
    case EXPIRED = 'expired';

    /** Admin-cancelled (P3). Terminal. No access. */
    case CANCELLED = 'cancelled';

    /**
     * Translation key suffix for the helper line (e.g. "اشتراك نشط — متبقي N حصص").
     * Used by {@see \App\Services\Subscription\SubscriptionPresentation::helperLineFor()}.
     */
    public function helperKey(): string
    {
        return 'subscriptions.view_state.'.$this->value.'.helper';
    }

    /**
     * Translation key suffix for the badge label.
     */
    public function labelKey(): string
    {
        return 'subscriptions.view_state.'.$this->value.'.label';
    }

    /**
     * Localized badge label.
     */
    public function label(): string
    {
        return __($this->labelKey());
    }

    /**
     * Filament-flavoured badge colour. The eight cases map onto Filament's
     * standard palette (success / warning / danger / gray / primary / info).
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING_FIRST_PAYMENT => 'warning',
            self::ACTIVE_PAID => 'success',
            self::ACTIVE_PAYMENT_DUE => 'warning',
            self::GRACE_ADMIN => 'warning',
            self::PAUSED_ADMIN => 'info',
            self::PAUSED_END_OF_PERIOD => 'primary',
            self::EXPIRED => 'gray',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Tailwind utility classes for the badge — the student blade + supervisor
     * inspector both render the canonical badge directly from this method so
     * the per-state palette can't drift across surfaces (INV-J1).
     */
    public function badgeClasses(): string
    {
        return match ($this->badgeColor()) {
            'success' => 'bg-green-100 text-green-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'danger' => 'bg-red-100 text-red-800',
            'info' => 'bg-blue-100 text-blue-800',
            'primary' => 'bg-indigo-100 text-indigo-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Convenience: terminal states never accept actions other than "create a
     * brand-new subscription record". UI hides per-state primary actions
     * accordingly.
     */
    public function isTerminal(): bool
    {
        return $this === self::CANCELLED;
    }

    /**
     * Whether scheduling is permitted in this state (grace counts as
     * "as-if-active" per the contract; expired/cancelled do not).
     */
    public function allowsScheduling(): bool
    {
        return match ($this) {
            self::ACTIVE_PAID,
            self::ACTIVE_PAYMENT_DUE,
            self::GRACE_ADMIN => true,
            default => false,
        };
    }

    /**
     * G6: whether the current student-facing "Pay" route should accept this
     * subscription. Replaces the legacy
     * `acceptsRetryPayment() || isCurrentCyclePaymentPending()` predicate
     * pair on BaseSubscription — both predicates reduce to:
     *   - PENDING_FIRST_PAYMENT  (acceptsRetryPayment, first-payment retry)
     *   - ACTIVE_PAYMENT_DUE     (isCurrentCyclePaymentPending, hybrid)
     */
    public function allowsPaymentRetry(): bool
    {
        return match ($this) {
            self::PENDING_FIRST_PAYMENT,
            self::ACTIVE_PAYMENT_DUE => true,
            default => false,
        };
    }

    /** All case values as a plain array (for forms / DB checks). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
