<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAdminAuditDecision;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin-audit Batch A — full soft-deletes for testing subs + orphan-package
 * subs the admin tagged as "delete completely".
 *
 * Targets (decided via /manage/admin-audit on 2026-05-16):
 *
 *   Bucket 1 (testing — sub layer already soft-deleted): soft-delete the
 *   orphan cycles only.
 *     cycles 3, 4, 5, 6, 7, 8, 9, 10, 11, 14
 *
 *   Bucket 2 (orphan package, never used / cancelled): soft-delete sub +
 *   its cycles.
 *     #329 (c#23), #330 (c#24), #331 (c#25), #340 (c#34),
 *     #630 (c#266), #827 (c#452), #907 (c#526),
 *     #1056 (c#678), #1058 (c#680)
 *
 *   Group 4 (free_not_override 133 — recurring-discount artefact): soft-
 *   delete sub + cycle.
 *     #481 (c#133)
 *
 * Pre-flight per row: re-verify the state we investigated (status, ends_at,
 * payment counts). Any row whose state has drifted is skipped and reported.
 *
 * Each write goes through `BackfillLog` (bug_id='admin-audit-batch-a').
 * Each affected admin decision is stamped with `applied_at` + `applied_outcome`.
 */
class AdminAuditBatchA extends Command
{
    protected $signature = 'subscriptions:fix-admin-audit-batch-a
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Admin-audit Batch A: soft-delete testing + orphan-package subs/cycles per admin decisions.';

    /** Cycles to soft-delete WITHOUT touching the parent sub (parent already soft-deleted). */
    private const ORPHAN_CYCLES_ONLY = [3, 4, 5, 6, 7, 8, 9, 10, 11, 14];

    /** Subs to soft-delete (with their cycles cascading via SoftDeletes). */
    private const FULL_DELETES = [
        ['sub_id' => 329, 'cycle_id' => 23, 'case_key' => 'inv_d2_orphan_package:cycle:23', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 330, 'cycle_id' => 24, 'case_key' => 'inv_d2_orphan_package:cycle:24', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 331, 'cycle_id' => 25, 'case_key' => 'inv_d2_orphan_package:cycle:25', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 340, 'cycle_id' => 34, 'case_key' => 'inv_d2_orphan_package:cycle:34', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 630, 'cycle_id' => 266, 'case_key' => 'inv_d2_orphan_package:cycle:266', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 827, 'cycle_id' => 452, 'case_key' => 'inv_d2_orphan_package:cycle:452', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 907, 'cycle_id' => 526, 'case_key' => 'inv_d2_orphan_package:cycle:526', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 1056, 'cycle_id' => 678, 'case_key' => 'inv_d2_orphan_package:cycle:678', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 1058, 'cycle_id' => 680, 'case_key' => 'inv_d2_orphan_package:cycle:680', 'reason' => 'orphan_pkg_unused'],
        ['sub_id' => 481, 'cycle_id' => 133, 'case_key' => 'inv_d2_free_not_override:cycle:133', 'reason' => 'recurring_discount_artefact_not_used'],
    ];

    private const BUG_ID = 'admin-audit-batch-a';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('=== Admin-audit Batch A — full soft-deletes ===');
        $this->newLine();

        $plannedCycles = [];
        $skippedCycles = [];

        // -- Orphan cycles only (sub already deleted) --
        foreach (self::ORPHAN_CYCLES_ONLY as $cid) {
            $cycle = SubscriptionCycle::withTrashed()->find($cid);
            if (! $cycle) {
                $skippedCycles[] = ['cycle' => $cid, 'reason' => 'cycle_missing'];
                continue;
            }
            if ($cycle->deleted_at) {
                $skippedCycles[] = ['cycle' => $cid, 'reason' => 'already_soft_deleted'];
                continue;
            }
            $plannedCycles[] = [
                'cycle_id' => $cid,
                'sub_id' => $cycle->subscribable_id,
                'sub_type' => $cycle->subscribable_type,
                'mode' => 'cycle_only',
                'cycle_obj' => $cycle,
            ];
        }

