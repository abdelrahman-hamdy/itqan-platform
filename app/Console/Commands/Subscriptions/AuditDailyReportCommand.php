<?php

namespace App\Console\Commands\Subscriptions;

use App\Models\SubscriptionAuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phase C — read-only daily summary of subscription_audit_log activity.
 *
 * Counts the rows written by every subscription writer in the configured
 * window, grouped by action. Surfaces rows whose `has_violations = true`
 * as a separate section so an on-call engineer can spot invariant
 * regressions immediately.
 *
 * Telegram delivery hop:
 *   This command does NOT call `itqan-alert` directly. The pipeline lives
 *   on the LiveKit VPS (see MEMORY.md → telegram_alert_pipeline.md). When
 *   `--telegram` is set, we write a `subscription_audit_violations` line to
 *   the `subscriptions` log channel (configured in config/logging.php as
 *   a daily file at storage/logs/subscriptions/audit-{date}.log). A
 *   server-side cron tails that file and forwards new violation events to
 *   `itqan-alert crit subscription-audit <msg>`. Keeping the hop in a log
 *   channel means we never block the artisan run on Telegram network I/O.
 *
 * Read-only by design — never writes to subscription_audit_log itself.
 */
class AuditDailyReportCommand extends Command
{
    protected $signature = 'subscriptions:audit-daily-report
                            {--since=24h : Look-back window (e.g. 24h, 6h, 7d)}
                            {--telegram : Forward new invariant violations to the Telegram alert pipeline}';

    protected $description = 'Daily summary of subscription_audit_log rows, grouped by action; surfaces invariant violations.';

    public function handle(): int
    {
        $since = $this->resolveSince($this->option('since'));
        if ($since === null) {
            $this->error("Invalid --since value '{$this->option('since')}'. Expected forms: 24h, 6h, 7d, 30m.");

            return self::INVALID;
        }

        $this->line("Subscription audit-log report — since {$since->toIso8601String()}");

        $byAction = SubscriptionAuditLog::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('action, COUNT(*) AS total, SUM(CASE WHEN has_violations = 1 THEN 1 ELSE 0 END) AS violations')
            ->groupBy('action')
            ->orderByDesc('total')
            ->get();

        if ($byAction->isEmpty()) {
            $this->line('  (no audit rows in window)');
        } else {
            $this->table(
                ['Action', 'Total', 'Violations'],
                $byAction->map(fn ($row) => [
                    (string) $row->action,
                    (int) $row->total,
                    (int) ($row->violations ?? 0),
                ])->all(),
            );
        }

        $violationsCount = SubscriptionAuditLog::query()
            ->where('created_at', '>=', $since)
            ->where('has_violations', true)
            ->count();

        if ($violationsCount > 0) {
            $this->newLine();
            $this->warn("Invariant violations in window: {$violationsCount}");

            // Show the latest 10 violations so the operator can jump straight
            // to the offending subs/actions.
            $latest = SubscriptionAuditLog::query()
                ->where('created_at', '>=', $since)
                ->where('has_violations', true)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'subscription_type', 'subscription_id', 'action', 'source', 'created_at']);

            $this->table(
                ['Audit ID', 'Sub', 'Action', 'Source', 'At'],
                $latest->map(fn ($row) => [
                    (int) $row->id,
                    sprintf('%s#%d', class_basename((string) $row->subscription_type), (int) $row->subscription_id),
                    (string) $row->action,
                    (string) $row->source,
                    $row->created_at?->toIso8601String() ?? '—',
                ])->all(),
            );
        } else {
            $this->info('No invariant violations in window.');
        }

        if ($this->option('telegram') && $violationsCount > 0) {
            $this->forwardToTelegramChannel($since, $violationsCount);
        }

        return self::SUCCESS;
    }

    /**
     * Convert "24h", "6h", "7d", "30m" into an absolute lower-bound timestamp.
     * Returns null on invalid input so the command can refuse cleanly.
     */
    private function resolveSince(?string $raw): ?CarbonImmutable
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        if (! preg_match('/^(\d+)\s*([smhd])$/i', $raw, $m)) {
            return null;
        }

        $n = (int) $m[1];
        $unit = strtolower($m[2]);

        $now = CarbonImmutable::now();

        return match ($unit) {
            's' => $now->subSeconds($n),
            'm' => $now->subMinutes($n),
            'h' => $now->subHours($n),
            'd' => $now->subDays($n),
            default => null,
        };
    }

    /**
     * Write a structured line to the `subscriptions` log channel. The
     * server-side tail-and-forward script picks it up and posts to
     * `itqan-alert`. Failures here are non-fatal — logging must never
     * fail the artisan exit code.
     */
    private function forwardToTelegramChannel(CarbonImmutable $since, int $violationsCount): void
    {
        try {
            Log::channel('subscriptions')->warning('subscription_audit_violations', [
                'event' => 'subscription_audit_violations',
                'since' => $since->toIso8601String(),
                'violations' => $violationsCount,
                'message' => sprintf(
                    'Subscription audit log: %d invariant violation(s) since %s.',
                    $violationsCount,
                    $since->toIso8601String(),
                ),
            ]);
        } catch (Throwable $e) {
            $this->warn("Failed to write violation alert to 'subscriptions' log channel: {$e->getMessage()}");
        }
    }
}
