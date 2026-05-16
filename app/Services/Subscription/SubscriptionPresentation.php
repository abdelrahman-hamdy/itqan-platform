<?php

namespace App\Services\Subscription;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionViewState;
use App\Enums\UserType;
use App\Models\BaseSubscription;
use App\Models\SubscriptionCycle;
use Carbon\Carbon;

/**
 * SubscriptionPresentation — the SOLE source of canonical view-state for
 * subscription surfaces. Read-only: never writes to a subscription, cycle,
 * or session row.
 *
 * Implements the deterministic algorithm from
 * `docs/subscription-invariants.md §1` (viewStateFor) and the per-state ×
 * per-role action matrix from §4 (primaryActionFor).
 *
 * Every UI/API/gate (mobile app subscription card, Filament resource badge,
 * student blade, supervisor table, teacher session list, parent overview)
 * MUST consume these methods. Reproducing the algorithm in a view or
 * controller is a regression (INV-J1 / INV-J2).
 *
 * The pricing/lock/reconciler call shape required of writer services does
 * not apply here — this service is pure.
 */
class SubscriptionPresentation
{
    // ========================================================================
    // §1 — viewStateFor (canonical state derivation)
    // ========================================================================

    /**
     * Derive the canonical SubscriptionViewState for `$sub`.
     *
     * The algorithm is the exact ordered cascade from §1 of the invariants
     * doc. Pure function: given identical input it always returns the same
     * case (INV-A7).
     *
     * `now` is evaluated in the academy timezone via `nowInAcademyTimezone()`
     * — display logic compares against academy-local time per the CLAUDE.md
     * timezone rules.
     */
    public function viewStateFor(BaseSubscription $sub): SubscriptionViewState
    {
        $status = $this->statusEnum($sub);

        // 1. Terminal: cancellation wins over everything.
        if ($status === SessionSubscriptionStatus::CANCELLED) {
            return SubscriptionViewState::CANCELLED;
        }

        $currentCycle = $this->loadCurrentCycle($sub);
        $now = nowInAcademyTimezone();

        // 2. No cycle ever materialised → pre-activation.
        if ($currentCycle === null) {
            return SubscriptionViewState::PENDING_FIRST_PAYMENT;
        }

        // 3. First-payment shape — §1 step 3: cycle is PENDING-payment AND
        //    the subscription has never recorded a paid cycle before.
        //
        //    The invariants doc references `cycle_state == PENDING`, but
        //    `SubscriptionCycle` doesn't carry a PENDING cycle state in this
        //    codebase (cycles are QUEUED → ACTIVE → ARCHIVED). The semantic
        //    equivalent we test here is "this is the first cycle and it has
        //    never been paid", expressed via `isBrandNewUnpaid()`.
        if ($this->isBrandNewUnpaid($sub, $currentCycle)) {
            return SubscriptionViewState::PENDING_FIRST_PAYMENT;
        }

        // 4. Admin/supervisor pause wins next.
        if ($status === SessionSubscriptionStatus::PAUSED) {
            return SubscriptionViewState::PAUSED_ADMIN;
        }

        // 5. Cycle not started yet:
        if ($currentCycle->starts_at !== null && $now->lt($currentCycle->starts_at)) {
            // The §1 algorithm folds "queued first activation" and "queued
            // but unpaid" into the same pending_first_payment surface.
            return SubscriptionViewState::PENDING_FIRST_PAYMENT;
        }

        // 6. Within the cycle window:
        if ($this->withinCycleWindow($currentCycle, $now)) {
            $sessionsRemaining = $this->cycleSessionsRemaining($currentCycle);

            if ($currentCycle->payment_status === SubscriptionCycle::PAYMENT_PAID) {
                return $sessionsRemaining > 0
                    ? SubscriptionViewState::ACTIVE_PAID
                    : SubscriptionViewState::PAUSED_END_OF_PERIOD;
            }

            // PAYMENT_PENDING — was the "lie-state" (in-window pending with
            // access). Now routes to PENDING_PAYMENT (no access) unless an
            // admin extended grace, which is checked at step 7 below.
            if ($currentCycle->payment_status === SubscriptionCycle::PAYMENT_PENDING) {
                $graceEndsAt = $currentCycle->grace_period_ends_at;
                if ($graceEndsAt !== null && $now->lt($graceEndsAt)) {
                    return SubscriptionViewState::GRACE_ADMIN;
                }

                return SubscriptionViewState::PENDING_PAYMENT;
            }
        }

        // 7. Past ends_at:
        $graceEndsAt = $currentCycle->grace_period_ends_at;
        if ($graceEndsAt !== null && $now->lt($graceEndsAt)) {
            return SubscriptionViewState::GRACE_ADMIN;
        }

        return SubscriptionViewState::EXPIRED;
    }

