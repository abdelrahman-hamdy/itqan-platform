<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\BackfillLog;
use App\Models\QuranSubscription;
use App\Models\SubscriptionAdminAuditDecision;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin-audit Batch D — resume subscriptions that were incorrectly paused.
 *
 *   sub 903: cycle 1 archived/paid, cycle 2 active/pending (next cycle
 *   advanced + waiting for renewal payment). Cron auto-paused it on
 *   2026-05-11 — wrong. Status PAUSED → ACTIVE. Restore 6 suspended
 *   sessions → SCHEDULED.
 *
 *   sub 1074: cycle 1 active/paid (just-started, 0 sessions yet). Got
 *   auto-paused too. Status PAUSED → ACTIVE. (No suspended sessions
 *   to restore.)
 */
class AdminAuditBatchD extends Command
{
    protected $signature = 'subscriptions:fix-admin-audit-batch-d-resume
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Admin-audit Batch D: resume sub 903 + 1074 (incorrectly auto-paused).';

    private const BUG_ID = 'admin-audit-batch-d';

    private const PLANS = [
        903 => [
            'case_key' => 'paused_no_audit_corrupt:sub:903',
            'restore_suspended_sessions' => true,
        ],
        1074 => [
            'case_key' => 'paused_no_audit_corrupt:sub:1074',
            'restore_suspended_sessions' => false,
        ],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('=== Admin-audit Batch D — resume incorrectly-paused ===');
        $this->newLine();

        $plans = [];
        foreach (self::PLANS as $subId => $cfg) {
            $sub = QuranSubscription::withoutGlobalScopes()->find($subId);
            if (! $sub) {
                $plans[] = ['ok' => false, 'sub_id' => $subId, 'skip_reason' => 'sub_missing'];
                continue;
            }
            if ($sub->status?->value !== 'paused') {
                $plans[] = ['ok' => false, 'sub_id' => $subId, 'skip_reason' => 'sub.status drifted (' . ($sub->status?->value ?? 'null') . ' — expected paused)'];
                continue;
            }
            $suspendedSessions = $cfg['restore_suspended_sessions']
                ? DB::table('quran_sessions')
                    ->where('quran_subscription_id', $subId)
                    ->where('status', SessionStatus::SUSPENDED->value)
                    ->pluck('id')
                    ->all()
                : [];
            $plans[] = [
                'ok' => true,
                'sub_id' => $subId,
                'sub' => $sub,
                'cfg' => $cfg,
                'suspended_session_ids' => $suspendedSessions,
            ];
        }

        $valid = array_filter($plans, fn ($p) => $p['ok']);
        $skipped = array_filter($plans, fn ($p) => ! $p['ok']);

        $this->info(sprintf('Planned: %d  Skipped: %d', count($valid), count($skipped)));
        $this->newLine();

        $rows = [];
        foreach ($valid as $p) {
            $rows[] = [
                $p['sub_id'],
                $p['sub']->status?->value,
                count($p['suspended_session_ids']) ? 'restore ' . count($p['suspended_session_ids']) . ' suspended sessions' : 'status flip only',
            ];
        }
        $this->table(['sub', 'status_now', 'action'], $rows);

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
        $cfg = $plan['cfg'];

        BackfillLog::create([
            'bug_id' => self::BUG_ID,
            'table_name' => 'quran_subscriptions',
            'row_id' => $sub->id,
            'column_name' => 'status+paused_at+pause_reason',
            'original_value' => json_encode($sub->only(['status', 'paused_at', 'pause_reason']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'new_value' => json_encode(['status' => 'active', 'paused_at' => null, 'pause_reason' => null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'backfill_command' => 'subscriptions:fix-admin-audit-batch-d-resume',
            'ran_at' => $now,
        ]);
        DB::table('quran_subscriptions')
            ->where('id', $sub->id)
            ->update([
                'status' => SessionSubscriptionStatus::ACTIVE->value,
                'paused_at' => null,
                'pause_reason' => null,
                'updated_at' => $now,
            ]);

        // Restore suspended sessions → SCHEDULED
        if (! empty($plan['suspended_session_ids'])) {
            foreach ($plan['suspended_session_ids'] as $sid) {
                BackfillLog::create([
                    'bug_id' => self::BUG_ID,
                    'table_name' => 'quran_sessions',
                    'row_id' => $sid,
                    'column_name' => 'status',
                    'original_value' => SessionStatus::SUSPENDED->value,
                    'new_value' => SessionStatus::SCHEDULED->value,
                    'backfill_command' => 'subscriptions:fix-admin-audit-batch-d-resume',
                    'ran_at' => $now,
                ]);
            }
            DB::table('quran_sessions')
                ->whereIn('id', $plan['suspended_session_ids'])
                ->update([
                    'status' => SessionStatus::SCHEDULED->value,
                    'updated_at' => $now,
                ]);
        }

        // Stamp decision
        SubscriptionAdminAuditDecision::query()
            ->where('case_key', $cfg['case_key'])
            ->whereNull('applied_at')
            ->update([
                'applied_at' => $now,
                'applied_outcome' => 'resumed_from_wrong_pause',
            ]);
    }
}
