<?php

namespace App\Console\Commands\Backfill;

use App\Enums\PaymentStatus;
use App\Models\BackfillLog;
use App\Models\Payment;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\DB;

/**
 * One-off remediation for the sub-772 incident (2026-05-11): student-initiated
 * renewals stacked a queued cycle on top of an unpaid active cycle, leaving
 * the active cycle un-payable through the normal student UI.
 *
 * Scope: exactly the 3 subscriptions identified by the audit on 2026-05-11:
 *   - quran_subscription #442 (cur cycle 918, queued cycle 925)
 *   - quran_subscription #749 (cur cycle 687, queued cycle 689)
 *   - quran_subscription #772 (cur cycle 762, queued cycle 1079, expired payment 1299)
 *
 * Per-tuple remediation (inside one transaction):
 *   1. Soft-delete the expired payment row currently linked to the active cycle.
 *   2. Soft-delete the queued cycle's pending payment row.
 *   3. Hard-delete the queued cycle row — ABORT if it has any sessions attached
 *      (cycle 1079 was verified to have 0 sessions; the other two must be
 *      re-verified at apply time).
 *   4. Mint a fresh pending manual-cash Payment, link it to the active cycle.
 *   5. Audit-log every mutation in `backfill_log` so `--rollback` is possible.
 *
 * Usage:
 *   php artisan subscriptions:fix-double-renewal-unpaid --dry-run
 *   php artisan subscriptions:fix-double-renewal-unpaid --apply
 *   php artisan subscriptions:fix-double-renewal-unpaid --rollback
 *
 * The 183-sub population of "current cycle = active + payment_status=pending
 * (no queued sibling)" is intentionally OUT OF SCOPE — different shape,
 * different audit. See MEMORY.md follow-up.
 */
class FixDoubleRenewalUnpaidCommand extends BaseBackfillCommand
{
    protected $signature = 'subscriptions:fix-double-renewal-unpaid
                            {--dry-run : Print what would change without mutating (default)}
                            {--apply : Apply remediation to the 3 hard-coded subs}
                            {--rollback : Reverse a prior --apply run via the backfill_log audit trail}';

    protected $description = 'sub-772 incident remediation — reattach pending payments to the active cycle and drop the phantom queued cycle for subs 442, 749, 772';

    protected const BUG_ID = 'sub_772_double_renewal_unpaid';

    protected const COMMAND_NAME = 'subscriptions:fix-double-renewal-unpaid';

    /**
     * Hard-coded targets — exactly the 3 subs identified by the audit.
     *
     * Note: `current_payment_id` for subs 442 + 749 is intentionally null
     * here. The command resolves the actual payment_id from the cycle row
     * at runtime; sub-772's id (1299) is recorded inline only as a
     * cross-check against the audit notes.
     *
     * @var list<array{sub_id:int,current_cycle_id:int,queued_cycle_id:int,expected_current_payment_id:?int}>
     */
    private const TARGETS = [
        ['sub_id' => 442, 'current_cycle_id' => 918, 'queued_cycle_id' => 925, 'expected_current_payment_id' => null],
        ['sub_id' => 749, 'current_cycle_id' => 687, 'queued_cycle_id' => 689, 'expected_current_payment_id' => null],
        ['sub_id' => 772, 'current_cycle_id' => 762, 'queued_cycle_id' => 1079, 'expected_current_payment_id' => 1299],
    ];

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dryRun = (bool) $this->option('dry-run') || ! $this->option('apply');

        if ($dryRun) {
            $this->warn('Dry-run mode (default). Pass --apply to mutate.');
        }

        $applied = 0;
        $skipped = 0;
        $aborted = 0;

