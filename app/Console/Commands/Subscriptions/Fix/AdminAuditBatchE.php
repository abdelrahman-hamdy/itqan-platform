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
 * Admin-audit Batch E — sub 749 cleanup.
 *
 * Admin cancelled sub 749 manually. cycle 374 (num=1) archived/paid 100 —
 * remains as historical record. cycle 687 (num=2) active/pending unpaid —
 * orphan renewal attempt; soft-delete per admin note.
 *
 * Safety: cycle 687 has NO completed payment (only expired/cancelled
 * retries linked); deleting it leaves no orphan financial record.
 */
class AdminAuditBatchE extends Command
{
    protected $signature = 'subscriptions:fix-admin-audit-batch-e
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Admin-audit Batch E: soft-delete unpaid cycle 687 for sub 749 (already cancelled).';

    private const BUG_ID = 'admin-audit-batch-e';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('=== Admin-audit Batch E — sub 749 cycle 687 soft-delete ===');
        $this->newLine();

        $sub = QuranSubscription::withoutGlobalScopes()->find(749);
        $cycle = SubscriptionCycle::withTrashed()->find(687);

        if (! $sub) {
            $this->error('sub#749 missing');
            return self::FAILURE;
        }
        if (! $cycle) {
            $this->error('cycle#687 missing');
            return self::FAILURE;
        }

        $skips = [];
        if ($sub->status?->value !== 'cancelled') {
            $skips[] = 'sub#749 status drifted (' . ($sub->status?->value ?? 'null') . ' — expected cancelled)';
        }
        if ($cycle->deleted_at) {
            $skips[] = 'cycle#687 already soft-deleted';
        }
        if ($cycle->payment_status !== 'pending') {
            $skips[] = "cycle#687 payment_status drifted ({$cycle->payment_status} — expected pending)";
        }
        $completedPayment = DB::table('payments')
            ->where('subscription_cycle_id', 687)
            ->where('status', 'completed')
            ->exists();
        if ($completedPayment) {
            $skips[] = 'cycle#687 has a completed payment — refusing to soft-delete';
        }

        if (! empty($skips)) {
            foreach ($skips as $s) $this->warn("  SKIP: {$s}");
            return self::FAILURE;
        }

        $this->line("  sub#749 status=cancelled cycle#687 state={$cycle->cycle_state} payment_status={$cycle->payment_status} → soft-delete cycle 687");

        if (! $apply) {
            $this->newLine();
            $this->comment('DRY-RUN. Re-run with --apply.');
            return self::SUCCESS;
        }

        $now = Carbon::now();
        DB::transaction(function () use ($cycle, $now) {
            BackfillLog::create([
                'bug_id' => self::BUG_ID,
                'table_name' => 'subscription_cycles',
                'row_id' => $cycle->id,
                'column_name' => 'deleted_at',
                'original_value' => json_encode($cycle->getAttributes(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_value' => $now->toDateTimeString(),
                'backfill_command' => 'subscriptions:fix-admin-audit-batch-e',
                'ran_at' => $now,
            ]);
            $cycle->delete();

            SubscriptionAdminAuditDecision::query()
                ->where('case_key', 'paused_no_audit_corrupt:sub:749')
                ->whereNull('applied_at')
                ->update([
                    'applied_at' => $now,
                    'applied_outcome' => 'unpaid_cycle_soft_deleted',
                ]);
        });

        $this->info('APPLIED.');
        return self::SUCCESS;
    }
}