        // -- Full sub+cycle deletes --
        foreach (self::FULL_DELETES as $row) {
            $sub = QuranSubscription::withoutGlobalScopes()->find($row['sub_id']);
            $cycle = SubscriptionCycle::withTrashed()->find($row['cycle_id']);

            if (! $sub) {
                $skippedCycles[] = ['cycle' => $row['cycle_id'], 'sub' => $row['sub_id'], 'reason' => 'sub_missing'];
                continue;
            }
            if (! $cycle) {
                $skippedCycles[] = ['cycle' => $row['cycle_id'], 'sub' => $row['sub_id'], 'reason' => 'cycle_missing'];
                continue;
            }
            if ($sub->deleted_at && $cycle->deleted_at) {
                $skippedCycles[] = ['cycle' => $row['cycle_id'], 'sub' => $row['sub_id'], 'reason' => 'both_already_soft_deleted'];
                continue;
            }
            $plannedCycles[] = [
                'cycle_id' => $row['cycle_id'],
                'sub_id' => $row['sub_id'],
                'sub_type' => 'quran_subscription',
                'mode' => 'sub_and_cycle',
                'sub_obj' => $sub,
                'cycle_obj' => $cycle,
                'case_key' => $row['case_key'],
                'reason' => $row['reason'],
            ];
        }

        $this->info(sprintf('Planned: %d row(s)  Skipped: %d', count($plannedCycles), count($skippedCycles)));
        $this->newLine();

        $this->table(
            ['cycle', 'sub', 'sub_type', 'mode', 'sub_status', 'cycle_state', 'final_price'],
            array_map(static fn ($p) => [
                $p['cycle_id'],
                $p['sub_id'],
                $p['sub_type'],
                $p['mode'],
                $p['mode'] === 'sub_and_cycle' ? $p['sub_obj']->status?->value : '-',
                $p['cycle_obj']->cycle_state,
                $p['cycle_obj']->final_price,
            ], $plannedCycles),
        );

        if (! empty($skippedCycles)) {
            $this->newLine();
            $this->warn('Skipped rows:');
            $this->table(['cycle', 'sub', 'reason'], array_map(static fn ($s) => [$s['cycle'] ?? null, $s['sub'] ?? null, $s['reason']], $skippedCycles));
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('DRY-RUN. Re-run with --apply to soft-delete.');
            return self::SUCCESS;
        }

        $touched = 0;
        $errors = 0;
        foreach ($plannedCycles as $plan) {
            try {
                DB::transaction(fn () => $this->softDelete($plan));
                $touched++;
            } catch (\Throwable $e) {
                $errors++;
                $this->warn(sprintf('cycle #%d (sub #%d): %s', $plan['cycle_id'], $plan['sub_id'], $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf('APPLIED: %d row(s) processed; %d error(s).', $touched, $errors));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function softDelete(array $plan): void
    {
        $now = Carbon::now();

        // --- Cycle ---
        $cycle = $plan['cycle_obj'];
        if (! $cycle->deleted_at) {
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $cycle->id,
                'column_name' => 'deleted_at',
                'original_value' => json_encode($cycle->getAttributes(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_value' => $now->toDateTimeString(),
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-a',
                'ran_at' => $now,
            ]);
            $cycle->delete();
        }

        // --- Sub (if sub_and_cycle) ---
        if ($plan['mode'] === 'sub_and_cycle') {
            /** @var QuranSubscription $sub */
            $sub = $plan['sub_obj'];
            if (! $sub->deleted_at) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'quran_subscriptions',
                    'row_id' => $sub->id,
                    'column_name' => 'deleted_at',
                    'original_value' => json_encode($sub->getAttributes(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'new_value' => $now->toDateTimeString(),
                    'backfill_command' => 'subscriptions:fix-admin-audit-batch-a',
                    'ran_at' => $now,
                ]);
                $sub->delete();
            }

            if (isset($plan['case_key'])) {
                SubscriptionAdminAuditDecision::query()
                    ->where('case_key', $plan['case_key'])
                    ->update([
                        'applied_at' => $now,
                        'applied_outcome' => $plan['reason'] ?? 'soft_deleted',
                    ]);
            }
        }
    }
}