        foreach (self::TARGETS as $target) {
            $result = $this->processTarget($target, $dryRun);
            match ($result) {
                'applied' => $applied++,
                'skipped' => $skipped++,
                'aborted' => $aborted++,
            };
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Tuples %s: %d. Skipped: %d. Aborted: %d.',
            $dryRun ? 'planned' : 'applied',
            $applied,
            $skipped,
            $aborted,
        ));

        return $aborted > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array{sub_id:int,current_cycle_id:int,queued_cycle_id:int,expected_current_payment_id:?int}  $target
     * @return 'applied'|'skipped'|'aborted'
     */
    private function processTarget(array $target, bool $dryRun): string
    {
        $subId = $target['sub_id'];
        $curCycleId = $target['current_cycle_id'];
        $queuedCycleId = $target['queued_cycle_id'];

        $current = SubscriptionCycle::query()
            ->where('id', $curCycleId)
            ->where('subscribable_id', $subId)
            ->first();

        $queued = SubscriptionCycle::query()
            ->where('id', $queuedCycleId)
            ->where('subscribable_id', $subId)
            ->first();

        if (! $current || ! $queued) {
            $this->line(sprintf(
                '  sub=%d: cycle rows missing (current=%s, queued=%s) — already remediated or never matched, skipping',
                $subId,
                $current ? 'present' : 'absent',
                $queued ? 'present' : 'absent',
            ));

            return 'skipped';
        }

        if ($current->cycle_state !== SubscriptionCycle::STATE_ACTIVE
            || $current->payment_status !== SubscriptionCycle::PAYMENT_PENDING) {
            $this->line(sprintf(
                '  sub=%d: current cycle %d is %s/%s (expected active/pending) — already remediated, skipping',
                $subId,
                $curCycleId,
                $current->cycle_state,
                $current->payment_status,
            ));

            return 'skipped';
        }

        if ($queued->cycle_state !== SubscriptionCycle::STATE_QUEUED
            || $queued->payment_status !== SubscriptionCycle::PAYMENT_PENDING) {
            $this->line(sprintf(
                '  sub=%d: queued cycle %d is %s/%s (expected queued/pending) — shape mismatch, ABORTING',
                $subId,
                $queuedCycleId,
                $queued->cycle_state,
                $queued->payment_status,
            ));

            return 'aborted';
        }

        // Refuse to delete a queued cycle that has sessions attached. cycle 1079
        // for sub 772 was verified to have 0 sessions; the audit must hold for
        // 442/925 and 749/689 too. If a session got materialized between
        // investigation and apply, abort and surface for manual review.
        $sessionCount = $this->queuedCycleSessionCount($queuedCycleId);
        if ($sessionCount > 0) {
            $this->line(sprintf(
                '  sub=%d: queued cycle %d has %d session(s) attached — ABORTING (manual review required)',
                $subId,
                $queuedCycleId,
                $sessionCount,
            ));

            return 'aborted';
        }

        $currentPaymentId = $current->payment_id;
        $queuedPaymentId = $queued->payment_id;

        $expected = $target['expected_current_payment_id'];
        if ($expected !== null && $currentPaymentId !== $expected) {
            $this->line(sprintf(
                '  sub=%d: current cycle payment_id=%s, expected %d from audit — ABORTING (shape drift)',
                $subId,
                $currentPaymentId ?? 'null',
                $expected,
            ));

            return 'aborted';
        }

        $this->info(sprintf(
            '  sub=%d: soft-delete payment %s (current) + %s (queued); hard-delete queued cycle %d; mint new pending payment on cycle %d',
            $subId,
            $currentPaymentId ?? 'null',
            $queuedPaymentId ?? 'null',
            $queuedCycleId,
            $curCycleId,
        ));

        if ($dryRun) {
            return 'applied';
        }

        DB::transaction(function () use ($subId, $current, $queued, $currentPaymentId, $queuedPaymentId): void {
            if ($currentPaymentId !== null) {
                $this->softDeletePayment($currentPaymentId, 'current_cycle_expired');
            }
            if ($queuedPaymentId !== null) {
                $this->softDeletePayment($queuedPaymentId, 'queued_cycle_pending');
            }

            // Hard-delete the queued cycle row. Audit-log the prior state by
            // recording the cycle's id under a synthetic column name so
            // --rollback knows nothing can restore it — the plan accepts this
            // because the queued cycle has 0 sessions and 0 value.
            $this->logChange($queued, 'cycle_state', $queued->cycle_state, 'hard_deleted');
            $queued->delete();

            // Mint replacement pending manual-cash payment, link to active cycle.
            $sub = \App\Models\BaseSubscription::query()
                ->where('id', $subId)
                ->first()
                ?? \App\Models\QuranSubscription::query()->find($subId);

            $amount = (float) ($current->final_price ?? $current->total_price ?? 0);
            $currency = $current->currency ?? 'SAR';

            $replacement = Payment::createPayment([
                'academy_id' => $current->academy_id,
                'user_id' => $sub->student_id ?? null,
                'payable_type' => $sub?->getMorphClass() ?? 'quran_subscription',
                'payable_id' => $subId,
                'subscription_cycle_id' => $current->id,
                'payment_method' => 'cash',
                'payment_gateway' => 'manual',
                'amount' => $amount,
                'currency' => $currency,
                'status' => PaymentStatus::PENDING,
                'payment_status' => 'pending',
                'notes' => __('subscriptions.renewal_payment_pending'),
                'metadata' => [
                    'manual_remediation' => true,
                    'incident_ref' => self::BUG_ID,
                    'replaces_payment_id' => $currentPaymentId,
                ],
            ]);

            // Record the OLD payment_id so rollback can restore it.
            $this->logChange($current, 'payment_id', $current->payment_id, $replacement->id);
            $current->update(['payment_id' => $replacement->id]);

            // Stamp the new payment row in the log so rollback can find + delete it.
            $this->logChange($replacement, 'id', null, $replacement->id);
        });

        return 'applied';
    }

    /**
     * Count session rows that reference the given queued cycle. The session
     * tables use a polymorphic `subscribable` relationship via the cycle row,
     * but `cycle_id` is the canonical FK column added in 2026-05-04 (see
     * `subscription_cycle_anchored_counting.md`). We're conservative here and
     * inspect all three concrete session tables.
     */
    private function queuedCycleSessionCount(int $cycleId): int
    {
        $tables = ['quran_sessions', 'academic_sessions', 'interactive_course_sessions'];
        $total = 0;
        foreach ($tables as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn($table, 'cycle_id')) {
                continue;
            }
            $total += (int) DB::table($table)->where('cycle_id', $cycleId)->count();
        }

        return $total;
    }

    private function softDeletePayment(int $paymentId, string $reason): void
    {
        $payment = Payment::query()->withTrashed()->find($paymentId);
        if (! $payment) {
            return;
        }
        if ($payment->trashed()) {
            return;
        }
        $this->logChange($payment, 'deleted_at', null, 'soft_delete:'.$reason);
        $payment->delete();
    }

    /**
     * Undo a prior --apply run.
     *
     * For each BackfillLog row from this command:
     *   - column=deleted_at, new_value=soft_delete:* → restore the payment.
     *   - column=payment_id                          → restore prior payment_id.
     *   - column=id (synthetic)                      → soft-delete the replacement payment.
     *   - column=cycle_state, new_value=hard_deleted → cannot restore (queued
     *     cycle was hard-deleted). Just mark log row reversed.
     */
    private function rollback(): int
    {
        $rows = BackfillLog::query()
            ->where('bug_id', self::BUG_ID)
            ->where('backfill_command', self::COMMAND_NAME)
            ->whereNull('reversed_at')
            ->orderByDesc('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No prior --apply run logged. Nothing to roll back.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $log) {
                if ($log->column_name === 'deleted_at'
                    && str_starts_with((string) $log->new_value, 'soft_delete:')) {
                    Payment::withTrashed()->where('id', $log->row_id)->restore();
                } elseif ($log->column_name === 'payment_id' && $log->table_name === 'subscription_cycles') {
                    DB::table('subscription_cycles')
                        ->where('id', $log->row_id)
                        ->update(['payment_id' => $log->original_value]);
                } elseif ($log->column_name === 'id' && $log->table_name === 'payments') {
                    Payment::where('id', $log->row_id)->delete();
                } elseif ($log->column_name === 'cycle_state'
                    && $log->new_value === 'hard_deleted') {
                    $this->warn(sprintf(
                        '  cycle %d was hard-deleted on apply and cannot be restored',
                        $log->row_id,
                    ));
                }

                $log->update(['reversed_at' => now()]);
            }
        });

        $this->info(sprintf('Reversed %d backfill log row(s).', $rows->count()));

        return self::SUCCESS;
    }
}