    // ========================================================================
    // §4 — primaryActionFor (per-state × per-role action matrix)
    // ========================================================================

    /**
     * Pick the single primary action key for `$sub` given the consumer's
     * role. Returns null when no primary action is offered (view-only).
     *
     * Action keys are the literals the UI binds to:
     *   - 'pay'           — gateway redirect for the outstanding cycle.
     *   - 'renew'         — Decision Table 3.1 renew routing.
     *   - 'resume'        — admin/supervisor resume of a paused sub.
     *   - 'confirm_cash'  — supervisor manual payment confirmation.
     *   - 'cancel'        — admin cancellation.
     *   - 'create_new'    — terminal-state "start a fresh subscription".
     *
     * The matrix mirrors §4 of the invariants doc. When the doc lists
     * multiple actions for a (state, role) pair we surface the PRIMARY one
     * (the leftmost, leading-CTA). Secondary actions (Edit, Pause, etc.)
     * are surfaced via separate menus, not by this single-action API.
     */
    public function primaryActionFor(BaseSubscription $sub, UserType $role): ?string
    {
        $state = $this->viewStateFor($sub);

        return match ([$state, $role]) {
            // pending_first_payment row
            [SubscriptionViewState::PENDING_FIRST_PAYMENT, UserType::STUDENT] => 'pay',
            [SubscriptionViewState::PENDING_FIRST_PAYMENT, UserType::PARENT] => 'pay',
            [SubscriptionViewState::PENDING_FIRST_PAYMENT, UserType::SUPERVISOR] => 'confirm_cash',
            [SubscriptionViewState::PENDING_FIRST_PAYMENT, UserType::ADMIN] => 'pay',
            [SubscriptionViewState::PENDING_FIRST_PAYMENT, UserType::SUPER_ADMIN] => 'pay',

            // active_paid: nothing to do
            [SubscriptionViewState::ACTIVE_PAID, UserType::ADMIN] => null,
            [SubscriptionViewState::ACTIVE_PAID, UserType::SUPER_ADMIN] => null,
            [SubscriptionViewState::ACTIVE_PAID, UserType::SUPERVISOR] => null,
            [SubscriptionViewState::ACTIVE_PAID, UserType::STUDENT] => null,
            [SubscriptionViewState::ACTIVE_PAID, UserType::PARENT] => null,

            // active_payment_due — pay outstanding (legacy; no longer
            // emitted by viewStateFor — see PENDING_PAYMENT below)
            [SubscriptionViewState::ACTIVE_PAYMENT_DUE, UserType::STUDENT] => 'pay',
            [SubscriptionViewState::ACTIVE_PAYMENT_DUE, UserType::PARENT] => 'pay',
            [SubscriptionViewState::ACTIVE_PAYMENT_DUE, UserType::SUPERVISOR] => 'confirm_cash',
            [SubscriptionViewState::ACTIVE_PAYMENT_DUE, UserType::ADMIN] => 'pay',
            [SubscriptionViewState::ACTIVE_PAYMENT_DUE, UserType::SUPER_ADMIN] => 'pay',

            // pending_payment — pay outstanding; no access until paid
            [SubscriptionViewState::PENDING_PAYMENT, UserType::STUDENT] => 'pay',
            [SubscriptionViewState::PENDING_PAYMENT, UserType::PARENT] => 'pay',
            [SubscriptionViewState::PENDING_PAYMENT, UserType::SUPERVISOR] => 'confirm_cash',
            [SubscriptionViewState::PENDING_PAYMENT, UserType::ADMIN] => 'pay',
            [SubscriptionViewState::PENDING_PAYMENT, UserType::SUPER_ADMIN] => 'pay',

            // grace_admin — renew
            [SubscriptionViewState::GRACE_ADMIN, UserType::STUDENT] => 'renew',
            [SubscriptionViewState::GRACE_ADMIN, UserType::PARENT] => 'renew',
            [SubscriptionViewState::GRACE_ADMIN, UserType::SUPERVISOR] => 'confirm_cash',
            [SubscriptionViewState::GRACE_ADMIN, UserType::ADMIN] => 'renew',
            [SubscriptionViewState::GRACE_ADMIN, UserType::SUPER_ADMIN] => 'renew',

            // paused_admin — admin/supervisor resume, students see no action
            [SubscriptionViewState::PAUSED_ADMIN, UserType::SUPERVISOR] => 'resume',
            [SubscriptionViewState::PAUSED_ADMIN, UserType::ADMIN] => 'resume',
            [SubscriptionViewState::PAUSED_ADMIN, UserType::SUPER_ADMIN] => 'resume',
            [SubscriptionViewState::PAUSED_ADMIN, UserType::STUDENT] => null,
            [SubscriptionViewState::PAUSED_ADMIN, UserType::PARENT] => null,

            // paused_end_of_period — renew
            [SubscriptionViewState::PAUSED_END_OF_PERIOD, UserType::STUDENT] => 'renew',
            [SubscriptionViewState::PAUSED_END_OF_PERIOD, UserType::PARENT] => 'renew',
            [SubscriptionViewState::PAUSED_END_OF_PERIOD, UserType::SUPERVISOR] => 'confirm_cash',
            [SubscriptionViewState::PAUSED_END_OF_PERIOD, UserType::ADMIN] => 'renew',
            [SubscriptionViewState::PAUSED_END_OF_PERIOD, UserType::SUPER_ADMIN] => 'renew',

            // expired — renew (= resubscribe path, P4 for supervisor cash)
            [SubscriptionViewState::EXPIRED, UserType::STUDENT] => 'renew',
            [SubscriptionViewState::EXPIRED, UserType::PARENT] => 'renew',
            [SubscriptionViewState::EXPIRED, UserType::SUPERVISOR] => 'confirm_cash',
            [SubscriptionViewState::EXPIRED, UserType::ADMIN] => 'renew',
            [SubscriptionViewState::EXPIRED, UserType::SUPER_ADMIN] => 'renew',

            // cancelled (terminal) — only "create new" path is available
            [SubscriptionViewState::CANCELLED, UserType::ADMIN] => 'create_new',
            [SubscriptionViewState::CANCELLED, UserType::SUPER_ADMIN] => 'create_new',
            [SubscriptionViewState::CANCELLED, UserType::SUPERVISOR] => 'create_new',
            [SubscriptionViewState::CANCELLED, UserType::STUDENT] => 'create_new',
            [SubscriptionViewState::CANCELLED, UserType::PARENT] => 'create_new',

            // Teachers: never see Pay/Renew/Cancel; schedule is gated elsewhere.
            default => null,
        };
    }

