<?php

namespace App\Services\Subscription;

use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionType;
use App\Enums\SubscriptionViewState;
use App\Enums\UserType;
use App\Exceptions\Subscription\RenewBlockedByPendingPayment;
use App\Jobs\RebuildFutureSessionsForCycle;
use App\Models\BaseSubscription;
use App\Models\Payment;
use App\Models\SubscriptionCycle;
use App\Models\User;
use App\Services\Subscription\Concerns\RecordsSubscriptionAudit;
use App\Support\Subscriptions\SubscriptionLock;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SubscriptionLifecycle — the SOLE writer of subscription lifecycle
 * transitions.
 *
 * Implements every locked mutator listed in §6 of
 * `docs/subscription-invariants.md`:
 *
 *     create · activate · pause · resume · extend · cancel · renew ·
 *     resubscribe · expire · advanceCycle
 *
 * Each public method follows the shape:
 *
 *     SubscriptionLock::for($sub, function () {
 *         DB::transaction(function () {
 *             // ... mutate cycle rows
 *             // ... mutate session_consumption rows (via SubscriptionConsumption)
 *             // ... queue jobs (RebuildFutureSessionsForCycle, notifications)
 *             // ... reconciler->sync($sub);  ← LAST line
 *         });
 *     });
 *
 * The trait {@see RecordsSubscriptionAudit} wraps every public mutator in
 * `withAudit($sub, $action, $source, $actor, $work)`, capturing before /
 * after snapshots and any invariant violations the reconciler raises.
 *
 * Existing services (`SubscriptionCreationService`, `SubscriptionRenewalService`,
 * `SubscriptionMaintenanceService`) remain in place during the A.5 →
 * A.7 → E migration. This service composes with them where the legacy
 * code is already correct + idempotent; the audit + lock + reconciler
 * wrapping on the new public surface is non-negotiable.
 */
class SubscriptionLifecycle
{
    use RecordsSubscriptionAudit;

    public function __construct(
        private readonly SubscriptionReconciler $reconciler,
        private readonly SubscriptionPricing $pricing,
        private readonly SubscriptionConsumption $consumption,
        private readonly Dispatcher $dispatcher,
    ) {}

    // ========================================================================
    // create / activate
    // ========================================================================

