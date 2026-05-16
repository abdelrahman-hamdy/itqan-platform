<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionSubscriptionStatus;
use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAdminAuditDecision;
use App\Models\SubscriptionCycle;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin-audit Batch C — paused → expired transitions per admin decisions.
 *
 * Per the policy clarified 2026-05-16: paused-end-of-period was wrong;
 * subs that ended naturally should be EXPIRED, with the current cycle
 * archived. (Pause is reserved for manual teacher/admin holds.) The 10
 * subs in this batch were misclassified-paused per admin notes on
 * /manage/admin-audit; this batch flips each to EXPIRED, archives the
 * current cycle (if active), and applies per-sub extras (cycle number
 * swap, cycle soft-delete, sessions_used adjustment, archive-only mode).
 *
 * Second-round answers received 2026-05-16:
 *   - sub 453: expire + archive, "ignore consumption" — suspended sessions
 *     left as-is.
 *   - sub 553: expire + archive + keep pkg 13; treat the paid-vs-package
 *     gap as an admin-given discount (total_price=300, discount=150,
 *     final_price stays at 150).
 *   - sub 961: handled in Batch F (cycle advance, not expire).
 */
class AdminAuditBatchC extends Command
{
    protected $signature = 'subscriptions:fix-admin-audit-batch-c-expire
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Admin-audit Batch C: flip 12 paused subs to EXPIRED + per-sub cycle ops.';

    private const BUG_ID = 'admin-audit-batch-c';

