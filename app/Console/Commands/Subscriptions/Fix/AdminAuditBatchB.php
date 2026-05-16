<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAdminAuditDecision;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin-audit Batch B — pricing fixes per admin decisions on
 * /manage/admin-audit.
 *
 * Five pricing fixes (payment-truth verified per case):
 *
 *   cycle 272 (sub 637): cycle.package_id 7 → 8 + rebuild snapshot. Cycle 1
 *   archived/paid 200 matches pkg 8 sale_monthly (16/mo × 30min). Sub & cycle
 *   1027 already on pkg 7 (correct for current cycle).
 *
 *   cycle 418 (sub 793): cycle.package_id 8 → 7 + rebuild snapshot. Archived
 *   cycle paid 150 = pkg 7 sale_monthly (12/mo × 30min).
 *   cycle 1060 (sub 793): cycle.package_id 8 → 12 + rebuild snapshot + reduce
 *   total_sessions 16 → 8. Active cycle paid 200 = pkg 12 sale_monthly
 *   (8/mo × 60min). Plus sub-row updates (pkg=12, dur=60, per_month=8) and
 *   session duration cascade on cycle 1060 sessions (30 → 60).
 *
 *   cycle 115 (sub 442): final_price 0 → 100. Archived/paid; payment#378
 *   completed 100 = pkg 11 sale_monthly. Plus cancel 12 pending/failed
 *   retry-window payments.
 *
 *   cycle 117 (sub 444): final_price 0 → 50. Active/paid; payment#380
 *   completed 50 = pkg 5 sale_monthly.
 *
 * Each row writes a BackfillLog (bug_id=admin-audit-batch-b) and stamps the
 * matching admin decision's applied_at.
 */
class AdminAuditBatchB extends Command
{
    protected $signature = 'subscriptions:fix-admin-audit-batch-b
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Admin-audit Batch B: pricing/package fixes (payment-truth verified).';

    private const BUG_ID = 'admin-audit-batch-b';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('=== Admin-audit Batch B — pricing fixes ===');
        $this->newLine();

        $plans = [];

        // ----- cycle 272 (sub 637): pkg 7 → 8 -----
        $plans[] = $this->buildPkgChangePlan(
            cycleId: 272,
            subId: 637,
            newPkgId: 8,
            expectedCurrentPkg: 7,
            caseKey: 'inv_d2_drift_ambiguous:cycle:272',
            outcome: 'pkg_change_to_match_paid_amount',
            note: 'Cycle 1 archived/paid 200 = pkg 8 sale_monthly',
        );

        // ----- cycle 418 (sub 793): pkg 8 → 7 -----
        $plans[] = $this->buildPkgChangePlan(
            cycleId: 418,
            subId: 793,
            newPkgId: 7,
            expectedCurrentPkg: 8,
            caseKey: 'inv_d2_drift_ambiguous:cycle:418',
            outcome: 'pkg_change_to_match_paid_amount',
            note: 'Cycle 1 archived/paid 150 = pkg 7 sale_monthly',
        );

        // ----- cycle 1060 (sub 793): pkg 8 → 12 + total_sessions 16 → 8 -----
        $plans[] = $this->buildPkgChangePlan(
            cycleId: 1060,
            subId: 793,
            newPkgId: 12,
            expectedCurrentPkg: 8,
            caseKey: 'inv_d2_drift_ambiguous:cycle:418', // shares decision row
            outcome: 'pkg_change_to_match_paid_amount',
            note: 'Cycle 2 active/paid 200 = pkg 12 sale_monthly',
            extraCycleUpdates: ['total_sessions' => 8],
            extraSubUpdates: [
                'package_id' => 12,
                'session_duration_minutes' => 60,
                'package_session_duration_minutes' => 60,
                'package_sessions_per_week' => 2,
            ],
            cascadeSessionDurationMinutes: 60,
        );