    // ========================================================================
    // Helper line (localized)
    // ========================================================================

    /**
     * Return the localized helper line for `$sub`. The line varies per
     * state and may interpolate `count` (sessions remaining) and `date`
     * (ends_at / grace_period_ends_at).
     *
     * The translation key follows the
     * `subscriptions.view_state.<case>.helper` convention so adding cases
     * never requires touching this method — only the lang files.
     */
    public function helperLineFor(BaseSubscription $sub): string
    {
        $state = $this->viewStateFor($sub);
        $cycle = $this->loadCurrentCycle($sub);

        $sessionsRemaining = $cycle ? $this->cycleSessionsRemaining($cycle) : null;
        $endsAt = $cycle?->ends_at ?? $sub->ends_at;
        $graceEndsAt = $cycle?->grace_period_ends_at ?? $sub->getGracePeriodEndsAt();

        return __($state->helperKey(), [
            'count' => (string) ($sessionsRemaining ?? 0),
            'date' => $this->formatDate($endsAt),
            'grace_date' => $this->formatDate($graceEndsAt),
        ]);
    }

    // ========================================================================
    // §J2 — API formatter dict
    // ========================================================================

    /**
     * Render the dict the mobile-app subscription card consumes.
     *
     * INV-J2: the API formatter MUST return `view_state` (case value) and
     * `primary_action` (action key) so the mobile app never re-derives state
     * locally. The `role` argument defaults to STUDENT — the most common
     * caller — but every role-aware controller passes its own role through.
     */
    public function formatForApi(BaseSubscription $sub, UserType $role = UserType::STUDENT): array
    {
        $state = $this->viewStateFor($sub);
        $cycle = $this->loadCurrentCycle($sub);

        return [
            'view_state' => $state->value,
            'primary_action' => $this->primaryActionFor($sub, $role),
            'helper_line' => $this->helperLineFor($sub),
            'sessions_remaining' => $cycle ? $this->cycleSessionsRemaining($cycle) : null,
            'ends_at' => $this->formatIso8601($cycle?->ends_at ?? $sub->ends_at),
            'grace_period_ends_at' => $this->formatIso8601(
                $cycle?->grace_period_ends_at ?? $sub->getGracePeriodEndsAt(),
            ),
        ];
    }

