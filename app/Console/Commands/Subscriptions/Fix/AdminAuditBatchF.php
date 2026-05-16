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
 * Admin-audit Batch F — sub 961 cycle advance.
 *
 * Context: sub 961's previous cycle (575) ended 2026-05-14 with the student
 * having pre-paid the next cycle (731, payment#1244 completed 200.00). The
 * "AdvanceSubscriptionCycles" cron should have promoted cycle 731 from
 * QUEUED to ACTIVE — but the sub was auto-paused before that ran, so the
 * cron skipped it (requires status=active). Admin manually resumed the sub
 * on 2026-05-16.
 *
 * The blue "pending" badge admin observed on the cycles list is the
 * cycle_state='queued' translated label (`supervisor.subscriptions.cycle_state_queued`
 * → "بانتظار التفعيل"/"معلقة" → blue). The PAYMENT for cycle 731 is already
 * paid (cycle.payment_status='paid') — queued is the lifecycle state, not
 * the payment state.
 *
 * Fix: do the cycle advance manually for this one sub (rather than running
 * the full cron, which would also affect any other eligible sub):
 *   - cycle 575: cycle_state active → archived, archived_at=now
 *   - cycle 731: cycle_state queued → active
 *   - sub.current_cycle_id 575 → 731
 *   - sub.ends_at copied from cycle 731.ends_at (2026-06-14)
 */
class AdminAuditBatchF extends Command
{
    protected $signature = 'subscriptions:fix-admin-audit-batch-f
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Admin-audit Batch F: advance sub 961 to its pre-paid queued cycle.';

    private const BUG_ID = 'admin-audit-batch-f';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('=== Admin-audit Batch F — sub 961 cycle advance ===');
        $this->newLine();

        $sub = QuranSubscription::withoutGlobalScopes()->find(961);
        $oldCycle = SubscriptionCycle::withTrashed()->find(575);
        $newCycle = SubscriptionCycle::withTrashed()->find(731);

        if (! $sub || ! $oldCycle || ! $newCycle) {
            $this->error('  SKIP: required sub or cycles missing');
            return self::FAILURE;
        }

        // Fast-path: AdvanceSubscriptionCycles cron may have already advanced
        // the sub (cycle 575 → archived, cycle 731 → active) once admin
        // resumed it. If so, just stamp the decision and exit.
        $alreadyAdvanced = (int) $sub->current_cycle_id === 731
            && $oldCycle->cycle_state === 'archived'
            && $newCycle->cycle_state === 'active';

        if ($alreadyAdvanced) {
            $this->info('  sub#961 already advanced (cycle 575 archived, cycle 731 active, sub.current_cycle_id=731)');
            if (! $apply) {
                $this->newLine();
                $this->comment('DRY-RUN. Re-run with --apply to mark the admin decision applied.');
                return self::SUCCESS;
            }
            $now = Carbon::now();
            SubscriptionAdminAuditDecision::query()
                ->where('case_key', 'paused_no_audit_corrupt:sub:961')
                ->whereNull('applied_at')
                ->update([
                    'applied_at' => $now,
                    'applied_outcome' => 'self_advanced_by_cron_after_admin_resume',
                ]);
            $this->info('APPLIED (decision-only).');
            return self::SUCCESS;
        }

        if ($sub->status?->value !== 'active') {
            $this->error("  SKIP: sub#961 status drifted ({$sub->status?->value}) — expected active");
            return self::FAILURE;
        }
        if ((int) $sub->current_cycle_id !== 575) {
            $this->error("  SKIP: sub#961 current_cycle_id drifted ({$sub->current_cycle_id}) — expected 575");
            return self::FAILURE;
        }
        if ($oldCycle->cycle_state !== 'active') {
            $this->error("  SKIP: cycle#575 state drifted ({$oldCycle->cycle_state}) — expected active");
            return self::FAILURE;
        }
        if ($newCycle->cycle_state !== 'queued') {
            $this->error("  SKIP: cycle#731 state drifted ({$newCycle->cycle_state}) — expected queued");
            return self::FAILURE;
        }
        if ($newCycle->payment_status !== 'paid') {
            $this->error("  SKIP: cycle#731 payment_status drifted ({$newCycle->payment_status}) — expected paid");
            return self::FAILURE;
        }

        $this->line('  sub#961 status=active current_cycle=575');
        $this->line('  cycle#575 (active → archived)  ends_at='.$oldCycle->ends_at);
        $this->line('  cycle#731 (queued → active)    ends_at='.$newCycle->ends_at);
        $this->line('  sub.current_cycle_id 575 → 731');
        $this->line('  sub.ends_at '.$sub->ends_at.' → '.$newCycle->ends_at);

        if (! $apply) {
            $this->newLine();
            $this->comment('DRY-RUN. Re-run with --apply.');
            return self::SUCCESS;
        }

        $now = Carbon::now();
        DB::transaction(function () use ($sub, $oldCycle, $newCycle, $now) {
            // Archive current cycle
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $oldCycle->id,
                'column_name' => 'cycle_state+archived_at',
                'original_value' => json_encode($oldCycle->only(['cycle_state', 'archived_at']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_value' => json_encode(['cycle_state' => 'archived', 'archived_at' => $now->toDateTimeString()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-f',
                'ran_at' => $now,
            ]);
            DB::table('subscription_cycles')
                ->where('id', $oldCycle->id)
                ->update(['cycle_state' => 'archived', 'archived_at' => $now, 'updated_at' => $now]);

            // Promote queued cycle
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $newCycle->id,
                'column_name' => 'cycle_state',
                'original_value' => 'queued',
                'new_value' => 'active',
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-f',
                'ran_at' => $now,
            ]);
            DB::table('subscription_cycles')
                ->where('id', $newCycle->id)
                ->update(['cycle_state' => 'active', 'updated_at' => $now]);

            // Repoint sub + extend ends_at
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'quran_subscriptions',
                'row_id' => $sub->id,
                'column_name' => 'current_cycle_id+ends_at',
                'original_value' => json_encode($sub->only(['current_cycle_id', 'ends_at']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_value' => json_encode(['current_cycle_id' => $newCycle->id, 'ends_at' => $newCycle->ends_at?->toDateTimeString()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-f',
                'ran_at' => $now,
            ]);
            DB::table('quran_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'current_cycle_id' => $newCycle->id,
                    'ends_at' => $newCycle->ends_at,
                    'updated_at' => $now,
                ]);

            SubscriptionAdminAuditDecision::query()
                ->where('case_key', 'paused_no_audit_corrupt:sub:961')
                ->whereNull('applied_at')
                ->update([
                    'applied_at' => $now,
                    'applied_outcome' => 'advanced_to_prepaid_queued_cycle',
                ]);
        });

        $this->info('APPLIED.');
        return self::SUCCESS;
    }
}