        // ----- cycle 115 (sub 442): final_price 0 → 100 -----
        $plans[] = $this->buildPriceFixPlan(
            cycleId: 115,
            subId: 442,
            newFinalPrice: 100.00,
            expectedFinalPrice: 0.00,
            caseKey: 'inv_d2_free_not_override:cycle:115',
            outcome: 'price_fixed_to_match_payment',
            note: 'pmt#378 completed 100 = pkg 11 sale_monthly',
            cancelRetryWindowPayments: true,
        );

        // ----- cycle 117 (sub 444): final_price 0 → 50 -----
        $plans[] = $this->buildPriceFixPlan(
            cycleId: 117,
            subId: 444,
            newFinalPrice: 50.00,
            expectedFinalPrice: 0.00,
            caseKey: 'inv_d2_free_not_override:cycle:117',
            outcome: 'price_fixed_to_match_payment',
            note: 'pmt#380 completed 50 = pkg 5 sale_monthly',
        );

        $valid = array_filter($plans, fn ($p) => $p['ok']);
        $skipped = array_filter($plans, fn ($p) => ! $p['ok']);

        $this->info(sprintf('Planned: %d  Skipped: %d', count($valid), count($skipped)));
        $this->newLine();

        foreach ($valid as $p) {
            $this->line("  cycle#{$p['cycle_id']} (sub#{$p['sub_id']}): {$p['action']} — {$p['note']}");
        }
        if (! empty($skipped)) {
            $this->newLine();
            $this->warn('Skipped:');
            foreach ($skipped as $p) {
                $this->warn("  cycle#{$p['cycle_id']}: {$p['skip_reason']}");
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('DRY-RUN. Re-run with --apply to make changes.');
            return self::SUCCESS;
        }

        $touched = 0;
        $errors = 0;
        foreach ($valid as $plan) {
            try {
                DB::transaction(fn () => $this->applyPlan($plan));
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn("cycle #{$plan['cycle_id']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("APPLIED: {$touched} plan(s); {$errors} error(s).");
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function buildPkgChangePlan(
        int $cycleId,
        int $subId,
        int $newPkgId,
        int $expectedCurrentPkg,
        string $caseKey,
        string $outcome,
        string $note,
        array $extraCycleUpdates = [],
        array $extraSubUpdates = [],
        ?int $cascadeSessionDurationMinutes = null,
    ): array {
        $cycle = SubscriptionCycle::withTrashed()->find($cycleId);
        if (! $cycle) {
            return ['ok' => false, 'cycle_id' => $cycleId, 'sub_id' => $subId, 'skip_reason' => 'cycle_missing'];
        }
        if ((int) $cycle->package_id !== $expectedCurrentPkg) {
            return ['ok' => false, 'cycle_id' => $cycleId, 'sub_id' => $subId, 'skip_reason' => "cycle.pkg drifted ({$cycle->package_id} ≠ expected {$expectedCurrentPkg})"];
        }
        $pkg = QuranPackage::withoutGlobalScopes()->withTrashed()->find($newPkgId);
        if (! $pkg) {
            return ['ok' => false, 'cycle_id' => $cycleId, 'sub_id' => $subId, 'skip_reason' => "target pkg#{$newPkgId} missing"];
        }
        return [
            'ok' => true,
            'kind' => 'pkg_change',
            'cycle_id' => $cycleId,
            'sub_id' => $subId,
            'new_pkg_id' => $newPkgId,
            'new_pkg' => $pkg,
            'extra_cycle_updates' => $extraCycleUpdates,
            'extra_sub_updates' => $extraSubUpdates,
            'cascade_session_dur' => $cascadeSessionDurationMinutes,
            'case_key' => $caseKey,
            'outcome' => $outcome,
            'action' => "pkg {$expectedCurrentPkg} → {$newPkgId}" . (empty($extraCycleUpdates) ? '' : ' + cycle:' . json_encode($extraCycleUpdates)) . (empty($extraSubUpdates) ? '' : ' + sub:' . json_encode(array_keys($extraSubUpdates))),
            'note' => $note,
            'cycle' => $cycle,
        ];
    }

    private function buildPriceFixPlan(
        int $cycleId,
        int $subId,
        float $newFinalPrice,
        float $expectedFinalPrice,
        string $caseKey,
        string $outcome,
        string $note,
        bool $cancelRetryWindowPayments = false,
    ): array {
        $cycle = SubscriptionCycle::withTrashed()->find($cycleId);
        if (! $cycle) {
            return ['ok' => false, 'cycle_id' => $cycleId, 'sub_id' => $subId, 'skip_reason' => 'cycle_missing'];
        }
        if ((float) $cycle->final_price !== $expectedFinalPrice) {
            return ['ok' => false, 'cycle_id' => $cycleId, 'sub_id' => $subId, 'skip_reason' => "final_price drifted ({$cycle->final_price} ≠ expected {$expectedFinalPrice})"];
        }
        return [
            'ok' => true,
            'kind' => 'price_fix',
            'cycle_id' => $cycleId,
            'sub_id' => $subId,
            'new_final_price' => $newFinalPrice,
            'case_key' => $caseKey,
            'outcome' => $outcome,
            'action' => "final_price {$expectedFinalPrice} → {$newFinalPrice}" . ($cancelRetryWindowPayments ? ' + cancel retry-window pending payments' : ''),
            'note' => $note,
            'cycle' => $cycle,
            'cancel_retry_window' => $cancelRetryWindowPayments,
        ];
    }

    private function applyPlan(array $plan): void
    {
        $now = Carbon::now();

        if ($plan['kind'] === 'pkg_change') {
            $this->applyPkgChange($plan, $now);
        } else {
            $this->applyPriceFix($plan, $now);
        }

        // Stamp decision (idempotent — multiple cycles can share a case_key)
        SubscriptionAdminAuditDecision::query()
            ->where('case_key', $plan['case_key'])
            ->whereNull('applied_at')
            ->update([
                'applied_at' => $now,
                'applied_outcome' => $plan['outcome'],
            ]);
    }

    private function applyPkgChange(array $plan, Carbon $now): void
    {
        /** @var SubscriptionCycle $cycle */
        $cycle = $plan['cycle'];
        $pkg = $plan['new_pkg'];

        $snapshot = [
            'id' => $pkg->id,
            'name' => (string) ($pkg->name ?? ''),
            'currency' => (string) ($cycle->currency ?? 'SAR'),
            'sessions_per_month' => (int) $pkg->sessions_per_month,
            'session_duration_minutes' => (int) $pkg->session_duration_minutes,
            'monthly_price' => $pkg->monthly_price !== null ? (float) $pkg->monthly_price : null,
            'quarterly_price' => $pkg->quarterly_price !== null ? (float) $pkg->quarterly_price : null,
            'yearly_price' => $pkg->yearly_price !== null ? (float) $pkg->yearly_price : null,
            'sale_monthly_price' => $pkg->sale_monthly_price !== null ? (float) $pkg->sale_monthly_price : null,
            'sale_quarterly_price' => $pkg->sale_quarterly_price !== null ? (float) $pkg->sale_quarterly_price : null,
            'sale_yearly_price' => $pkg->sale_yearly_price !== null ? (float) $pkg->sale_yearly_price : null,
        ];

        BackfillLog::create([
            'bug_id' => self::BUG_ID,
            'table_name' => 'subscription_cycles',
            'row_id' => $cycle->id,
            'column_name' => 'package_id+snapshot',
            'original_value' => json_encode($cycle->getAttributes(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_value' => json_encode(['package_id' => $plan['new_pkg_id'], 'package_snapshot' => $snapshot, 'extra' => $plan['extra_cycle_updates']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'backfill_command' => 'subscriptions:fix-admin-audit-batch-b',
            'ran_at' => $now,
        ]);

        DB::table('subscription_cycles')
            ->where('id', $cycle->id)
            ->update(array_merge([
                'package_id' => $plan['new_pkg_id'],
                'package_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ], $plan['extra_cycle_updates']));

        // Sub-row updates if any
        if (! empty($plan['extra_sub_updates'])) {
            $sub = QuranSubscription::withoutGlobalScopes()->find($plan['sub_id']);
            if ($sub) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'quran_subscriptions',
                    'row_id' => $sub->id,
                    'column_name' => implode(',', array_keys($plan['extra_sub_updates'])),
                    'original_value' => json_encode($sub->only(array_keys($plan['extra_sub_updates'])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'new_value' => json_encode($plan['extra_sub_updates'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'backfill_command' => 'subscriptions:fix-admin-audit-batch-b',
                    'ran_at' => $now,
                ]);
                DB::table('quran_subscriptions')
                    ->where('id', $sub->id)
                    ->update(array_merge($plan['extra_sub_updates'], ['updated_at' => $now]));
            }
        }

        // Cascade session duration on cycle's sessions
        if ($plan['cascade_session_dur'] !== null) {
            $sessionIds = DB::table('quran_sessions')
                ->where('subscription_cycle_id', $cycle->id)
                ->pluck('id')
                ->all();
            foreach ($sessionIds as $sid) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'quran_sessions',
                    'row_id' => $sid,
                    'column_name' => 'session_duration_minutes',
                    'original_value' => (string) DB::table('quran_sessions')->where('id', $sid)->value('session_duration_minutes'),
                    'new_value' => (string) $plan['cascade_session_dur'],
                    'backfill_command' => 'subscriptions:fix-admin-audit-batch-b',
                    'ran_at' => $now,
                ]);
            }
            if (! empty($sessionIds)) {
                DB::table('quran_sessions')
                    ->whereIn('id', $sessionIds)
                    ->update(['session_duration_minutes' => $plan['cascade_session_dur'], 'updated_at' => $now]);
            }
        }
    }

    private function applyPriceFix(array $plan, Carbon $now): void
    {
        /** @var SubscriptionCycle $cycle */
        $cycle = $plan['cycle'];

        BackfillLog::create([
            'bug_id' => self::BUG_ID,
            'table_name' => 'subscription_cycles',
            'row_id' => $cycle->id,
            'column_name' => 'final_price',
            'original_value' => (string) $cycle->final_price,
            'new_value' => (string) $plan['new_final_price'],
            'backfill_command' => 'subscriptions:fix-admin-audit-batch-b',
            'ran_at' => $now,
        ]);

        DB::table('subscription_cycles')
            ->where('id', $cycle->id)
            ->update([
                'final_price' => $plan['new_final_price'],
                'total_price' => $plan['new_final_price'],
                'updated_at' => $now,
            ]);

        if (! empty($plan['cancel_retry_window'])) {
            // Cancel only pending/failed payments tied to this sub. Leaves
            // completed/cancelled/expired alone (they're already terminal).
            $morphIds = ['quran_subscription', 'App\\Models\\QuranSubscription'];
            $payments = DB::table('payments')
                ->where(function ($q) use ($plan, $morphIds) {
                    $q->where('subscription_id', $plan['sub_id'])
                        ->orWhere(function ($qq) use ($plan, $morphIds) {
                            $qq->whereIn('payable_type', $morphIds)->where('payable_id', $plan['sub_id']);
                        });
                })
                ->whereIn('status', ['pending', 'failed'])
                ->get();
            foreach ($payments as $p) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'payments',
                    'row_id' => $p->id,
                    'column_name' => 'status',
                    'original_value' => (string) $p->status,
                    'new_value' => 'cancelled',
                    'backfill_command' => 'subscriptions:fix-admin-audit-batch-b',
                    'ran_at' => $now,
                ]);
            }
            if ($payments->isNotEmpty()) {
                DB::table('payments')
                    ->whereIn('id', $payments->pluck('id')->all())
                    ->update(['status' => 'cancelled', 'updated_at' => $now]);
            }
        }
    }
}