    // ========================================================================
    // Helpers (private)
    // ========================================================================

    /**
     * Load the current cycle without triggering a fresh query when the
     * relation is already eager-loaded.
     */
    private function loadCurrentCycle(BaseSubscription $sub): ?SubscriptionCycle
    {
        if ($sub->relationLoaded('currentCycle')) {
            $cycle = $sub->currentCycle;
        } else {
            $cycle = $sub->currentCycle()->first();
        }

        return $cycle instanceof SubscriptionCycle ? $cycle : null;
    }

    /**
     * Normalise the subscription's `status` column to the enum (defensive:
     * raw rows from legacy code paths may surface a string).
     */
    private function statusEnum(BaseSubscription $sub): SessionSubscriptionStatus
    {
        if ($sub->status instanceof SessionSubscriptionStatus) {
            return $sub->status;
        }

        // CourseSubscription casts `status` to EnrollmentStatus, which is a
        // different BackedEnum. Coerce via the underlying value so the view-
        // state lookup still works for all three sub types.
        $raw = $sub->status instanceof \BackedEnum
            ? (string) $sub->status->value
            : (string) ($sub->status ?? '');

        return SessionSubscriptionStatus::tryFrom($raw)
            ?? SessionSubscriptionStatus::PENDING;
    }

    /**
     * §1 step 3 — "first-payment shape". The cycle is PENDING-payment AND
     * the subscription has never recorded a paid cycle before. We check the
     * latter via `subscription.last_payment_date` (a paid cycle always
     * stamps it).
     */
    private function isBrandNewUnpaid(BaseSubscription $sub, SubscriptionCycle $cycle): bool
    {
        if ($cycle->payment_status !== SubscriptionCycle::PAYMENT_PENDING) {
            return false;
        }

        // Brand-new = no prior paid cycle. `last_payment_date` is the
        // cheapest proxy; falling back to "this is cycle_number == 1" keeps
        // us correct on rows that predate the timestamp.
        if ($sub->last_payment_date !== null) {
            return false;
        }

        return ((int) $cycle->cycle_number) <= 1;
    }

    /**
     * §1 step 6 — within the cycle's [starts_at, ends_at] window.
     */
    private function withinCycleWindow(SubscriptionCycle $cycle, Carbon $now): bool
    {
        if ($cycle->starts_at !== null && $now->lt($cycle->starts_at)) {
            return false;
        }

        if ($cycle->ends_at !== null && $now->gt($cycle->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Sessions left on this cycle, clamped at zero (INV-B4).
     */
    private function cycleSessionsRemaining(SubscriptionCycle $cycle): int
    {
        return max(0, (int) $cycle->total_sessions - (int) $cycle->sessions_used);
    }

    private function formatIso8601(?Carbon $when): ?string
    {
        return $when?->copy()->setTimezone(getAcademyTimezone())->toIso8601String();
    }

    /**
     * Format for the helper-line `:date` placeholder. Academy timezone,
     * date-only (the helper line is a sentence, not a timestamp).
     */
    private function formatDate(?Carbon $when): string
    {
        if ($when === null) {
            return '';
        }

        return $when->copy()->setTimezone(getAcademyTimezone())->format('Y-m-d');
    }
}