    /**
     * mode = 'expire_and_archive' (default) | 'archive_only_no_expire'
     */
    private const PLANS = [
        375 => [
            'case_key' => 'paused_no_audit_corrupt:sub:375',
            'expected_current_cycle' => 64,
            'mode' => 'expire_and_archive',
            'cycle_sessions_used' => 6,  // admin: actual is 6 not 7
        ],
        435 => [
            'case_key' => 'paused_no_audit_corrupt:sub:435',
            'expected_current_cycle' => 109,
            'mode' => 'expire_and_archive',
        ],
        444 => [
            'case_key' => 'paused_no_audit_corrupt:sub:444',
            'expected_current_cycle' => 117,
            'mode' => 'expire_and_archive',
            'note' => 'Batch B already fixed cycle 117 final_price 0→50 — this just expires the sub + archives the cycle',
        ],
        495 => [
            'case_key' => 'paused_no_audit_corrupt:sub:495',
            'expected_current_cycle' => 139,
            'mode' => 'expire_and_archive',
            'note' => 'Scheduled→carryover deferred — admin can re-decide on second round',
        ],
        501 => [
            'case_key' => 'paused_no_audit_corrupt:sub:501',
            'expected_current_cycle' => 143,
            'mode' => 'expire_and_archive',
        ],
        535 => [
            'case_key' => 'paused_no_audit_corrupt:sub:535',
            'expected_current_cycle' => 172,
            'mode' => 'expire_and_archive',
            'swap_cycle_numbers' => [172 => 2, 532 => 1],  // chronological fix
        ],
        686 => [
            'case_key' => 'paused_no_audit_corrupt:sub:686',
            'expected_current_cycle' => 318,
            'mode' => 'expire_and_archive',
            'soft_delete_cycle' => 701,  // archived/failed, no completed payment, safe
        ],
        787 => [
            'case_key' => 'paused_no_audit_corrupt:sub:787',
            'expected_current_cycle' => 412,
            'mode' => 'archive_only_no_expire',  // admin: "just mark ended cycle archived"
        ],
        817 => [
            'case_key' => 'paused_no_audit_corrupt:sub:817',
            'expected_current_cycle' => 541,
            'mode' => 'expire_and_archive',
            'soft_delete_cycle' => 542,  // queued/paid (orphan payment risk verified empty)
        ],
        974 => [
            'case_key' => 'paused_no_audit_corrupt:sub:974',
            'expected_current_cycle' => 587,
            'mode' => 'expire_and_archive',
        ],
        // Second-round answers (2026-05-16)
        453 => [
            'case_key' => 'paused_no_audit_corrupt:sub:453',
            'expected_current_cycle' => 120,
            'mode' => 'expire_and_archive',
            // admin: "ignore consumption" — no sessions_used change.
        ],
        553 => [
            'case_key' => 'paused_no_audit_corrupt:sub:553',
            'expected_current_cycle' => 190,
            'mode' => 'expire_and_archive',
            // Admin-given discount: keep pkg 13 (12/mo × 60min, monthly=360
            // sale=300), recognize the 150 paid as 300 - 150 discount. The
            // sub was admin-created, so an arbitrary discount is allowed.
            'extra_cycle_updates' => [
                'total_price' => 300.00,
                'discount_amount' => 150.00,
                'pricing_override_reason' => 'admin_created_with_discount_2026_05_16',
            ],
        ],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('=== Admin-audit Batch C — paused → expired ===');
        $this->newLine();

        $plans = [];
        foreach (self::PLANS as $subId => $cfg) {
            $sub = QuranSubscription::withoutGlobalScopes()->find($subId);
            if (! $sub) {
                $plans[] = ['ok' => false, 'sub_id' => $subId, 'skip_reason' => 'sub_missing'];
                continue;
            }
            $currentMode = $cfg['mode'];
            // Sanity: status should still be paused (unless archive-only which allows any non-terminal)
            if ($currentMode === 'expire_and_archive' && $sub->status?->value !== 'paused') {
                $plans[] = ['ok' => false, 'sub_id' => $subId, 'skip_reason' => 'sub.status drifted (' . ($sub->status?->value ?? 'null') . ' — expected paused)'];
                continue;
            }
            if (($cfg['expected_current_cycle'] ?? null) && (int) $sub->current_cycle_id !== (int) $cfg['expected_current_cycle']) {
                $plans[] = ['ok' => false, 'sub_id' => $subId, 'skip_reason' => "current_cycle_id drifted ({$sub->current_cycle_id} ≠ expected {$cfg['expected_current_cycle']})"];
                continue;
            }
            $cycle = SubscriptionCycle::withTrashed()->find($cfg['expected_current_cycle']);
            if (! $cycle) {
                $plans[] = ['ok' => false, 'sub_id' => $subId, 'skip_reason' => 'cycle_missing'];
                continue;
            }
            $plans[] = [
                'ok' => true,
                'sub_id' => $subId,
                'sub' => $sub,
                'cycle' => $cycle,
                'cfg' => $cfg,
                'mode' => $currentMode,
            ];
        }

        $valid = array_filter($plans, fn ($p) => $p['ok']);
        $skipped = array_filter($plans, fn ($p) => ! $p['ok']);

        $this->info(sprintf('Planned: %d  Skipped: %d', count($valid), count($skipped)));
        $this->newLine();

        $rows = [];
        foreach ($valid as $p) {
            $extras = [];
            if (! empty($p['cfg']['cycle_sessions_used'])) $extras[] = "sessions_used={$p['cfg']['cycle_sessions_used']}";
            if (! empty($p['cfg']['swap_cycle_numbers'])) $extras[] = 'swap_nums(' . implode(',', array_map(fn ($k, $v) => "$k=>$v", array_keys($p['cfg']['swap_cycle_numbers']), $p['cfg']['swap_cycle_numbers'])) . ')';
            if (! empty($p['cfg']['soft_delete_cycle'])) $extras[] = "soft_delete_cyc#{$p['cfg']['soft_delete_cycle']}";
            if (! empty($p['cfg']['extra_cycle_updates'])) $extras[] = 'cycle_set(' . implode(',', array_keys($p['cfg']['extra_cycle_updates'])) . ')';
            $rows[] = [
                $p['sub_id'],
                $p['mode'],
                $p['cycle']->id,
                $p['cycle']->cycle_state,
                $p['sub']->status?->value,
                implode(', ', $extras),
            ];
        }
        $this->table(['sub', 'mode', 'cycle', 'cycle_state', 'sub_status_now', 'extras'], $rows);

        if (! empty($skipped)) {
            $this->newLine();
            $this->warn('Skipped:');
            foreach ($skipped as $s) {
                $this->warn("  sub#{$s['sub_id']}: {$s['skip_reason']}");
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('DRY-RUN. Re-run with --apply.');
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
                $this->warn("sub #{$plan['sub_id']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("APPLIED: {$touched} sub(s); {$errors} error(s).");
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function applyPlan(array $plan): void
    {
        $now = Carbon::now();
        /** @var QuranSubscription $sub */
        $sub = $plan['sub'];
        /** @var SubscriptionCycle $cycle */
        $cycle = $plan['cycle'];
        $cfg = $plan['cfg'];

        // 1. Archive the current cycle if active
        if ($cycle->cycle_state === 'active') {
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $cycle->id,
                'column_name' => 'cycle_state',
                'original_value' => $cycle->cycle_state,
                'new_value' => 'archived',
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-c-expire',
                'ran_at' => $now,
            ]);
            DB::table('subscription_cycles')
                ->where('id', $cycle->id)
                ->update([
                    'cycle_state' => 'archived',
                    'archived_at' => $cycle->archived_at ?: $now,
                    'updated_at' => $now,
                ]);
        }

        // 1b. Arbitrary cycle column updates (e.g. discount fields for sub 553)
        if (! empty($cfg['extra_cycle_updates'])) {
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $cycle->id,
                'column_name' => implode(',', array_keys($cfg['extra_cycle_updates'])),
                'original_value' => json_encode($cycle->only(array_keys($cfg['extra_cycle_updates'])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_value' => json_encode($cfg['extra_cycle_updates'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-c-expire',
                'ran_at' => $now,
            ]);
            DB::table('subscription_cycles')
                ->where('id', $cycle->id)
                ->update(array_merge($cfg['extra_cycle_updates'], ['updated_at' => $now]));
        }

        // 2. Adjust sessions_used if requested
        if (isset($cfg['cycle_sessions_used'])) {
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $cycle->id,
                'column_name' => 'sessions_used',
                'original_value' => (string) $cycle->sessions_used,
                'new_value' => (string) $cfg['cycle_sessions_used'],
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-c-expire',
                'ran_at' => $now,
            ]);
            DB::table('subscription_cycles')
                ->where('id', $cycle->id)
                ->update(['sessions_used' => $cfg['cycle_sessions_used'], 'updated_at' => $now]);
        }

        // 3. Swap cycle numbers (sub 535)
        if (! empty($cfg['swap_cycle_numbers'])) {
            // Two-step swap with a temporary high number to avoid unique-violation if
            // (subscribable, cycle_number) is indexed unique.
            $entries = $cfg['swap_cycle_numbers'];
            $temp = 9999;
            foreach ($entries as $cid => $newNum) {
                $cur = DB::table('subscription_cycles')->where('id', $cid)->value('cycle_number');
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'subscription_cycles',
                    'row_id' => $cid,
                    'column_name' => 'cycle_number',
                    'original_value' => (string) $cur,
                    'new_value' => (string) $newNum,
                    'backfill_command' => 'subscriptions:fix-admin-audit-batch-c-expire',
                    'ran_at' => $now,
                ]);
            }
            foreach ($entries as $cid => $_newNum) {
                DB::table('subscription_cycles')->where('id', $cid)->update(['cycle_number' => $temp++, 'updated_at' => $now]);
            }
            foreach ($entries as $cid => $newNum) {
                DB::table('subscription_cycles')->where('id', $cid)->update(['cycle_number' => $newNum, 'updated_at' => $now]);
            }
        }

        // 4. Soft-delete an extra cycle (sub 686 cycle 701, sub 817 cycle 542)
        if (! empty($cfg['soft_delete_cycle'])) {
            $extra = SubscriptionCycle::withTrashed()->find($cfg['soft_delete_cycle']);
            if ($extra && ! $extra->deleted_at) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'subscription_cycles',
                    'row_id' => $extra->id,
                    'column_name' => 'deleted_at',
                    'original_value' => json_encode($extra->getAttributes(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'new_value' => $now->toDateTimeString(),
                    'backfill_command' => 'subscriptions:fix-admin-audit-batch-c-expire',
                    'ran_at' => $now,
                ]);
                $extra->delete();
            }
        }

        // 5. Flip sub.status to EXPIRED (unless archive_only)
        if ($plan['mode'] === 'expire_and_archive') {
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'quran_subscriptions',
                'row_id' => $sub->id,
                'column_name' => 'status+paused_at+pause_reason+ended_at',
                'original_value' => json_encode($sub->only(['status', 'paused_at', 'pause_reason', 'ended_at']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_value' => json_encode(['status' => 'expired', 'paused_at' => null, 'pause_reason' => null, 'ended_at' => $now->toDateTimeString()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-c-expire',
                'ran_at' => $now,
            ]);
            DB::table('quran_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'status' => SessionSubscriptionStatus::EXPIRED->value,
                    'paused_at' => null,
                    'pause_reason' => null,
                    'ended_at' => $sub->ended_at ?: $now,
                    'updated_at' => $now,
                ]);
        }

        // 6. Stamp the admin decision
        SubscriptionAdminAuditDecision::query()
            ->where('case_key', $cfg['case_key'])
            ->whereNull('applied_at')
            ->update([
                'applied_at' => $now,
                'applied_outcome' => $plan['mode'] === 'expire_and_archive' ? 'expired_cycle_archived' : 'cycle_archived_only',
            ]);
    }
}