    /**
     * Create a new subscription + its first (PENDING) cycle.
     *
     * Composes with the existing `SubscriptionCreationService` for the
     * type-specific factory work — the model's static `createSubscription`
     * factory is already idempotent and runs inside a DB::transaction. We
     * wrap with our lock + reconciler so the row lands in invariant-clean
     * state and the audit row captures the create event.
     */
    public function create(
        SubscriptionType $type,
        array $data,
        User $actor,
        string $source = 'admin',
        array $duplicateKeyValues = [],
    ): BaseSubscription {
        $creation = app(SubscriptionCreationService::class);

        // Plan 4.7 / REOPEN #1 — Lifecycle::create is the single canonical
        // entry point. When the caller supplies the per-type duplicate keys
        // (e.g. ['quran_teacher_id' => 42, 'package_id' => 7]) we run the
        // 3-step dedup pipeline:
        //   1. reuseRecentCancelled (60-min retry-window — Bug #9)
        //   2. cancelDuplicatePending (no window — cancel every PENDING sibling)
        //   3. create
        // SubscriptionCreationService owns the implementation today; Phase E
        // folds those steps into private helpers on this service and deletes
        // the Creation service entirely. The user-visible API stays this one.
        $subscription = empty($duplicateKeyValues)
            ? $creation->create($type->value, $data)
            : $creation->createWithDuplicateHandling($type->value, $data, $duplicateKeyValues);

        return SubscriptionLock::for($subscription, function () use ($subscription, $actor, $source) {
            return DB::transaction(function () use ($subscription, $actor, $source) {
                return $this->withAudit(
                    $subscription,
                    'create',
                    $source,
                    $actor,
                    function () use ($subscription) {
                        // Materialise the first cycle if the factory didn't
                        // (legacy factories may not — ensureCurrentCycle is
                        // idempotent).
                        $subscription->ensureCurrentCycle();
                        $this->reconciler->sync($subscription);

                        return $subscription->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    /**
     * First activation — current cycle PENDING → ACTIVE+PAID, sub
     * PENDING → ACTIVE. Called once per subscription on the first
     * successful payment.
     *
     * Subsequent payments go through `SubscriptionPayment::markCyclePaid`,
     * not this method.
     */
    public function activate(
        BaseSubscription $sub,
        ?Payment $payment,
        User $actor,
        string $source,
    ): BaseSubscription {
        return SubscriptionLock::for($sub, function () use ($sub, $payment, $actor, $source) {
            return DB::transaction(function () use ($sub, $payment, $actor, $source) {
                return $this->withAudit(
                    $sub,
                    'activate',
                    $source,
                    $actor,
                    function () use ($sub, $payment) {
                        $cycle = $sub->ensureCurrentCycle();

                        SubscriptionCycle::query()
                            ->whereKey($cycle->getKey())
                            ->lockForUpdate()
                            ->update([
                                'cycle_state' => SubscriptionCycle::STATE_ACTIVE,
                                'payment_status' => SubscriptionCycle::PAYMENT_PAID,
                                'payment_id' => $payment?->getKey() ?? $cycle->payment_id,
                            ]);

                        // First-activation stamp drives the §1 "first-payment
                        // shape" predicate in SubscriptionPresentation.
                        $sub->last_payment_date = now();
                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        $this->reconciler->sync($sub);

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    // ========================================================================
    // pause / resume / extend
    // ========================================================================

    /**
     * Pause a subscription (INV-F1: ACTIVE only, never during grace).
     *
     * INV-F5: future scheduled sessions inside the pause window
     * `[now, now + max_pause_days]` are cancelled with reason
     * `cancelled_by_pause`. Each cancellation reverses its
     * `session_consumption` row (per INV-B5 + P9).
     */
    public function pause(
        BaseSubscription $sub,
        User $actor,
        string $reason,
    ): BaseSubscription {
        return SubscriptionLock::for($sub, function () use ($sub, $actor, $reason) {
            return DB::transaction(function () use ($sub, $actor, $reason) {
                return $this->withAudit(
                    $sub,
                    'pause',
                    'admin',
                    $actor,
                    function () use ($sub, $actor, $reason) {
                        // INV-F1: ACTIVE only, no grace.
                        if ($sub->status !== SessionSubscriptionStatus::ACTIVE) {
                            throw new \RuntimeException(sprintf(
                                'Cannot pause: subscription %s#%d is in status %s (INV-F1 requires ACTIVE).',
                                $sub->getMorphClass(),
                                $sub->getKey(),
                                $sub->status?->value ?? 'null',
                            ));
                        }

                        if ($sub->getGracePeriodEndsAt() !== null
                            && $sub->getGracePeriodEndsAt()->isFuture()) {
                            throw new \RuntimeException(sprintf(
                                'Cannot pause: subscription %s#%d is in grace period (INV-F1 forbids).',
                                $sub->getMorphClass(),
                                $sub->getKey(),
                            ));
                        }

                        $sub->status = SessionSubscriptionStatus::PAUSED;
                        $sub->paused_at = now();
                        $sub->pause_reason = $reason;
                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        $this->cancelSessionsInPauseWindow($sub, $actor, $reason);

                        $this->reconciler->sync($sub);

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    /**
     * Resume a paused subscription (INV-F4).
     *
     * ends_at += (now - paused_at). The queued cycle's starts_at is
     * recomputed to align with the new current-cycle ends_at.
     */
    public function resume(BaseSubscription $sub, User $actor): BaseSubscription
    {
        return SubscriptionLock::for($sub, function () use ($sub, $actor) {
            return DB::transaction(function () use ($sub, $actor) {
                return $this->withAudit(
                    $sub,
                    'resume',
                    'admin',
                    $actor,
                    function () use ($sub) {
                        if ($sub->status !== SessionSubscriptionStatus::PAUSED) {
                            throw new \RuntimeException(sprintf(
                                'Cannot resume: subscription %s#%d is in status %s (must be PAUSED).',
                                $sub->getMorphClass(),
                                $sub->getKey(),
                                $sub->status?->value ?? 'null',
                            ));
                        }

                        $pausedAt = $sub->paused_at;
                        $extension = $pausedAt
                            ? max(0, $pausedAt->diffInSeconds(now()))
                            : 0;

                        $currentCycle = $sub->currentCycle()->lockForUpdate()->first();
                        if ($currentCycle && $currentCycle->ends_at) {
                            $currentCycle->ends_at = $currentCycle->ends_at->copy()->addSeconds($extension);
                            $currentCycle->save();
                        }

                        $queuedCycle = $sub->queuedCycle()->lockForUpdate()->first();
                        if ($queuedCycle && $currentCycle) {
                            // Shift the queued window by the same amount so the
                            // student doesn't lose `$extension` seconds of paid
                            // time. Re-anchor starts_at to the new current
                            // ends_at (INV-A5) and slide ends_at by the same
                            // delta so the queued window length is preserved.
                            $queuedCycle->starts_at = $currentCycle->ends_at;
                            if ($queuedCycle->ends_at) {
                                $queuedCycle->ends_at = $queuedCycle->ends_at->copy()->addSeconds($extension);
                            }
                            $queuedCycle->save();
                        }

                        $sub->status = SessionSubscriptionStatus::ACTIVE;
                        $sub->paused_at = null;
                        $sub->pause_reason = null;
                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        $this->reconciler->sync($sub);

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    /**
     * G9 — reactivate a CANCELLED subscription in place.
     *
     * Distinct from {@see resubscribe()}: resubscribe mints a brand-new
     * cycle for an expired sub. reactivate restores the existing thread —
     * the student explicitly opted out, now wants back in WITHOUT losing
     * the cycle history.
     *
     * Side effects:
     *   - clears the grace-period metadata keys
     *   - status=ACTIVE, payment_status=PAID, last_payment_date=now()
     *   - resets starts_at/ends_at if past (via billing_cycle->calculateEndDate)
     *   - re-enables the linked circle/lesson (syncLinkedEducationUnitActiveFlag)
     *   - un-suspends future sessions cancelled during the cancellation window
     *   - auto_renew is left FALSE — opt-back-in is explicit, not automatic.
     */
    public function reactivate(BaseSubscription $sub, User $actor): BaseSubscription
    {
        return SubscriptionLock::for($sub, function () use ($sub, $actor) {
            return DB::transaction(function () use ($sub, $actor) {
                return $this->withAudit(
                    $sub,
                    'reactivate',
                    'admin',
                    $actor,
                    function () use ($sub) {
                        if ($sub->status !== SessionSubscriptionStatus::CANCELLED) {
                            throw new \RuntimeException(sprintf(
                                'Cannot reactivate: subscription %s#%d is in status %s (must be CANCELLED).',
                                $sub->getMorphClass(),
                                $sub->getKey(),
                                $sub->status?->value ?? 'null',
                            ));
                        }

                        $metadata = $sub->metadata ?? [];
                        foreach ([
                            'grace_period_ends_at',
                            'grace_period_expires_at',
                            'grace_period_started_at',
                            'grace_notification_last_sent_at',
                            'renewal_failed_count',
                            'last_renewal_failure_at',
                            'last_renewal_failure_reason',
                        ] as $key) {
                            unset($metadata[$key]);
                        }

                        $sub->status = SessionSubscriptionStatus::ACTIVE;
                        $sub->payment_status = SubscriptionPaymentStatus::PAID;
                        $sub->last_payment_date = now();
                        $sub->cancelled_at = null;
                        $sub->cancellation_reason = null;
                        $sub->auto_renew = false;
                        $sub->metadata = $metadata ?: null;

                        if (! $sub->starts_at || ($sub->ends_at && $sub->ends_at->isPast())) {
                            $sub->starts_at = now();
                            if (method_exists($sub, 'calculateEndDate')) {
                                $sub->ends_at = $sub->calculateEndDate(now());
                            }
                        }

                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        if (method_exists($sub, 'syncLinkedEducationUnitActiveFlag')) {
                            $sub->syncLinkedEducationUnitActiveFlag(true);
                        }
                        if (method_exists($sub, 'restoreSuspendedSessions')) {
                            $sub->restoreSuspendedSessions();
                        }

                        $this->reconciler->sync($sub);

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    /**
     * INV-F2: extend ONLY writes `grace_period_ends_at`. NEVER touches
     * `ends_at`. Display badge becomes `grace_admin`.
     *
     * Capped at `config('subscriptions.max_grace_days')`. Higher values
     * raise — admin override is a separate feature (Phase A.7).
     */
    public function extend(
        BaseSubscription $sub,
        int $graceDays,
        User $actor,
        string $reason,
    ): BaseSubscription {
        $cap = (int) config('subscriptions.max_grace_days', 14);
        if ($graceDays < 1 || $graceDays > $cap) {
            throw new \InvalidArgumentException(sprintf(
                'graceDays must be in [1, %d]; got %d.',
                $cap,
                $graceDays,
            ));
        }

        return SubscriptionLock::for($sub, function () use ($sub, $graceDays, $actor, $reason) {
            return DB::transaction(function () use ($sub, $graceDays, $actor, $reason) {
                return $this->withAudit(
                    $sub,
                    'extend',
                    'admin',
                    $actor,
                    function () use ($sub, $graceDays, $actor, $reason) {
                        $currentCycle = $sub->currentCycle()->lockForUpdate()->first();
                        if ($currentCycle === null) {
                            throw new \RuntimeException(sprintf(
                                'Cannot extend %s#%d: no current cycle.',
                                $sub->getMorphClass(),
                                $sub->getKey(),
                            ));
                        }

                        // INV-F2 — grace stacks on prior grace if present,
                        // otherwise from ends_at.
                        $baseDate = $currentCycle->grace_period_ends_at
                            ?? $currentCycle->ends_at
                            ?? now();

                        $newGraceEndsAt = $baseDate->copy()->addDays($graceDays);

                        // Only writes grace; ends_at is untouched. Record
                        // an extensions audit entry in metadata.
                        $metadata = $currentCycle->metadata ?? [];
                        $extensions = $metadata['extensions'] ?? [];
                        $extensions[] = [
                            'type' => 'grace_period',
                            'grace_days' => $graceDays,
                            'extended_by_user_id' => $actor->getKey(),
                            'reason' => $reason,
                            'ends_at_at_time' => $currentCycle->ends_at?->toDateTimeString(),
                            'grace_period_ends_at' => $newGraceEndsAt->toDateTimeString(),
                            'extended_at' => now()->toDateTimeString(),
                        ];
                        $cap = (int) config('subscriptions.grace.extensions_log_cap', 50);
                        $metadata['extensions'] = $cap > 0
                            ? array_slice($extensions, -$cap)
                            : $extensions;

                        $currentCycle->grace_period_ends_at = $newGraceEndsAt;
                        $currentCycle->metadata = $metadata;
                        $currentCycle->save();

                        $this->reconciler->sync($sub);

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    // ========================================================================
    // cancel (admin-only)
    // ========================================================================

    /**
     * Admin cancellation (INV-G2). Sets `cancelled_at`,
     * `cancelled_by_user_id`, `cancellation_reason`. Future scheduled
     * sessions are cancelled (P9 reverses consumption). Terminal.
     *
     * INV-G1 is enforced upstream by the controller layer (no student
     * cancel route exists post-Phase A.7); this method ALSO refuses if
     * the actor isn't an admin/super_admin/supervisor — supervisors can
     * cancel per the §4 matrix, admins can always cancel, students never
     * reach this method.
     */
    public function cancel(
        BaseSubscription $sub,
        User $actor,
        string $reason,
    ): BaseSubscription {
        $this->assertCancelAuthorised($actor);

        return SubscriptionLock::for($sub, function () use ($sub, $actor, $reason) {
            return DB::transaction(function () use ($sub, $actor, $reason) {
                return $this->withAudit(
                    $sub,
                    'cancel',
                    'admin',
                    $actor,
                    function () use ($sub, $actor, $reason) {
                        $sub->status = SessionSubscriptionStatus::CANCELLED;
                        $sub->cancelled_at = now();
                        $sub->cancellation_reason = $reason;
                        if (DB::getSchemaBuilder()->hasColumn($sub->getTable(), 'cancelled_by_user_id')) {
                            $sub->cancelled_by_user_id = $actor->getKey();
                        } else {
                            $metadata = $sub->metadata ?? [];
                            $metadata['cancelled_by_user_id'] = $actor->getKey();
                            $sub->metadata = $metadata;
                        }
                        $sub->auto_renew = false;
                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        // Archive the current cycle so it cannot promote.
                        $currentCycle = $sub->currentCycle()->lockForUpdate()->first();
                        if ($currentCycle && $currentCycle->cycle_state !== SubscriptionCycle::STATE_ARCHIVED) {
                            $currentCycle->cycle_state = SubscriptionCycle::STATE_ARCHIVED;
                            $currentCycle->archived_at = now();
                            $currentCycle->save();
                        }

                        // P9 — cancel future scheduled sessions + reverse consumption.
                        $this->cancelAllFutureSessions($sub, $actor, $reason);

                        $this->reconciler->sync($sub);

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    // ========================================================================
    // renew / resubscribe
    // ========================================================================

    /**
     * Renewal — Decision Table 3.1 routing.
     *
     *   - active_payment_due (current cycle PENDING) → throws
     *     RenewBlockedByPendingPayment so the controller routes to the
     *     existing-payment flow.
     *   - active_paid / paused_end_of_period → delegates to the existing
     *     SubscriptionRenewalService::renew(), then dispatches
     *     RebuildFutureSessionsForCycle if the new cycle's package_id
     *     differs from the previous package.
     *   - grace_admin / expired → routes to resubscribe() (immediate
     *     replacement starting today).
     */
    public function renew(
        BaseSubscription $sub,
        array $options,
        User $actor,
        string $source,
    ): BaseSubscription {
        // Decision Table 3.1 routing happens BEFORE the lock so the
        // hybrid + expired branches don't double-acquire.
        $presentation = app(SubscriptionPresentation::class);
        $state = $presentation->viewStateFor($sub);

        if ($state === SubscriptionViewState::ACTIVE_PAYMENT_DUE) {
            $pendingCycle = $sub->pendingPaymentCycle() ?? $sub->currentCycle;
            throw new RenewBlockedByPendingPayment($sub, $pendingCycle);
        }

        if (in_array($state, [SubscriptionViewState::EXPIRED, SubscriptionViewState::GRACE_ADMIN], true)) {
            return $this->resubscribe($sub, $options, $actor, $source);
        }

        return SubscriptionLock::for($sub, function () use ($sub, $options, $actor, $source) {
            return DB::transaction(function () use ($sub, $options, $actor, $source) {
                return $this->withAudit(
                    $sub,
                    'renew',
                    $source,
                    $actor,
                    function () use ($sub, $options, $actor) {
                        $previousPackageId = $sub->currentCycle?->package_id;

                        // Compose with the legacy renewal service — it
                        // already handles queue-vs-replace, carryover
                        // policy, payment creation, and per-type session
                        // generation. We re-wrap with our reconciler so
                        // invariants are re-checked at the end.
                        $renewal = app(SubscriptionRenewalService::class);
                        $renewed = $renewal->renew($sub, array_merge($options, [
                            'actor_id' => $actor->getKey(),
                        ]));

                        $renewed = $renewed->fresh(['currentCycle', 'queuedCycle']);

                        // INV-E2 — package change propagation.
                        $newCycle = $renewed->queuedCycle()->first()
                            ?? $renewed->currentCycle()->first();
                        if ($newCycle !== null
                            && $previousPackageId !== null
                            && $newCycle->package_id !== null
                            && (int) $newCycle->package_id !== (int) $previousPackageId
                        ) {
                            $this->dispatcher->dispatch(
                                new RebuildFutureSessionsForCycle(
                                    cycleId: (int) $newCycle->getKey(),
                                    cycleType: $renewed->getMorphClass(),
                                ),
                            );
                        }

                        $this->reconciler->sync($renewed);

                        return $renewed->fresh(['currentCycle', 'queuedCycle']);
                    },
                );
            });
        });
    }

    /**
     * Resubscribe — replaces today, archives current cycle, mints a new
     * one starting now with the existing package.
     *
     * Supervisor late-cash on an expired sub (P4) routes here from
     * `SubscriptionPayment::confirmCashPayment`.
     */
    public function resubscribe(
        BaseSubscription $sub,
        array $options,
        User $actor,
        string $source,
    ): BaseSubscription {
        return SubscriptionLock::for($sub, function () use ($sub, $options, $actor, $source) {
            return DB::transaction(function () use ($sub, $options, $actor, $source) {
                return $this->withAudit(
                    $sub,
                    'resubscribe',
                    $source,
                    $actor,
                    function () use ($sub, $options, $actor) {
                        $previousPackageId = $sub->currentCycle?->package_id;

                        $renewal = app(SubscriptionRenewalService::class);
                        $resubbed = $renewal->resubscribe($sub, array_merge($options, [
                            'actor_id' => $actor->getKey(),
                            'force_replace_now' => true,
                        ]));

                        $resubbed = $resubbed->fresh(['currentCycle']);

                        $newCycle = $resubbed->currentCycle()->first();
                        if ($newCycle !== null
                            && $previousPackageId !== null
                            && $newCycle->package_id !== null
                            && (int) $newCycle->package_id !== (int) $previousPackageId
                        ) {
                            $this->dispatcher->dispatch(
                                new RebuildFutureSessionsForCycle(
                                    cycleId: (int) $newCycle->getKey(),
                                    cycleType: $resubbed->getMorphClass(),
                                ),
                            );
                        }

                        $this->reconciler->sync($resubbed);

                        return $resubbed->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    // ========================================================================
    // expire (cron) + advanceCycle (cron)
    // ========================================================================

    /**
     * Expire a subscription whose `ends_at` has passed.
     *
     * INV-G4 — hybrid-expire path: if the current cycle is still
     * PAYMENT_PENDING AND ends_at < now, transition cycle.payment_status
     * → FAILED, cycle.cycle_state → ARCHIVED, sub.status → EXPIRED, and
     * notify the student.
     *
     * Otherwise the standard expiry transition runs: cycle ARCHIVED,
     * sub.status → EXPIRED.
     */
    public function expire(
        BaseSubscription $sub,
        ?User $actor = null,
        string $source = 'cron',
    ): BaseSubscription {
        return SubscriptionLock::for($sub, function () use ($sub, $actor, $source) {
            return DB::transaction(function () use ($sub, $actor, $source) {
                return $this->withAudit(
                    $sub,
                    'expire',
                    $source,
                    $actor,
                    function () use ($sub) {
                        $currentCycle = $sub->currentCycle()->lockForUpdate()->first();

                        $isHybridExpire = $currentCycle !== null
                            && $currentCycle->payment_status === SubscriptionCycle::PAYMENT_PENDING
                            && $currentCycle->ends_at !== null
                            && $currentCycle->ends_at->isPast();

                        if ($currentCycle !== null) {
                            $currentCycle->cycle_state = SubscriptionCycle::STATE_ARCHIVED;
                            $currentCycle->archived_at = now();
                            if ($isHybridExpire) {
                                $currentCycle->payment_status = SubscriptionCycle::PAYMENT_FAILED;
                            }
                            $currentCycle->save();
                        }

                        $sub->status = SessionSubscriptionStatus::EXPIRED;
                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        $this->reconciler->sync($sub);

                        if ($isHybridExpire) {
                            // G7.b: P8 hybrid-expire path — student's last
                            // cycle expired with payment still pending. Use
                            // the dedicated SubscriptionExpiredUnpaid notice
                            // so the CTA surfaces "Pay overdue + resubscribe"
                            // instead of the plain "Renew" copy used for the
                            // clean-expire flow.
                            try {
                                $sub->student?->notify(
                                    new \App\Notifications\SubscriptionExpiredUnpaid($sub),
                                );
                            } catch (Throwable $notifyError) {
                                Log::warning('subscription.hybrid_expire_unpaid_notify_failed', [
                                    'subscription_id' => $sub->getKey(),
                                    'subscription_type' => $sub->getMorphClass(),
                                    'cycle_id' => $currentCycle?->getKey(),
                                    'error' => $notifyError->getMessage(),
                                ]);
                            }
                        }

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    /**
     * Promote a queued cycle to active when the current cycle ends.
     *
     * INV-B6 — counters are NOT reset on promotion. The queued cycle
     * already carries its own (zeroed) counters from materialisation; we
     * only flip the state, archive the previous cycle, and re-mirror.
     */
    public function advanceCycle(
        BaseSubscription $sub,
        ?User $actor = null,
        string $source = 'cron',
    ): BaseSubscription {
        return SubscriptionLock::for($sub, function () use ($sub, $actor, $source) {
            return DB::transaction(function () use ($sub, $actor, $source) {
                return $this->withAudit(
                    $sub,
                    'advance_cycle',
                    $source,
                    $actor,
                    function () use ($sub) {
                        $currentCycle = $sub->currentCycle()->lockForUpdate()->first();
                        $queuedCycle = $sub->queuedCycle()->lockForUpdate()->first();

                        if ($queuedCycle === null) {
                            // Nothing to advance — surface as a no-op so
                            // the audit row records the cron tick.
                            return $sub->fresh(['currentCycle']);
                        }

                        // Archive the previous cycle if it's still active.
                        if ($currentCycle !== null
                            && $currentCycle->cycle_state !== SubscriptionCycle::STATE_ARCHIVED) {
                            $currentCycle->cycle_state = SubscriptionCycle::STATE_ARCHIVED;
                            $currentCycle->archived_at = now();
                            $currentCycle->save();
                        }

                        // Promote the queued cycle. INV-B6 — no counter
                        // reset; we flip state only.
                        $queuedCycle->cycle_state = SubscriptionCycle::STATE_ACTIVE;
                        $queuedCycle->save();

                        $sub->current_cycle_id = $queuedCycle->getKey();
                        $sub->status = SessionSubscriptionStatus::ACTIVE;
                        $sub->reconciling = true;
                        try {
                            $sub->save();
                        } finally {
                            $sub->reconciling = false;
                        }

                        $this->reconciler->sync($sub);

                        return $sub->fresh(['currentCycle']);
                    },
                );
            });
        });
    }

    // ========================================================================
    // Helpers (private)
    // ========================================================================

    /**
     * INV-F5 — cancel future SCHEDULED/READY sessions inside the pause
     * window, reverse their consumption rows, and notify the student
     * (P9 / G7.a).
     *
     * Safety: ONGOING sessions are deliberately untouched — kicking
     * students mid-meeting violated the
     * `meeting_auto_complete_must_check_participants` rule on 2026-04-20.
     * The natural-end path handles the in-flight case.
     */
    private function cancelSessionsInPauseWindow(
        BaseSubscription $sub,
        User $actor,
        string $reason,
    ): void {
        $windowDays = (int) config('subscriptions.max_pause_days', 30);
        $windowEnd = now()->copy()->addDays($windowDays);

        $this->cancelFutureSessions(
            $sub,
            $actor,
            scheduledBetween: [now(), $windowEnd],
            newStatus: \App\Enums\SessionStatus::SUSPENDED->value,
            consumptionReason: 'cancelled_by_pause: '.$reason,
            notificationCause: 'subscription_paused',
        );
    }

    /**
     * P9 / G7.a — cancel every future SCHEDULED/READY session belonging
     * to this subscription, reverse the consumption rows, and notify the
     * student. ONGOING sessions are not touched (same safety rule as
     * cancelSessionsInPauseWindow).
     */
    private function cancelAllFutureSessions(
        BaseSubscription $sub,
        User $actor,
        string $reason,
    ): void {
        $this->cancelFutureSessions(
            $sub,
            $actor,
            scheduledBetween: [now(), null],
            newStatus: \App\Enums\SessionStatus::CANCELLED->value,
            consumptionReason: 'cancelled_by_subscription_cancel: '.$reason,
            notificationCause: 'subscription_cancelled',
        );
    }

    /**
     * Shared cancel-future-sessions body for the pause + cancel paths.
     * Walks SCHEDULED/READY sessions in the given window, flips them to
     * $newStatus, reverses any consumption row charged against each, and
     * fires a SessionCancelledBySubscriptionPause notification.
     *
     * @param  array{0:\Carbon\Carbon,1:\Carbon\Carbon|null}  $scheduledBetween
     */
    private function cancelFutureSessions(
        BaseSubscription $sub,
        User $actor,
        array $scheduledBetween,
        string $newStatus,
        string $consumptionReason,
        string $notificationCause,
    ): void {
        if (! method_exists($sub, 'sessions')) {
            return;
        }

        $query = $sub->sessions()
            ->whereIn('status', [
                \App\Enums\SessionStatus::SCHEDULED->value,
                \App\Enums\SessionStatus::READY->value,
            ])
            ->where('scheduled_at', '>', $scheduledBetween[0]);

        if ($scheduledBetween[1] !== null) {
            $query->where('scheduled_at', '<=', $scheduledBetween[1]);
        }

        $sessions = $query->lockForUpdate()->get();

        $student = $sub->student;

        foreach ($sessions as $session) {
            $session->status = $newStatus;
            $session->save();

            $consumptionRows = \App\Models\SessionConsumption::query()
                ->where('session_id', $session->getKey())
                ->where('session_type', $session->getMorphClass())
                ->where('subscription_id', $sub->getKey())
                ->where('subscription_type', $sub->getMorphClass())
                ->whereNull('reversed_at')
                ->get();

            foreach ($consumptionRows as $row) {
                // INV-C1: caller already holds SubscriptionLock::for($sub).
                // The cache lock has no reentrancy registry — calling
                // reverse() here would block 5s on its own lock and throw
                // SubscriptionLockTimeout, rolling back the whole pause/cancel.
                $this->consumption->reverseLocked($row, $consumptionReason, $actor);
            }

            // Notify the student so the upcoming-session card disappears
            // from their dashboard. Wrapped because a notification failure
            // must NOT roll the whole pause/cancel back.
            try {
                $student?->notify(
                    new \App\Notifications\SessionCancelledBySubscriptionPause(
                        $session,
                        $sub,
                        $notificationCause,
                    ),
                );
            } catch (Throwable $notifyError) {
                Log::warning('subscription.session_cancel_notify_failed', [
                    'subscription_id' => $sub->getKey(),
                    'session_id' => $session->getKey(),
                    'cause' => $notificationCause,
                    'error' => $notifyError->getMessage(),
                ]);
            }
        }
    }

    // ========================================================================
    // Admin data-fix mutators (F2 — cycle inspector + editor)
    // ========================================================================

    /**
     * Hard-delete a single CLEAN scheduled session belonging to $sub.
     *
     * "Clean" guarding lives in the supervisor controller (it has the user-
     * facing dependency list). This method enforces the canonical mutator
     * shape (lock + audit + reconciler) so a delete from any caller stays
     * INV-A1/INV-B3 clean.
     *
     * The cycle's session counters are derived from session_consumption
     * (INV-B3), not from the session table; deleting a clean session has no
     * effect on sessions_used. The reconciler still runs as backstop.
     */
    public function adminDeleteSession(
        BaseSubscription $sub,
        \App\Models\BaseSession $session,
        User $actor,
    ): void {
        $this->runAdminMutation($sub, 'admin.delete_session', $actor, function () use ($session) {
            $session->forceDelete();
        });
    }

    /**
     * Mark a single scheduled session as CANCELLED. Soft-cancel — the row
     * stays, status flips. No consumption manipulation here; admin walks
     * the consumption rows separately via the inspector's per-row actions
     * (INV-B3 read-derived from session_consumption regardless).
     */
    public function adminCancelSession(
        BaseSubscription $sub,
        \App\Models\BaseSession $session,
        User $actor,
    ): void {
        $this->runAdminMutation($sub, 'admin.cancel_session', $actor, function () use ($session) {
            $session->status = \App\Enums\SessionStatus::CANCELLED;
            $session->save();
        });
    }

    /**
     * Apply a validated patch to a SubscriptionCycle. Caller is responsible
     * for conflict-detection (see {@see \App\Support\Subscriptions\CycleEditValidator})
     * — this method only enforces the mutator-shape contract.
     *
     * If `ends_at` is in the patch AND a queued sibling exists, the queued
     * cycle's `starts_at` is realigned to the new ends_at to preserve INV-A5
     * (queued anchors to active's end). All other fields pass through
     * verbatim from `$patch`.
     *
     * @param  array<string, mixed>  $patch  Subset of cycle columns to update
     */
    public function adminEditCycle(
        BaseSubscription $sub,
        SubscriptionCycle $cycle,
        array $patch,
        User $actor,
    ): SubscriptionCycle {
        return $this->runAdminMutation($sub, 'admin.edit_cycle', $actor, function () use ($sub, $cycle, $patch) {
            // Capture before fill() — once fill() runs `$cycle->ends_at`
            // already reflects the patched value and the delta would be zero.
            $previousEndsAt = $cycle->ends_at;

            $cycle->fill($patch);
            $cycle->save();

            if (array_key_exists('ends_at', $patch)) {
                $queued = $sub->queuedCycle()->first();
                if ($queued !== null && $queued->id !== $cycle->id) {
                    // INV-A5: starts_at re-anchors to the new current ends_at.
                    // Slide ends_at by the same delta so the queued window
                    // length is preserved — admin shifting the current cycle
                    // shouldn't silently shrink (or grow) paid queued time.
                    $queued->starts_at = $cycle->ends_at;
                    if ($previousEndsAt && $cycle->ends_at && $queued->ends_at) {
                        $deltaSeconds = $previousEndsAt->diffInSeconds($cycle->ends_at, false);
                        if ($deltaSeconds !== 0) {
                            $queued->ends_at = $queued->ends_at->copy()->addSeconds($deltaSeconds);
                        }
                    }
                    $queued->save();
                }
            }

            return $cycle->fresh();
        });
    }

    /**
     * Canonical envelope for the F2 admin mutators: SubscriptionLock → DB
     * transaction → withAudit($work) → reconciler->sync. Returns whatever
     * `$work` returns so callers can chain (e.g. the freshly-saved cycle).
     */
    private function runAdminMutation(
        BaseSubscription $sub,
        string $action,
        User $actor,
        callable $work,
    ): mixed {
        return SubscriptionLock::for($sub, function () use ($sub, $action, $actor, $work) {
            return DB::transaction(function () use ($sub, $action, $actor, $work) {
                $result = $this->withAudit($sub, $action, 'admin', $actor, $work);
                $this->reconciler->sync($sub);

                return $result;
            });
        });
    }

    /**
     * INV-G2 + §4 matrix — cancel is admin/super_admin/supervisor only.
     * Students never reach this method (the route is removed in A.7);
     * teachers are not authorised.
     */
    private function assertCancelAuthorised(User $actor): void
    {
        $type = $actor->user_type instanceof UserType
            ? $actor->user_type
            : UserType::tryFrom((string) $actor->user_type);

        if (! in_array($type, [UserType::ADMIN, UserType::SUPER_ADMIN, UserType::SUPERVISOR], true)) {
            throw new \RuntimeException(sprintf(
                'Subscription cancel requires admin/super_admin/supervisor role; actor #%d has %s.',
                $actor->getKey() ?? 0,
                $type?->value ?? 'null',
            ));
        }
    }
}
