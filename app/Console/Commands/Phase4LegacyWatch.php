<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — temporary daily-watch for the 7-day observation window
 * between PR 2 (legacy code deletion) and PR 3 (column drop).
 *
 * Emits ONE Telegram alert per run summarising:
 *   (a) drift row count from the daily subscriptions:audit-all-subs CSV
 *   (b) any subscription_audit_log rows in the last 24h that mention
 *       the dropped surface (subscription_counted / v2_consumption_complete
 *       / isLegacyConsumptionCycle)
 *   (c) recent storage/logs/laravel.log entries that point at the
 *       removed call sites (BadMethodCallException / Undefined method
 *       isSubscriptionCounted / reverseSubscriptionAndEarnings on a
 *       trait / updateSubscriptionUsage)
 *
 * Severity = crit when (b)>0 OR (c)>0 OR (a) is above the Step-0 baseline;
 * otherwise info.
 *
 * DELETE WITH PR 3.
 */
class Phase4LegacyWatch extends Command
{
    protected $signature = 'phase4:legacy-watch';

    protected $description = 'Phase 4 cleanup 7-day watch — temporary; deleted with PR 3.';

    private const SOURCE_TAG = 'phase4-watch';

    private const DRIFT_CSV = 'audit/MASTER-decisions.csv';

    /**
     * Baseline drift count from `storage/audit/MASTER-decisions.csv` —
     * measured on prod 2026-05-17 right after PR 2 deploy. The watch
     * upgrades to `crit` if the live count rises above this. Bump after
     * an intentional sweep that legitimately grows the CSV; otherwise
     * any rise indicates new drift surfaced after the cleanup.
     */
    private const DRIFT_BASELINE = 728;

    public function handle(): int
    {
        $driftCount = $this->driftCount();
        $auditHits = $this->auditLogHits();
        $logHits = $this->laravelLogHits();

        $isCrit = $driftCount > self::DRIFT_BASELINE || $auditHits > 0 || $logHits > 0;
        $severity = $isCrit ? 'crit' : 'info';

        $body = sprintf(
            "drift=%d (baseline %d)\naudit_log_hits=%d\nlaravel_log_hits=%d",
            $driftCount,
            self::DRIFT_BASELINE,
            $auditHits,
            $logHits,
        );

        if (function_exists('alert_telegram')) {
            alert_telegram($severity, self::SOURCE_TAG, "phase4 daily watch\n{$body}");
        }

        $this->info("severity={$severity}");
        $this->line($body);

        return self::SUCCESS;
    }

    private function driftCount(): int
    {
        $path = storage_path(self::DRIFT_CSV);
        if (! is_file($path)) {
            return 0;
        }

        $count = 0;
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return 0;
        }

        // skip header
        fgetcsv($handle);
        while (fgetcsv($handle) !== false) {
            $count++;
        }
        fclose($handle);

        return $count;
    }

    private function auditLogHits(): int
    {
        if (! Schema::hasTable('subscription_audit_log')) {
            return 0;
        }

        $patterns = [
            '%subscription_counted%',
            '%v2_consumption_complete%',
            '%isLegacyConsumptionCycle%',
        ];

        $columns = ['before_state', 'after_state', 'invariant_violations'];

        $query = DB::table('subscription_audit_log')
            ->where('created_at', '>', now()->subDay())
            ->where(function ($q) use ($patterns, $columns) {
                foreach ($columns as $col) {
                    foreach ($patterns as $p) {
                        $q->orWhere($col, 'like', $p);
                    }
                }
            });

        return (int) $query->count();
    }

    private function laravelLogHits(): int
    {
        $logPath = storage_path('logs/laravel.log');
        if (! is_file($logPath)) {
            return 0;
        }

        $cutoff = now()->subDay();
        $hits = 0;
        $inWindow = false; // tracks whether the most-recent header line is within the 24h window
        $matchers = [
            'BadMethodCallException',
            'isSubscriptionCounted',
            'reverseSubscriptionAndEarnings',
            'updateSubscriptionUsage',
        ];

        $handle = fopen($logPath, 'r');
        if ($handle === false) {
            return 0;
        }

        while (($line = fgets($handle)) !== false) {
            // Header line carries the timestamp. Stack-trace + continuation
            // lines inherit the most-recent header's in-window status —
            // otherwise we'd match the body of a stack trace from a months-
            // old entry whose header was already filtered out.
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m)) {
                try {
                    $ts = \Carbon\Carbon::parse($m[1]);
                    $inWindow = $ts->gte($cutoff);
                } catch (\Throwable $e) {
                    $inWindow = false;
                }
            }

            if (! $inWindow) {
                continue;
            }

            foreach ($matchers as $needle) {
                if (stripos($line, $needle) !== false) {
                    $hits++;
                    break;
                }
            }
        }
        fclose($handle);

        return $hits;
    }
}
